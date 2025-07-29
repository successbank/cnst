<?php
require_once 'includes/db_connect.php';

// Product ID 949 정보 조회
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = 949");
$stmt->execute();
$product = $stmt->fetch();

if ($product) {
    echo "<h2>Product ID 949 정보</h2>";
    echo "<p>제품명: " . $product['product_name'] . "</p>";
    echo "<p>규격: " . $product['specifications'] . "</p>";
    echo "<p>카테고리: " . $product['category_code'] . "</p>";
    echo "<p>가격: " . number_format($product['price']) . "원</p>";
    
    // 단위중량 정보 조회
    if ($product['specifications']) {
        $stmt2 = $pdo->prepare("SELECT * FROM unit_weights WHERE specification = ? AND is_active = 1");
        $stmt2->execute([$product['specifications']]);
        $unit_weight = $stmt2->fetch();
        
        echo "<h3>단위중량 정보</h3>";
        if ($unit_weight) {
            echo "<p>규격: " . $unit_weight['specification'] . "</p>";
            echo "<p>단위중량: " . $unit_weight['unit_weight'] . " kg/m</p>";
        } else {
            echo "<p>단위중량 정보가 없습니다.</p>";
        }
    }
} else {
    echo "<p>Product ID 949를 찾을 수 없습니다.</p>";
}
?>