<?php
// 직접 DB 연결
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 데이터베이스 연결 성공\n\n";
} catch (PDOException $e) {
    die("❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

// 배치 처리할 회원 ID
$member_id = isset($argv[1]) ? (int)$argv[1] : 11230;

echo "=== 특정 회원의 로그인 이력 가져오기 ===\n";
echo "대상 회원 ID: $member_id\n\n";

// Excel 파일에서 해당 회원 정보 찾기
$file_path = '/home/successbank/projects/docker/project1/html/114/member.xls';
$content = file_get_contents($file_path);
$content = iconv('EUC-KR', 'UTF-8//IGNORE', $content);

// 먼저 해당 회원의 user_id 찾기
$stmt = $pdo->prepare("SELECT user_id, name FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    die("❌ 회원을 찾을 수 없습니다: ID $member_id\n");
}

echo "회원 정보: {$member['user_id']} ({$member['name']})\n";

// HTML 테이블 파싱
preg_match_all('/<TR[^>]*>(.*?)<\/TR>/si', $content, $rows);

$found = false;

// 각 행 검색
for ($i = 1; $i < count($rows[0]); $i++) {
    preg_match_all('/<TD[^>]*>(.*?)<\/TD>/si', $rows[1][$i], $cells);
    
    if (count($cells[1]) < 30) continue;
    
    $mb_id = trim(strip_tags($cells[1][3]));
    
    if ($mb_id === $member['user_id']) {
        $found = true;
        
        $mb_lognum = trim(strip_tags($cells[1][27])); // MB_LOGNUM
        $mb_logdate = trim(strip_tags($cells[1][28])); // MB_LOGDATE
        
        echo "\nExcel 데이터 찾음:\n";
        echo "- 로그인 횟수: $mb_lognum\n";
        echo "- 마지막 로그인: $mb_logdate\n";
        
        // 로그인 날짜 파싱
        $last_login_date = null;
        if (!empty($mb_logdate) && $mb_logdate != '0') {
            if (strlen($mb_logdate) >= 14) {
                $year = substr($mb_logdate, 0, 4);
                $month = substr($mb_logdate, 4, 2);
                $day = substr($mb_logdate, 6, 2);
                $hour = substr($mb_logdate, 8, 2);
                $minute = substr($mb_logdate, 10, 2);
                $second = substr($mb_logdate, 12, 2);
                
                if (checkdate($month, $day, $year)) {
                    $last_login_date = "$year-$month-$day $hour:$minute:$second";
                    echo "- 파싱된 날짜: $last_login_date\n";
                }
            }
        }
        
        try {
            // 1. members 테이블 업데이트
            $stmt = $pdo->prepare("UPDATE members SET 
                total_login_count = ?, 
                last_login_imported = ?,
                login_history_imported = 1
                WHERE id = ?");
            $stmt->execute([$mb_lognum, $last_login_date, $member_id]);
            echo "\n✅ members 테이블 업데이트 완료\n";
            
            // 2. 로그인 요약 테이블
            $stmt = $pdo->prepare("INSERT INTO member_login_summary 
                (member_id, user_id, last_login_date, total_login_count) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                last_login_date = VALUES(last_login_date),
                total_login_count = VALUES(total_login_count),
                updated_at = NOW()");
            $stmt->execute([$member_id, $member['user_id'], $last_login_date, $mb_lognum]);
            echo "✅ member_login_summary 테이블 업데이트 완료\n";
            
            // 3. 로그인 로그 레코드 생성 (마지막 로그인만)
            if ($last_login_date) {
                $stmt = $pdo->prepare("INSERT IGNORE INTO member_login_logs 
                    (member_id, user_id, login_date, login_count) 
                    VALUES (?, ?, ?, 1)");
                $stmt->execute([$member_id, $member['user_id'], $last_login_date]);
                echo "✅ member_login_logs 테이블에 로그 추가 완료\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ 오류: " . $e->getMessage() . "\n";
        }
        
        break;
    }
}

if (!$found) {
    echo "\n⚠️ Excel 파일에서 해당 회원을 찾을 수 없습니다.\n";
    
    // 빈 요약 레코드 생성
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO member_login_summary 
            (member_id, user_id, total_login_count) 
            VALUES (?, ?, 0)");
        $stmt->execute([$member_id, $member['user_id']]);
        echo "빈 로그인 요약 레코드 생성\n";
    } catch (PDOException $e) {
        echo "❌ 오류: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ 작업 완료!\n";