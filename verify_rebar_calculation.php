<?php
// rebar_quote.php 계산식 검증 파일
require_once 'db.php';

echo "=== rebar_quote.php 계산식 검증 ===\n\n";

// 1. 실제 데이터베이스에서 데이터 가져오기
echo "[1] 데이터베이스 데이터 확인\n";

// D10 규격 정보
$stmt = $pdo->prepare("SELECT * FROM rebar_specifications WHERE spec_name = 'D10' AND is_active = TRUE");
$stmt->execute();
$spec = $stmt->fetch();

echo "규격: D10\n";
echo "- ID: " . $spec['id'] . "\n";
echo "- 직경: " . $spec['diameter'] . "mm\n";
echo "- 단위중량: " . $spec['unit_weight'] . "kg/m\n\n";

// D10 기준단가
$stmt = $pdo->prepare("SELECT unit_price FROM rebar_prices WHERE spec_id = ? AND is_active = TRUE");
$stmt->execute([$spec['id']]);
$price = $stmt->fetch();
echo "기준단가: " . ($price['unit_price'] ?? 0) . "원/kg\n\n";

// 8m 길이 정보
$stmt = $pdo->prepare("SELECT * FROM rebar_length_info WHERE spec_id = ? AND length = 8");
$stmt->execute([$spec['id']]);
$length_info = $stmt->fetch();

echo "길이 정보 (8m):\n";
echo "- 톤당 본수: " . $length_info['pieces_per_ton'] . "본\n";
echo "- 본당 중량: " . $length_info['weight_per_piece'] . "kg\n\n";

// SD500 재질 정보
$stmt = $pdo->prepare("SELECT * FROM rebar_materials WHERE material_code = 'SD500' AND is_active = TRUE");
$stmt->execute();
$material = $stmt->fetch();

echo "재질: SD500\n";
echo "- ID: " . $material['id'] . "\n";
echo "- 추가단가: " . $material['additional_price'] . "원/kg\n\n";

// 2. 계산식 검증
echo "\n[2] 계산식 검증 (5톤 주문 기준)\n";
echo "================================================\n";

$ton_quantity = 5;
$spec_id = $spec['id'];
$material_id = $material['id'];
$length = 8;

// rebar_quote.php의 실제 계산 로직 재현
$stmt = $pdo->prepare("
    SELECT 
        rl.*,
        rs.spec_name,
        rs.unit_weight,
        rp.unit_price
    FROM rebar_length_info rl
    JOIN rebar_specifications rs ON rl.spec_id = rs.id
    LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id AND rp.is_active = TRUE
    WHERE rl.spec_id = ? AND rl.length = ?
");
$stmt->execute([$spec_id, $length]);
$result = $stmt->fetch();

// 재질 추가 단가 조회
$stmt = $pdo->prepare("SELECT material_name, additional_price FROM rebar_materials WHERE id = ?");
$stmt->execute([$material_id]);
$material_data = $stmt->fetch();
$material_price = $material_data['additional_price'];
$material_name = $material_data['material_name'];

// 계산 과정
echo "입력값:\n";
echo "- 톤수: {$ton_quantity}톤\n";
echo "- 규격: " . $result['spec_name'] . "\n";
echo "- 길이: " . $result['length'] . "m\n";
echo "- 재질: " . $material_name . "\n\n";

echo "데이터베이스 값:\n";
echo "- 단위중량: " . $result['unit_weight'] . "kg/m\n";
echo "- 톤당 본수: " . $result['pieces_per_ton'] . "본\n";
echo "- 기준단가: " . ($result['unit_price'] ?: 0) . "원/kg\n";
echo "- 재질 추가단가: " . $material_price . "원/kg\n\n";

echo "계산 과정:\n";
echo "================================================\n";

// 1단계: 실제 본수 계산
$pieces_per_ton = $result['pieces_per_ton'];
$actual_quantity = $ton_quantity * $pieces_per_ton;
echo "1) 실제 본수 = 톤수 × 톤당 본수\n";
echo "   = {$ton_quantity} × {$pieces_per_ton}\n";
echo "   = {$actual_quantity}본\n\n";

// 2단계: 본당 중량 계산
$weight_per_piece = $result['unit_weight'] * $result['length'];
echo "2) 본당 중량 = 단위중량 × 길이\n";
echo "   = {$result['unit_weight']} × {$result['length']}\n";
echo "   = {$weight_per_piece}kg\n\n";

// 3단계: 총 중량 계산
$total_weight = $weight_per_piece * $actual_quantity;
echo "3) 총 중량 = 본당 중량 × 실제 본수\n";
echo "   = {$weight_per_piece} × {$actual_quantity}\n";
echo "   = {$total_weight}kg\n\n";

// 4단계: 적용 단가 계산
$base_price = $result['unit_price'] ?: 0;
$final_price = $base_price + $material_price;
echo "4) 적용 단가 = 기준단가 + 재질 추가단가\n";
echo "   = {$base_price} + {$material_price}\n";
echo "   = {$final_price}원/kg\n\n";

// 5단계: 총 금액 계산
$total_price = $total_weight * $final_price;
echo "5) 총 금액 = 총 중량 × 적용 단가\n";
echo "   = {$total_weight} × {$final_price}\n";
echo "   = " . number_format($total_price) . "원\n\n";

// 3. 결과 요약
echo "================================================\n";
echo "최종 계산 결과:\n";
echo "- 총 본수: " . number_format($actual_quantity) . "본\n";
echo "- 총 중량: " . number_format($total_weight, 2) . "kg\n";
echo "- 적용 단가: " . number_format($final_price) . "원/kg\n";
echo "- 총 금액: " . number_format($total_price) . "원\n";

// 4. AJAX 계산 시뮬레이션
echo "\n\n[3] AJAX 계산 API 호출 시뮬레이션\n";
echo "================================================\n";

$url = "http://211.248.112.67:1112/rebar_quote.php?action=calculate&spec_id={$spec_id}&material_id={$material_id}&length={$length}&quantity={$ton_quantity}";
echo "요청 URL: " . $url . "\n\n";

// 계산 결과 배열 (AJAX 응답 형식)
$ajax_response = [
    'success' => true,
    'data' => [
        'spec_name' => $result['spec_name'],
        'length' => $result['length'],
        'ton_quantity' => $ton_quantity,
        'quantity' => $actual_quantity,
        'unit_weight' => $result['unit_weight'],
        'weight_per_piece' => $weight_per_piece,
        'total_weight' => $total_weight,
        'base_price' => $base_price,
        'material_name' => $material_name,
        'material_price' => $material_price,
        'final_price' => $final_price,
        'total_price' => $total_price,
        'pieces_per_ton' => $pieces_per_ton
    ]
];

echo "AJAX 응답 데이터:\n";
echo json_encode($ajax_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n=== 검증 완료 ===\n";
?>