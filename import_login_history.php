<?php
// 직접 DB 연결
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 데이터베이스 연결 성공\n\n";
} catch (PDOException $e) {
    die("❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

// Excel 파일에서 로그인 정보 읽기
$file_path = '/home/successbank/projects/docker/project1/html/114/member.xls';
$content = file_get_contents($file_path);

// EUC-KR에서 UTF-8로 변환
$content = iconv('EUC-KR', 'UTF-8//IGNORE', $content);

// HTML 테이블 파싱
preg_match_all('/<TR[^>]*>(.*?)<\/TR>/si', $content, $rows);

$imported = 0;
$updated = 0;
$errors = 0;

echo "=== 로그인 이력 가져오기 시작 ===\n\n";

// 헤더 행 건너뛰기
for ($i = 1; $i < count($rows[0]); $i++) {
    // 각 행의 TD 태그 파싱
    preg_match_all('/<TD[^>]*>(.*?)<\/TD>/si', $rows[1][$i], $cells);
    
    if (count($cells[1]) < 30) continue; // 필요한 컬럼이 없으면 건너뛰기
    
    // 데이터 추출
    $mb_id = trim(strip_tags($cells[1][3]));  // MB_ID
    $mb_lognum = trim(strip_tags($cells[1][27])); // MB_LOGNUM (로그인 횟수)
    $mb_logdate = trim(strip_tags($cells[1][28])); // MB_LOGDATE (마지막 로그인)
    
    // 필수 필드 체크
    if (empty($mb_id) || empty($mb_lognum) || $mb_lognum == '0') {
        continue;
    }
    
    try {
        // 회원 ID 조회
        $stmt = $pdo->prepare("SELECT id, user_id FROM members WHERE user_id = ?");
        $stmt->execute([$mb_id]);
        $member = $stmt->fetch();
        
        if (!$member) {
            continue;
        }
        
        // 로그인 날짜 파싱
        $last_login_date = null;
        if (!empty($mb_logdate) && $mb_logdate != '0') {
            // YYYYMMDDHHmmss 형식 파싱
            if (strlen($mb_logdate) >= 14) {
                $year = substr($mb_logdate, 0, 4);
                $month = substr($mb_logdate, 4, 2);
                $day = substr($mb_logdate, 6, 2);
                $hour = substr($mb_logdate, 8, 2);
                $minute = substr($mb_logdate, 10, 2);
                $second = substr($mb_logdate, 12, 2);
                
                if (checkdate($month, $day, $year)) {
                    $last_login_date = "$year-$month-$day $hour:$minute:$second";
                }
            } elseif (strlen($mb_logdate) == 8) {
                // YYYYMMDD 형식
                $year = substr($mb_logdate, 0, 4);
                $month = substr($mb_logdate, 4, 2);
                $day = substr($mb_logdate, 6, 2);
                
                if (checkdate($month, $day, $year)) {
                    $last_login_date = "$year-$month-$day 00:00:00";
                }
            }
        }
        
        // 1. members 테이블 업데이트
        $stmt = $pdo->prepare("UPDATE members SET 
            total_login_count = ?, 
            last_login_imported = ?,
            login_history_imported = 1
            WHERE id = ?");
        $stmt->execute([$mb_lognum, $last_login_date, $member['id']]);
        
        // 2. 로그인 요약 테이블에 추가/업데이트
        $stmt = $pdo->prepare("INSERT INTO member_login_summary 
            (member_id, user_id, last_login_date, total_login_count) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            last_login_date = VALUES(last_login_date),
            total_login_count = VALUES(total_login_count),
            updated_at = NOW()");
        $stmt->execute([$member['id'], $member['user_id'], $last_login_date, $mb_lognum]);
        
        // 3. 최근 로그인이 있는 경우 로그 레코드 생성
        if ($last_login_date) {
            // 이미 해당 날짜의 로그가 있는지 확인
            $stmt = $pdo->prepare("SELECT id FROM member_login_logs 
                WHERE member_id = ? AND DATE(login_date) = DATE(?)");
            $stmt->execute([$member['id'], $last_login_date]);
            
            if (!$stmt->fetch()) {
                // 없으면 추가
                $stmt = $pdo->prepare("INSERT INTO member_login_logs 
                    (member_id, user_id, login_date, login_count) 
                    VALUES (?, ?, ?, 1)");
                $stmt->execute([$member['id'], $member['user_id'], $last_login_date]);
            }
        }
        
        $imported++;
        
        if ($imported % 100 == 0) {
            echo "진행: $imported 건 처리...\n";
        }
        
    } catch (PDOException $e) {
        $errors++;
        echo "❌ 오류 [$mb_id]: " . $e->getMessage() . "\n";
    }
}

// 4. 통계 업데이트
echo "\n통계 업데이트 중...\n";

// 첫 로그인 날짜 업데이트
$sql = "UPDATE member_login_summary s
        SET first_login_date = (
            SELECT MIN(login_date) 
            FROM member_login_logs l 
            WHERE l.member_id = s.member_id
        )";
$pdo->exec($sql);

// 최근 30일 로그인 횟수 계산
$sql = "UPDATE member_login_summary s
        SET last_30_days_count = (
            SELECT COUNT(*) 
            FROM member_login_logs l 
            WHERE l.member_id = s.member_id 
            AND l.login_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        )";
$pdo->exec($sql);

echo "\n=== 가져오기 완료 ===\n";
echo "✅ 처리 완료: $imported 건\n";
echo "❌ 오류: $errors 건\n";

// 샘플 데이터 확인
echo "\n=== 로그인 많은 회원 TOP 10 ===\n";
$stmt = $pdo->query("
    SELECT m.user_id, m.name, s.total_login_count, s.last_login_date
    FROM member_login_summary s
    JOIN members m ON s.member_id = m.id
    ORDER BY s.total_login_count DESC
    LIMIT 10
");

$top_users = $stmt->fetchAll();
foreach ($top_users as $user) {
    echo sprintf("%-15s %-10s 로그인: %4d회, 마지막: %s\n", 
        $user['user_id'], 
        $user['name'], 
        $user['total_login_count'],
        $user['last_login_date'] ?: 'N/A'
    );
}