<?php
// 파일 다운로드 처리 스크립트

if (!isset($_GET['file']) || !isset($_GET['type'])) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

$type = $_GET['type'];
$filename = basename($_GET['file']); // 보안을 위해 basename 사용

// 허용된 디렉토리만 접근 가능
$allowedTypes = ['quote', 'notice', 'news', 'consignment'];
if (!in_array($type, $allowedTypes)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// 게시판 읽기 권한 확인 (디렉토리명 == board_type, 공개 게시판 notice/news는 guest read=1)
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/BoardPermissionHelper.php';
if (!BoardPermissionHelper::canAccess($type, 'read')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

$filepath = __DIR__ . '/uploads/' . $type . '/' . $filename;

// 파일 존재 확인
if (!file_exists($filepath)) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

// 파일 타입 확인
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filepath);
finfo_close($finfo);

// 파일 크기
$filesize = filesize($filepath);

// 헤더 설정
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 파일 출력
readfile($filepath);
exit;
?>