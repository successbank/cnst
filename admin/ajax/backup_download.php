<?php
/**
 * 백업 파일 다운로드 AJAX
 */
require_once '../admin_check.php';

// 파일명 검증
$filename = $_GET['filename'] ?? '';

if (empty($filename)) {
    die('파일명이 없습니다.');
}

// 파일명 보안 검증 (directory traversal 방지)
$filename = basename($filename);

// SQL 파일 패턴 검증
if (!preg_match('/^[a-zA-Z0-9_\-]+\.sql$/', $filename)) {
    die('잘못된 파일명입니다.');
}

// 백업 디렉토리 경로
$backupDir = dirname(dirname(__DIR__)) . '/backups';
$filepath = $backupDir . '/' . $filename;

// 파일 존재 확인
if (!file_exists($filepath)) {
    die('파일이 존재하지 않습니다.');
}

// 다운로드 헤더 설정
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache');
header('Pragma: no-cache');

// 파일 출력
readfile($filepath);
exit;
