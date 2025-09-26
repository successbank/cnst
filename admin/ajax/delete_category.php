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

    if (!$category_id) {
        throw new Exception('카테고리 ID가 필요합니다.');
    }

    // 카테고리에 제품이 있는지 확인
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM products p
        JOIN product_categories pc ON p.category_code = pc.category_code
        WHERE pc.id = ?
    ");
    $stmt->execute([$category_id]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('이 카테고리에 제품이 있어 삭제할 수 없습니다.');
    }

    // 카테고리 삭제
    $stmt = $pdo->prepare("DELETE FROM product_categories WHERE id = ?");
    $stmt->execute([$category_id]);

    echo json_encode([
        'success' => true,
        'message' => '카테고리가 삭제되었습니다.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>