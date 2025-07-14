<?php
require_once 'db.php';

try {
    // product_categories 테이블에 click_count 컬럼 추가
    $sql = "ALTER TABLE product_categories ADD COLUMN click_count INT DEFAULT 0 COMMENT '카테고리 클릭 수' AFTER display_order";
    $pdo->exec($sql);
    echo "product_categories 테이블에 click_count 컬럼을 추가했습니다.<br>";
    
    // 인덱스 추가 (검색 성능 향상)
    $sql = "ALTER TABLE product_categories ADD INDEX idx_click_count (click_count)";
    $pdo->exec($sql);
    echo "click_count 인덱스를 추가했습니다.<br>";
    
    // 초기 클릭 수 설정 (옵션: 제품 수에 비례하여 초기값 설정)
    $sql = "UPDATE product_categories pc 
            SET click_count = (
                SELECT COUNT(*) * 10 
                FROM products p 
                WHERE p.category_code = pc.category_code 
                AND p.is_active = 1
            )";
    $pdo->exec($sql);
    echo "초기 클릭 수를 제품 수 기반으로 설정했습니다.<br>";
    
    echo "<br><strong>완료!</strong><br>";
    echo "<a href='products.php'>제품소개 페이지로 돌아가기</a>";
    
} catch (Exception $e) {
    // 이미 컬럼이 존재하는 경우
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "click_count 컬럼이 이미 존재합니다.<br>";
        echo "<a href='products.php'>제품소개 페이지로 돌아가기</a>";
    } else {
        echo "오류 발생: " . $e->getMessage();
    }
}
?>