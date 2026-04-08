<?php
// AJAX 응답을 위해 에러 출력 억제 (화면에는 표시 안 함, 로그에는 기록)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/**
 * 백업 설정 저장 AJAX
 */
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../../db.php';
require_once '../../includes/settings.php';
require_once '../../includes/EmailService.php';

try {
    // 이메일 설정 저장 요청인 경우
    if (isset($_POST['save_email_settings'])) {
        $emailAutoEnabled = $_POST['backup_email_auto_enabled'] ?? '0';
        $emailManualEnabled = $_POST['backup_email_manual_enabled'] ?? '0';
        $emailRecipients = trim($_POST['backup_email_recipients'] ?? '');
        $emailOnSuccess = $_POST['backup_email_on_success'] ?? '0';
        $emailOnFailure = $_POST['backup_email_on_failure'] ?? '0';
        $emailAttachFile = $_POST['backup_email_attach_file'] ?? '1';

        // 이메일 주소 유효성 검증
        if (!empty($emailRecipients)) {
            $validation = EmailService::validateEmails($emailRecipients);
            if (!empty($validation['invalid'])) {
                throw new Exception('잘못된 이메일 주소: ' . implode(', ', $validation['invalid']));
            }
            // 유효한 이메일만 저장
            $emailRecipients = implode(', ', $validation['valid']);
        }

        // 이메일 설정 저장
        $emailSettings = [
            'backup_email_auto_enabled' => $emailAutoEnabled === '1' ? '1' : '0',
            'backup_email_manual_enabled' => $emailManualEnabled === '1' ? '1' : '0',
            'backup_email_recipients' => $emailRecipients,
            'backup_email_on_success' => $emailOnSuccess === '1' ? '1' : '0',
            'backup_email_on_failure' => $emailOnFailure === '1' ? '1' : '0',
            'backup_email_attach_file' => $emailAttachFile === '1' ? '1' : '0'
        ];

        foreach ($emailSettings as $key => $value) {
            saveSetting($key, $value);
        }

        echo json_encode([
            'success' => true,
            'message' => '이메일 설정이 저장되었습니다.'
        ]);
        exit;
    }

    // 기존 백업 설정 저장
    $backupAutoEnabled = $_POST['backup_auto_enabled'] ?? '0';
    $backupScheduleTime = $_POST['backup_schedule_time'] ?? '01:00';
    $backupRetentionDays = $_POST['backup_retention_days'] ?? '30';
    $backupMaxFiles = $_POST['backup_max_files'] ?? '30';

    // 유효성 검증
    if (!preg_match('/^[0-2][0-9]:[0-5][0-9]$/', $backupScheduleTime)) {
        throw new Exception('잘못된 시간 형식입니다.');
    }

    $retentionDays = intval($backupRetentionDays);
    if ($retentionDays < 1 || $retentionDays > 365) {
        throw new Exception('보관 일수는 1~365일 사이어야 합니다.');
    }

    $maxFiles = intval($backupMaxFiles);
    if ($maxFiles < 1 || $maxFiles > 100) {
        throw new Exception('최대 파일 수는 1~100개 사이어야 합니다.');
    }

    // 설정 저장
    $settings = [
        'backup_auto_enabled' => $backupAutoEnabled === '1' ? '1' : '0',
        'backup_schedule_time' => $backupScheduleTime,
        'backup_retention_days' => (string)$retentionDays,
        'backup_max_files' => (string)$maxFiles
    ];

    foreach ($settings as $key => $value) {
        saveSetting($key, $value);
    }

    echo json_encode([
        'success' => true,
        'message' => '설정이 저장되었습니다.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
