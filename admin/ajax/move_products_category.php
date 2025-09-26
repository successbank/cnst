<?php
session_start();
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('잘못된 요청입니다.');
    }

    $product_ids = json_decode($_POST['product_ids'] ?? '[]', true);
    $target_category = $_POST['target_category'] ?? '';

    if (empty($product_ids) || empty($target_category)) {
        throw new Exception('제품과 대상 카테고리를 선택해주세요.');
    }

    // 대상 카테고리 존재 확인
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE category_code = ?");
    $stmt->execute([$target_category]);
    if ($stmt->fetchColumn() == 0) {
        throw new Exception('대상 카테고리를 찾을 수 없습니다.');
    }

    // 제품 카테고리 업데이트
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $stmt = $pdo->prepare("
        UPDATE products 
        SET category_code = ? 
        WHERE id IN ($placeholders)
    ");
    $params = array_merge([$target_category], $product_ids);
    $stmt->execute($params);
    $moved_count = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => '제품 이동이 완료되었습니다.',
        'moved_count' => $moved_count
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>