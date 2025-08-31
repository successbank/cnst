<?php
require_once 'db.php';

try {
    $pdo = getDB();
    
    // users 테이블 확인
    echo "Checking users table:\n";
    $stmt = $pdo->query("SELECT * FROM users WHERE is_admin = 1 OR user_type = 'admin'");
    $admins = $stmt->fetchAll();
    
    if ($admins) {
        echo "\nAdmin accounts found:\n";
        foreach ($admins as $admin) {
            echo "ID: " . $admin['user_id'] . "\n";
            echo "Password (hashed): " . $admin['password'] . "\n";
            echo "Is Admin: " . ($admin['is_admin'] ?? 'N/A') . "\n";
            echo "User Type: " . ($admin['user_type'] ?? 'N/A') . "\n";
            echo "---\n";
        }
    } else {
        echo "No admin accounts found in users table.\n";
    }
    
    // 모든 사용자 확인
    echo "\nAll users:\n";
    $stmt = $pdo->query("SELECT user_id, name, email, is_admin, user_type FROM users LIMIT 10");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        echo "ID: " . $user['user_id'] . ", Name: " . $user['name'] . ", Email: " . $user['email'] . ", Admin: " . ($user['is_admin'] ?? 'N/A') . ", Type: " . ($user['user_type'] ?? 'N/A') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>