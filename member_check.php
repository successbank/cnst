<?php
// 세션이 시작되지 않았다면 시작
if(!isset($_SESSION)) {
    session_start();
}

// 회원 로그인 체크
function checkLogin() {
    if(!isset($_SESSION['member_id']) || !isset($_SESSION['user_id'])) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// 로그인 상태 확인
function isLoggedIn() {
    return isset($_SESSION['member_id']) && isset($_SESSION['user_id']);
}

// 회원 정보 가져오기
function getMemberInfo() {
    if(isLoggedIn()) {
        return [
            'id' => $_SESSION['member_id'],
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['member_name'] ?? '',
            'email' => $_SESSION['member_email'] ?? ''
        ];
    }
    return null;
}
?>