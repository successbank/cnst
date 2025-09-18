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

// 제품 ID와 상태 가져오기
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$show_on_homepage = isset($_POST['show_on_homepage']) ? (int)$_POST['show_on_homepage'] : 0;

if (!$product_id) {
    echo json_encode(['success' => false, 'error' => '제품 ID가 필요합니다.']);
    exit;
}

try {
    // 제품 존재 여부 확인
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => '존재하지 않는 제품입니다.']);
        exit;
    }
    
    // 메인페이지 노출 상태 업데이트
    $stmt = $pdo->prepare("UPDATE products SET show_on_homepage = ? WHERE id = ?");
    $stmt->execute([$show_on_homepage, $product_id]);
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'product_id' => $product_id,
        'show_on_homepage' => $show_on_homepage,
        'message' => $show_on_homepage ? '메인페이지에 노출됩니다.' : '메인페이지에서 숨겨졌습니다.'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => '데이터베이스 오류가 발생했습니다.']);
}
?>