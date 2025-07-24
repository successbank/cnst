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

// CSV 파일 읽기
$csv_file = '/home/successbank/projects/docker/project1/html/member_id_password.csv';

if (!file_exists($csv_file)) {
    die("❌ CSV 파일을 찾을 수 없습니다: $csv_file\n");
}

$updated = 0;
$not_found = 0;
$errors = 0;

echo "=== 회원 패스워드 업데이트 시작 ===\n\n";

// CSV 파일 열기
if (($handle = fopen($csv_file, "r")) !== FALSE) {
    // 헤더 행 건너뛰기
    $header = fgetcsv($handle, 1000, ",");
    
    $total_lines = count(file($csv_file)) - 1; // 헤더 제외
    echo "총 $total_lines 개의 레코드를 처리합니다...\n\n";
    
    $count = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $count++;
        $user_id = trim($data[0]);
        $password = trim($data[1]);
        
        if (empty($user_id) || empty($password)) {
            continue;
        }
        
        try {
            // 기존 회원 확인
            $stmt = $pdo->prepare("SELECT id, user_id, name FROM members WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $member = $stmt->fetch();
            
            if ($member) {
                // 패스워드 업데이트
                $stmt = $pdo->prepare("UPDATE members SET password = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$password, $user_id]);
                $updated++;
                echo "✅ 업데이트: $user_id (이름: {$member['name']})\n";
            } else {
                $not_found++;
                echo "⚠️  찾을 수 없음: $user_id\n";
            }
            
        } catch (PDOException $e) {
            $errors++;
            echo "❌ 오류 [$user_id]: " . $e->getMessage() . "\n";
        }
        
        // 진행 상황 표시
        if ($count % 100 == 0) {
            echo "\n--- 진행 상황: $count / $total_lines 처리 ---\n\n";
        }
    }
    fclose($handle);
}

echo "\n\n=== 패스워드 업데이트 완료 ===\n";
echo "✅ 업데이트 성공: $updated 건\n";
echo "⚠️  회원 없음: $not_found 건\n";
echo "❌ 오류: $errors 건\n";
echo "\n총 처리: " . ($updated + $not_found + $errors) . " 건\n";

// 업데이트된 회원 샘플 확인
echo "\n=== 업데이트 확인 (샘플 5건) ===\n";
$stmt = $pdo->query("SELECT user_id, password, name FROM members ORDER BY updated_at DESC LIMIT 5");
$samples = $stmt->fetchAll();

foreach($samples as $sample) {
    echo "ID: {$sample['user_id']}, 이름: {$sample['name']}, 패스워드: " . substr($sample['password'], 0, 10) . "...\n";
}