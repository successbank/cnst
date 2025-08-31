<?php
require_once 'db.php';

try {
    $pdo = getDB();
    
    // 관리자 계정 확인
    echo "=== Admin accounts (is_admin = 1) ===\n";
    $stmt = $pdo->query("SELECT * FROM members WHERE is_admin = 1");
    $admins = $stmt->fetchAll();
    
    if ($admins) {
        foreach ($admins as $admin) {
            echo "\nUser ID: " . $admin['user_id'] . "\n";
            echo "Name: " . $admin['name'] . "\n";
            echo "Email: " . $admin['email'] . "\n";
            echo "Password (hashed): " . $admin['password'] . "\n";
            echo "Is Active: " . $admin['is_active'] . "\n";
            echo "Last Login: " . $admin['last_login'] . "\n";
        }
    } else {
        echo "No admin accounts found.\n";
    }
    
    // 모든 멤버 확인 (처음 5명만)
    echo "\n\n=== All members (first 5) ===\n";
    $stmt = $pdo->query("SELECT user_id, name, email, is_admin, is_active FROM members LIMIT 5");
    $members = $stmt->fetchAll();
    
    foreach ($members as $member) {
        echo "ID: " . $member['user_id'] . ", Name: " . $member['name'] . ", Admin: " . $member['is_admin'] . ", Active: " . $member['is_active'] . "\n";
    }
    
    // 테스트용: admin 계정이 있는지 확인
    echo "\n\n=== Checking specific user IDs ===\n";
    $test_ids = ['admin', 'administrator', 'root', 'master'];
    foreach ($test_ids as $test_id) {
        $stmt = $pdo->prepare("SELECT user_id, password FROM members WHERE user_id = ?");
        $stmt->execute([$test_id]);
        $result = $stmt->fetch();
        if ($result) {
            echo "Found: " . $test_id . " (password hash: " . substr($result['password'], 0, 20) . "...)\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>