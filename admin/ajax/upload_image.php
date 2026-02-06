<?php
require_once '../admin_check.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false];

if (!isset($_FILES['image'])) {
    $response['message'] = '이미지가 전송되지 않았습니다.';
    echo json_encode($response);
    exit;
}

$file = $_FILES['image'];
$uploadDir = '../../uploads/news/';
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// 업로드 디렉토리 생성
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 파일 검증
if ($file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = '파일 업로드 오류가 발생했습니다.';
    echo json_encode($response);
    exit;
}

if (!in_array($file['type'], $allowedTypes)) {
    $response['message'] = '허용되지 않는 파일 형식입니다. (JPG, PNG, GIF, WEBP만 가능)';
    echo json_encode($response);
    exit;
}

// H2: 확장자 화이트리스트 검증
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions)) {
    $response['message'] = '허용되지 않는 파일 확장자입니다.';
    echo json_encode($response);
    exit;
}

// H2: 실제 이미지 파일 검증
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    $response['message'] = '유효한 이미지 파일이 아닙니다.';
    echo json_encode($response);
    exit;
}

if ($file['size'] > $maxSize) {
    $response['message'] = '파일 크기는 5MB를 초과할 수 없습니다.';
    echo json_encode($response);
    exit;
}

// 파일명 생성 (중복 방지)
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'news_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
$filepath = $uploadDir . $filename;

// 파일 이동
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    $response['success'] = true;
    $response['filename'] = $filename;
    $response['url'] = '/uploads/news/' . $filename;
    $response['message'] = '이미지가 업로드되었습니다.';
} else {
    $response['message'] = '파일 저장 중 오류가 발생했습니다.';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>