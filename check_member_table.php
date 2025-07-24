<?php
require_once 'db.php';

try {
    // members 테이블 구조 확인
    $stmt = $pdo->query("DESCRIBE members");
    $columns = $stmt->fetchAll();
    
    echo "=== Members Table Structure ===\n";
    foreach($columns as $col) {
        echo sprintf("%-20s %-15s %-10s %-10s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'],
            $col['Key'],
            $col['Default']
        );
    }
    
    // 샘플 데이터 확인
    echo "\n=== Sample Data (first 5 records) ===\n";
    $stmt = $pdo->query("SELECT id, user_id, password FROM members LIMIT 5");
    $samples = $stmt->fetchAll();
    
    foreach($samples as $row) {
        echo "ID: {$row['id']}, User ID: {$row['user_id']}, Password: " . substr($row['password'], 0, 20) . "...\n";
    }
    
    // 전체 레코드 수
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
    $total = $stmt->fetch()['total'];
    echo "\nTotal members: $total\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}