<?php
// 임시로 세션 설정하여 관리자 페이지 테스트
session_start();
$_SESSION['admin_id'] = 'test_admin';
$_SESSION['admin_name'] = '테스트 관리자';

// 관리자 페이지로 리다이렉트
header('Location: admin_product_groups.php');
exit;