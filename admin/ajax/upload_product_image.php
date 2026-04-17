<?php
session_start();
require_once '../../db.php';
require_once '../../includes/csrf.php';

// 디버깅을 위한 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// H3: 관리자 권한 확인 (표준 패턴)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '관리자 인증이 필요합니다.']);
    exit;
}

// [보안] CSRF 토큰 검증 (2026-04-17)
if (!verifyCsrfToken(false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF 토큰이 유효하지 않습니다.']);
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

// H2: 확장자 화이트리스트 검증
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$extCheck = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extCheck, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => '허용되지 않는 파일 확장자입니다.']);
    exit;
}

// H2: 실제 이미지 파일 검증
$imgCheck = @getimagesize($file['tmp_name']);
if ($imgCheck === false) {
    echo json_encode(['success' => false, 'message' => '유효한 이미지 파일이 아닙니다.']);
    exit;
}

// 파일 크기 제한 (상향: 25MB)
if ($file['size'] > 25 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => '파일 크기는 25MB 이하여야 합니다.']);
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
        // 2MB 이상인 경우 600KB 목표로 압축/리사이즈 시도
        $two_mb = 2 * 1024 * 1024;
        $target_size = 600 * 1024; // 600KB
        
        if (filesize($upload_path) >= $two_mb && extension_loaded('gd')) {
            $imageInfo = @getimagesize($upload_path);
            if ($imageInfo !== false) {
                $mime = $imageInfo['mime'] ?? '';
                // 최대 1600px 리사이즈 후 포맷별 재인코딩으로 용량 감축
                compress_image_to_target($upload_path, $mime, $target_size);
            }
        }
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
    echo json_encode(['success' => false, 'message' => '처리 중 오류가 발생했습니다.']);
}
?>
<?php
// 이미지 압축/리사이즈 유틸리티
if (!function_exists('compress_image_to_target')) {
    function compress_image_to_target(string $path, string $mime, int $targetSizeBytes): void {
        $info = @getimagesize($path);
        if ($info === false) return;
        list($width, $height) = $info;

        // 로드
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $src = @imagecreatefromjpeg($path);
                break;
            case 'image/png':
                $src = @imagecreatefrompng($path);
                break;
            case 'image/gif':
                $src = @imagecreatefromgif($path);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($path);
                } else { $src = null; }
                break;
            default:
                $src = null;
        }
        if (!$src) return;

        // 1) 최대 변 1600px로 축소
        $maxDim = 1600;
        $scale = min(1.0, $maxDim / max($width, $height));
        $newW = (int)max(1, round($width * $scale));
        $newH = (int)max(1, round($height * $scale));
        $dst = imagecreatetruecolor($newW, $newH);

        // 알파 처리 (PNG/GIF)
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

        // 2) 포맷별 저장 및 목표 용량까지 품질 단계 조정
        $tmpPath = $path . '.tmp';
        @unlink($tmpPath);

        $ok = false;
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            foreach ([85, 80, 75, 70, 65, 60, 55, 50, 45, 40] as $q) {
                @imagejpeg($dst, $tmpPath, $q);
                if (file_exists($tmpPath) && filesize($tmpPath) <= $targetSizeBytes) { $ok = true; break; }
            }
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            foreach ([80, 75, 70, 65, 60, 55, 50, 45, 40] as $q) {
                @imagewebp($dst, $tmpPath, $q);
                if (file_exists($tmpPath) && filesize($tmpPath) <= $targetSizeBytes) { $ok = true; break; }
            }
        } elseif ($mime === 'image/png') {
            // PNG는 용량 제어가 어렵기 때문에 우선 최대 압축으로 저장
            @imagepng($dst, $tmpPath, 9);
            if (file_exists($tmpPath) && filesize($tmpPath) <= $targetSizeBytes) { $ok = true; }
            // 그래도 크면 한 번 더 축소(최대변 1000px) 후 재시도
            if (!$ok) {
                $maxDim2 = 1000;
                $scale2 = min(1.0, $maxDim2 / max($newW, $newH));
                $w2 = (int)max(1, round($newW * $scale2));
                $h2 = (int)max(1, round($newH * $scale2));
                $dst2 = imagecreatetruecolor($w2, $h2);
                imagealphablending($dst2, false);
                imagesavealpha($dst2, true);
                $transparent2 = imagecolorallocatealpha($dst2, 0, 0, 0, 127);
                imagefilledrectangle($dst2, 0, 0, $w2, $h2, $transparent2);
                imagecopyresampled($dst2, $dst, 0, 0, 0, 0, $w2, $h2, $newW, $newH);
                @imagepng($dst2, $tmpPath, 9);
                imagedestroy($dst2);
                if (file_exists($tmpPath) && filesize($tmpPath) <= $targetSizeBytes) { $ok = true; }
            }
        } elseif ($mime === 'image/gif') {
            @imagegif($dst, $tmpPath);
            if (file_exists($tmpPath) && filesize($tmpPath) <= $targetSizeBytes) { $ok = true; }
        }

        // 성공 시 원본 대체, 실패 시 그래도 리사이즈/재인코딩본이 더 작으면 교체
        if (file_exists($tmpPath)) {
            $orig = @filesize($path) ?: PHP_INT_MAX;
            $cand = @filesize($tmpPath) ?: PHP_INT_MAX;
            if ($ok || $cand < $orig) {
                @rename($tmpPath, $path);
            } else {
                @unlink($tmpPath);
            }
        }

        // 정리
        imagedestroy($dst);
        imagedestroy($src);
    }
}
?>
