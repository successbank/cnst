<?php
/**
 * 임포트 이력 조회 AJAX 엔드포인트
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../db.php';
require_once '../admin_check.php';
require_once '../../includes/ProductImportExportService.php';

function jsonResponse($success, $data = []) {
    echo json_encode(array_merge(['success' => $success], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

$service = new ProductImportExportService($pdo);

$limit = min((int)($_GET['limit'] ?? 20), 100);
$offset = max((int)($_GET['offset'] ?? 0), 0);

try {
    $history = $service->getImportHistory($limit, $offset);
    $total = $service->getImportHistoryCount();

    jsonResponse(true, [
        'history' => $history,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
    ]);
} catch (Exception $e) {
    jsonResponse(false, ['error' => $e->getMessage()]);
}
