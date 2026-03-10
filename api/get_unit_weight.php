<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';
require_once '../includes/input_validator.php';
if (!checkIpRateLimit('api_get_unit_weight', 30, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests'], JSON_UNESCAPED_UNICODE);
    exit;
}

// CORS 설정 (허용 도메인 제한)
$allowed_origins = ['https://cnst.co.kr', 'https://www.cnst.co.kr'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

$response = ['success' => false, 'data' => null, 'error' => null];

try {
    // 파라미터 검증
    $category_code = $_GET['category'] ?? '';
    $specification = $_GET['specification'] ?? '';
    $material = $_GET['material'] ?? '';
    
    if (empty($category_code)) {
        throw new Exception('카테고리 코드가 필요합니다.');
    }
    
    // 제품 정보 조회
    $stmt = $pdo->prepare("
        SELECT 
            product_name,
            calculation_type,
            unit_weight_data,
            available_materials,
            available_sizes
        FROM products 
        WHERE category_code = ? AND has_calculator = 1
        LIMIT 1
    ");
    $stmt->execute([$category_code]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('해당 제품을 찾을 수 없습니다.');
    }
    
    // JSON 데이터 파싱
    $unit_weight_data = json_decode($product['unit_weight_data'], true);
    $available_materials = json_decode($product['available_materials'], true);
    $available_sizes = json_decode($product['available_sizes'], true);
    
    $response_data = [
        'product_name' => $product['product_name'],
        'calculation_type' => $product['calculation_type'],
        'available_materials' => $available_materials,
        'available_sizes' => $available_sizes
    ];
    
    // 특정 규격과 재질의 단위중량 조회
    if (!empty($specification)) {
        if (isset($unit_weight_data[$specification])) {
            if (!empty($material) && isset($unit_weight_data[$specification][$material])) {
                $response_data['unit_weight'] = $unit_weight_data[$specification][$material];
            } else {
                // 재질이 지정되지 않았거나 해당 재질이 없으면 첫 번째 값 반환
                $response_data['unit_weight'] = reset($unit_weight_data[$specification]);
            }
        } else {
            throw new Exception('해당 규격을 찾을 수 없습니다.');
        }
    } else {
        // 전체 단위중량 데이터 반환
        $response_data['unit_weight_data'] = $unit_weight_data;
    }
    
    $response['success'] = true;
    $response['data'] = $response_data;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>