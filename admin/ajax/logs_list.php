<?php
/**
 * 로그 목록 조회 AJAX
 * GET: log_type, date_from, date_to, search, page, per_page
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../admin_check.php';
require_once '../../db.php';
require_once '../../includes/LogService.php';

try {
    $logService = new LogService($pdo);

    $logType = $_GET['log_type'] ?? 'login';
    $filters = [
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null,
        'search' => $_GET['search'] ?? '',
        'page' => intval($_GET['page'] ?? 1),
        'per_page' => intval($_GET['per_page'] ?? 20)
    ];

    // 유효한 로그 유형인지 확인
    $validTypes = array_keys($logService->getLogTypes());
    if (!in_array($logType, $validTypes)) {
        echo json_encode(['success' => false, 'message' => '유효하지 않은 로그 유형입니다.']);
        exit;
    }

    $result = $logService->getLogs($logType, $filters);

    echo json_encode([
        'success' => true,
        'data' => $result
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '로그 조회 중 오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
