<?php
require_once '../db.php';

try {
    // base_length 컬럼이 이미 존재하는지 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'base_length'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // 컬럼 추가
        $sql = "ALTER TABLE products ADD COLUMN base_length INT DEFAULT 6 COMMENT '기준길이(m)' AFTER min_order_qty";
        $pdo->exec($sql);
        echo "base_length 컬럼이 성공적으로 추가되었습니다.<br>";
        
        // 기본값 설정
        $pdo->exec("UPDATE products SET base_length = 6 WHERE base_length IS NULL");
        echo "기본값이 설정되었습니다.<br>";
    } else {
        echo "base_length 컬럼이 이미 존재합니다.<br>";
    }
    
    echo "<br><a href='admin_products_edit.php?id=28'>제품 편집 페이지로 돌아가기</a>";
    
} catch (PDOException $e) {
    echo "오류: " . $e->getMessage();
}
?>