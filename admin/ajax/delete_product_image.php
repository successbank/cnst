<?php
session_start();
require_once '../../db.php';

// 관리자 권한 체크
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
    exit;
}

// 요청 파라미터 확인
if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => '제품 ID가 필요합니다.']);
    exit;
}

$product_id = intval($_POST['product_id']);

try {
    // 제품 정보 조회
    $stmt = $pdo->prepare("SELECT main_image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => '제품을 찾을 수 없습니다.']);
        exit;
    }
    
    // 이미지 파일 삭제
    if ($product['main_image']) {
        $file_path = dirname(dirname(__DIR__)) . $product['main_image'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
    }
    
    // 데이터베이스 업데이트
    $stmt = $pdo->prepare("UPDATE products SET main_image = NULL, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$product_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => '이미지가 삭제되었습니다.'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()]);
}
?>