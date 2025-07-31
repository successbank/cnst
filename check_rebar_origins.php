<?php
require_once 'db.php';

echo "<h2>철근 제품 원산지 설정 상태 확인</h2>";

// 모든 철근 제품 조회
$stmt = $pdo->query("
    SELECT id, product_name, origin, available_origins 
    FROM products 
    WHERE category_code = 'rebar' 
    AND is_active = 1
    ORDER BY product_name
");
$products = $stmt->fetchAll();

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr style='background: #e0e0e0;'>";
echo "<th>ID</th>";
echo "<th>제품명</th>";
echo "<th>기본 원산지</th>";
echo "<th>available_origins</th>";
echo "<th>원산지 개수</th>";
echo "<th>상태</th>";
echo "</tr>";

$single_origin_products = [];
$multi_origin_products = [];

foreach ($products as $product) {
    $origins_array = [];
    if (!empty($product['available_origins'])) {
        $origins_array = json_decode($product['available_origins'], true);
    }
    
    $origin_count = is_array($origins_array) ? count($origins_array) : 0;
    $status = $origin_count > 1 ? '✅ 복수 원산지' : '❌ 단일 원산지';
    $row_color = $origin_count > 1 ? '#e8f5e9' : '#ffebee';
    
    echo "<tr style='background: {$row_color};'>";
    echo "<td>{$product['id']}</td>";
    echo "<td><strong>{$product['product_name']}</strong></td>";
    echo "<td>{$product['origin']}</td>";
    echo "<td>" . htmlspecialchars(substr($product['available_origins'], 0, 50)) . "...</td>";
    echo "<td style='text-align: center;'><strong>{$origin_count}</strong></td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
    
    if ($origin_count <= 1) {
        $single_origin_products[] = $product;
    } else {
        $multi_origin_products[] = $product;
    }
}

echo "</table>";

echo "<h3>통계</h3>";
echo "<ul>";
echo "<li>전체 철근 제품: " . count($products) . "개</li>";
echo "<li style='color: green;'>복수 원산지 설정: " . count($multi_origin_products) . "개</li>";
echo "<li style='color: red;'>단일 원산지만 있음: " . count($single_origin_products) . "개</li>";
echo "</ul>";

if (count($single_origin_products) > 0) {
    echo "<h3>단일 원산지만 있는 제품 목록</h3>";
    echo "<p>다음 제품들은 아직 복수 원산지가 설정되지 않았습니다:</p>";
    echo "<ul>";
    foreach ($single_origin_products as $product) {
        echo "<li>{$product['product_name']} (ID: {$product['id']}, 현재: {$product['origin']})</li>";
    }
    echo "</ul>";
}
?>