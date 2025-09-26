<?php
session_start();
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('잘못된 요청입니다.');
    }

    $source_categories = json_decode($_POST['source_categories'] ?? '[]', true);
    $target_category = $_POST['target_category'] ?? '';

    if (empty($source_categories) || empty($target_category)) {
        throw new Exception('소스 카테고리와 대상 카테고리를 선택해주세요.');
    }

    // 소스 카테고리 정보 가져오기
    $placeholders = str_repeat('?,', count($source_categories) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT category_name 
        FROM product_categories 
        WHERE category_code IN ($placeholders)
    ");
    $stmt->execute($source_categories);
    $source_names = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 대상 카테고리 정보 가져오기
    $stmt = $pdo->prepare("SELECT category_name FROM product_categories WHERE category_code = ?");
    $stmt->execute([$target_category]);
    $target_name = $stmt->fetchColumn();

    // 이동될 제품 수 계산
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM products 
        WHERE category_code IN ($placeholders)
    ");
    $stmt->execute($source_categories);
    $product_count = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'source_names' => $source_names,
        'target_name' => $target_name,
        'product_count' => $product_count
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>