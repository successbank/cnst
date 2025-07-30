<?php
require_once dirname(__DIR__) . '/db.php';

try {
    // min_price와 max_price 컬럼 추가
    $queries = [
        "ALTER TABLE products ADD COLUMN min_price DECIMAL(10,2) DEFAULT NULL AFTER price",
        "ALTER TABLE products ADD COLUMN max_price DECIMAL(10,2) DEFAULT NULL AFTER min_price"
    ];
    
    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            echo "컬럼 추가 성공: " . substr($query, 0, 50) . "...\n";
        } catch (PDOException $e) {
            if ($e->getCode() == '42S21') {
                echo "컬럼이 이미 존재합니다.\n";
            } else {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n컬럼 추가 작업 완료\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>