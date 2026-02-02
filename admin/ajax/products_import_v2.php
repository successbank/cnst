<?php
/**
 * 제품 CSV 임포트 실행 v2 엔드포인트
 * 트랜잭션 기반 일괄 처리 + 이력 기록
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

if (!isset($_FILES['import_file'])) {
    jsonResponse(false, ['error' => '파일이 전송되지 않았습니다.']);
}

$service = new ProductImportExportService($pdo);

// 파일 검증
$error = $service->validateUploadedFile($_FILES['import_file']);
if ($error) {
    jsonResponse(false, ['error' => $error]);
}

try {
    $adminId = $_SESSION['admin_id'] ?? $_SESSION['admin_name'] ?? 'unknown';
    $adminName = $_SESSION['admin_name'] ?? 'unknown';
    $filename = $_FILES['import_file']['name'];
    $fileSize = $_FILES['import_file']['size'];

    $results = $service->executeImport(
        $_FILES['import_file']['tmp_name'],
        $adminId,
        $adminName,
        $filename,
        $fileSize
    );

    $message = "처리 완료: ";
    $message .= "신규 {$results['created']}건, ";
    $message .= "수정 {$results['updated']}건, ";
    $message .= "삭제 {$results['deleted']}건";
    if (!empty($results['errors'])) {
        $message .= ", 오류 " . count($results['errors']) . "건";
    }

    jsonResponse(true, [
        'message' => $message,
        'created' => $results['created'],
        'updated' => $results['updated'],
        'deleted' => $results['deleted'],
        'errors' => $results['errors'],
        'error_count' => count($results['errors']),
        'history_id' => $results['history_id'] ?? null,
    ]);

} catch (Exception $e) {
    jsonResponse(false, ['error' => '임포트 실패: ' . $e->getMessage()]);
}
