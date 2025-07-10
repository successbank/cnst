<?php
session_start();

// 세션 파괴
session_destroy();

// 로그인 페이지로 리다이렉트
header('Location: admin_login.php');
exit;
?>