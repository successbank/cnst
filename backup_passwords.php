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

$backup_file = 'backup_member_passwords_' . date('Y-m-d_His') . '.csv';

echo "=== 패스워드 백업 ===\n";
echo "백업 파일: $backup_file\n\n";

// 모든 회원의 ID와 현재 패스워드 백업
$stmt = $pdo->query("SELECT id, user_id, password FROM members ORDER BY id");
$members = $stmt->fetchAll();

$fp = fopen($backup_file, 'w');
fputcsv($fp, ['id', 'user_id', 'password']);

$count = 0;
foreach ($members as $member) {
    fputcsv($fp, [$member['id'], $member['user_id'], $member['password']]);
    $count++;
}

fclose($fp);

echo "✅ $count 개의 레코드를 백업했습니다.\n";
echo "백업 파일: $backup_file\n";