<?php
require_once 'db.php';
require_once 'includes/rebar_unit_weights.php';

echo "=== 철근 제품 등록 테스트 ===\n\n";

// 1. 철근 규격 확인
echo "1. 철근 규격 데이터:\n";
$specs = getAllRebarSpecs($pdo);
foreach ($specs as $spec) {
    echo "   - {$spec['spec_name']}: 직경 {$spec['diameter']}mm, 단중 {$spec['weight_per_meter']}kg/m\n";
}
echo "\n";

// 2. 철근 재질 확인
echo "2. 철근 재질 데이터:\n";
$materials = getAllRebarMaterials($pdo);
foreach ($materials as $material) {
    echo "   - {$material['material_name']}: {$material['price_per_kg']}원/kg\n";
}
echo "\n";

// 3. 번들 데이터 샘플 확인
echo "3. 번들 데이터 샘플 (D16, 8m):\n";
$bundle_info = getRebarBundleInfo($pdo, 'D16', 8.0);
if ($bundle_info) {
    echo "   - 본중: {$bundle_info['piece_weight']}kg\n";
    echo "   - 본수: {$bundle_info['pieces_per_ton']}개\n";
    echo "   - 총중량: {$bundle_info['weight_per_ton']}kg\n";
} else {
    echo "   번들 데이터 없음\n";
}
echo "\n";

// 4. 가격 계산 테스트
echo "4. 가격 계산 테스트:\n";
echo "   제품: D16 철근, SD400 재질, 8m, 국산\n";
echo "   기준 단가: 1000원\n";
echo "   원산지 단가: 0원 (국산)\n";
echo "   재질 단가: 0원/kg (SD400)\n";

$base_price = 1000;
$origin_price = 0;
$material_price = 0;
$length = 8.0;
$quantity = 105; // D16 8m의 본수
$unit_weight = 1.56; // D16의 단중

$calculated_price = calculateRebarPrice($base_price, $origin_price, $material_price, $length, $quantity, $unit_weight);

echo "   계산된 가격: " . number_format($calculated_price) . "원\n";
echo "   계산 과정:\n";
echo "     - 1개당 무게 = 1.56 × 8 = 12.48kg\n";
echo "     - 총 무게 = 12.48 × 105 = 1,310.4kg\n";
echo "     - 재질 비용 = 0 × 1,310.4 = 0원\n";
echo "     - 총 가격 = (1000 + 0) × 105 + 0 = " . number_format($calculated_price) . "원\n";

echo "\n";

// 5. 실제 제품 등록 테스트 (1개만)
echo "5. 샘플 제품 등록:\n";

try {
    // D10, SD400, 6m, 국산 제품 등록
    $product_name = "철근 D10 SD400 6m (테스트)";
    $specifications = "D10 (10mm, 0.56kg/m)";
    $description = "테스트 철근 제품";
    $test_price = 300000; // 테스트 가격

    // 중복 체크
    $check_stmt = $pdo->prepare("SELECT id FROM products WHERE product_name = ?");
    $check_stmt->execute([$product_name]);

    if ($check_stmt->rowCount() > 0) {
        echo "   이미 등록된 제품입니다.\n";
    } else {
        $insert_stmt = $pdo->prepare("
            INSERT INTO products (
                category_code,
                product_name,
                specifications,
                description,
                price,
                origin,
                material,
                length,
                stock_status,
                is_active
            ) VALUES (
                'rebar', ?, ?, ?, ?, '국산', 'SD400', 6.0, 'in_stock', 1
            )
        ");

        $insert_stmt->execute([$product_name, $specifications, $description, $test_price]);
        $product_id = $pdo->lastInsertId();

        echo "   제품 등록 완료! (ID: {$product_id})\n";
        echo "   제품명: {$product_name}\n";
        echo "   가격: " . number_format($test_price) . "원\n";
    }

} catch (Exception $e) {
    echo "   오류 발생: " . $e->getMessage() . "\n";
}

echo "\n=== 테스트 완료 ===\n";
?>