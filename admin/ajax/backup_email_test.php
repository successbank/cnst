<?php
/**
 * 백업 알림 테스트 이메일 발송 AJAX
 */
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../../db.php';
require_once '../../includes/BackupNotificationService.php';

try {
    $recipients = trim($_POST['recipients'] ?? '');
    $attachBackup = isset($_POST['attach_backup']) && $_POST['attach_backup'] === '1';

    if (empty($recipients)) {
        throw new Exception('수신자 이메일을 입력하세요.');
    }

    // 이메일 유효성 검증
    $validation = EmailService::validateEmails($recipients);
    if (empty($validation['valid'])) {
        throw new Exception('유효한 이메일 주소가 없습니다.');
    }

    if (!empty($validation['invalid'])) {
        throw new Exception('잘못된 이메일 주소: ' . implode(', ', $validation['invalid']));
    }

    // 테스트 이메일 발송
    $notificationService = new BackupNotificationService($pdo);
    $result = $notificationService->sendTestEmail($recipients, $attachBackup);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        throw new Exception($result['message']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
