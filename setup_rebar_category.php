<?php
require_once 'db.php';

try {
    // 철근 카테고리 확인
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE category_code = 'rebar'");
    $stmt->execute();
    $category = $stmt->fetch();
    
    if (!$category) {
        // 철근 카테고리 추가
        $stmt = $pdo->prepare("
            INSERT INTO product_categories (category_code, category_name, display_order, is_active) 
            VALUES ('rebar', '철근', 1, 1)
        ");
        $stmt->execute();
        echo "철근 카테고리가 추가되었습니다.\n";
    } else {
        echo "철근 카테고리가 이미 존재합니다.\n";
        
        // 활성화 상태 확인
        if (!$category['is_active']) {
            $stmt = $pdo->prepare("UPDATE product_categories SET is_active = 1 WHERE category_code = 'rebar'");
            $stmt->execute();
            echo "철근 카테고리를 활성화했습니다.\n";
        }
    }
    
    // 철근 제품이 있는지 확인 (카테고리 카운트를 위해)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_code = 'rebar'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        // 더미 제품 하나 추가 (카테고리가 비어있지 않게)
        $stmt = $pdo->prepare("
            INSERT INTO products (category_code, product_name, specifications, description, unit, is_active) 
            VALUES ('rebar', '철근 제품', '다양한 규격', '철근 견적 시스템을 이용해주세요', 'TON', 1)
        ");
        $stmt->execute();
        echo "철근 카테고리에 기본 제품을 추가했습니다.\n";
    }
    
    echo "\n실행 완료!\n";
    echo "<br><a href='products.php'>제품 페이지로 이동</a>";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>