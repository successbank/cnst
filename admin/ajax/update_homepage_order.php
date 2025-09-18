<?php
session_start();
require_once '../../db.php';
require_once '../admin_check.php';

// JSON 응답 헤더
header('Content-Type: application/json');

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => '잘못된 요청입니다.']);
    exit;
}

// 제품 ID와 순서 가져오기
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$order = isset($_POST['order']) ? (int)$_POST['order'] : 0;

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => '제품 ID가 필요합니다.']);
    exit;
}

// 순서는 0 이상이어야 함
if ($order < 0) {
    $order = 0;
}

try {
    // 제품 존재 여부 및 메인페이지 노출 상태 확인
    $stmt = $pdo->prepare("SELECT id, show_on_homepage FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'error' => '존재하지 않는 제품입니다.']);
        exit;
    }
    
    if (!$product['show_on_homepage']) {
        echo json_encode(['success' => false, 'error' => '메인페이지에 노출되지 않는 제품입니다.']);
        exit;
    }
    
    // 노출 순서 업데이트
    $stmt = $pdo->prepare("UPDATE products SET homepage_display_order = ? WHERE id = ?");
    $stmt->execute([$order, $product_id]);
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'product_id' => $product_id,
        'order' => $order,
        'message' => '노출 순서가 변경되었습니다.'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => '데이터베이스 오류가 발생했습니다.']);
}
?>