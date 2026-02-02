<?php
/**
 * 로그 통계 조회 AJAX
 * GET: period (optional), history (optional)
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../admin_check.php';
require_once '../../db.php';
require_once '../../includes/LogService.php';
require_once '../../includes/LogCleanupService.php';

try {
    $logService = new LogService($pdo);
    $cleanupService = new LogCleanupService($pdo);

    $period = $_GET['period'] ?? 'daily';
    $includeHistory = isset($_GET['history']) && $_GET['history'] === '1';

    // 통계 조회
    $stats = $logService->getStats($period);

    // 정리 이력 포함 여부
    if ($includeHistory) {
        $stats['history'] = $cleanupService->getCleanupHistory(10);
    }

    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '통계 조회 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
