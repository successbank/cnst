<?php
// 오류 표시 끄기 (JSON 응답을 위해)
error_reporting(0);
ini_set('display_errors', 0);

// 세션 체크
require_once 'admin_check.php';

// JSON 응답 헤더
header('Content-Type: application/json');

// 업로드 디렉토리 설정
$uploadDir = '../uploads/notices/';
$uploadUrl = '/uploads/notices/';

// 디렉토리가 없으면 생성
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => '업로드 디렉토리를 생성할 수 없습니다.']);
        exit;
    }
}

// 파일 업로드 확인
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '파일 업로드에 실패했습니다.']);
    exit;
}

$file = $_FILES['image'];

// 파일 타입 확인
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => '허용되지 않는 파일 형식입니다.']);
    exit;
}

// 파일 크기 확인 (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => '파일 크기는 5MB를 초과할 수 없습니다.']);
    exit;
}

// 파일명 생성 (중복 방지)
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = date('YmdHis') . '_' . uniqid() . '.' . $extension;
$uploadPath = $uploadDir . $filename;

// 파일 이동
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    // GD 라이브러리 확인
    if (extension_loaded('gd')) {
        // 이미지 리사이징 (선택사항)
        $maxWidth = 1200;
        $maxHeight = 1200;
        
        $imageInfo = @getimagesize($uploadPath);
        if ($imageInfo !== false) {
            list($width, $height) = $imageInfo;
            
            if ($width > $maxWidth || $height > $maxHeight) {
                // 비율 계산
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = round($width * $ratio);
                $newHeight = round($height * $ratio);
                
                // 이미지 리사이징
                $srcImage = null;
                try {
                    switch ($file['type']) {
                        case 'image/jpeg':
                        case 'image/jpg':
                            $srcImage = @imagecreatefromjpeg($uploadPath);
                            break;
                        case 'image/png':
                            $srcImage = @imagecreatefrompng($uploadPath);
                            break;
                        case 'image/gif':
                            $srcImage = @imagecreatefromgif($uploadPath);
                            break;
                    }
                    
                    if ($srcImage) {
                        $dstImage = @imagecreatetruecolor($newWidth, $newHeight);
                        
                        if ($dstImage) {
                            // PNG와 GIF의 투명도 유지
                            if ($file['type'] == 'image/png' || $file['type'] == 'image/gif') {
                                @imagecolortransparent($dstImage, imagecolorallocatealpha($dstImage, 0, 0, 0, 127));
                                @imagealphablending($dstImage, false);
                                @imagesavealpha($dstImage, true);
                            }
                            
                            @imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                            
                            // 이미지 저장
                            switch ($file['type']) {
                                case 'image/jpeg':
                                case 'image/jpg':
                                    @imagejpeg($dstImage, $uploadPath, 90);
                                    break;
                                case 'image/png':
                                    @imagepng($dstImage, $uploadPath, 9);
                                    break;
                                case 'image/gif':
                                    @imagegif($dstImage, $uploadPath);
                                    break;
                            }
                            
                            @imagedestroy($dstImage);
                        }
                        @imagedestroy($srcImage);
                    }
                } catch (Exception $e) {
                    // 리사이징 실패해도 원본 파일은 유지
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'url' => $uploadUrl . $filename,
        'filename' => $filename
    ]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => '파일 저장에 실패했습니다.']);
    exit;
}

// 모든 경로에서 종료되도록 보장
exit;