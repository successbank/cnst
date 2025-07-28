<?php
// rebar_quote.php의 계산식 테스트

echo "=== rebar_quote.php 계산식 점검 ===\n\n";

// 예시 데이터
$ton_quantity = 5; // 5톤
$pieces_per_ton = 189.00; // D10 기준
$unit_weight = 0.56; // D10 단위중량 (kg/m)
$length = 8; // 8m
$base_price = 850; // 기준단가
$material_price = 30; // SD500 추가단가

echo "입력값:\n";
echo "- 톤수: {$ton_quantity}톤\n";
echo "- 톤당 본수: {$pieces_per_ton}본\n";
echo "- 단위중량: {$unit_weight}kg/m\n";
echo "- 길이: {$length}m\n";
echo "- 기준단가: {$base_price}원/kg\n";
echo "- 재질 추가단가: {$material_price}원/kg\n\n";

echo "계산 과정:\n";

// 1. 실제 본수 계산
$actual_quantity = $ton_quantity * $pieces_per_ton;
echo "1. 실제 본수 = 톤수 × 톤당 본수\n";
echo "   = {$ton_quantity} × {$pieces_per_ton} = {$actual_quantity}본\n\n";

// 2. 본당 중량 계산
$weight_per_piece = $unit_weight * $length;
echo "2. 본당 중량 = 단위중량 × 길이\n";
echo "   = {$unit_weight} × {$length} = {$weight_per_piece}kg\n\n";

// 3. 총 중량 계산
$total_weight = $weight_per_piece * $actual_quantity;
echo "3. 총 중량 = 본당 중량 × 실제 본수\n";
echo "   = {$weight_per_piece} × {$actual_quantity} = {$total_weight}kg\n\n";

// 4. 적용 단가 계산
$final_price = $base_price + $material_price;
echo "4. 적용 단가 = 기준단가 + 재질 추가단가\n";
echo "   = {$base_price} + {$material_price} = {$final_price}원/kg\n\n";

// 5. 총 금액 계산
$total_price = $total_weight * $final_price;
echo "5. 총 금액 = 총 중량 × 적용 단가\n";
echo "   = {$total_weight} × {$final_price} = " . number_format($total_price) . "원\n\n";

echo "=== 계산 결과 ===\n";
echo "총 중량: " . number_format($total_weight, 2) . "kg\n";
echo "총 금액: " . number_format($total_price) . "원\n";
?>