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

// CSRF 토큰 검증
if (!verifyCsrfToken(false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF 토큰이 유효하지 않습니다.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    // 게시글 조회
    $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['success' => false, 'message' => '게시글을 찾을 수 없습니다.']);
        exit;
    }

    // approved/rejected 상태만 재발송 허용
    if (!in_array($post['status'], ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => '승인 또는 거절 상태의 의뢰만 재발송할 수 있습니다.']);
        exit;
    }

    if (empty($post['contact_phone'])) {
        echo json_encode(['success' => false, 'message' => '수신자 연락처가 없어 발송할 수 없습니다.']);
        exit;
    }

    require_once '../includes/KakaoNotificationService.php';
    $kakaoService = new KakaoNotificationService($pdo);

    $admin_username = $_SESSION['admin_id'] ?? null;

    // 재발송 횟수 제한 체크
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) FROM kakao_notifications
        WHERE board_type = 'consignment' AND board_id = ?
        AND parent_notification_id IS NOT NULL
    ");
    $stmtCount->execute([$id]);
    $resendCount = (int)$stmtCount->fetchColumn();
    $maxRetry = defined('KAKAO_RETRY_COUNT') ? KAKAO_RETRY_COUNT : 3;
    if ($resendCount >= $maxRetry) {
        echo json_encode(['success' => false, 'message' => "재발송 횟수 제한({$maxRetry}회)을 초과했습니다."]);
        exit;
    }

    // 마지막 알림 ID 조회 (parent_notification_id용)
    $stmtLast = $pdo->prepare("
        SELECT id FROM kakao_notifications
        WHERE board_type = 'consignment' AND board_id = ?
        ORDER BY id DESC LIMIT 1
    ");
    $stmtLast->execute([$id]);
    $lastNotificationId = $stmtLast->fetchColumn();

    // 기존 저장된 정보 기반으로 메시지 재생성
    if ($post['status'] === 'approved') {
        $templateData = [
            'title' => $post['title'],
            'contact_person' => $post['contact_person'] ?? $post['writer'],
            'category' => $post['category'] ?? '-',
            'company_name' => $post['company_name'] ?? '-',
            'approved_price' => number_format((int)$post['approved_price']),
            'custom_message' => $post['approved_message'] ?? '',
            'stock_quantity' => $post['stock_quantity'] ?? '-',
        ];
        $messageType = 'CONSIGN_APPROVED';
    } else {
        $templateData = [
            'title' => $post['title'],
            'contact_person' => $post['contact_person'] ?? $post['writer'],
            'reason' => $post['reject_reason'] ?? '-',
            'custom_message' => $post['approved_message'] ?? '',
        ];
        $messageType = 'CONSIGN_REJECTED';
    }

    $kakao_result = $kakaoService->sendNotification(
        'consignment', $id, $post['contact_phone'],
        $post['contact_person'] ?? $post['writer'],
        $messageType, $templateData,
        ['sent_by' => $admin_username, 'parent_notification_id' => $lastNotificationId]
    );

    // board_consignment 발송 상태 업데이트
    $sendStatus = ($kakao_result['success']) ? 'sent' : 'failed';
    $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = ?, kakao_sent_at = NOW() WHERE id = ?");
    $stmt->execute([$sendStatus, $id]);

    if ($kakao_result['success']) {
        echo json_encode(['success' => true, 'message' => '알림톡이 재발송되었습니다.', 'kakao_result' => $kakao_result]);
    } else {
        echo json_encode(['success' => false, 'message' => '발송 실패: ' . ($kakao_result['error'] ?? '알 수 없는 오류'), 'kakao_result' => $kakao_result]);
    }

} catch (Exception $e) {
    error_log("resend_consignment_kakao error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '재발송 중 오류가 발생했습니다.']);
}
?>
