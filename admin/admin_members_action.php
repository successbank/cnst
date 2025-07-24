<?php
require_once 'admin_check.php';
require_once '../db.php';

// POST 요청만 처리
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_members.php');
    exit;
}

$action = $_POST['action'] ?? '';

// 비밀번호 변경 처리
if ($action === 'change_password') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';
    
    // 유효성 검사
    if (!$member_id) {
        header('Location: admin_members.php?error=invalid_member');
        exit;
    }
    
    if (strlen($new_password) < 4) {
        header('Location: admin_members.php?action=view&id=' . $member_id . '&error=password_too_short');
        exit;
    }
    
    if ($new_password !== $new_password_confirm) {
        header('Location: admin_members.php?action=view&id=' . $member_id . '&error=password_mismatch');
        exit;
    }
    
    try {
        // 비밀번호 해시화
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // 비밀번호 업데이트
        $stmt = $pdo->prepare("UPDATE members SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hashed_password, $member_id]);
        
        header('Location: admin_members.php?action=view&id=' . $member_id . '&msg=password_changed');
        exit;
        
    } catch (PDOException $e) {
        error_log('Password change error: ' . $e->getMessage());
        header('Location: admin_members.php?action=view&id=' . $member_id . '&error=update_failed');
        exit;
    }
}

// 회원 정보 수정 처리
if ($action === 'update_member') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $homepage = trim($_POST['homepage'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $landline = trim($_POST['landline'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $address_detail = trim($_POST['address_detail'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $memo = trim($_POST['memo'] ?? '');
    
    // 유효성 검사
    if (!$member_id) {
        header('Location: admin_members.php?error=invalid_member');
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: admin_members.php?action=view&id=' . $member_id . '&error=invalid_email');
        exit;
    }
    
    try {
        // 이메일 중복 체크 (자신 제외)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ? AND id != ?");
        $stmt->execute([$email, $member_id]);
        if ($stmt->fetchColumn() > 0) {
            header('Location: admin_members.php?action=view&id=' . $member_id . '&error=email_exists');
            exit;
        }
        
        // 회원 정보 업데이트
        $stmt = $pdo->prepare("
            UPDATE members SET 
                email = ?, company = ?, homepage = ?, phone = ?, landline = ?, 
                zipcode = ?, address = ?, address_detail = ?,
                is_active = ?, memo = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$email, $company, $homepage, $phone, $landline, $zipcode, $address, $address_detail, $is_active, $memo, $member_id]);
        
        header('Location: admin_members.php?action=view&id=' . $member_id . '&msg=info_updated');
        exit;
        
    } catch (PDOException $e) {
        error_log('Member update error: ' . $e->getMessage());
        header('Location: admin_members.php?action=view&id=' . $member_id . '&error=update_failed');
        exit;
    }
}

// 메모만 저장 처리 (AJAX)
if ($action === 'save_memo') {
    header('Content-Type: application/json; charset=utf-8');
    
    $member_id = (int)($_POST['member_id'] ?? 0);
    $memo = trim($_POST['memo'] ?? '');
    
    // 유효성 검사
    if (!$member_id) {
        echo json_encode(['success' => false, 'message' => '잘못된 회원 정보입니다.']);
        exit;
    }
    
    try {
        // 메모 업데이트
        $stmt = $pdo->prepare("UPDATE members SET memo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$memo, $member_id]);
        
        echo json_encode(['success' => true, 'message' => '메모가 저장되었습니다.']);
        exit;
        
    } catch (PDOException $e) {
        error_log('Memo save error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다: ' . $e->getMessage()]);
        exit;
    }
}

// 메모 추가 처리 (AJAX)
if ($action === 'add_memo') {
    header('Content-Type: application/json; charset=utf-8');
    
    $member_id = (int)($_POST['member_id'] ?? 0);
    $new_memo = trim($_POST['new_memo'] ?? '');
    
    // 유효성 검사
    if (!$member_id) {
        echo json_encode(['success' => false, 'message' => '잘못된 회원 정보입니다.']);
        exit;
    }
    
    if (!$new_memo) {
        echo json_encode(['success' => false, 'message' => '메모 내용을 입력해주세요.']);
        exit;
    }
    
    try {
        // 기존 메모 가져오기
        $stmt = $pdo->prepare("SELECT memo FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $current_memo = $stmt->fetchColumn() ?: '';
        
        // 새 메모 추가
        $updated_memo = $current_memo ? $current_memo . "\n---\n" . $new_memo : $new_memo;
        
        // 메모 업데이트
        $stmt = $pdo->prepare("UPDATE members SET memo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$updated_memo, $member_id]);
        
        echo json_encode(['success' => true, 'message' => '메모가 추가되었습니다.']);
        exit;
        
    } catch (PDOException $e) {
        error_log('Memo add error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다.']);
        exit;
    }
}

// 다른 action들을 위한 확장 가능
header('Location: admin_members.php');
exit;
?>