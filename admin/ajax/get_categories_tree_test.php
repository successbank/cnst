<?php
// 테스트용 - 세션 체크 없이
require_once '../../db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // 컬럼 존재 확인
    $columnCheck = $pdo->query("SHOW COLUMNS FROM product_categories LIKE 'parent_id'");
    if ($columnCheck->rowCount() == 0) {
        // parent_id 컬럼이 없으면 추가 시도
        try {
            $pdo->exec("ALTER TABLE product_categories ADD COLUMN parent_id INT DEFAULT NULL");
            $pdo->exec("ALTER TABLE product_categories ADD COLUMN level INT DEFAULT 0");
            $pdo->exec("ALTER TABLE product_categories ADD COLUMN path VARCHAR(255) DEFAULT NULL");
        } catch (Exception $e) {
            // 이미 있거나 권한 없음
        }
    }

    // 모든 카테고리 조회
    $stmt = $pdo->query("
        SELECT
            pc.*,
            COUNT(DISTINCT p.id) as product_count
        FROM product_categories pc
        LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
        GROUP BY pc.id
        ORDER BY IFNULL(pc.parent_id, 0), pc.display_order, pc.id
    ");

    $categories = $stmt->fetchAll();

    // 디버그 정보
    $debug = [
        'total_categories' => count($categories),
        'has_parent_column' => true,
        'sample_data' => array_slice($categories, 0, 3)
    ];

    // 트리 구조로 변환
    $tree = buildTree($categories);

    echo json_encode([
        'success' => true,
        'categories' => $tree,
        'debug' => $debug
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// 트리 구조 생성 함수
function buildTree($categories, $parentId = 0) {
    $tree = [];

    foreach ($categories as $category) {
        // parent_id가 null인 경우 0으로 처리
        $catParentId = ($category['parent_id'] === null || $category['parent_id'] === '') ? 0 : intval($category['parent_id']);

        if ($catParentId === $parentId) {
            $children = buildTree($categories, intval($category['id']));

            $node = [
                'id' => intval($category['id']),
                'category_code' => $category['category_code'],
                'category_name' => $category['category_name'],
                'parent_id' => $category['parent_id'],
                'level' => isset($category['level']) ? intval($category['level']) : 0,
                'display_order' => intval($category['display_order']),
                'is_active' => intval($category['is_active']),
                'product_count' => intval($category['product_count']),
                'expanded' => true,
                'children' => $children
            ];

            $tree[] = $node;
        }
    }

    return $tree;
}
?>