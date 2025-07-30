<?php
session_start();
require_once '../../db.php';

// 디버깅을 위한 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// 관리자 권한 체크
if (!isset($_SESSION['admin_id']) || !$_SESSION['admin_id']) {
    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
    exit;
}

// 요청 파라미터 확인
if (!isset($_POST['product_id']) || !isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터가 누락되었습니다.']);
    exit;
}

$product_id = intval($_POST['product_id']);
$file = $_FILES['image'];

// 파일 유효성 검사
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => '허용되지 않은 파일 형식입니다.']);
    exit;
}

// 파일 크기 제한 (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => '파일 크기는 5MB 이하여야 합니다.']);
    exit;
}

// 업로드 디렉토리 설정
$upload_dir = dirname(dirname(__DIR__)) . '/uploads/products/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => '업로드 디렉토리를 생성할 수 없습니다.']);
        exit;
    }
}

// 디렉토리 쓰기 권한 확인
if (!is_writable($upload_dir)) {
    echo json_encode(['success' => false, 'message' => '업로드 디렉토리에 쓰기 권한이 없습니다: ' . $upload_dir]);
    exit;
}

// 파일명 생성 (제품ID_타임스탬프.확장자)
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$new_filename = $product_id . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $new_filename;
$web_path = '/uploads/products/' . $new_filename;

try {
    // 기존 이미지 조회
    $stmt = $pdo->prepare("SELECT main_image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => '제품을 찾을 수 없습니다.']);
        exit;
    }
    
    // 파일 업로드
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // 기존 이미지 삭제
        if ($product['main_image']) {
            $old_file_path = dirname(dirname(__DIR__)) . $product['main_image'];
            if (file_exists($old_file_path)) {
                @unlink($old_file_path);
            }
        }
        
        // 데이터베이스 업데이트
        $stmt = $pdo->prepare("UPDATE products SET main_image = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$web_path, $product_id]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => '이미지가 성공적으로 업로드되었습니다.',
                'image_url' => $web_path
            ]);
        } else {
            // 업로드된 파일 삭제
            @unlink($upload_path);
            echo json_encode(['success' => false, 'message' => '데이터베이스 업데이트에 실패했습니다.']);
        }
    } else {
        // 업로드 실패 원인 파악
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => '파일이 php.ini의 upload_max_filesize 설정보다 큽니다.',
            UPLOAD_ERR_FORM_SIZE => '파일이 HTML 폼의 MAX_FILE_SIZE 설정보다 큽니다.',
            UPLOAD_ERR_PARTIAL => '파일이 부분적으로만 업로드되었습니다.',
            UPLOAD_ERR_NO_FILE => '파일이 업로드되지 않았습니다.',
            UPLOAD_ERR_NO_TMP_DIR => '임시 폴더가 없습니다.',
            UPLOAD_ERR_CANT_WRITE => '디스크에 파일을 쓸 수 없습니다.',
            UPLOAD_ERR_EXTENSION => 'PHP 확장에 의해 파일 업로드가 중지되었습니다.'
        ];
        
        $error_message = isset($upload_errors[$file['error']]) ? $upload_errors[$file['error']] : '알 수 없는 오류';
        echo json_encode(['success' => false, 'message' => '파일 업로드에 실패했습니다: ' . $error_message]);
    }
    
} catch (Exception $e) {
    error_log('Upload error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '오류가 발생했습니다: ' . $e->getMessage()]);
}
?>