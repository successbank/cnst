<?php
// 철근 규격별 단중(단위중량) 데이터 정의
// 단위: kg/m

$rebar_unit_weights = [
    'D10' => 0.56,
    'D13' => 0.995,
    'D16' => 1.56,
    'D19' => 2.25,
    'D22' => 3.04,
    'D25' => 3.98,
    'D29' => 5.04,
    'D32' => 6.23,
    'D35' => 7.51,
    'D38' => 8.95,
    'D41' => 10.5,
    'D51' => 15.9
];

// 철근 규격별 단중을 반환하는 함수
function getRebarUnitWeight($spec_name) {
    global $rebar_unit_weights;
    
    // D10, D13 등의 형태로 정규화
    $spec_name = strtoupper(trim($spec_name));
    
    // 단중 반환
    return isset($rebar_unit_weights[$spec_name]) ? $rebar_unit_weights[$spec_name] : null;
}

// 전체 철근 규격 목록을 단중과 함께 반환
function getAllRebarSpecsWithWeight() {
    global $rebar_unit_weights;
    
    $specs = [];
    foreach ($rebar_unit_weights as $spec => $weight) {
        $specs[] = [
            'spec_name' => $spec,
            'unit_weight' => $weight,
            'display_text' => $spec . ' - ' . $weight . 'kg/m'
        ];
    }
    
    return $specs;
}

// 제품명에서 철근 규격을 추출하는 함수
function extractRebarSpec($product_name) {
    // 제품명에서 D10, D13 등의 패턴을 찾음
    if (preg_match('/\b(D\d{2,3})\b/i', $product_name, $matches)) {
        return strtoupper($matches[1]);
    }
    return null;
}

// 제품명으로부터 단중을 가져오는 함수
function getRebarUnitWeightFromProductName($product_name) {
    $spec = extractRebarSpec($product_name);
    if ($spec) {
        return getRebarUnitWeight($spec);
    }
    return null;
}
?>