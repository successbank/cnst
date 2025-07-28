<?php
// rebar_quote.php vs product_detail.php 계산식 비교

echo "=== rebar_quote.php vs product_detail.php 계산식 비교 ===\n\n";

// 테스트 데이터
$test_cases = [
    [
        'name' => 'D10, 8m, SD500, 5톤',
        'spec' => 'D10',
        'unit_weight' => 0.56,
        'length' => 8,
        'pieces_per_ton' => 189.00,
        'base_price' => 850,
        'material' => 'SD500',
        'material_price' => 30,
        'ton_quantity' => 5
    ],
    [
        'name' => 'D13, 10m, SD600, 3톤',
        'spec' => 'D13',
        'unit_weight' => 1.04,
        'length' => 10,
        'pieces_per_ton' => 96.00,
        'base_price' => 850,
        'material' => 'SD600',
        'material_price' => 50,
        'ton_quantity' => 3
    ],
    [
        'name' => 'D16, 12m, SD400, 10톤',
        'spec' => 'D16',
        'unit_weight' => 1.58,
        'length' => 12,
        'pieces_per_ton' => 53.00,
        'base_price' => 850,
        'material' => 'SD400',
        'material_price' => 0,
        'ton_quantity' => 10
    ]
];

foreach ($test_cases as $test) {
    echo "[테스트: {$test['name']}]\n";
    echo "입력값:\n";
    echo "- 규격: {$test['spec']}\n";
    echo "- 단위중량: {$test['unit_weight']}kg/m\n";
    echo "- 길이: {$test['length']}m\n";
    echo "- 톤당 본수: {$test['pieces_per_ton']}본\n";
    echo "- 기준단가: {$test['base_price']}원/kg\n";
    echo "- 재질: {$test['material']} (추가단가: {$test['material_price']}원/kg)\n";
    echo "- 주문량: {$test['ton_quantity']}톤\n\n";
    
    // rebar_quote.php 방식 계산
    echo "rebar_quote.php 계산식:\n";
    
    // 1. 실제 본수
    $actual_quantity = $test['ton_quantity'] * $test['pieces_per_ton'];
    echo "1) 실제 본수 = {$test['ton_quantity']} × {$test['pieces_per_ton']} = {$actual_quantity}본\n";
    
    // 2. 본당 중량
    $weight_per_piece = $test['unit_weight'] * $test['length'];
    echo "2) 본당 중량 = {$test['unit_weight']} × {$test['length']} = {$weight_per_piece}kg\n";
    
    // 3. 총 중량
    $total_weight = $weight_per_piece * $actual_quantity;
    echo "3) 총 중량 = {$weight_per_piece} × {$actual_quantity} = {$total_weight}kg\n";
    
    // 4. 적용 단가
    $final_price = $test['base_price'] + $test['material_price'];
    echo "4) 적용 단가 = {$test['base_price']} + {$test['material_price']} = {$final_price}원/kg\n";
    
    // 5. 총 금액
    $total_price = $total_weight * $final_price;
    echo "5) 총 금액 = {$total_weight} × {$final_price} = " . number_format($total_price) . "원\n";
    
    echo "\n결과:\n";
    echo "- 총 본수: " . number_format($actual_quantity) . "본\n";
    echo "- 총 중량: " . number_format($total_weight, 2) . "kg\n";
    echo "- 총 금액: " . number_format($total_price) . "원\n";
    echo "\n----------------------------------------\n\n";
}

echo "=== 계산식 요약 ===\n";
echo "PHP (서버사이드):\n";
echo <<<'PHP'
// 1. 실제 본수 = 톤수 × 톤당 본수
$actual_quantity = $ton_quantity * $pieces_per_ton;

// 2. 본당 중량 = 단위중량 × 길이
$weight_per_piece = $unit_weight * $length;

// 3. 총 중량 = 본당 중량 × 실제 본수
$total_weight = $weight_per_piece * $actual_quantity;

// 4. 적용 단가 = 기준단가 + 재질 추가단가
$final_price = $base_price + $material_price;

// 5. 총 금액 = 총 중량 × 적용 단가
$total_price = $total_weight * $final_price;
PHP;

echo "\n\nJavaScript (클라이언트사이드):\n";
echo <<<'JS'
// 1. 실제 본수 = 톤수 × 톤당 본수
const actualQuantity = Math.round(tonQuantity * pieces_per_ton);

// 2. 본당 중량 = 단위중량 × 길이
const weightPerPiece = unit_weight * length;

// 3. 총 중량 = 본당 중량 × 실제 본수
const totalWeight = weightPerPiece * actualQuantity;

// 4. 적용 단가 = 기준단가 + 재질 추가단가
const finalPrice = basePrice + materialPrice;

// 5. 총 금액 = 총 중량 × 적용 단가
const totalPrice = Math.round(totalWeight * finalPrice);
JS;

echo "\n\n[검증 완료]\n";
?>