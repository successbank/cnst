<?php
/**
 * 백업 복원 AJAX
 */
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../../db.php';

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

    // Docker 환경에서 mysql 복원 실행
    $command = sprintf(
        'docker exec -i project1_mysql mysql -u root -prootpassword %s < %s 2>&1',
        'project1_db',
        escapeshellarg($filepath)
    );

    $output = shell_exec($command);

    // 오류 확인
    if ($output !== null && (strpos($output, 'ERROR') !== false || strpos($output, 'error') !== false)) {
        throw new Exception('복원 실패: ' + $output);
    }

    echo json_encode([
        'success' => true,
        'message' => '복원이 완료되었습니다.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
