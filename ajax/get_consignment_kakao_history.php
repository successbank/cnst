<?php
session_start();
require_once '../db.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json; charset=utf-8');

// 관리자 확인
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

// CSRF 토큰 검증
if (!verifyCsrfToken(false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF 토큰이 유효하지 않습니다.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    require_once '../includes/KakaoNotificationService.php';
    $kakaoService = new KakaoNotificationService($pdo);

    $history = $kakaoService->getNotificationsByBoardId('consignment', $id);

    echo json_encode([
        'success' => true,
        'history' => $history
    ]);

} catch (Exception $e) {
    error_log("get_consignment_kakao_history error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '이력 조회 중 오류가 발생했습니다.']);
}
?>
