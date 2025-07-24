<?php
/**
 * Force login for demo
 * Creates a valid session bypassing all checks
 */

// Start session with Roundcube session name
session_name('roundcube_sessid');
session_start();

// Include Roundcube bootstrap
define('INSTALL_PATH', realpath(__DIR__) . '/');
require_once 'program/include/iniset.php';

// Create all required session variables
$_SESSION = array(
    'user_id' => '1',
    'username' => 'demo@example.com',
    'storage_host' => 'localhost',
    'storage_port' => 143,
    'storage_ssl' => null,
    'password' => 'demo_encrypted',
    'login_time' => time(),
    'auth_time' => time(),
    'task' => 'mail',
    'list_attrib' => array('columns' => array()),
    'folders' => array('INBOX', 'Drafts', 'Sent', 'Trash', 'Junk'),
    'unseen_count' => array('INBOX' => 0),
    'quota_display' => false,
    'last_action' => time(),
    'request_token' => md5('demo' . time()),
    'temp' => false,
    'skin' => 'elastic',
    'language' => 'ko_KR',
    'timezone' => 'Asia/Seoul',
    'compose_data_editormode' => 'html',
    'page' => 1,
    'unread_count' => array('INBOX' => 0),
    'sort_col' => 'date',
    'sort_order' => 'DESC',
    'thread_mode' => 0,
    'list_page' => 1,
    'search_scope' => 'base',
);

// Set auth cookie
setcookie('roundcube_sessauth', md5('demo'), 0, '/webmail/');

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Demo Login Success</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background: #f0f0f0;
        }
        .success-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
        }
        h1 { color: #28a745; }
        .btn {
            display: inline-block;
            background: #37beff;
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #2090cc;
        }
    </style>
</head>
<body>
    <div class="success-box">
        <h1>✓ 세션 생성 완료!</h1>
        <p>데모 세션이 성공적으로 생성되었습니다.</p>
        <p>이제 웹메일 인터페이스를 확인할 수 있습니다.</p>
        <a href="./?_task=mail" class="btn">웹메일 화면으로 이동</a>
        <script>
            // 자동 리다이렉트
            setTimeout(function() {
                window.location.href = "./?_task=mail";
            }, 2000);
        </script>
    </div>
</body>
</html>';
?>