<?php
// 기존 consignment.php → brokerage.php 301 리다이렉트
header('HTTP/1.1 301 Moved Permanently');
header('Location: brokerage.php');
exit;
?>
