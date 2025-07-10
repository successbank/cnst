<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 관리자 로그인 체크
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// user_role이 없는 기존 세션 처리 (역호환성)
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'admin';
}
