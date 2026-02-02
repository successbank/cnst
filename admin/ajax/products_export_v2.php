<?php
/**
 * 제품 CSV 엑스포트 v2 엔드포인트
 */
session_start();
require_once '../../db.php';
require_once '../admin_check.php';
require_once '../../includes/ProductImportExportService.php';

$service = new ProductImportExportService($pdo);

$categoryCode = $_GET['category'] ?? '';
$selectedIdsParam = $_GET['ids'] ?? '';
$selectedIds = [];

if ($selectedIdsParam) {
    $selectedIds = array_filter(
        array_map('intval', explode(',', $selectedIdsParam)),
        fn($v) => $v > 0
    );
}

$service->exportProducts($categoryCode, $selectedIds);
exit;
