<?php
session_start();
require_once '../db.php';
require_once '../includes/csrf.php';

// JSON 응답 설정
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

// POST만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);
$reject_reason = trim($_POST['reject_reason'] ?? '');
$admin_note = trim($_POST['admin_note'] ?? '');

if (!$id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

// 거절 시 사유 필수
if ($action === 'reject' && empty($reject_reason)) {
    echo json_encode(['success' => false, 'message' => '거절 사유를 입력해주세요.']);
    exit;
}

try {
    // 대상 게시글 확인
    $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();

    if (!$post) {
        echo json_encode(['success' => false, 'message' => '게시글을 찾을 수 없습니다.']);
        exit;
    }

    // $_SESSION['admin_id']에는 username이 저장됨 → 숫자 ID 조회
    $admin_username = $_SESSION['admin_id'] ?? null;
    $admin_id = null;
    if ($admin_username) {
        $stmtAdmin = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmtAdmin->execute([$admin_username]);
        $admin_id = $stmtAdmin->fetchColumn() ?: null;
    }

    if ($action === 'approve') {
        // 승인 금액 수신 및 검증
        $approved_price = (int)preg_replace('/[^0-9]/', '', $_POST['approved_price'] ?? '0');
        $approved_message = trim($_POST['approved_message'] ?? '');

        if ($approved_price <= 0) {
            echo json_encode(['success' => false, 'message' => '중개 금액을 입력해주세요. (0원 초과)']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE board_consignment
            SET status = 'approved',
                reviewed_by = ?,
                reviewed_at = NOW(),
                reject_reason = NULL,
                admin_note = ?,
                approved_price = ?,
                approved_message = ?,
                kakao_send_status = 'pending'
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $admin_note ?: null, $approved_price, $approved_message ?: null, $id]);

        // 카카오 알림 발송 (승인)
        $kakao_result = null;
        try {
            require_once '../includes/KakaoNotificationService.php';
            $kakaoService = new KakaoNotificationService($pdo);
            if (!empty($post['contact_phone'])) {
                $templateData = [
                    'title' => $post['title'],
                    'contact_person' => $post['contact_person'] ?? $post['writer'],
                    'category' => $post['category'] ?? '-',
                    'company_name' => $post['company_name'] ?? '-',
                    'approved_price' => number_format($approved_price),
                    'custom_message' => $approved_message,
                    'stock_quantity' => $post['stock_quantity'] ?? '-',
                ];
                $kakao_result = $kakaoService->sendNotification(
                    'consignment', $id, $post['contact_phone'],
                    $post['contact_person'] ?? $post['writer'],
                    'CONSIGN_APPROVED', $templateData,
                    ['sent_by' => $admin_username]
                );

                // 발송 결과에 따라 상태 업데이트
                $sendStatus = ($kakao_result['success']) ? 'sent' : 'failed';
                $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = ?, kakao_sent_at = NOW() WHERE id = ?");
                $stmt->execute([$sendStatus, $id]);
            } else {
                // 연락처 없음 - 카카오 발송 건너뜀
                $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = NULL WHERE id = ?");
                $stmt->execute([$id]);
            }
        } catch (Exception $e) {
            error_log("카카오 승인 알림 발송 실패: " . $e->getMessage());
            $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = 'failed' WHERE id = ?");
            $stmt->execute([$id]);
        }

        $response = [
            'success' => true,
            'message' => '판매의뢰가 승인되었습니다. 중개판매 게시판에 게시됩니다.'
        ];
        if ($kakao_result) {
            $response['kakao_result'] = $kakao_result;
        }
        echo json_encode($response);

    } elseif ($action === 'reject') {
        $reject_message = trim($_POST['reject_message'] ?? '');

        $stmt = $pdo->prepare("
            UPDATE board_consignment
            SET status = 'rejected',
                reviewed_by = ?,
                reviewed_at = NOW(),
                reject_reason = ?,
                admin_note = ?,
                approved_message = ?,
                kakao_send_status = 'pending'
            WHERE id = ?
        ");
        $stmt->execute([$admin_id, $reject_reason, $admin_note ?: null, $reject_message ?: null, $id]);

        // 카카오 알림 발송 (거절)
        $kakao_result = null;
        try {
            require_once '../includes/KakaoNotificationService.php';
            $kakaoService = new KakaoNotificationService($pdo);
            if (!empty($post['contact_phone'])) {
                $templateData = [
                    'title' => $post['title'],
                    'contact_person' => $post['contact_person'] ?? $post['writer'],
                    'reason' => $reject_reason,
                    'custom_message' => $reject_message,
                ];
                $kakao_result = $kakaoService->sendNotification(
                    'consignment', $id, $post['contact_phone'],
                    $post['contact_person'] ?? $post['writer'],
                    'CONSIGN_REJECTED', $templateData,
                    ['sent_by' => $admin_username]
                );

                $sendStatus = ($kakao_result['success']) ? 'sent' : 'failed';
                $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = ?, kakao_sent_at = NOW() WHERE id = ?");
                $stmt->execute([$sendStatus, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = NULL WHERE id = ?");
                $stmt->execute([$id]);
            }
        } catch (Exception $e) {
            error_log("카카오 거절 알림 발송 실패: " . $e->getMessage());
            $stmt = $pdo->prepare("UPDATE board_consignment SET kakao_send_status = 'failed' WHERE id = ?");
            $stmt->execute([$id]);
        }

        $response = [
            'success' => true,
            'message' => '판매의뢰가 반려되었습니다.'
        ];
        if ($kakao_result) {
            $response['kakao_result'] = $kakao_result;
        }
        echo json_encode($response);
    }

} catch (PDOException $e) {
    error_log("admin_review_consignment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '처리 중 오류가 발생했습니다.']);
}
?>
