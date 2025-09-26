<?php
session_start();
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('잘못된 요청입니다.');
    }

    $category_id = intval($_POST['category_id'] ?? 0);
    $new_parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    if (!$category_id) {
        throw new Exception('카테고리 ID가 필요합니다.');
    }

    // 자기 자신을 부모로 설정하는지 확인
    if ($category_id == $new_parent_id) {
        throw new Exception('카테고리를 자기 자신의 하위로 이동할 수 없습니다.');
    }

    // 현재 카테고리 정보 가져오기
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();

    if (!$category) {
        throw new Exception('카테고리를 찾을 수 없습니다.');
    }

    // 순환 참조 체크 (하위 카테고리를 부모로 설정하는지)
    if ($new_parent_id && isDescendant($pdo, $category_id, $new_parent_id)) {
        throw new Exception('하위 카테고리를 부모로 설정할 수 없습니다.');
    }

    // 새 부모의 레벨과 경로 계산
    $new_level = 0;
    $new_path = $category['category_code'];

    if ($new_parent_id) {
        $stmt = $pdo->prepare("SELECT level, path FROM product_categories WHERE id = ?");
        $stmt->execute([$new_parent_id]);
        $parent = $stmt->fetch();

        if (!$parent) {
            throw new Exception('부모 카테고리를 찾을 수 없습니다.');
        }

        $new_level = $parent['level'] + 1;
        $new_path = $parent['path'] . '/' . $category['category_code'];
    }

    // 트랜잭션 시작
    $pdo->beginTransaction();

    try {
        // 카테고리 업데이트
        $stmt = $pdo->prepare("
            UPDATE product_categories 
            SET parent_id = ?, level = ?, path = ?
            WHERE id = ?
        ");
        $stmt->execute([$new_parent_id, $new_level, $new_path, $category_id]);

        // 하위 카테고리들의 레벨과 경로 업데이트
        updateDescendants($pdo, $category_id, $new_level, $new_path);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '카테고리가 이동되었습니다.'
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

// 하위 카테고리인지 확인
function isDescendant($pdo, $parentId, $childId) {
    $stmt = $pdo->prepare("SELECT parent_id FROM product_categories WHERE id = ?");
    $stmt->execute([$childId]);
    $result = $stmt->fetch();
    
    if (!$result || !$result['parent_id']) {
        return false;
    }
    
    if ($result['parent_id'] == $parentId) {
        return true;
    }
    
    return isDescendant($pdo, $parentId, $result['parent_id']);
}

// 하위 카테고리 레벨 및 경로 업데이트
function updateDescendants($pdo, $categoryId, $parentLevel, $parentPath) {
    $stmt = $pdo->prepare("
        SELECT id, category_code 
        FROM product_categories 
        WHERE parent_id = ?
    ");
    $stmt->execute([$categoryId]);
    $children = $stmt->fetchAll();
    
    foreach ($children as $child) {
        $childLevel = $parentLevel + 1;
        $childPath = $parentPath . '/' . $child['category_code'];
        
        $updateStmt = $pdo->prepare("
            UPDATE product_categories 
            SET level = ?, path = ? 
            WHERE id = ?
        ");
        $updateStmt->execute([$childLevel, $childPath, $child['id']]);
        
        // 재귀적으로 하위 카테고리 업데이트
        updateDescendants($pdo, $child['id'], $childLevel, $childPath);
    }
}
?>