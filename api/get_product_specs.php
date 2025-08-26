<?php
header('Content-Type: application/json');
require_once '../db.php';

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
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>