<?php
session_start();
require_once '../db.php';
require_once '../member_check.php';
require_once '../includes/csrf.php';

header('Content-Type: application/json');

// CSRF 토큰 검증
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrfToken(false)) {
    echo json_encode(['success' => false, 'message' => 'CSRF 토큰이 유효하지 않습니다.']);
    exit;
}

// 로그인 체크
if (!isset($_SESSION['member_id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$member_id = $_SESSION['member_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'save_address') {
        // 주소 저장
        $address_name = $_POST['address_name'] ?? '';
        $recipient_name = $_POST['recipient_name'] ?? '';
        $recipient_phone = $_POST['recipient_phone'] ?? '';
        $zipcode = $_POST['zipcode'] ?? '';
        $address = $_POST['address'] ?? '';
        $address_detail = $_POST['address_detail'] ?? '';
        $is_default = $_POST['is_default'] ?? '0';
        
        // 필수 필드 검증
        if (empty($address_name) || empty($zipcode) || empty($address)) {
            echo json_encode(['success' => false, 'message' => '필수 항목을 입력해주세요.']);
            exit;
        }
        
        $pdo->beginTransaction();
        
        // 기본 배송지로 설정하는 경우, 기존 기본 배송지 해제
        if ($is_default == '1') {
            $stmt = $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?");
            $stmt->execute([$member_id]);
        }
        
        // 새 주소 저장
        $stmt = $pdo->prepare("
            INSERT INTO member_addresses (member_id, address_name, recipient_name, recipient_phone, zipcode, address, address_detail, is_default) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $member_id,
            $address_name,
            $recipient_name,
            $recipient_phone,
            $zipcode,
            $address,
            $address_detail,
            $is_default
        ]);
        
        $new_address_id = $pdo->lastInsertId();
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => '배송지가 추가되었습니다.',
            'address_id' => $new_address_id
        ]);
        
    } elseif ($action === 'delete_address') {
        // 주소 삭제
        $address_id = $_POST['address_id'] ?? 0;
        
        if (!$address_id) {
            echo json_encode(['success' => false, 'message' => '삭제할 주소를 선택해주세요.']);
            exit;
        }
        
        // 본인 소유 주소인지 확인
        $stmt = $pdo->prepare("SELECT id FROM member_addresses WHERE id = ? AND member_id = ?");
        $stmt->execute([$address_id, $member_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '삭제 권한이 없습니다.']);
            exit;
        }
        
        // 주소 삭제
        $stmt = $pdo->prepare("DELETE FROM member_addresses WHERE id = ? AND member_id = ?");
        $stmt->execute([$address_id, $member_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => '배송지가 삭제되었습니다.'
        ]);
        
    } elseif ($action === 'update_address') {
        // 주소 수정
        $address_id = $_POST['address_id'] ?? 0;
        $address_name = $_POST['address_name'] ?? '';
        $recipient_name = $_POST['recipient_name'] ?? '';
        $recipient_phone = $_POST['recipient_phone'] ?? '';
        $zipcode = $_POST['zipcode'] ?? '';
        $address = $_POST['address'] ?? '';
        $address_detail = $_POST['address_detail'] ?? '';
        $is_default = $_POST['is_default'] ?? '0';
        
        // 필수 필드 검증
        if (!$address_id || empty($address_name) || empty($zipcode) || empty($address)) {
            echo json_encode(['success' => false, 'message' => '필수 항목을 입력해주세요.']);
            exit;
        }
        
        // 본인 소유 주소인지 확인
        $stmt = $pdo->prepare("SELECT id FROM member_addresses WHERE id = ? AND member_id = ?");
        $stmt->execute([$address_id, $member_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '수정 권한이 없습니다.']);
            exit;
        }
        
        $pdo->beginTransaction();
        
        // 기본 배송지로 설정하는 경우, 기존 기본 배송지 해제
        if ($is_default == '1') {
            $stmt = $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?");
            $stmt->execute([$member_id]);
        }
        
        // 주소 업데이트
        $stmt = $pdo->prepare("
            UPDATE member_addresses 
            SET address_name = ?, recipient_name = ?, recipient_phone = ?, 
                zipcode = ?, address = ?, address_detail = ?, is_default = ?,
                updated_at = NOW()
            WHERE id = ? AND member_id = ?
        ");
        
        $stmt->execute([
            $address_name,
            $recipient_name,
            $recipient_phone,
            $zipcode,
            $address,
            $address_detail,
            $is_default,
            $address_id,
            $member_id
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => '배송지가 수정되었습니다.'
        ]);
        
    } elseif ($action === 'set_default') {
        // 기본 배송지 설정
        $address_id = $_POST['address_id'] ?? 0;
        
        if (!$address_id) {
            echo json_encode(['success' => false, 'message' => '주소를 선택해주세요.']);
            exit;
        }
        
        $pdo->beginTransaction();
        
        // 모든 주소의 기본 설정 해제
        $stmt = $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?");
        $stmt->execute([$member_id]);
        
        // 선택한 주소를 기본으로 설정
        $stmt = $pdo->prepare("UPDATE member_addresses SET is_default = 1 WHERE id = ? AND member_id = ?");
        $stmt->execute([$address_id, $member_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => '기본 배송지로 설정되었습니다.'
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    }
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Address Save PDO Error: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    
    // 개발 환경에서는 실제 에러 메시지 반환
    $errorMessage = '데이터베이스 오류가 발생했습니다.';
    if (isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], '211.248.112.67') !== false)) {
        $errorMessage .= ' - ' . $e->getMessage();
    }
    
    echo json_encode(['success' => false, 'message' => $errorMessage]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Address Save Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>