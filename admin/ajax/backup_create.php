<?php
// AJAX 응답을 위해 에러 출력 억제 (화면에는 표시 안 함, 로그에는 기록)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/**
 * 수동 백업 실행 AJAX (PDO 기반)
 */
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../../db.php';
require_once '../../includes/BackupNotificationService.php';
require_once '../../includes/DatabaseBackupService.php';

try {
    // 백업 디렉토리 설정
    $backupDir = dirname(dirname(__DIR__)) . '/backups';

    // 디렉토리 생성 (없으면)
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    // 파일명 생성
    $filename = 'project1_db_' . date('Ymd_His') . '.sql';
    $filepath = $backupDir . '/' . $filename;

    // backup_logs에 진행 중 상태 기록
    // created_by 컬럼이 INT 타입이므로 숫자가 아니면 NULL 처리
    $adminId = isset($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id'])
        ? intval($_SESSION['admin_id'])
        : null;
    $stmt = $pdo->prepare("INSERT INTO backup_logs (filename, backup_type, status, created_by) VALUES (?, 'manual', 'in_progress', ?)");
    $stmt->execute([$filename, $adminId]);
    $logId = $pdo->lastInsertId();

    // 스트리밍 방식 백업 실행
    $backupService = new DatabaseBackupService($pdo);
    $result = $backupService->generateBackupToFile($filepath);

    if (!$result['success']) {
        $stmt = $pdo->prepare("UPDATE backup_logs SET status = 'failed', error_message = ? WHERE id = ?");
        $stmt->execute([$result['error'] ?? '백업 데이터 생성 실패', $logId]);
        throw new Exception($result['error'] ?? '백업 데이터 생성 실패');
    }

    // 파일 권한 설정 (www-data가 삭제 가능하도록)
    chmod($filepath, 0644);

    // 성공 기록
    $fileSize = filesize($filepath);
    $tablesCount = $result['tables_count'];
    $stmt = $pdo->prepare("UPDATE backup_logs SET status = 'success', file_size = ?, tables_count = ? WHERE id = ?");
    $stmt->execute([$fileSize, $tablesCount, $logId]);

    // 이메일 알림 발송 (수동 백업)
    try {
        $notificationService = new BackupNotificationService($pdo);
        $notificationService->notifyBackupSuccess('manual', $filename, $fileSize, $tablesCount);
    } catch (Exception $emailError) {
        // 이메일 발송 실패는 백업 성공에 영향 없음
    }

    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'file_size' => $fileSize,
        'tables_count' => $tablesCount
    ]);

} catch (Exception $e) {
    // 이메일 실패 알림 발송
    try {
        $notificationService = new BackupNotificationService($pdo);
        $notificationService->notifyBackupFailure('manual', $e->getMessage());
    } catch (Exception $emailError) {
        // 이메일 발송 실패는 무시
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
