<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';
require_once '../includes/MemberAdminLogService.php';

// 로그 서비스 초기화
$logService = new MemberAdminLogService($pdo);

// 관리자 ID 가져오기 ($_SESSION['admin_id']에는 username이 저장됨 → admin_users.id 조회)
$admin_id = 0;
if (!empty($_SESSION['admin_id'])) {
    $stmtAdmin = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmtAdmin->execute([$_SESSION['admin_id']]);
    $admin_id = (int)($stmtAdmin->fetchColumn() ?: 0);
}

// POST 요청만 처리
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_members.php');
    exit;
}

$action = $_POST['action'] ?? '';

// CSRF 검증은 admin_check.php가 모든 POST에 대해 전역(includes/csrf.php verifyCsrfToken)으로 수행한다.
// 과거 이 파일에 로컬 verifyCsrfToken()를 재정의해 "Cannot redeclare" fatal(HTTP 500)이 발생했으므로 제거함.
// 핸들러 내부의 verifyCsrfToken() 호출은 includes/csrf.php의 공용 함수로 해석된다.

/**
 * 비교할 회원 필드 목록
 */
function getMemberFields() {
    return [
        'email' => '이메일',
        'company' => '회사명',
        'homepage' => '홈페이지',
        'phone' => '휴대폰',
        'landline' => '일반전화',
        'zipcode' => '우편번호',
        'address' => '주소',
        'address_detail' => '상세주소',
        'is_active' => '상태',
        'memo' => '메모',
        'member_grade' => '회원등급'
    ];
}

// =========================================
// 회원 상태 변경 처리 (POST 전환)
// =========================================
if ($action === 'toggle_status') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    
    // CSRF 토큰 검증
    if (!verifyCsrfToken()) {
        $_SESSION['error_message'] = '보안 토큰이 유효하지 않습니다. 페이지를 새로고침해주세요.';
        header('Location: admin_members.php?action=view&id=' . $member_id);
        exit;
    }
    
    if (!$member_id) {
        header('Location: admin_members.php?error=invalid_member');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 현재 상태 조회
        $stmt = $pdo->prepare("SELECT id, user_id, name, is_active FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
        
        if (!$member) {
            $pdo->rollBack();
            header('Location: admin_members.php?error=member_not_found');
            exit;
        }
        
        $oldStatus = $member['is_active'];
        $newStatus = $oldStatus ? 0 : 1;
        
        // 상태 업데이트
        $stmt = $pdo->prepare("UPDATE members SET is_active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $member_id]);
        
        // 로그 기록
        $statusText = $newStatus ? '활성' : '비활성';
        $logService->logAction(
            $member_id,
            $admin_id,
            'status_change',
            ['is_active' => $oldStatus],
            ['is_active' => $newStatus],
            "상태 변경: " . ($oldStatus ? '활성' : '비활성') . " → " . $statusText
        );
        
        $pdo->commit();
        header('Location: admin_members.php?action=view&id=' . $member_id . '&msg=status_changed');
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Status toggle error: ' . $e->getMessage());
        header('Location: admin_members.php?action=view&id=' . $member_id . '&error=update_failed');
        exit;
    }
}

// =========================================
// 회원 삭제 처리 (POST 전환)
// =========================================
if ($action === 'delete_member') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    
    // CSRF 토큰 검증
    if (!verifyCsrfToken()) {
        $_SESSION['error_message'] = '보안 토큰이 유효하지 않습니다. 페이지를 새로고침해주세요.';
        header('Location: admin_members.php?action=view&id=' . $member_id);
        exit;
    }
    
    if (!$member_id) {
        header('Location: admin_members.php?error=invalid_member');
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 삭제 전 회원 정보 조회 (로그용)
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND is_admin = 0");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$member) {
            $pdo->rollBack();
            $_SESSION['error_message'] = '삭제할 수 없는 회원입니다. (관리자 또는 존재하지 않음)';
            header('Location: admin_members.php');
            exit;
        }
        
        // 비밀번호 제거 (보안상)
        unset($member['password']);
        
        // 로그 기록 (삭제 전 정보 보관)
        $logService->logAction(
            $member_id,
            $admin_id,
            'delete',
            $member,
            null,
            "회원 삭제: {$member['user_id']} ({$member['name']})"
        );
        
        // 회원 삭제
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ? AND is_admin = 0");
        $stmt->execute([$member_id]);
        
        $pdo->commit();
        $_SESSION['success_message'] = '회원이 삭제되었습니다.';
        header('Location: admin_members.php?msg=deleted');
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Member delete error: ' . $e->getMessage());
        $_SESSION['error_message'] = '삭제 중 오류가 발생했습니다.';
        header('Location: admin_members.php');
        exit;
    }
}

// =========================================
// 비밀번호 변경 처리
// =========================================
if ($action === 'change_password') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';
    
    // CSRF 토큰 검증
    if (!verifyCsrfToken()) {
        $_SESSION['error_message'] = '보안 토큰이 유효하지 않습니다. 페이지를 새로고침해주세요.';
        header('Location: admin_members.php?action=view&id=' . $member_id);
        exit;
    }
    
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
        $pdo->beginTransaction();
        
        // 회원 존재 확인
        $stmt = $pdo->prepare("SELECT user_id, name FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
        
        if (!$member) {
            $pdo->rollBack();
            header('Location: admin_members.php?error=member_not_found');
            exit;
        }
        
        // 비밀번호 해시화
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // 비밀번호 업데이트
        $stmt = $pdo->prepare("UPDATE members SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hashed_password, $member_id]);
        
        // 로그 기록 (비밀번호 값은 기록하지 않음)
        $logService->logAction(
            $member_id,
            $admin_id,
            'password_change',
            null,
            null,
            "비밀번호 변경: {$member['user_id']} ({$member['name']})"
        );
        
        $pdo->commit();
        header('Location: admin_members.php?action=view&id=' . $member_id . '&msg=password_changed');
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Password change error: ' . $e->getMessage());
        header('Location: admin_members.php?action=view&id=' . $member_id . '&error=update_failed');
        exit;
    }
}

// =========================================
// 회원 정보 수정 처리
// =========================================
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
    $member_grade = trim($_POST['member_grade'] ?? 'normal');

    // CSRF 토큰 검증
    if (!verifyCsrfToken()) {
        $_SESSION['error_message'] = '보안 토큰이 유효하지 않습니다. 페이지를 새로고침해주세요.';
        header('Location: admin_members.php?action=view&id=' . $member_id);
        exit;
    }

    // 유효성 검사
    if (!$member_id) {
        header('Location: admin_members.php?error=invalid_member');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: admin_members.php?action=view&id=' . $member_id . '&error=invalid_email');
        exit;
    }

    // 회원 등급 유효성 검사
    $valid_grades = ['normal', 'silver', 'gold', 'vip'];
    if (!in_array($member_grade, $valid_grades)) {
        $member_grade = 'normal';
    }

    try {
        $pdo->beginTransaction();
        
        // 이메일 중복 체크 (자신 제외)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ? AND id != ?");
        $stmt->execute([$email, $member_id]);
        if ($stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            header('Location: admin_members.php?action=view&id=' . $member_id . '&error=email_exists');
            exit;
        }

        // 기존 회원 정보 조회 (변경 비교용)
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $oldMember = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$oldMember) {
            $pdo->rollBack();
            header('Location: admin_members.php?error=member_not_found');
            exit;
        }

        // 새 데이터
        $newData = [
            'email' => $email,
            'company' => $company,
            'homepage' => $homepage,
            'phone' => $phone,
            'landline' => $landline,
            'zipcode' => $zipcode,
            'address' => $address,
            'address_detail' => $address_detail,
            'is_active' => $is_active,
            'memo' => $memo,
            'member_grade' => $member_grade
        ];

        // 변경사항 비교
        $diff = MemberAdminLogService::compareData($oldMember, $newData, getMemberFields());
        
        // 등급 변경 시 grade_updated_at 업데이트
        $grade_update_sql = '';
        $isGradeChange = ($oldMember['member_grade'] !== $member_grade);
        if ($isGradeChange) {
            $grade_update_sql = ', grade_updated_at = NOW()';
        }

        // 회원 정보 업데이트
        $stmt = $pdo->prepare("
            UPDATE members SET
                email = ?, company = ?, homepage = ?, phone = ?, landline = ?,
                zipcode = ?, address = ?, address_detail = ?,
                is_active = ?, memo = ?, member_grade = ?, updated_at = NOW()
                $grade_update_sql
            WHERE id = ?
        ");
        $stmt->execute([$email, $company, $homepage, $phone, $landline, $zipcode, $address, $address_detail, $is_active, $memo, $member_grade, $member_id]);

        // 변경사항이 있을 때만 로그 기록
        if (!empty($diff['changed'])) {
            // 등급 변경은 별도 타입으로 기록
            if ($isGradeChange) {
                $logService->logAction(
                    $member_id,
                    $admin_id,
                    'grade_change',
                    ['member_grade' => $oldMember['member_grade']],
                    ['member_grade' => $member_grade],
                    "등급 변경: {$oldMember['member_grade']} → {$member_grade}"
                );
            }
            
            // 상태 변경 별도 기록
            if ($oldMember['is_active'] != $is_active) {
                $logService->logAction(
                    $member_id,
                    $admin_id,
                    'status_change',
                    ['is_active' => $oldMember['is_active']],
                    ['is_active' => $is_active],
                    "상태 변경: " . ($oldMember['is_active'] ? '활성' : '비활성') . " → " . ($is_active ? '활성' : '비활성')
                );
            }
            
            // 그 외 정보 변경 기록
            $otherChanges = array_diff($diff['changed'], ['상태', '회원등급']);
            if (!empty($otherChanges)) {
                $logService->logAction(
                    $member_id,
                    $admin_id,
                    'update',
                    $diff['old'],
                    $diff['new'],
                    MemberAdminLogService::generateDescription('update', $otherChanges)
                );
            }
        }

        $pdo->commit();
        header('Location: admin_members.php?action=view&id=' . $member_id . '&msg=info_updated');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Member update error: ' . $e->getMessage());
        header('Location: admin_members.php?action=view&id=' . $member_id . '&error=update_failed');
        exit;
    }
}

// =========================================
// 메모만 저장 처리 (AJAX)
// =========================================
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
        // 기존 메모 조회
        $stmt = $pdo->prepare("SELECT memo FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $oldMemo = $stmt->fetchColumn() ?: '';
        
        // 메모 업데이트
        $stmt = $pdo->prepare("UPDATE members SET memo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$memo, $member_id]);
        
        // 로그 기록
        if ($oldMemo !== $memo) {
            $logService->logAction(
                $member_id,
                $admin_id,
                'memo_add',
                ['memo' => mb_substr($oldMemo, 0, 200)],
                ['memo' => mb_substr($memo, 0, 200)],
                '메모 저장'
            );
        }
        
        echo json_encode(['success' => true, 'message' => '메모가 저장되었습니다.']);
        exit;
        
    } catch (PDOException $e) {
        error_log('Memo save error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다: ' . $e->getMessage()]);
        exit;
    }
}

// =========================================
// 메모 추가 처리 (AJAX)
// =========================================
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
        
        // 로그 기록
        $logService->logAction(
            $member_id,
            $admin_id,
            'memo_add',
            null,
            ['memo' => mb_substr($new_memo, 0, 200)],
            '메모 추가'
        );
        
        echo json_encode(['success' => true, 'message' => '메모가 추가되었습니다.']);
        exit;
        
    } catch (PDOException $e) {
        error_log('Memo add error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => '저장 중 오류가 발생했습니다.']);
        exit;
    }
}

// =========================================
// 회원 추가 처리
// =========================================
if ($action === 'add_member') {
    // CSRF 토큰 검증
    if (!verifyCsrfToken()) {
        $_SESSION['error_message'] = '보안 토큰이 유효하지 않습니다. 페이지를 새로고침해주세요.';
        header('Location: admin_members_add.php');
        exit;
    }

    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $homepage = trim($_POST['homepage'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $landline = trim($_POST['landline'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $address_detail = trim($_POST['address_detail'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $memo = trim($_POST['memo'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    // 관리자 계정은 admin_users 체계로 별도 관리하므로 회원추가에서는 항상 일반회원으로 생성
    $is_admin = 0;
    $member_grade = trim($_POST['member_grade'] ?? 'normal');

    // 회원 등급 유효성 검사
    $valid_grades = ['normal', 'silver', 'gold', 'vip'];
    if (!in_array($member_grade, $valid_grades)) {
        $member_grade = 'normal';
    }
    
    // 유효성 검사
    if (!$user_id || !$password || !$name || !$email) {
        $_SESSION['error_message'] = '필수 항목을 모두 입력해주세요.';
        header('Location: admin_members_add.php');
        exit;
    }
    
    if (strlen($user_id) < 4 || strlen($user_id) > 20) {
        $_SESSION['error_message'] = '아이디는 4-20자여야 합니다.';
        header('Location: admin_members_add.php');
        exit;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $user_id)) {
        $_SESSION['error_message'] = '아이디는 영문, 숫자, 언더스코어(_)만 사용 가능합니다.';
        header('Location: admin_members_add.php');
        exit;
    }
    
    if (strlen($password) < 4) {
        $_SESSION['error_message'] = '비밀번호는 4자 이상이어야 합니다.';
        header('Location: admin_members_add.php');
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = '올바른 이메일 주소를 입력해주세요.';
        header('Location: admin_members_add.php');
        exit;
    }
    
    try {
        // 아이디 중복 체크
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error_message'] = '이미 사용중인 아이디입니다.';
            header('Location: admin_members_add.php');
            exit;
        }
        
        // 이메일 중복 체크
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error_message'] = '이미 사용중인 이메일입니다.';
            header('Location: admin_members_add.php');
            exit;
        }
        
        // 비밀번호 해시화
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // 회원 정보 삽입
        $stmt = $pdo->prepare("
            INSERT INTO members (
                user_id, password, name, email, company, homepage, phone, landline,
                zipcode, address, address_detail, position, memo, is_active, is_admin,
                member_grade, grade_updated_at, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW()
            )
        ");

        $stmt->execute([
            $user_id, $hashed_password, $name, $email, $company, $homepage, $phone, $landline,
            $zipcode, $address, $address_detail, $position, $memo, $is_active, $is_admin, $member_grade
        ]);
        
        $_SESSION['success_message'] = '회원이 성공적으로 추가되었습니다.';
        header('Location: admin_members.php');
        exit;
        
    } catch (PDOException $e) {
        error_log('Member add error: ' . $e->getMessage());
        $_SESSION['error_message'] = '회원 추가 중 오류가 발생했습니다.';
        header('Location: admin_members_add.php');
        exit;
    }
}

// 다른 action들을 위한 확장 가능
header('Location: admin_members.php');
exit;
?>
