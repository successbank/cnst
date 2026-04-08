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
$type = $_POST['type'] ?? '';

if (!$id || !in_array($type, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['success' => false, 'message' => '게시글을 찾을 수 없습니다.']);
        exit;
    }

    require_once '../includes/KakaoNotificationService.php';
    $kakaoService = new KakaoNotificationService($pdo);

    if ($type === 'approve') {
        $approved_price = (int)preg_replace('/[^0-9]/', '', $_POST['approved_price'] ?? '0');
        $approved_message = trim($_POST['approved_message'] ?? '');

        $templateData = [
            'title' => $post['title'],
            'contact_person' => $post['contact_person'] ?? $post['writer'],
            'category' => $post['category'] ?? '-',
            'company_name' => $post['company_name'] ?? '-',
            'approved_price' => number_format($approved_price),
            'custom_message' => $approved_message,
            'stock_quantity' => $post['stock_quantity'] ?? '-',
        ];
        $result = $kakaoService->previewMessage('CONSIGN_APPROVED', $templateData);
    } else {
        $reject_reason = trim($_POST['reject_reason'] ?? '');
        $reject_message = trim($_POST['reject_message'] ?? '');

        $templateData = [
            'title' => $post['title'],
            'contact_person' => $post['contact_person'] ?? $post['writer'],
            'reason' => $reject_reason ?: '(사유 미입력)',
            'custom_message' => $reject_message,
        ];
        $result = $kakaoService->previewMessage('CONSIGN_REJECTED', $templateData);
    }

    if ($result['success']) {
        // 수신자 전화번호 마스킹
        $phone = $post['contact_phone'] ?? '';
        $maskedPhone = '';
        if ($phone) {
            $digits = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($digits) >= 7) {
                $maskedPhone = substr($digits, 0, 3) . '-****-' . substr($digits, -4);
            } else {
                $maskedPhone = '***';
            }
        }

        echo json_encode([
            'success' => true,
            'preview' => $result['preview'],
            'recipient_phone' => $maskedPhone,
            'recipient_name' => $post['contact_person'] ?? $post['writer'],
            'has_phone' => !empty($post['contact_phone']),
        ]);
    } else {
        echo json_encode($result);
    }

} catch (Exception $e) {
    error_log("preview_consignment_message error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '미리보기 생성 중 오류가 발생했습니다.']);
}
?>
