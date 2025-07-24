<?php
require_once 'db.php';

// 테스트 계정
$test_id = 'lovearum';
$test_pw = '73370000';

echo "=== 로그인 테스트 ===\n";
echo "ID: $test_id\n";
echo "PW: $test_pw\n\n";

$stmt = $pdo->prepare("SELECT password FROM members WHERE user_id = ?");
$stmt->execute([$test_id]);
$member = $stmt->fetch();

if ($member) {
    echo "DB 패스워드: " . substr($member['password'], 0, 50) . "...\n";
    echo "패스워드 형식: " . (strpos($member['password'], '$2y$') === 0 ? "bcrypt" : "평문") . "\n";
    
    if (password_verify($test_pw, $member['password'])) {
        echo "\n✅ 로그인 가능!\n";
    } else {
        echo "\n❌ 로그인 불가 (패스워드 불일치)\n";
    }
} else {
    echo "❌ 회원을 찾을 수 없음\n";
}