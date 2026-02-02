<?php
/**
 * 수동 백업 실행 AJAX (PDO 기반)
 */
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../../db.php';
require_once '../../includes/BackupNotificationService.php';

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

    // PDO 기반 백업 실행
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

    // 파일 권한 설정 (www-data가 삭제 가능하도록)
    chmod($filepath, 0644);

    // 테이블 수 계산
    $tablesCount = preg_match_all('/CREATE TABLE/', $output);

    // 성공 기록
    $fileSize = filesize($filepath);
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
        // 시스템/손상된 테이블 건너뛰기
        if (strpos($table, 'RECOVER_YOUR_DATA') !== false) {
            continue;
        }

        try {
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
        } catch (PDOException $e) {
            // 테이블 처리 실패 시 건너뛰기
            $output .= "-- 오류: 테이블 `$table` 백업 실패 - " . $e->getMessage() . "\n\n";
        }
    }

    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    $output .= "\n-- 백업 완료\n";

    return $output;
}
