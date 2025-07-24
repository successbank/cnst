<?php
// 직접 DB 연결
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "✅ 데이터베이스 연결 성공\n\n";
} catch (PDOException $e) {
    die("❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

// Excel 파일 읽기
$file_path = '/home/successbank/projects/docker/project1/html/114/member.xls';
$content = file_get_contents($file_path);

// EUC-KR에서 UTF-8로 변환
$content = iconv('EUC-KR', 'UTF-8//IGNORE', $content);

// HTML 테이블 파싱
preg_match_all('/<TR[^>]*>(.*?)<\/TR>/si', $content, $rows);

$imported = 0;
$skipped = 0;
$updated = 0;
$errors = 0;

echo "=== 회원 데이터 가져오기 시작 ===\n\n";

// 헤더 행 건너뛰기
$total_records = count($rows[0]) - 1;
echo "총 " . $total_records . "개의 레코드를 처리합니다...\n\n";
for ($i = 1; $i < count($rows[0]); $i++) {
    // 각 행의 TD 태그 파싱
    preg_match_all('/<TD[^>]*>(.*?)<\/TD>/si', $rows[1][$i], $cells);
    
    if (count($cells[1]) < 20) continue; // 필요한 컬럼이 없으면 건너뛰기
    
    // 데이터 추출
    $mb_uid = trim(strip_tags($cells[1][0]));
    $mb_type = trim(strip_tags($cells[1][1]));
    $mb_flag = trim(strip_tags($cells[1][2]));
    $mb_id = trim(strip_tags($cells[1][3]));
    $mb_pw = trim(strip_tags($cells[1][4]));
    $mb_name = trim(strip_tags($cells[1][5]));
    $mb_nic = trim(strip_tags($cells[1][6]));  // 회사명
    $mb_email = trim(strip_tags($cells[1][11]));
    $mb_zip = trim(strip_tags($cells[1][13]));
    $mb_addr1 = trim(strip_tags($cells[1][14]));
    $mb_addr2 = trim(strip_tags($cells[1][15]));
    $mb_job = trim(strip_tags($cells[1][16]));  // 업종
    $mb_url = trim(strip_tags($cells[1][17]));  // 홈페이지
    $mb_hand_tel = trim(strip_tags($cells[1][18]));  // 휴대폰
    $mb_home_tel = trim(strip_tags($cells[1][19]));  // 자택전화
    $mb_regisday = isset($cells[1][22]) ? trim(strip_tags($cells[1][22])) : '';
    
    // 필수 필드 체크
    if (empty($mb_id) || empty($mb_pw) || empty($mb_name)) {
        continue;
    }
    
    // 이메일이 없으면 기본값 설정
    if (empty($mb_email)) {
        $mb_email = $mb_id . '@noemail.com';
    }
    
    // 가입일 처리
    if (empty($mb_regisday) || $mb_regisday == '0' || strlen($mb_regisday) < 8) {
        $created_at = date('Y-m-d H:i:s');
    } else {
        // YYYYMMDD 형식을 Y-m-d H:i:s로 변환
        $year = substr($mb_regisday, 0, 4);
        $month = substr($mb_regisday, 4, 2);
        $day = substr($mb_regisday, 6, 2);
        if (checkdate($month, $day, $year)) {
            $created_at = "$year-$month-$day 00:00:00";
        } else {
            $created_at = date('Y-m-d H:i:s');
        }
    }
    
    try {
        // 기존 회원 체크
        $stmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
        $stmt->execute([$mb_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // 기존 회원 업데이트
            $stmt = $pdo->prepare("
                UPDATE members SET 
                    password = ?, name = ?, email = ?, phone = ?, landline = ?,
                    company = ?, business_type = ?, homepage = ?, zipcode = ?, address = ?, address_detail = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([
                $mb_pw, $mb_name, $mb_email, $mb_hand_tel, $mb_home_tel,
                $mb_nic, $mb_job, $mb_url, $mb_zip, $mb_addr1, $mb_addr2,
                $mb_id
            ]);
            $updated++;
            echo "📝 업데이트: $mb_id ($mb_name)\n";
        } else {
            // 새 회원 추가
            $stmt = $pdo->prepare("
                INSERT INTO members (
                    user_id, password, name, email, phone, landline,
                    company, business_type, homepage, zipcode, address, address_detail,
                    is_active, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
            ");
            $stmt->execute([
                $mb_id, $mb_pw, $mb_name, $mb_email, $mb_hand_tel, $mb_home_tel,
                $mb_nic, $mb_job, $mb_url, $mb_zip, $mb_addr1, $mb_addr2,
                $created_at
            ]);
            $imported++;
            echo "✅ 추가: $mb_id ($mb_name) - 가입일: $created_at\n";
        }
        
    } catch (PDOException $e) {
        $errors++;
        echo "❌ 오류 [$mb_id]: " . $e->getMessage() . "\n";
    }
    
    // 진행 상황 표시
    if (($imported + $updated + $skipped) % 100 == 0) {
        echo "\n--- 진행 상황: " . ($imported + $updated + $skipped) . "건 처리 ---\n\n";
    }
}

echo "\n\n=== 가져오기 완료 ===\n";
echo "✅ 신규 추가: $imported 건\n";
echo "📝 업데이트: $updated 건\n";
echo "⚠️ 오류: $errors 건\n";
echo "\n총 처리: " . ($imported + $updated) . " 건\n";

// 통계 확인
$stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
$total = $stmt->fetch()['total'];
echo "\n현재 전체 회원 수: $total 명\n";
?>