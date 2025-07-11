<?php
require_once '../db.php';

try {
    // 원산지와 제조사 컬럼 추가
    $sql = "ALTER TABLE products 
            ADD COLUMN origin VARCHAR(100) DEFAULT NULL COMMENT '원산지' AFTER stock_status,
            ADD COLUMN manufacturer VARCHAR(100) DEFAULT NULL COMMENT '제조사' AFTER origin";
    
    $pdo->exec($sql);
    echo "컬럼이 성공적으로 추가되었습니다.\n";
    
    // 기본값 설정
    $pdo->exec("UPDATE products SET origin = '대한민국' WHERE origin IS NULL");
    $pdo->exec("UPDATE products SET manufacturer = '포스코' WHERE manufacturer IS NULL AND category_code IN ('h-beam', 'i-beam')");
    
    echo "기본값이 설정되었습니다.\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "컬럼이 이미 존재합니다.\n";
    } else {
        echo "오류: " . $e->getMessage() . "\n";
    }
}
?>