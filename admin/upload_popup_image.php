<?php
/**
 * 레이어팝업 에디터 이미지 업로드 핸들러
 */
require_once 'admin_check.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('이미지 파일이 업로드되지 않았습니다.');
    }

    $file = $_FILES['image'];

    // 파일 유효성 검사
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('JPG, PNG, GIF, WEBP 이미지만 업로드 가능합니다.');
    }

    // 파일 크기 검사 (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('파일 크기는 5MB 이하여야 합니다.');
    }

    // 업로드 디렉토리 설정
    $uploadDir = '../uploads/popups/';

    // 디렉토리가 없으면 생성
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('업로드 디렉토리를 생성할 수 없습니다.');
        }
    }

    // 파일명 생성 (고유하게)
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'popup_editor_' . date('Ymd_His') . '_' . uniqid() . '.' . strtolower($extension);
    $targetPath = $uploadDir . $filename;

    // 파일 이동
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('파일 업로드에 실패했습니다.');
    }

    // 성공 응답 (TinyMCE 형식)
    echo json_encode([
        'success' => true,
        'url' => '/uploads/popups/' . $filename,
        'location' => '/uploads/popups/' . $filename
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
