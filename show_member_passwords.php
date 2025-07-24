<?php
// 직접 DB 연결
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

// 전체 회원 수 확인
$stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
$total = $stmt->fetch()['total'];
echo "=== Members Table (ID / Password) ===\n";
echo "Total: $total records\n\n";

// 회원 정보 조회 (처음 50개만)
$stmt = $pdo->query("SELECT user_id, password FROM members ORDER BY id LIMIT 50");
$members = $stmt->fetchAll();

echo sprintf("%-20s | %s\n", "USER_ID", "PASSWORD");
echo str_repeat("-", 50) . "\n";

foreach($members as $member) {
    echo sprintf("%-20s | %s\n", $member['user_id'], $member['password']);
}

echo "\n... (showing first 50 records)\n";

// 패스워드 통계
echo "\n=== Password Statistics ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as empty_pw FROM members WHERE password IS NULL OR password = ''");
$empty = $stmt->fetch()['empty_pw'];
echo "Empty passwords: $empty\n";
echo "With passwords: " . ($total - $empty) . "\n";