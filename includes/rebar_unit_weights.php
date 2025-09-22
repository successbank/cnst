<?php
// 철근 단중 및 계산 관련 함수들

// 철근 규격별 단중 (kg/m)
$REBAR_UNIT_WEIGHTS = [
    'D10' => 0.560,
    'D13' => 0.995,
    'D16' => 1.560,
    'D19' => 2.250,
    'D22' => 3.040,
    'D25' => 3.980,
    'D29' => 5.040,
    'D32' => 6.230,
    'D35' => 7.510,
    'D38' => 8.950,
    'D41' => 10.500,
    'D51' => 15.900
];

/**
 * 철근 규격에서 단중 가져오기
 */
function getRebarUnitWeight($spec) {
    global $REBAR_UNIT_WEIGHTS;
    return isset($REBAR_UNIT_WEIGHTS[$spec]) ? $REBAR_UNIT_WEIGHTS[$spec] : null;
}

/**
 * 제품명에서 철근 규격 추출
 */
function extractRebarSpec($product_name) {
    preg_match('/(D\d+)/', $product_name, $matches);
    return isset($matches[1]) ? $matches[1] : null;
}

/**
 * 제품명에서 철근 단중 가져오기
 */
function getRebarUnitWeightFromProductName($product_name) {
    $spec = extractRebarSpec($product_name);
    return $spec ? getRebarUnitWeight($spec) : null;
}

/**
 * 철근 가격 계산
 * 공식: (기준 단가 + 원산지 단가 + 재질 단가) × 길이 × 단위(개)
 *
 * @param float $base_price 기준 단가
 * @param float $origin_price 원산지 단가
 * @param float $material_price 재질 단가 (kg당)
 * @param float $length 길이 (m)
 * @param int $quantity 수량 (개/번들)
 * @param float $unit_weight 단중 (kg/m)
 * @return float 계산된 가격
 */
function calculateRebarPrice($base_price, $origin_price, $material_price, $length, $quantity, $unit_weight) {
    // 1개당 무게 = 단중 × 길이
    $weight_per_piece = $unit_weight * $length;

    // 총 무게 = 1개당 무게 × 수량
    $total_weight = $weight_per_piece * $quantity;

    // 재질 비용 = 재질 단가(kg당) × 총 무게
    $material_cost = $material_price * $total_weight;

    // 총 가격 = (기준 단가 + 원산지 단가) × 수량 + 재질 비용
    $total_price = ($base_price + $origin_price) * $quantity + $material_cost;

    return $total_price;
}

/**
 * 철근 번들 정보 가져오기
 */
function getRebarBundleInfo($pdo, $spec, $length) {
    $stmt = $pdo->prepare("
        SELECT piece_weight, pieces_per_ton, weight_per_ton
        FROM rebar_length_data
        WHERE spec_name = ? AND length = ?
        LIMIT 1
    ");
    $stmt->execute([$spec, $length]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 모든 철근 규격 가져오기
 */
function getAllRebarSpecs($pdo) {
    $stmt = $pdo->query("
        SELECT id, spec_name, diameter, weight_per_meter
        FROM rebar_specifications
        WHERE is_active = 1
        ORDER BY diameter
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 모든 철근 재질 가져오기
 */
function getAllRebarMaterials($pdo) {
    $stmt = $pdo->query("
        SELECT id, material_name, price_per_kg, description
        FROM rebar_materials
        WHERE is_active = 1
        ORDER BY display_order
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>