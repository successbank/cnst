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
    $custom_url = trim($_POST['custom_url'] ?? '');
    $url_target = $_POST['url_target'] ?? '_self';

    if (!$category_id || empty($category_name)) {
        throw new Exception('필수 정보가 누락되었습니다.');
    }

    // URL 유효성 검사 (선택사항)
    if (!empty($custom_url)) {
        // 상대 경로 또는 절대 URL 허용
        if (!filter_var($custom_url, FILTER_VALIDATE_URL) && !preg_match('/^\//', $custom_url)) {
            throw new Exception('유효한 URL을 입력해주세요.');
        }
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
        SET category_name = ?, display_order = ?, custom_url = ?, url_target = ?
        WHERE id = ?
    ");

    $stmt->execute([$category_name, $display_order, $custom_url, $url_target, $category_id]);

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