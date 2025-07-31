<?php
require_once 'db.php';

try {
    // stock_types 컬럼이 이미 존재하는지 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock_types'");
    if ($stmt->rowCount() > 0) {
        echo "stock_types column already exists.\n";
    } else {
        // stock_types 컬럼 추가
        $pdo->exec("ALTER TABLE products ADD COLUMN stock_types TEXT DEFAULT '[\"일반재고\"]' COMMENT '재고 상태 목록 (JSON 형식)' AFTER stock_type");
        echo "stock_types column added successfully.\n";
        
        // 기존 stock_type 데이터 마이그레이션
        $stmt = $pdo->prepare("
            UPDATE products 
            SET stock_types = CASE 
                WHEN stock_type = '장기재고' THEN '[\"장기재고\"]'
                WHEN stock_type = '중고' THEN '[\"중고\"]'
                ELSE '[\"일반재고\"]'
            END
            WHERE stock_types IS NULL OR stock_types = ''
        ");
        $stmt->execute();
        echo "Migrated existing stock_type data to stock_types.\n";
    }
    
    // 테스트: 몇 개 제품의 stock_types 확인
    $stmt = $pdo->query("SELECT id, product_name, stock_type, stock_types FROM products LIMIT 5");
    $products = $stmt->fetchAll();
    echo "\nSample products with stock_types:\n";
    foreach ($products as $product) {
        echo "ID: {$product['id']}, Name: {$product['product_name']}, Old: {$product['stock_type']}, New: {$product['stock_types']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>