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

    // PDO 기반 백업 실행
    logMessage('백업 데이터 생성 중...');
    $output = generateBackup($pdo);

    if (empty($output)) {
        $stmt = $pdo->prepare("UPDATE backup_logs SET status = 'failed', error_message = ? WHERE id = ?");
        $stmt->execute(['백업 데이터 생성 실패', $logId]);
        throw new Exception('백업 데이터 생성 실패');
    }

    // SQL 파일 저장
    if (file_put_contents($filepath, $output) === false) {
        $stmt = $pdo->prepare("UPDATE backup_logs SET status = 'failed', error_message = ? WHERE id = ?");
        $stmt->execute(['파일 저장 실패', $logId]);
        throw new Exception('백업 파일 저장 실패');
    }

    // 테이블 수 계산
    $tablesCount = preg_match_all('/CREATE TABLE/', $output);

    // 성공 기록
    $fileSize = filesize($filepath);
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

    // 오래된 백업 삭제 (보관 일수 기준)
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
    $stmt = $pdo->prepare("SELECT filename FROM backup_logs WHERE created_at < ? AND backup_type = 'auto'");
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

    // 최대 파일 수 기준 삭제
    $stmt = $pdo->query("SELECT filename FROM backup_logs WHERE backup_type = 'auto' ORDER BY created_at DESC");
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
 * PDO를 사용하여 데이터베이스 백업 SQL 생성
 */
function generateBackup($pdo) {
    $output = '';

    // 헤더 추가
    $output .= "-- 충남스틸 데이터베이스 백업\n";
    $output .= "-- 생성일시: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- 데이터베이스: " . DB_NAME . "\n";
    $output .= "-- -------------------------------------------------\n\n";

    $output .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $output .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
    $output .= "SET time_zone = '+09:00';\n\n";

    // 모든 테이블 가져오기
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // 테이블 생성 구문
        $output .= "-- -------------------------------------------------\n";
        $output .= "-- 테이블 구조: `$table`\n";
        $output .= "-- -------------------------------------------------\n\n";

        $output .= "DROP TABLE IF EXISTS `$table`;\n";

        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $output .= $createTable['Create Table'] . ";\n\n";

        // 데이터 덤프
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $output .= "-- 데이터 덤프: `$table`\n";

            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_map(function($val) use ($pdo) {
                    if ($val === null) {
                        return 'NULL';
                    }
                    return $pdo->quote($val);
                }, array_values($row));

                $output .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }
    }

    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $output .= "\n-- 백업 완료\n";

    return $output;
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
