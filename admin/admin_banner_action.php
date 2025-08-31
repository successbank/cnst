<?php
require_once 'admin_check.php';
require_once '../db.php';

// 액션 가져오기
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'create':
        createBanner();
        break;
    case 'update':
        updateBanner();
        break;
    case 'delete':
        deleteBanner();
        break;
    case 'toggle':
        toggleBanner();
        break;
    case 'move':
        moveBanner();
        break;
    default:
        $_SESSION['message'] = '잘못된 요청입니다.';
        $_SESSION['message_type'] = 'danger';
        header('Location: admin_banners.php');
        exit;
}

function createBanner() {
    global $pdo;
    
    try {
        // 데이터 검증
        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            throw new Exception('제목을 입력해주세요.');
        }
        
        // 이미지 업로드 처리
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = uploadBannerImage($_FILES['image']);
        }
        
        // 데이터 삽입
        $stmt = $pdo->prepare("
            INSERT INTO banners (title, subtitle, image_path, link_url, link_target, display_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $title,
            $_POST['subtitle'] ?? '',
            $imagePath,
            $_POST['link_url'] ?? '',
            $_POST['link_target'] ?? '_self',
            $_POST['display_order'] ?? 0,
            isset($_POST['is_active']) ? 1 : 0
        ]);
        
        $_SESSION['message'] = '배너가 성공적으로 추가되었습니다.';
        $_SESSION['message_type'] = 'success';
        
    } catch (Exception $e) {
        $_SESSION['message'] = '오류: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: admin_banners.php');
    exit;
}

function updateBanner() {
    global $pdo;
    
    try {
        $id = $_POST['id'] ?? 0;
        if (!$id) {
            throw new Exception('배너 ID가 없습니다.');
        }
        
        // 기존 배너 정보 가져오기
        $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch();
        
        if (!$banner) {
            throw new Exception('배너를 찾을 수 없습니다.');
        }
        
        // 데이터 검증
        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            throw new Exception('제목을 입력해주세요.');
        }
        
        // 이미지 업로드 처리
        $imagePath = $banner['image_path'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // 기존 이미지 삭제
            if ($banner['image_path']) {
                $oldImagePath = '../uploads/banners/' . $banner['image_path'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $imagePath = uploadBannerImage($_FILES['image']);
        }
        
        // 데이터 업데이트
        $stmt = $pdo->prepare("
            UPDATE banners 
            SET title = ?, subtitle = ?, image_path = ?, link_url = ?, 
                link_target = ?, display_order = ?, is_active = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $title,
            $_POST['subtitle'] ?? '',
            $imagePath,
            $_POST['link_url'] ?? '',
            $_POST['link_target'] ?? '_self',
            $_POST['display_order'] ?? 0,
            isset($_POST['is_active']) ? 1 : 0,
            $id
        ]);
        
        $_SESSION['message'] = '배너가 성공적으로 수정되었습니다.';
        $_SESSION['message_type'] = 'success';
        
    } catch (Exception $e) {
        $_SESSION['message'] = '오류: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: admin_banners.php');
    exit;
}

function deleteBanner() {
    global $pdo;
    
    try {
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            throw new Exception('배너 ID가 없습니다.');
        }
        
        // 배너 정보 가져오기
        $stmt = $pdo->prepare("SELECT image_path FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch();
        
        if ($banner) {
            // 이미지 파일 삭제
            if ($banner['image_path']) {
                $imagePath = '../uploads/banners/' . $banner['image_path'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            // 데이터베이스에서 삭제
            $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['message'] = '배너가 삭제되었습니다.';
            $_SESSION['message_type'] = 'success';
        } else {
            throw new Exception('배너를 찾을 수 없습니다.');
        }
        
    } catch (Exception $e) {
        $_SESSION['message'] = '오류: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: admin_banners.php');
    exit;
}

function toggleBanner() {
    global $pdo;
    
    try {
        $id = $_GET['id'] ?? 0;
        $status = $_GET['status'] ?? 0;
        
        if (!$id) {
            throw new Exception('배너 ID가 없습니다.');
        }
        
        $stmt = $pdo->prepare("UPDATE banners SET is_active = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        $_SESSION['message'] = '배너 상태가 변경되었습니다.';
        $_SESSION['message_type'] = 'success';
        
    } catch (Exception $e) {
        $_SESSION['message'] = '오류: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: admin_banners.php');
    exit;
}

function moveBanner() {
    global $pdo;
    
    try {
        $id = $_GET['id'] ?? 0;
        $direction = $_GET['direction'] ?? '';
        
        if (!$id || !in_array($direction, ['up', 'down'])) {
            throw new Exception('잘못된 요청입니다.');
        }
        
        // 모든 배너를 순서대로 가져오기
        $stmt = $pdo->query("SELECT id, display_order FROM banners ORDER BY display_order ASC, id ASC");
        $banners = $stmt->fetchAll();
        
        // 현재 배너의 인덱스 찾기
        $currentIndex = -1;
        foreach ($banners as $index => $banner) {
            if ($banner['id'] == $id) {
                $currentIndex = $index;
                break;
            }
        }
        
        if ($currentIndex === -1) {
            throw new Exception('배너를 찾을 수 없습니다.');
        }
        
        // 이동할 위치 계산
        $newIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        
        // 범위 체크
        if ($newIndex < 0 || $newIndex >= count($banners)) {
            throw new Exception('더 이상 이동할 수 없습니다.');
        }
        
        // 순서 바꾸기
        $temp = $banners[$currentIndex];
        $banners[$currentIndex] = $banners[$newIndex];
        $banners[$newIndex] = $temp;
        
        // 모든 배너의 display_order 업데이트
        $stmt = $pdo->prepare("UPDATE banners SET display_order = ? WHERE id = ?");
        foreach ($banners as $index => $banner) {
            $stmt->execute([$index, $banner['id']]);
        }
        
        $_SESSION['message'] = '배너 순서가 변경되었습니다.';
        $_SESSION['message_type'] = 'success';
        
    } catch (Exception $e) {
        $_SESSION['message'] = '오류: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }
    
    header('Location: admin_banners.php');
    exit;
}

function uploadBannerImage($file) {
    // 업로드 디렉토리 설정
    $uploadDir = '../uploads/banners/';
    
    // 디렉토리가 없으면 생성
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('업로드 디렉토리를 생성할 수 없습니다.');
        }
    }
    
    // 파일 유효성 검사
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('JPG, PNG, GIF 이미지만 업로드 가능합니다.');
    }
    
    // 파일 크기 검사 (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('파일 크기는 5MB 이하여야 합니다.');
    }
    
    // 파일명 생성
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'banner_' . time() . '_' . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    // 파일 이동
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('파일 업로드에 실패했습니다.');
    }
    
    return $filename;
}
?>