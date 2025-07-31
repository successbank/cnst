<?php
require_once 'db.php';

$product_id = 729; // D13

// 현재 데이터 확인
$stmt = $pdo->prepare("
    SELECT 
        id, 
        product_name, 
        origin,
        available_origins
    FROM products 
    WHERE id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

echo "<h2>D13 원산지 정보 수정</h2>";
echo "<h3>현재 상태</h3>";
echo "<pre>";
echo "ID: " . $product['id'] . "\n";
echo "제품명: " . $product['product_name'] . "\n";
echo "기본 원산지: " . $product['origin'] . "\n";
echo "현재 available_origins: " . $product['available_origins'] . "\n";
echo "</pre>";

// available_origins 파싱
if (!empty($product['available_origins'])) {
    $current_origins = json_decode($product['available_origins'], true);
    echo "<p>현재 원산지: " . implode(', ', $current_origins) . "</p>";
}

// 관리자가 설정한 대로 업데이트 (국산, 일본산, 베트남산)
$new_origins = '["국산", "일본산", "베트남산"]';

$stmt = $pdo->prepare("
    UPDATE products 
    SET available_origins = ?,
        origin = '국산'
    WHERE id = ?
");
$stmt->execute([$new_origins, $product_id]);

echo "<h3>업데이트 완료</h3>";
echo "<p>✅ D13의 원산지를 다음과 같이 업데이트했습니다:</p>";
echo "<ul>";
echo "<li>국산</li>";
echo "<li>일본산</li>";
echo "<li>베트남산</li>";
echo "</ul>";

// 업데이트 후 확인
$stmt = $pdo->prepare("SELECT available_origins FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$updated = $stmt->fetch();

echo "<h3>업데이트 결과 확인</h3>";
echo "<p>업데이트된 available_origins: " . $updated['available_origins'] . "</p>";

echo "<p><a href='http://211.248.112.67:1112/product_detail.php?id=729' target='_blank'>D13 제품 상세 페이지 확인하기</a></p>";

// 다른 철근 제품들의 원산지도 확인
echo "<h3>다른 철근 제품 원산지 확인</h3>";
$stmt = $pdo->query("
    SELECT id, product_name, available_origins 
    FROM products 
    WHERE category_code = 'rebar' 
    AND available_origins IS NOT NULL
    ORDER BY product_name
    LIMIT 10
");
$products = $stmt->fetchAll();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>제품명</th><th>원산지</th></tr>";
foreach ($products as $p) {
    $origins = json_decode($p['available_origins'], true);
    echo "<tr>";
    echo "<td>{$p['id']}</td>";
    echo "<td>{$p['product_name']}</td>";
    echo "<td>" . (is_array($origins) ? implode(', ', $origins) : $p['available_origins']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>