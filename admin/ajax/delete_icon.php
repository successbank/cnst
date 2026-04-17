<?php
session_start();
require_once '../../db.php';
require_once '../../includes/csrf.php';

// 관리자 권한 확인
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '인증되지 않은 요청입니다.']);
    exit;
}

// [보안] CSRF 토큰 검증 (2026-04-17)
if (!verifyCsrfToken(false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF 토큰이 유효하지 않습니다.']);
    exit;
}

// JSON 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 아이콘 정보 가져오기 (이미지 삭제를 위해)
    $stmt = $pdo->prepare("SELECT icon_image FROM product_icons WHERE id = ?");
    $stmt->execute([$id]);
    $icon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$icon) {
        throw new Exception('존재하지 않는 아이콘입니다.');
    }
    
    // 아이콘 삭제
    $stmt = $pdo->prepare("DELETE FROM product_icons WHERE id = ?");
    $stmt->execute([$id]);
    
    // 이미지 파일 삭제 [보안 2026-04-17] 경로 탐색 방지 - uploads/ 하위로 제한
    if ($icon['icon_image']) {
        $uploadsBase = realpath(__DIR__ . '/../../uploads');
        $targetPath = realpath(__DIR__ . '/../../' . $icon['icon_image']);
        if ($uploadsBase !== false
            && $targetPath !== false
            && strpos($targetPath, $uploadsBase . DIRECTORY_SEPARATOR) === 0
            && is_file($targetPath)) {
            unlink($targetPath);
        } else {
            error_log("delete_icon.php: blocked unsafe unlink attempt for icon id={$id}, path=" . $icon['icon_image']);
        }
    }

    // 트랜잭션 커밋
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => '아이콘이 삭제되었습니다.']);

} catch (Exception $e) {
    // 트랜잭션 롤백
    $pdo->rollBack();
    // [보안 2026-04-17] 내부 예외 메시지 노출 방지
    error_log('delete_icon.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '아이콘 삭제 중 오류가 발생했습니다.']);
}