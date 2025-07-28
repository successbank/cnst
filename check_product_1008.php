<?php
require_once 'db.php';

$product_id = 1008;

// 제품 정보 확인
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name 
    FROM products p 
    JOIN product_categories pc ON p.category_code = pc.category_code 
    WHERE p.id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

echo "=== 제품 정보 ===\n";
echo "ID: " . $product['id'] . "\n";
echo "제품명: " . $product['product_name'] . "\n";
echo "카테고리 코드: " . $product['category_code'] . "\n";
echo "카테고리명: " . $product['category_name'] . "\n";
echo "규격: " . $product['specifications'] . "\n\n";

// 철근 카테고리 확인
$is_rebar = ($product['category_code'] === 'rebar' || 
             $product['category_code'] === '114' || 
             $product['category_code'] == 114 ||
             strpos(strtolower($product['category_name']), '철근') !== false);

echo "철근 제품 여부: " . ($is_rebar ? "예" : "아니오") . "\n";
echo "카테고리 코드 타입: " . gettype($product['category_code']) . "\n";
echo "카테고리 코드 값: '" . $product['category_code'] . "'\n";

// 규격명 추출
$spec_name = '';
if (preg_match('/^(D\d+)/', $product['specifications'], $matches)) {
    $spec_name = $matches[1];
}
echo "\n추출된 규격명: " . ($spec_name ?: "없음") . "\n";

// 철근 규격 확인
if ($spec_name) {
    $stmt = $pdo->prepare("SELECT * FROM rebar_specifications WHERE spec_name = ? AND is_active = TRUE");
    $stmt->execute([$spec_name]);
    $rebar_spec = $stmt->fetch();
    
    if ($rebar_spec) {
        echo "철근 규격 정보 존재: 예 (ID: " . $rebar_spec['id'] . ")\n";
    } else {
        echo "철근 규격 정보 존재: 아니오\n";
    }
}
?>