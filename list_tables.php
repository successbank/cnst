<?php
require_once 'db.php';

try {
    $pdo = getDB();
    
    // 모든 테이블 조회
    echo "Database: " . DB_NAME . "\n\n";
    echo "Tables:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "- " . $table . "\n";
    }
    
    // 각 테이블의 구조 확인
    echo "\nTable structures:\n";
    foreach ($tables as $table) {
        echo "\n=== $table ===\n";
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        foreach ($columns as $column) {
            echo $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>