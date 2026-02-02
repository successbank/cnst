<?php
/**
 * 로그 설정 저장 AJAX
 * POST: log_retention_days, log_auto_cleanup_enabled, log_cleanup_schedule_time, log_cleanup_targets
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../admin_check.php';
require_once '../../db.php';
require_once '../../includes/LogCleanupService.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
        exit;
    }

    $cleanupService = new LogCleanupService($pdo);

    // 설정 값 수집
    $settings = [];

    if (isset($_POST['log_retention_days'])) {
        $days = intval($_POST['log_retention_days']);
        if ($days < 7 || $days > 365) {
            echo json_encode(['success' => false, 'message' => '보관 기간은 7일 ~ 365일 사이여야 합니다.']);
            exit;
        }
        $settings['log_retention_days'] = (string) $days;
    }

    if (isset($_POST['log_auto_cleanup_enabled'])) {
        $settings['log_auto_cleanup_enabled'] = $_POST['log_auto_cleanup_enabled'] === '1' ? '1' : '0';
    }

    if (isset($_POST['log_cleanup_schedule_time'])) {
        $time = $_POST['log_cleanup_schedule_time'];
        if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            $settings['log_cleanup_schedule_time'] = $time;
        }
    }

    if (isset($_POST['log_cleanup_targets'])) {
        $targets = $_POST['log_cleanup_targets'];
        // JSON 문자열인지 확인
        if (is_string($targets)) {
            $decoded = json_decode($targets, true);
            if (is_array($decoded)) {
                $settings['log_cleanup_targets'] = $targets;
            }
        } elseif (is_array($targets)) {
            $settings['log_cleanup_targets'] = json_encode($targets);
        }
    }

    if (empty($settings)) {
        echo json_encode(['success' => false, 'message' => '저장할 설정이 없습니다.']);
        exit;
    }

    $result = $cleanupService->saveSettings($settings);

    if ($result) {
        echo json_encode(['success' => true, 'message' => '설정이 저장되었습니다.']);
    } else {
        echo json_encode(['success' => false, 'message' => '설정 저장에 실패했습니다.']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '설정 저장 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
