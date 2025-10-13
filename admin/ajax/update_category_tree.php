<?php
session_start();
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('잘못된 요청입니다.');
    }

    $category_id = $_POST['id'] ?? null;
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
    $category_name = trim($_POST['category_name'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 99);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $custom_url = trim($_POST['custom_url'] ?? '');
    $url_target = $_POST['url_target'] ?? '_self';

    if (!$category_id || empty($category_name)) {
        throw new Exception('필수 정보가 누락되었습니다.');
    }

    // 카테고리 존재 확인
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        throw new Exception('카테고리를 찾을 수 없습니다.');
    }

    // 부모가 변경된 경우 순환 참조 체크
    if ($parent_id && $parent_id != $category['parent_id']) {
        if (isDescendant($pdo, $category_id, $parent_id)) {
            throw new Exception('하위 카테고리를 부모로 설정할 수 없습니다.');
        }
    }

    // 레벨과 경로 재계산
    $level = 0;
    $path = $category['category_code'];
    
    if ($parent_id) {
        $stmt = $pdo->prepare("SELECT level, path FROM product_categories WHERE id = ?");
        $stmt->execute([$parent_id]);
        $parent = $stmt->fetch();
        
        if ($parent) {
            $level = $parent['level'] + 1;
            $path = $parent['path'] . '/' . $category['category_code'];
        }
    }

    // URL 유효성 검사 (선택사항)
    if (!empty($custom_url)) {
        // 외부 URL (http/https로 시작) 또는 내부 경로 (/, ? 로 시작) 허용
        $is_external = preg_match('/^https?:\/\//', $custom_url);
        $is_internal = preg_match('/^[\/\?]/', $custom_url);

        if (!$is_external && !$is_internal) {
            throw new Exception('유효한 URL을 입력해주세요. (예: ?category=code 또는 https://example.com)');
        }

        // 외부 URL인 경우 유효성 검사
        if ($is_external && !filter_var($custom_url, FILTER_VALIDATE_URL)) {
            throw new Exception('유효한 외부 URL을 입력해주세요.');
        }
    }

    // 카테고리 업데이트
    $stmt = $pdo->prepare("
        UPDATE product_categories
        SET category_name = ?, parent_id = ?, level = ?, path = ?,
            display_order = ?, is_active = ?, custom_url = ?, url_target = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $category_name,
        $parent_id,
        $level,
        $path,
        $display_order,
        $is_active,
        $custom_url,
        $url_target,
        $category_id
    ]);

    // 하위 카테고리들의 레벨과 경로 업데이트
    updateDescendants($pdo, $category_id, $level, $path);

    echo json_encode([
        'success' => true,
        'message' => '카테고리가 수정되었습니다.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'sql_error' => $e->getCode(),
        'debug' => [
            'custom_url' => $custom_url ?? null,
            'url_target' => $url_target ?? null
        ]
    ]);
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