<?php
session_start();
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

try {
    $category_id = $_GET['id'] ?? null;

    if (!$category_id) {
        throw new Exception('카테고리 ID가 필요합니다.');
    }

    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();

    if (!$category) {
        throw new Exception('카테고리를 찾을 수 없습니다.');
    }

    echo json_encode([
        'success' => true,
        'category' => $category
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>