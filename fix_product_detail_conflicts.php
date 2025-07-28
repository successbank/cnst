<?php
// Git 충돌 해결을 위한 임시 스크립트
$file = file_get_contents('product_detail.php');

// Git 충돌 마커 제거
$file = preg_replace('/<<<<<<< HEAD.*?=======/s', '', $file);
$file = preg_replace('/>>>>>>> [a-f0-9]+/m', '', $file);

// 파일 저장
file_put_contents('product_detail_fixed.php', $file);

echo "충돌 해결 완료. product_detail_fixed.php 파일을 확인하세요.\n";
?>