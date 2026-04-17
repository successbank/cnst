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

// M2: 세션 Idle 타임아웃 (30분) - 활동 없이 30분 경과 시 만료
$session_idle_timeout = 1800;
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > $session_idle_timeout) {
    session_destroy();
    header('Location: admin_login.php?msg=timeout');
    exit;
}

// [보안 2026-04-17 감사 M-5] 세션 Absolute 타임아웃 (8시간)
// 활동 여부와 무관하게 로그인 후 8시간 경과 시 강제 재인증
// - 장시간 방치된 세션 토큰 탈취 시 피해 시간 제한
// - 기존 세션에 admin_session_started가 없으면 admin_login_time을 사용해 fallback
$session_absolute_timeout = 8 * 3600;
$sessionStartedAt = $_SESSION['admin_session_started'] ?? $_SESSION['admin_login_time'] ?? time();
if ((time() - $sessionStartedAt) > $session_absolute_timeout) {
    session_destroy();
    header('Location: admin_login.php?msg=timeout');
    exit;
}

// 활동 시 시간 갱신 (idle 용 - absolute는 갱신하지 않음)
$_SESSION['admin_login_time'] = time();

// [보안 2026-04-17 감사 M-6] 관리자 IP 화이트리스트 (환경변수 기반 옵션)
// 설정 방법: docker-compose.yml의 php 서비스 environment에 ADMIN_ALLOWED_IPS 추가
//   예) ADMIN_ALLOWED_IPS: "203.0.113.10,198.51.100.20"
// 빈 값이면 비활성화되어 사용자 잠금 리스크 없음
// 내부 네트워크(127.0.0.1, 172.x, 10.x)는 화이트리스트 여부와 무관하게 항상 허용
// (컨테이너 내부 헬스체크/디버깅 차단 방지)
$allowed_ips_raw = getenv('ADMIN_ALLOWED_IPS') ?: '';
$allowed_ips = array_values(array_filter(array_map('trim', explode(',', $allowed_ips_raw))));
if (!empty($allowed_ips)) {
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $isInternal = ($client_ip === '127.0.0.1' || $client_ip === '::1'
                   || strpos($client_ip, '172.') === 0
                   || strpos($client_ip, '10.') === 0);
    if (!$isInternal && !in_array($client_ip, $allowed_ips, true)) {
        error_log('admin_check.php: IP whitelist block for ' . $client_ip);
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
