<?php
session_start();
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('잘못된 요청입니다.');
    }

    $updates = json_decode($_POST['updates'] ?? '[]', true);

    if (empty($updates)) {
        throw new Exception('업데이트할 데이터가 없습니다.');
    }

    // 트랜잭션 시작
    $pdo->beginTransaction();

    try {
        // 각 카테고리 업데이트
        $stmt = $pdo->prepare("
            UPDATE product_categories
            SET parent_id = ?, display_order = ?, level = ?
            WHERE id = ?
        ");

        foreach ($updates as $update) {
            // 레벨 계산
            $level = 0;
            if ($update['parent_id']) {
                $parentStmt = $pdo->prepare("SELECT level FROM product_categories WHERE id = ?");
                $parentStmt->execute([$update['parent_id']]);
                $parentLevel = $parentStmt->fetchColumn();
                $level = $parentLevel + 1;
            }

            $stmt->execute([
                $update['parent_id'],
                $update['display_order'],
                $level,
                $update['id']
            ]);
        }

        // path 업데이트
        updateAllPaths($pdo);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '카테고리 구조가 업데이트되었습니다.'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("update_category_structure error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => '처리 중 오류가 발생했습니다.'
    ]);
}

// 모든 카테고리의 path 업데이트
function updateAllPaths($pdo) {
    // 먼저 최상위 카테고리들의 path 설정
    $stmt = $pdo->query("
        SELECT id, category_code
        FROM product_categories
        WHERE parent_id IS NULL OR parent_id = 0
    ");
    $topCategories = $stmt->fetchAll();

    $updatePathStmt = $pdo->prepare("UPDATE product_categories SET path = ? WHERE id = ?");
    foreach ($topCategories as $category) {
        $updatePathStmt->execute([$category['category_code'], $category['id']]);

        // 하위 카테고리들 재귀적으로 업데이트
        updateChildPaths($pdo, $category['id'], $category['category_code']);
    }
}

// 하위 카테고리들의 path 업데이트
function updateChildPaths($pdo, $parentId, $parentPath) {
    $stmt = $pdo->prepare("
        SELECT id, category_code
        FROM product_categories
        WHERE parent_id = ?
    ");
    $stmt->execute([$parentId]);
    $children = $stmt->fetchAll();

    $updatePathStmt = $pdo->prepare("UPDATE product_categories SET path = ? WHERE id = ?");
    foreach ($children as $child) {
        $childPath = $parentPath . '/' . $child['category_code'];

        $updatePathStmt->execute([$childPath, $child['id']]);

        // 재귀적으로 하위 카테고리 업데이트
        updateChildPaths($pdo, $child['id'], $childPath);
    }
}
?>