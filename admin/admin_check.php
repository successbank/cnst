<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// [보안] 2FA pending 상태에서 admin 페이지 직접 접근 차단
if (isset($_SESSION['totp_pending']) && $_SESSION['totp_pending'] === true) {
    $currentScript = basename($_SERVER['PHP_SELF']);
    if ($currentScript !== 'admin_totp_verify.php') {
        header('Location: admin_totp_verify.php');
        exit;
    }
}

// 관리자 로그인 체크
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// M2: 세션 타임아웃 (30분)
$session_timeout = 1800;
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > $session_timeout) {
    session_destroy();
    header('Location: admin_login.php?msg=timeout');
    exit;
}
// 활동 시 시간 갱신
$_SESSION['admin_login_time'] = time();

// [보안] 관리자 IP 제한 (빈 배열이면 비활성화)
// 고정 IP 확보 후 아래 배열에 추가하여 활성화
$allowed_ips = [];
if (!empty($allowed_ips)) {
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($client_ip, $allowed_ips)) {
        session_destroy();
        http_response_code(403);
        die('접근이 허용되지 않은 IP입니다.');
    }
}

// user_role이 없는 기존 세션 처리 (역호환성)
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'admin';
}

// [보안] CSRF 토큰 보호 - POST/PUT/DELETE 요청 시 검증
require_once __DIR__ . '/../includes/csrf.php';
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    verifyCsrfToken(true);
}
