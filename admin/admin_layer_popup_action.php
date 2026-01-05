<?php
require_once 'admin_check.php';
require_once '../db.php';

// 액션 가져오기
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'create':
        createPopup();
        break;
    case 'update':
        updatePopup();
        break;
    case 'delete':
        deletePopup();
        break;
    case 'toggle':
        togglePopup();
        break;
    case 'move':
        movePopup();
        break;
    default:
        $_SESSION['message'] = '잘못된 요청입니다.';
        $_SESSION['message_type'] = 'danger';
        header('Location: admin_layer_popups.php');
        exit;
}

function createPopup() {
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
            $imagePath = uploadPopupImage($_FILES['image']);
        }

        // 날짜 처리
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        // 데이터 삽입
        $stmt = $pdo->prepare("
            INSERT INTO layer_popups (
                title, image_path, content, link_url, link_target,
                position_top, position_left, width, height,
                start_date, end_date, is_active, display_order, hide_today
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $title,
            $imagePath,
            $_POST['content'] ?? '',
            $_POST['link_url'] ?? '',
            $_POST['link_target'] ?? '_self',
            intval($_POST['position_top'] ?? 100),
            intval($_POST['position_left'] ?? 100),
            intval($_POST['width'] ?? 400),
            intval($_POST['height'] ?? 500),
            $startDate,
            $endDate,
            isset($_POST['is_active']) ? 1 : 0,
            intval($_POST['display_order'] ?? 0),
            isset($_POST['hide_today']) ? 1 : 0
        ]);

        $_SESSION['message'] = '팝업이 성공적으로 추가되었습니다.';
        $_SESSION['message_type'] = 'success';

    } catch (Exception $e) {
        $_SESSION['message'] = '오류: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: admin_layer_popups.php');
    exit;
}

function updatePopup() {
    global $pdo;

    try {
        $id = $_POST['id'] ?? 0;
        if (!$id) {
            throw new Exception('팝업 ID가 없습니다.');
        }

        // 기존 팝업 정보 가져오기
        $stmt = $pdo->prepare("SELECT * FROM layer_popups WHERE id = ?");
        $stmt->execute([$id]);
        $popup = $stmt->fetch();

        if (!$popup) {
            throw new Exception('팝업을 찾을 수 없습니다.');
        }

        // 데이터 검증
        $title = trim($_POST['title'] ?? '');
        if (empty($title)) {
            throw new Exception('제목을 입력해주세요.');
        }

        // 이미지 업로드 처리
        $imagePath = $popup['image_path'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // 기존 이미지 삭제
            if ($popup['image_path']) {
                $oldImagePath = '../uploads/popups/' . $popup['image_path'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $imagePath = uploadPopupImage($_FILES['image']);
        }

        // 날짜 처리
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        // 데이터 업데이트
        $stmt = $pdo->prepare("
            UPDATE layer_popups
            SET title = ?, image_path = ?, content = ?, link_url = ?, link_target = ?,
                position_top = ?, position_left = ?, width = ?, height = ?,
                start_date = ?, end_date = ?, is_active = ?, display_order = ?, hide_today = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $title,
            $imagePath,
            $_POST['content'] ?? '',
            $_POST['link_url'] ?? '',
            $_POST['link_target'] ?? '_self',
            intval($_POST['position_top'] ?? 100),
            intval($_POST['position_left'] ?? 100),
            intval($_POST['width'] ?? 400),
            intval($_POST['height'] ?? 500),
            $startDate,
            $endDate,
            isset($_POST['is_active']) ? 1 : 0,
            intval($_POST['display_order'] ?? 0),
            isset($_POST['hide_today']) ? 1 : 0,
            $id
        ]);

        $_SESSION['message'] = '팝업이 성공적으로 수정되었습니다.';
        $_SESSION['message_type'] = 'success';

    } catch (Exception $e) {
        $_SESSION['message'] = '오류: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: admin_layer_popups.php');
    exit;
}

function deletePopup() {
    global $pdo;

    try {
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            throw new Exception('팝업 ID가 없습니다.');
        }

        // 팝업 정보 가져오기
        $stmt = $pdo->prepare("SELECT image_path FROM layer_popups WHERE id = ?");
        $stmt->execute([$id]);
        $popup = $stmt->fetch();

        if ($popup) {
            // 이미지 파일 삭제
            if ($popup['image_path']) {
                $imagePath = '../uploads/popups/' . $popup['image_path'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // 데이터베이스에서 삭제
            $stmt = $pdo->prepare("DELETE FROM layer_popups WHERE id = ?");
            $stmt->execute([$id]);

            $_SESSION['message'] = '팝업이 삭제되었습니다.';
            $_SESSION['message_type'] = 'success';
        } else {
            throw new Exception('팝업을 찾을 수 없습니다.');
        }

    } catch (Exception $e) {
        $_SESSION['message'] = '오류: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: admin_layer_popups.php');
    exit;
}

function togglePopup() {
    global $pdo;

    try {
        $id = $_GET['id'] ?? 0;
        $status = $_GET['status'] ?? 0;

        if (!$id) {
            throw new Exception('팝업 ID가 없습니다.');
        }

        $stmt = $pdo->prepare("UPDATE layer_popups SET is_active = ? WHERE id = ?");
        $stmt->execute([$status, $id]);

        $_SESSION['message'] = '팝업 상태가 변경되었습니다.';
        $_SESSION['message_type'] = 'success';

    } catch (Exception $e) {
        $_SESSION['message'] = '오류: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: admin_layer_popups.php');
    exit;
}

function movePopup() {
    global $pdo;

    try {
        $id = $_GET['id'] ?? 0;
        $direction = $_GET['direction'] ?? '';

        if (!$id || !in_array($direction, ['up', 'down'])) {
            throw new Exception('잘못된 요청입니다.');
        }

        // 모든 팝업을 순서대로 가져오기
        $stmt = $pdo->query("SELECT id, display_order FROM layer_popups ORDER BY display_order ASC, id ASC");
        $popups = $stmt->fetchAll();

        // 현재 팝업의 인덱스 찾기
        $currentIndex = -1;
        foreach ($popups as $index => $popup) {
            if ($popup['id'] == $id) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === -1) {
            throw new Exception('팝업을 찾을 수 없습니다.');
        }

        // 이동할 위치 계산
        $newIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        // 범위 체크
        if ($newIndex < 0 || $newIndex >= count($popups)) {
            throw new Exception('더 이상 이동할 수 없습니다.');
        }

        // 순서 바꾸기
        $temp = $popups[$currentIndex];
        $popups[$currentIndex] = $popups[$newIndex];
        $popups[$newIndex] = $temp;

        // 모든 팝업의 display_order 업데이트
        $stmt = $pdo->prepare("UPDATE layer_popups SET display_order = ? WHERE id = ?");
        foreach ($popups as $index => $popup) {
            $stmt->execute([$index, $popup['id']]);
        }

        $_SESSION['message'] = '팝업 순서가 변경되었습니다.';
        $_SESSION['message_type'] = 'success';

    } catch (Exception $e) {
        $_SESSION['message'] = '오류: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: admin_layer_popups.php');
    exit;
}

function uploadPopupImage($file) {
    // 업로드 디렉토리 설정
    $uploadDir = '../uploads/popups/';

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
    $filename = 'popup_' . time() . '_' . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;

    // 파일 이동
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('파일 업로드에 실패했습니다.');
    }

    return $filename;
}
?>
