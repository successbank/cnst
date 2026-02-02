<?php
// AJAX 응답을 위해 에러 출력 억제 (화면에는 표시 안 함, 로그에는 기록)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/**
 * 백업 파일 삭제 AJAX
 */
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../../db.php';

try {
    $filenames = $_POST['filenames'] ?? [];

    if (empty($filenames)) {
        throw new Exception('삭제할 파일이 없습니다.');
    }

    $backupDir = dirname(dirname(__DIR__)) . '/backups';
    $deletedCount = 0;
    $errors = [];

    foreach ($filenames as $filename) {
        // 파일명 보안 검증
        $filename = basename($filename);

        // SQL 파일 패턴 검증
        if (!preg_match('/^[a-zA-Z0-9_\-]+\.sql$/', $filename)) {
            $errors[] = $filename . ': 잘못된 파일명';
            continue;
        }

        $filepath = $backupDir . '/' . $filename;

        // 파일 존재 확인 후 삭제
        if (file_exists($filepath)) {
            // 쓰기 권한 확인
            if (!is_writable($filepath)) {
                $errors[] = $filename . ': 쓰기 권한 없음';
                error_log("Backup delete - No write permission for: $filepath");
                continue;
            }

            if (@unlink($filepath)) {
                // DB 로그에서도 삭제
                $stmt = $pdo->prepare("DELETE FROM backup_logs WHERE filename = ?");
                $stmt->execute([$filename]);
                $deletedCount++;
            } else {
                $lastError = error_get_last();
                $errorMsg = $lastError ? $lastError['message'] : '알 수 없는 오류';
                $errors[] = $filename . ': 삭제 실패 - ' . $errorMsg;
                error_log("Backup delete failed for $filepath: $errorMsg");
            }
        } else {
            $errors[] = $filename . ': 파일이 존재하지 않음';
            error_log("Backup delete - File not found: $filepath");
        }
    }

    if ($deletedCount > 0) {
        echo json_encode([
            'success' => true,
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ]);
    } else {
        throw new Exception(implode(', ', $errors));
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
