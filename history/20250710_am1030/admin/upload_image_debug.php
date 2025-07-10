<?php
// 세션 시작
session_start();

// 관리자 체크
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
    exit;
}

// JSON 헤더 설정
header('Content-Type: application/json');

// 업로드 디렉토리 설정
$uploadDir = '../uploads/notices/';
$uploadUrl = '/uploads/notices/';

// 디렉토리 생성
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => '업로드 디렉토리를 생성할 수 없습니다.']);
        exit;
    }
}

// 파일 업로드 확인
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $error_message = '파일 업로드에 실패했습니다.';
    if (isset($_FILES['image']['error'])) {
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = '파일 크기가 서버 제한을 초과했습니다.';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = '파일 크기가 폼 제한을 초과했습니다.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = '파일이 부분적으로만 업로드되었습니다.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = '파일이 업로드되지 않았습니다.';
                break;
        }
    }
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

$file = $_FILES['image'];

// 파일 타입 확인
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => '허용되지 않는 파일 형식입니다. (JPG, PNG, GIF만 가능)']);
    exit;
}

// 파일 크기 확인 (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => '파일 크기는 5MB를 초과할 수 없습니다.']);
    exit;
}

// 파일명 생성
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = date('YmdHis') . '_' . uniqid() . '.' . $extension;
$uploadPath = $uploadDir . $filename;

// 파일 이동
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode([
        'success' => true,
        'url' => $uploadUrl . $filename,
        'filename' => $filename
    ]);
} else {
    echo json_encode(['success' => false, 'message' => '파일 저장에 실패했습니다.']);
}
?>