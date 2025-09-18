<?php
require_once 'db.php';

// 철근 제품의 has_calculator 값 확인
$stmt = $pdo->query("
    SELECT id, product_name, has_calculator, category_code 
    FROM products 
    WHERE category_code = 'rebar' 
    ORDER BY id
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Rebar Products Calculator Status:</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Product Name</th><th>has_calculator</th></tr>";
foreach ($products as $product) {
    echo "<tr>";
    echo "<td>{$product['id']}</td>";
    echo "<td>{$product['product_name']}</td>";
    echo "<td>{$product['has_calculator']}</td>";
    echo "</tr>";
}
echo "</table>";

// has_calculator를 1로 업데이트
if (isset($_GET['update'])) {
    $stmt = $pdo->prepare("UPDATE products SET has_calculator = 1 WHERE category_code = 'rebar'");
    $stmt->execute();
    echo "<p>Updated all rebar products to has_calculator = 1</p>";
    echo "<a href='check_rebar_calculator.php'>Refresh</a>";
} else {
    echo "<p><a href='check_rebar_calculator.php?update=1'>Update all rebar products to has_calculator = 1</a></p>";
}
?>