<?php
session_start();
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('잘못된 요청입니다.');
    }

    $category_id = $_POST['category_id'] ?? null;
    $category_name = trim($_POST['category_name'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 99);

    if (!$category_id || empty($category_name)) {
        throw new Exception('필수 정보가 누락되었습니다.');
    }

    // 카테고리 존재 확인
    $stmt = $pdo->prepare("SELECT id FROM product_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    if (!$stmt->fetch()) {
        throw new Exception('카테고리를 찾을 수 없습니다.');
    }

    // 카테고리 업데이트
    $stmt = $pdo->prepare("
        UPDATE product_categories 
        SET category_name = ?, display_order = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$category_name, $display_order, $category_id]);

    echo json_encode([
        'success' => true,
        'message' => '카테고리가 수정되었습니다.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>