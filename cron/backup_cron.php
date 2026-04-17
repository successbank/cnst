#!/usr/bin/env php
<?php
/**
 * 자동 백업 Cron 스크립트 (PDO 기반)
 *
 * 사용법 (Docker 컨테이너 내에서):
 * docker exec project1_php php /var/www/html/cron/backup_cron.php
 *
 * Crontab 설정 예시 (호스트):
 * 0 1 * * * docker exec project1_php php /var/www/html/cron/backup_cron.php >> /var/log/db_backup.log 2>&1
 */

// 에러 출력
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 타임존 설정
date_default_timezone_set('Asia/Seoul');

// 경로 설정
$baseDir = dirname(__DIR__);
require_once $baseDir . '/db.php';
require_once $baseDir . '/includes/settings.php';
require_once $baseDir . '/includes/BackupNotificationService.php';
require_once $baseDir . '/includes/DatabaseBackupService.php';

// 로그 함수
function logMessage($message) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

logMessage('자동 백업 시작');

try {
    // 자동 백업 활성화 여부 확인
    $autoEnabled = getSetting('backup_auto_enabled', '1');
    if ($autoEnabled !== '1') {
        logMessage('자동 백업이 비활성화되어 있습니다.');
        exit(0);
    }

    // 설정 가져오기
    $retentionDays = intval(getSetting('backup_retention_days', '30'));
    $maxFiles = intval(getSetting('backup_max_files', '30'));

    // 백업 디렉토리 설정
    $backupDir = $baseDir . '/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
        logMessage('백업 디렉토리 생성: ' . $backupDir);
    }

    // 파일명 생성
    $filename = 'project1_db_' . date('Ymd_His') . '.sql';
    $filepath = $backupDir . '/' . $filename;

    // backup_logs에 진행 중 상태 기록
    $stmt = $pdo->prepare("INSERT INTO backup_logs (filename, backup_type, status) VALUES (?, 'auto', 'in_progress')");
    $stmt->execute([$filename]);
    $logId = $pdo->lastInsertId();

    // 스트리밍 방식 백업 실행
    logMessage('백업 데이터 생성 중...');
    $backupService = new DatabaseBackupService($pdo);
    $result = $backupService->generateBackupToFile($filepath);

    if (!$result['success']) {
        $stmt = $pdo->prepare("UPDATE backup_logs SET status = 'failed', error_message = ? WHERE id = ?");
        $stmt->execute([$result['error'] ?? '백업 데이터 생성 실패', $logId]);
        throw new Exception($result['error'] ?? '백업 데이터 생성 실패');
    }

    // [보안 2026-04-17 감사 M-11] 백업 파일에 0600 권한 강제 (소유자 외 읽기 차단)
    // - nginx /backups/ 블록과 더불어 파일시스템 레벨 방어 계층 추가
    // - 컨테이너 공격으로 권한 상승 시 백업 평문 유출 위험 감소
    @chmod($filepath, 0600);

    // 성공 기록
    $fileSize = filesize($filepath);
    $tablesCount = $result['tables_count'];
    $stmt = $pdo->prepare("UPDATE backup_logs SET status = 'success', file_size = ?, tables_count = ? WHERE id = ?");
    $stmt->execute([$fileSize, $tablesCount, $logId]);

    logMessage('백업 완료: ' . $filename . ' (' . formatSize($fileSize) . ', ' . $tablesCount . '개 테이블)');

    // 이메일 알림 발송 (자동 백업) - 백업 파일 경로 전달하여 첨부 가능
    try {
        $notificationService = new BackupNotificationService($pdo);
        $emailResult = $notificationService->notifyBackupSuccess('auto', $filename, $fileSize, $tablesCount, $filepath);
        if ($emailResult !== null) {
            if ($emailResult['success']) {
                logMessage('이메일 알림 발송 완료: ' . $emailResult['message']);
            } else {
                logMessage('이메일 알림 발송 실패: ' . $emailResult['message']);
            }
        }
    } catch (Exception $emailError) {
        logMessage('이메일 알림 오류: ' . $emailError->getMessage());
    }

    // Stale in_progress 레코드 정리 (24시간 이상 in_progress → failed)
    $staleCutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $stmt = $pdo->prepare("SELECT id, filename FROM backup_logs WHERE status = 'in_progress' AND created_at < ?");
    $stmt->execute([$staleCutoff]);
    $staleRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($staleRecords as $stale) {
        $stalePath = $backupDir . '/' . $stale['filename'];
        if (file_exists($stalePath)) {
            unlink($stalePath);
            logMessage('Stale 백업 파일 삭제: ' . $stale['filename']);
        }
        $stmt = $pdo->prepare("UPDATE backup_logs SET status = 'failed', error_message = '자동 정리: 24시간 이상 in_progress 상태' WHERE id = ?");
        $stmt->execute([$stale['id']]);
        logMessage('Stale 레코드 정리: ' . $stale['filename'] . ' (id=' . $stale['id'] . ')');
    }

    // 오래된 백업 삭제 (보관 일수 기준 - 전체 백업 대상)
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
    $stmt = $pdo->prepare("SELECT filename FROM backup_logs WHERE created_at < ? AND status = 'success'");
    $stmt->execute([$cutoffDate]);
    $oldBackups = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($oldBackups as $oldFile) {
        $oldPath = $backupDir . '/' . $oldFile;
        if (file_exists($oldPath)) {
            unlink($oldPath);
            logMessage('오래된 백업 삭제: ' . $oldFile);
        }
        $stmt = $pdo->prepare("DELETE FROM backup_logs WHERE filename = ?");
        $stmt->execute([$oldFile]);
    }

    // 최대 파일 수 기준 삭제 (전체 성공 백업 대상)
    $stmt = $pdo->query("SELECT filename FROM backup_logs WHERE status = 'success' ORDER BY created_at DESC");
    $allBackups = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($allBackups) > $maxFiles) {
        $toDelete = array_slice($allBackups, $maxFiles);
        foreach ($toDelete as $deleteFile) {
            $deletePath = $backupDir . '/' . $deleteFile;
            if (file_exists($deletePath)) {
                unlink($deletePath);
                logMessage('초과 백업 삭제: ' . $deleteFile);
            }
            $stmt = $pdo->prepare("DELETE FROM backup_logs WHERE filename = ?");
            $stmt->execute([$deleteFile]);
        }
    }

    logMessage('자동 백업 완료');

} catch (Exception $e) {
    logMessage('오류: ' . $e->getMessage());

    // 실패 이메일 알림 발송
    try {
        $notificationService = new BackupNotificationService($pdo);
        $emailResult = $notificationService->notifyBackupFailure('auto', $e->getMessage());
        if ($emailResult !== null) {
            if ($emailResult['success']) {
                logMessage('실패 알림 이메일 발송 완료');
            } else {
                logMessage('실패 알림 이메일 발송 실패: ' . $emailResult['message']);
            }
        }
    } catch (Exception $emailError) {
        logMessage('이메일 알림 오류: ' . $emailError->getMessage());
    }

    exit(1);
}

/**
 * 파일 크기 포맷 함수
 */
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
