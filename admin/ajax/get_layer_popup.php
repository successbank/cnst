<?php
/**
 * 레이어팝업 정보 조회 API
 */
header('Content-Type: application/json; charset=utf-8');

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 로그인 체크 (JSON 응답)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once '../../db.php';

try {
    $id = intval($_GET['id'] ?? 0);

    if (!$id) {
        throw new Exception('팝업 ID가 필요합니다.');
    }

    $stmt = $pdo->prepare("SELECT * FROM layer_popups WHERE id = ?");
    $stmt->execute([$id]);
    $popup = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$popup) {
        throw new Exception('팝업을 찾을 수 없습니다.');
    }

    // null 값 처리
    $popup['image_path'] = $popup['image_path'] ?? '';
    $popup['content'] = $popup['content'] ?? '';
    $popup['link_url'] = $popup['link_url'] ?? '';
    $popup['link_target'] = $popup['link_target'] ?? '_self';

    echo json_encode([
        'success' => true,
        'popup' => $popup
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
