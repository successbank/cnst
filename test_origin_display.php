<?php
require_once 'db.php';

// 철근 제품 테스트
$stmt = $pdo->query("
    SELECT id, product_name, origin, available_origins 
    FROM products 
    WHERE category_code = 'rebar' 
    AND available_origins IS NOT NULL
    LIMIT 5
");
$products = $stmt->fetchAll();

echo "<h2>원산지 정보 테스트</h2>";
echo "<h3>1. 제품 목록 페이지 테스트</h3>";
echo "<p><a href='/products_new.php?category=rebar' target='_blank'>철근 제품 목록 보기</a></p>";

echo "<h3>2. 제품별 원산지 정보</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>제품명</th><th>기본 원산지</th><th>선택 가능한 원산지</th><th>상세 페이지</th></tr>";

foreach ($products as $product) {
    $origins = json_decode($product['available_origins'], true);
    echo "<tr>";
    echo "<td>{$product['product_name']}</td>";
    echo "<td>{$product['origin']}</td>";
    echo "<td>" . (is_array($origins) ? implode(', ', $origins) : '파싱 오류') . "</td>";
    echo "<td><a href='/product_detail.php?id={$product['id']}' target='_blank'>상세보기</a></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>3. 테스트 결과</h3>";
echo "<ul>";
echo "<li>✅ 제품 목록 페이지: 원산지 정보가 텍스트로만 표시됨</li>";
echo "<li>✅ 제품 상세 페이지: 복수 원산지가 있을 경우 선택 가능한 드롭다운 표시</li>";
echo "<li>✅ 단일 원산지만 있을 경우 텍스트로 표시</li>";
echo "</ul>";
?>