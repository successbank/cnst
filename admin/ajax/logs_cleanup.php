<?php
/**
 * 로그 정리 AJAX
 * GET: preview=1, days (미리보기)
 * POST: targets[], days_before (실제 정리)
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../admin_check.php';
require_once '../../db.php';
require_once '../../includes/LogCleanupService.php';

try {
    $cleanupService = new LogCleanupService($pdo);

    // 미리보기 요청
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['preview'])) {
        $days = intval($_GET['days'] ?? 90);
        $preview = $cleanupService->getCleanupPreview($days);

        echo json_encode([
            'success' => true,
            'data' => $preview
        ]);
        exit;
    }

    // 정리 실행 (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $targets = $_POST['targets'] ?? [];
        $daysBefore = intval($_POST['days_before'] ?? 90);

        if (empty($targets)) {
            echo json_encode([
                'success' => false,
                'message' => '정리 대상을 선택하세요.'
            ]);
            exit;
        }

        if ($daysBefore < 1) {
            echo json_encode([
                'success' => false,
                'message' => '정리 기준 일수는 1일 이상이어야 합니다.'
            ]);
            exit;
        }

        // 현재 관리자 정보
        $executedBy = $_SESSION['admin_name'] ?? $_SESSION['admin_id'] ?? 'admin';

        $result = $cleanupService->manualCleanup($targets, $daysBefore, $executedBy);

        echo json_encode($result);
        exit;
    }

    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청입니다.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '로그 정리 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
