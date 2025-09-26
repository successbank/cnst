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
    $is_active = intval($_POST['is_active'] ?? 0);

    if (!$category_id) {
        throw new Exception('카테고리 ID가 필요합니다.');
    }

    // 카테고리 존재 확인
    $stmt = $pdo->prepare("SELECT id FROM product_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    if (!$stmt->fetch()) {
        throw new Exception('카테고리를 찾을 수 없습니다.');
    }

    // 상태 업데이트
    $stmt = $pdo->prepare("UPDATE product_categories SET is_active = ? WHERE id = ?");
    $stmt->execute([$is_active, $category_id]);

    echo json_encode([
        'success' => true,
        'message' => $is_active ? '활성화되었습니다.' : '비활성화되었습니다.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>