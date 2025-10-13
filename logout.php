<?php
session_start();
require_once 'db.php';
require_once 'includes/auth_tokens.php';

// Remember Me 토큰 삭제 (로그인 유지 해제)
if(isset($_SESSION['member_id'])) {
    deleteAllRememberTokens($_SESSION['member_id']);
}

// Remember Me 쿠키 삭제
deleteRememberCookie();

// 세션 파괴
session_destroy();

// 메인 페이지로 리다이렉트
header('Location: index.php');
exit;
?>