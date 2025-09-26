<?php
session_start();
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

try {
    // 컬럼 존재 확인
    $columnCheck = $pdo->query("SHOW COLUMNS FROM product_categories LIKE 'parent_id'");
    if ($columnCheck->rowCount() == 0) {
        throw new Exception('트리 구조 컬럼이 없습니다. 먼저 마이그레이션을 실행해주세요.');
    }

    // 모든 카테고리 조회 (계층 정보 포함)
    $stmt = $pdo->query("
        SELECT
            pc.*,
            COUNT(DISTINCT p.id) as product_count,
            IFNULL(pc.parent_id, 0) as parent_id,
            IFNULL(pc.level, 0) as level
        FROM product_categories pc
        LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
        GROUP BY pc.id
        ORDER BY IFNULL(pc.parent_id, 0), pc.display_order, pc.id
    ");

    $categories = $stmt->fetchAll();

    // 트리 구조로 변환
    $tree = buildTree($categories);

    echo json_encode([
        'success' => true,
        'categories' => $tree
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_detail' => $e->getTraceAsString()
    ]);
}

// 트리 구조 생성 함수
function buildTree($categories, $parentId = 0) {
    $tree = [];

    foreach ($categories as $category) {
        // parent_id가 null인 경우 0으로 처리
        $catParentId = $category['parent_id'] === null ? 0 : intval($category['parent_id']);

        if ($catParentId === $parentId) {
            $node = [
                'id' => $category['id'],
                'category_code' => $category['category_code'],
                'category_name' => $category['category_name'],
                'parent_id' => $category['parent_id'],
                'level' => isset($category['level']) ? $category['level'] : 0,
                'display_order' => $category['display_order'],
                'is_active' => $category['is_active'],
                'product_count' => intval($category['product_count']),
                'expanded' => true, // 기본적으로 펼쳐진 상태
                'children' => buildTree($categories, intval($category['id']))
            ];

            $tree[] = $node;
        }
    }

    return $tree;
}
?>