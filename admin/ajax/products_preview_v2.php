<?php
/**
 * 제품 CSV 미리보기/검증 v2 엔드포인트
 * 업로드된 CSV를 분석하여 INSERT/UPDATE/DELETE 건수, diff, 오류를 반환
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, ['error' => '잘못된 요청입니다.']);
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    jsonResponse(false, ['error' => '보안 토큰이 유효하지 않습니다.']);
}

if (!isset($_FILES['preview_file'])) {
    jsonResponse(false, ['error' => '파일이 전송되지 않았습니다.']);
}

$service = new ProductImportExportService($pdo);

// 파일 검증
$error = $service->validateUploadedFile($_FILES['preview_file']);
if ($error) {
    jsonResponse(false, ['error' => $error]);
}

try {
    $result = $service->parseAndValidate($_FILES['preview_file']['tmp_name']);
    jsonResponse(true, $result);
} catch (Exception $e) {
    jsonResponse(false, ['error' => '파일 처리 중 오류: ' . $e->getMessage()]);
}
