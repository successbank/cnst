<?php
session_start();
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('잘못된 요청입니다.');
    }

    $category_code = trim($_POST['category_code'] ?? '');
    $category_name = trim($_POST['category_name'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 99);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // 유효성 검사
    if (empty($category_code) || empty($category_name)) {
        throw new Exception('카테고리 코드와 이름은 필수입니다.');
    }

    // 카테고리 코드 형식 체크 (영문 소문자, 하이픈만 허용)
    if (!preg_match('/^[a-z0-9-]+$/', $category_code)) {
        throw new Exception('카테고리 코드는 영문 소문자와 숫자, 하이픈(-)만 사용 가능합니다.');
    }

    // 중복 체크
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE category_code = ?");
    $stmt->execute([$category_code]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('이미 존재하는 카테고리 코드입니다.');
    }

    // 카테고리 추가
    $stmt = $pdo->prepare("
        INSERT INTO product_categories (category_code, category_name, display_order, is_active)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([$category_code, $category_name, $display_order, $is_active]);

    echo json_encode([
        'success' => true,
        'message' => '카테고리가 추가되었습니다.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>