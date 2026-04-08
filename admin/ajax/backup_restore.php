<?php
// AJAX 응답을 위해 에러 출력 억제 (화면에는 표시 안 함, 로그에는 기록)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/**
 * 백업 복원 AJAX (PDO 기반)
 * 스트리밍 방식으로 SQL 파일 복원
 */
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../../db.php';
require_once '../../includes/DatabaseBackupService.php';

try {
    $filename = $_POST['filename'] ?? '';

    if (empty($filename)) {
        throw new Exception('파일명이 없습니다.');
    }

    // 파일명 보안 검증
    $filename = basename($filename);

    // SQL 파일 패턴 검증
    if (!preg_match('/^[a-zA-Z0-9_\-]+\.sql$/', $filename)) {
        throw new Exception('잘못된 파일명입니다.');
    }

    $backupDir = dirname(dirname(__DIR__)) . '/backups';
    $filepath = $backupDir . '/' . $filename;

    // 파일 존재 확인
    if (!file_exists($filepath)) {
        throw new Exception('백업 파일이 존재하지 않습니다.');
    }

    // 스트리밍 방식 복원 실행
    $backupService = new DatabaseBackupService($pdo);
    $result = $backupService->restoreFromFile($filepath);

    if (!$result['success']) {
        throw new Exception($result['error'] ?? '복원 실패');
    }

    $executed = $result['executed'];
    $errors = $result['errors'];

    $message = "복원이 완료되었습니다. ({$executed}개 구문 실행)";
    if (!empty($errors)) {
        $message .= " 경고: " . count($errors) . "건의 비치명적 에러 발생";
    }

    $response = [
        'success' => true,
        'message' => $message,
        'executed' => $executed,
        'warnings' => count($errors)
    ];

    if (!empty($errors)) {
        $response['error_details'] = array_slice($errors, 0, 10);
    }

    echo json_encode($response);

} catch (Exception $e) {
    // FK check 복원 시도
    try { $pdo->exec("SET FOREIGN_KEY_CHECKS=1"); } catch (Exception $ignored) {}

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
