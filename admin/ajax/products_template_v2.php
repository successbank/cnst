<?php
/**
 * 제품 CSV 템플릿 다운로드 v2 엔드포인트
 */
session_start();
require_once '../../db.php';
require_once '../admin_check.php';
require_once '../../includes/ProductImportExportService.php';

$service = new ProductImportExportService($pdo);

$categoryCode = $_GET['category'] ?? '';
$includeSample = isset($_GET['sample']);

$service->downloadTemplate($categoryCode, $includeSample);
exit;
