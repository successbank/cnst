<?php
require_once 'db.php';

$product_id = 729;

$stmt = $pdo->prepare("
    SELECT 
        id, 
        product_name, 
        origin, 
        available_origins, 
        stock_type, 
        stock_types 
    FROM products 
    WHERE id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

echo "<h2>제품 ID 729 데이터 확인</h2>";
echo "<pre>";
echo "ID: " . $product['id'] . "\n";
echo "제품명: " . $product['product_name'] . "\n";
echo "기본 원산지: " . $product['origin'] . "\n";
echo "사용 가능한 원산지: " . $product['available_origins'] . "\n";
echo "기존 재고 타입: " . $product['stock_type'] . "\n";
echo "새로운 재고 타입: " . $product['stock_types'] . "\n";
echo "</pre>";

// 원산지 파싱
echo "<h3>원산지 파싱 결과</h3>";
if (!empty($product['available_origins'])) {
    $origins = json_decode($product['available_origins'], true);
    echo "<p>원산지 개수: " . count($origins) . "개</p>";
    echo "<p>원산지 목록: " . implode(', ', $origins) . "</p>";
} else {
    echo "<p>available_origins가 비어있습니다.</p>";
}

// 재고 타입 파싱
echo "<h3>재고 타입 파싱 결과</h3>";
if (!empty($product['stock_types'])) {
    $stock_types = json_decode($product['stock_types'], true);
    echo "<p>재고 타입 개수: " . count($stock_types) . "개</p>";
    echo "<p>재고 타입 목록: " . implode(', ', $stock_types) . "</p>";
} else {
    echo "<p>stock_types가 비어있습니다.</p>";
}

// 테스트 데이터 업데이트
echo "<h3>테스트 데이터 업데이트</h3>";
$stmt = $pdo->prepare("
    UPDATE products 
    SET 
        available_origins = ?,
        stock_types = ?
    WHERE id = ?
");
$stmt->execute([
    '["국산", "베트남산"]',
    '["일반재고", "장기재고"]',
    $product_id
]);

echo "<p>✅ 제품 ID 729의 데이터를 업데이트했습니다:</p>";
echo "<ul>";
echo "<li>원산지: 국산, 베트남산 (2개)</li>";
echo "<li>재고 상태: 일반재고, 장기재고 (2개)</li>";
echo "</ul>";

echo "<p><a href='/product_detail.php?id=729' target='_blank'>제품 상세 페이지 다시 확인하기</a></p>";
?>