<?php
require_once 'db.php';

$product_id = 729; // D13

// 현재 데이터 확인
$stmt = $pdo->prepare("
    SELECT 
        id, 
        product_name, 
        stock_types,
        stock_type
    FROM products 
    WHERE id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

echo "<h2>D13 재고 상태 수정</h2>";
echo "<h3>현재 상태</h3>";
echo "<pre>";
echo "ID: " . $product['id'] . "\n";
echo "제품명: " . $product['product_name'] . "\n";
echo "현재 stock_types: " . $product['stock_types'] . "\n";
echo "기존 stock_type: " . $product['stock_type'] . "\n";
echo "</pre>";

// stock_types 파싱
if (!empty($product['stock_types'])) {
    $current_stock_types = json_decode($product['stock_types'], true);
    echo "<p>현재 재고 타입: " . implode(', ', $current_stock_types) . "</p>";
}

// 관리자가 설정한 대로 업데이트 (일반재고, 장기재고, 중고)
$new_stock_types = '["일반재고", "장기재고", "중고"]';

$stmt = $pdo->prepare("
    UPDATE products 
    SET stock_types = ?
    WHERE id = ?
");
$stmt->execute([$new_stock_types, $product_id]);

echo "<h3>업데이트 완료</h3>";
echo "<p>✅ D13의 재고 상태를 다음과 같이 업데이트했습니다:</p>";
echo "<ul>";
echo "<li>일반재고</li>";
echo "<li>장기재고</li>";
echo "<li>중고</li>";
echo "</ul>";

// 업데이트 후 확인
$stmt = $pdo->prepare("SELECT stock_types FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$updated = $stmt->fetch();

echo "<h3>업데이트 결과 확인</h3>";
echo "<p>업데이트된 stock_types: " . $updated['stock_types'] . "</p>";

echo "<p><a href='http://211.248.112.67:1112/product_detail.php?id=729' target='_blank'>D13 제품 상세 페이지 확인하기</a></p>";

// 다른 철근 제품들의 stock_types도 확인
echo "<h3>다른 철근 제품 재고 상태 확인</h3>";
$stmt = $pdo->query("
    SELECT id, product_name, stock_types 
    FROM products 
    WHERE category_code = 'rebar' 
    AND stock_types IS NOT NULL
    ORDER BY product_name
    LIMIT 10
");
$products = $stmt->fetchAll();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>제품명</th><th>재고 상태</th></tr>";
foreach ($products as $p) {
    $types = json_decode($p['stock_types'], true);
    echo "<tr>";
    echo "<td>{$p['id']}</td>";
    echo "<td>{$p['product_name']}</td>";
    echo "<td>" . (is_array($types) ? implode(', ', $types) : $p['stock_types']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>