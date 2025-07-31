<?php
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? '';

if (empty($category)) {
    echo json_encode(['success' => false, 'message' => '카테고리를 선택해주세요.']);
    exit;
}

try {
    // 해당 카테고리의 모든 활성 제품 가져오기
    $stmt = $pdo->prepare("
        SELECT id, product_name, origin, available_origins, stock_type, stock_types, specifications
        FROM products 
        WHERE category_code = ? AND is_active = 1
        ORDER BY product_name
    ");
    $stmt->execute([$category]);
    $products = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '제품을 불러오는 중 오류가 발생했습니다.'
    ]);
}