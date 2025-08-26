<?php
// 이미지 보기 스크립트

if (!isset($_GET['file']) || !isset($_GET['type'])) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

$type = $_GET['type'];
$filename = $_GET['file']; // rawurldecode는 자동으로 처리됨

// 허용된 디렉토리만 접근 가능
$allowedTypes = ['quote', 'notice', 'news', 'consignment'];
if (!in_array($type, $allowedTypes)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

$filepath = __DIR__ . '/uploads/' . $type . '/' . $filename;

// 파일 존재 확인
if (!file_exists($filepath)) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found: ' . htmlspecialchars($filename));
}

// 이미지 파일인지 확인
$imageInfo = @getimagesize($filepath);
if ($imageInfo === false) {
    header('HTTP/1.0 415 Unsupported Media Type');
    exit('Not an image file');
}

// MIME 타입 설정
$mimeType = $imageInfo['mime'];

// 헤더 설정
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: public, max-age=86400');

// 이미지 출력
readfile($filepath);
exit;
?>