<?php
require_once 'db.php';

$product_id = $_GET['id'] ?? 219;

// 제품 정보 가져오기
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name
    FROM products p 
    JOIN product_categories pc ON p.category_code = pc.category_code 
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found");
}

echo "<h2>Product Info:</h2>";
echo "<pre>";
echo "Product Name: " . $product['product_name'] . "\n";
echo "Category: " . $product['category_code'] . "\n";
echo "Available Materials: " . $product['available_materials'] . "\n";
echo "</pre>";

// 철근 규격 추출
preg_match('/(H?D\d+)/', $product['product_name'], $matches);
$rebar_spec = isset($matches[1]) ? $matches[1] : '';

// 재질 추출
$available_materials = json_decode($product['available_materials'] ?? '[]', true);
$rebar_material = !empty($available_materials[0]) ? $available_materials[0] : 'SD400';

echo "<h2>Extracted Data:</h2>";
echo "<pre>";
echo "Rebar Spec: " . $rebar_spec . "\n";
echo "Rebar Material: " . $rebar_material . "\n";
echo "</pre>";

// 번들 데이터 조회
echo "<h2>Bundle Data Query:</h2>";
$bundle_stmt = $pdo->prepare("
    SELECT p_unit_length, p_bd_count, p_bd_weight 
    FROM rebar_bundle_data 
    WHERE p_standard = ? AND p_material = ? 
    AND p_bd_count > 0 AND p_bd_weight > 0
    ORDER BY p_unit_length
");
$bundle_stmt->execute([$rebar_spec, $rebar_material]);
$bundle_data = $bundle_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
echo "Query params: p_standard = '$rebar_spec', p_material = '$rebar_material'\n";
echo "Bundle data count: " . count($bundle_data) . "\n";
print_r($bundle_data);
echo "</pre>";

// 다른 재질로도 확인
echo "<h2>Available Bundle Data (all materials for this spec):</h2>";
$stmt = $pdo->prepare("
    SELECT DISTINCT p_material, COUNT(*) as count 
    FROM rebar_bundle_data 
    WHERE p_standard = ?
    GROUP BY p_material
");
$stmt->execute([$rebar_spec]);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($materials);
echo "</pre>";
?>