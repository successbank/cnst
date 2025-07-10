<?php
require_once '../admin_check.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false];

// JSON 입력 받기
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['filename'])) {
    $response['message'] = '파일명이 전송되지 않았습니다.';
    echo json_encode($response);
    exit;
}

$filename = basename($input['filename']); // 보안을 위해 basename 사용
$filepath = '../../uploads/news/' . $filename;

// 파일이 업로드 디렉토리에 있는지 확인
if (strpos(realpath($filepath), realpath('../../uploads/news/')) !== 0) {
    $response['message'] = '잘못된 파일 경로입니다.';
    echo json_encode($response);
    exit;
}

// 파일 삭제
if (file_exists($filepath) && unlink($filepath)) {
    $response['success'] = true;
    $response['message'] = '이미지가 삭제되었습니다.';
} else {
    $response['message'] = '파일을 찾을 수 없거나 삭제할 수 없습니다.';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>