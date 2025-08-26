<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once '../includes/SteelCalculator.php';

$response = ['success' => false];

try {
    $category = $_GET['category'] ?? '';
    $spec_id = $_GET['spec_id'] ?? null;
    $length = floatval($_GET['length'] ?? 0);
    $quantity = intval($_GET['quantity'] ?? 1);
    
    if (!$category || !$spec_id || $length <= 0) {
        throw new Exception('필수 파라미터가 누락되었습니다.');
    }
    
    // 규격 정보 조회
    $stmt = $pdo->prepare("
        SELECT 
            ps.*,
            p.category_code,
            pp.unit_price,
            pp.price_type
        FROM product_specifications ps
        JOIN products p ON ps.product_id = p.id
        LEFT JOIN product_prices pp ON ps.id = pp.spec_id 
            AND pp.is_active = 1
            AND (pp.effective_date <= CURDATE() OR pp.effective_date IS NULL)
            AND (pp.expiry_date >= CURDATE() OR pp.expiry_date IS NULL)
        WHERE ps.id = ? AND ps.is_active = 1
    ");
    
    $stmt->execute([$spec_id]);
    $spec = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$spec) {
        throw new Exception('규격 정보를 찾을 수 없습니다.');
    }
    
    $calculator = new SteelCalculator($pdo);
    
    // 규격 정보를 배열로 구성
    $specifications = [
        'unit_weight' => $spec['unit_weight'],
        'height' => $spec['height'],
        'width' => $spec['width'],
        'thickness' => $spec['thickness'],
        'web_thickness' => $spec['web_thickness'],
        'flange_thickness' => $spec['flange_thickness'],
        'outer_diameter' => $spec['outer_diameter'],
        'diameter' => $spec['outer_diameter'], // 환봉용
        'width1' => $spec['width'],
        'width2' => $spec['height'] ?? $spec['width']
    ];
    
    // 중량 계산
    $weight = $calculator->calculateWeight($category, $specifications, $length, $quantity);
    
    $response['success'] = true;
    $response['weight'] = round($weight, 2);
    
    // 가격 계산 (가격 정보가 있는 경우)
    if (!empty($spec['unit_price']) && !empty($spec['price_type'])) {
        $price = $calculator->calculatePrice($weight, $spec['unit_price'], $spec['price_type']);
        $response['price'] = round($price, 0);
        $response['price_type'] = $spec['price_type'];
    }
    
    // 계산 이력 저장
    $stmt = $pdo->prepare("
        INSERT INTO calculation_history 
        (session_id, product_id, spec_id, input_data, calculated_weight, calculated_price, user_ip)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $inputData = json_encode([
        'category' => $category,
        'length' => $length,
        'quantity' => $quantity
    ]);
    
    $stmt->execute([
        session_id(),
        $spec['product_id'],
        $spec_id,
        $inputData,
        $weight,
        $response['price'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>