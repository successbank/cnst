<?php
require_once 'db.php';

// 철근 카테고리의 제품들 가져오기
$stmt = $pdo->query("
    SELECT id, product_name, origin, available_origins 
    FROM products 
    WHERE category_code = 'rebar' 
    AND is_active = 1
    LIMIT 10
");
$products = $stmt->fetchAll();

echo "<h2>철근 제품의 원산지 설정 확인</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>제품명</th><th>기본 원산지</th><th>available_origins (원본)</th><th>파싱된 원산지</th><th>원산지 개수</th></tr>";

foreach ($products as $product) {
    echo "<tr>";
    echo "<td>{$product['id']}</td>";
    echo "<td>{$product['product_name']}</td>";
    echo "<td>{$product['origin']}</td>";
    echo "<td>" . htmlspecialchars($product['available_origins']) . "</td>";
    
    // available_origins 파싱
    $origins_array = [];
    if (!empty($product['available_origins'])) {
        $origins_array = json_decode($product['available_origins'], true);
    }
    
    echo "<td>";
    if (is_array($origins_array)) {
        echo implode(', ', $origins_array);
    } else {
        echo "파싱 실패";
    }
    echo "</td>";
    
    echo "<td>" . (is_array($origins_array) ? count($origins_array) : 0) . "</td>";
    echo "</tr>";
}

echo "</table>";

// products_new.php와 동일한 로직으로 테스트
echo "<h3>products_new.php 로직 테스트</h3>";

$stmt = $pdo->query("
    SELECT p.*, pc.category_name 
    FROM products p 
    JOIN product_categories pc ON p.category_code = pc.category_code 
    WHERE p.category_code = 'rebar' AND p.is_active = 1
    LIMIT 5
");
$products = $stmt->fetchAll();

// 각 제품의 available_origins 파싱
foreach ($products as &$product) {
    if (!empty($product['available_origins'])) {
        $product['available_origins_array'] = json_decode($product['available_origins'], true);
    } else {
        $product['available_origins_array'] = [$product['origin']];
    }
    
    echo "<div style='margin: 20px; padding: 10px; border: 1px solid #ccc;'>";
    echo "<h4>{$product['product_name']}</h4>";
    echo "<p>available_origins: " . htmlspecialchars($product['available_origins']) . "</p>";
    echo "<p>available_origins_array: " . print_r($product['available_origins_array'], true) . "</p>";
    echo "<p>배열 개수: " . count($product['available_origins_array']) . "</p>";
    
    if (count($product['available_origins_array']) > 1) {
        echo "<p style='color: green;'>✓ 복수 원산지 표시됨</p>";
    } else {
        echo "<p style='color: red;'>✗ 단일 원산지만 있음</p>";
    }
    echo "</div>";
}
?>