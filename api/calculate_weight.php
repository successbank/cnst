<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';

// CORS 설정
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$response = ['success' => false, 'data' => null, 'error' => null];

try {
    // POST 데이터 가져오기
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 파라미터 검증
    $category_code = $input['category'] ?? '';
    $specification = $input['specification'] ?? '';
    $material = $input['material'] ?? '';
    $length = floatval($input['length'] ?? 0);
    $quantity = intval($input['quantity'] ?? 0);
    
    if (empty($category_code) || empty($specification) || $quantity <= 0) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }
    
    // 제품 정보 조회
    $stmt = $pdo->prepare("
        SELECT 
            product_name,
            calculation_type,
            unit_weight_data
        FROM products 
        WHERE category_code = ? AND has_calculator = 1
        LIMIT 1
    ");
    $stmt->execute([$category_code]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('해당 제품을 찾을 수 없습니다.');
    }
    
    // 단위중량 가져오기
    $unit_weight_data = json_decode($product['unit_weight_data'], true);
    $unit_weight = 0;
    
    if (isset($unit_weight_data[$specification])) {
        if (!empty($material) && isset($unit_weight_data[$specification][$material])) {
            $unit_weight = $unit_weight_data[$specification][$material];
        } else {
            $unit_weight = reset($unit_weight_data[$specification]);
        }
    } else {
        throw new Exception('해당 규격의 단위중량을 찾을 수 없습니다.');
    }
    
    // 중량 계산
    $calculated_weight = 0;
    $calculation_steps = [];
    
    if ($product['calculation_type'] === 'linear') {
        // 선형 제품: 단위중량 × 길이 × 수량
        if ($length <= 0) {
            throw new Exception('선형 제품은 길이가 필요합니다.');
        }
        
        $weight_per_piece = $unit_weight * $length;
        $calculated_weight = $weight_per_piece * $quantity;
        
        $calculation_steps = [
            "단위중량: {$unit_weight} kg/m",
            "1본 중량: {$unit_weight} × {$length}m = " . round($weight_per_piece, 2) . " kg",
            "총 중량: " . round($weight_per_piece, 2) . " × {$quantity}본 = " . round($calculated_weight, 1) . " kg"
        ];
    } else {
        // 판재 제품: 단위중량(장) × 수량
        $calculated_weight = $unit_weight * $quantity;
        
        $calculation_steps = [
            "단위중량(장): {$unit_weight} kg",
            "총 중량: {$unit_weight} × {$quantity}장 = " . round($calculated_weight, 1) . " kg"
        ];
    }
    
    // 계산 로그 저장 (옵션)
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $log_stmt = $pdo->prepare("
        INSERT INTO calculation_logs 
        (product_id, category_code, specification, material, length, quantity, calculated_weight, user_ip)
        VALUES (
            (SELECT id FROM products WHERE category_code = ? LIMIT 1),
            ?, ?, ?, ?, ?, ?, ?
        )
    ");
    $log_stmt->execute([
        $category_code, $category_code, $specification, $material, 
        $length, $quantity, $calculated_weight, $user_ip
    ]);
    
    $response['success'] = true;
    $response['data'] = [
        'product_name' => $product['product_name'],
        'specification' => $specification,
        'material' => $material,
        'length' => $length,
        'quantity' => $quantity,
        'unit_weight' => $unit_weight,
        'calculated_weight' => round($calculated_weight, 1),
        'calculation_steps' => $calculation_steps
    ];
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>