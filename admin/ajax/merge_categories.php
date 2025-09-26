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
    $deactivate_source = intval($_POST['deactivate_source'] ?? 0);

    if (empty($source_categories) || empty($target_category)) {
        throw new Exception('소스 카테고리와 대상 카테고리를 선택해주세요.');
    }

    if (in_array($target_category, $source_categories)) {
        throw new Exception('소스 카테고리와 대상 카테고리가 같을 수 없습니다.');
    }

    // 트랜잭션 시작
    $pdo->beginTransaction();

    try {
        // 1. 제품 카테고리 이동
        $placeholders = str_repeat('?,', count($source_categories) - 1) . '?';
        $stmt = $pdo->prepare("
            UPDATE products 
            SET category_code = ? 
            WHERE category_code IN ($placeholders)
        ");
        $params = array_merge([$target_category], $source_categories);
        $stmt->execute($params);
        $moved_count = $stmt->rowCount();

        // 2. 소스 카테고리 비활성화 (옵션)
        if ($deactivate_source) {
            $stmt = $pdo->prepare("
                UPDATE product_categories 
                SET is_active = 0 
                WHERE category_code IN ($placeholders)
            ");
            $stmt->execute($source_categories);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '카테고리 병합이 완료되었습니다.',
            'moved_count' => $moved_count
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>