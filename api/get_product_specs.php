<?php
header('Content-Type: application/json');
require_once '../db.php';
require_once '../includes/input_validator.php';
if (!checkIpRateLimit('api_get_product_specs', 30, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests']);
    exit;
}

$response = ['success' => false];

try {
    $product_id = $_GET['product_id'] ?? null;
    
    if (!$product_id) {
        throw new Exception('제품 ID가 필요합니다.');
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            ps.*,
            pp.unit_price,
            pp.price_type
        FROM product_specifications ps
        LEFT JOIN product_prices pp ON ps.id = pp.spec_id 
            AND pp.is_active = 1 
            AND (pp.effective_date <= CURDATE() OR pp.effective_date IS NULL)
            AND (pp.expiry_date >= CURDATE() OR pp.expiry_date IS NULL)
        WHERE ps.product_id = ? AND ps.is_active = 1
        ORDER BY ps.display_order, ps.spec_name
    ");
    
    $stmt->execute([$product_id]);
    $specifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['specifications'] = $specifications;
    
} catch (Exception $e) {
    error_log("Product Specs Error: " . $e->getMessage());
    $response['error'] = '서버 오류가 발생했습니다.';
}

echo json_encode($response);
?>