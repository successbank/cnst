<?php
/**
 * Create demo session with database user
 */

// Load Roundcube
define('INSTALL_PATH', realpath(__DIR__) . '/');
require_once 'program/include/iniset.php';

// Initialize Roundcube
$RCMAIL = rcmail::get_instance();

// First, create or get the demo user from database
$user = rcube_user::query('demo@example.com', 'localhost');
if (!$user) {
    // Create user in database
    $user = rcube_user::create('demo@example.com', 'localhost');
}

if ($user) {
    // Get user ID
    $user_id = is_object($user) ? $user->ID : $user;
    
    // Create session directly
    session_name('roundcube_sessid');
    session_start();
    
    // Set all required session variables
    $_SESSION = array(
        'user_id' => $user_id,
        'username' => 'demo@example.com',
        'storage_host' => 'localhost',
        'storage_port' => 143,
        'storage_ssl' => null,
        'password' => $RCMAIL->encrypt('demo'),
        'login_time' => time(),
        'auth_time' => time(),
        'task' => 'mail',
        'list_attrib' => array('columns' => array()),
        'folders' => array('INBOX', 'Drafts', 'Sent', 'Trash', 'Junk'),
        'unseen_count' => array('INBOX' => 0),
        'quota_display' => false,
        'last_action' => time(),
        'request_token' => rcube_utils::random_bytes(32),
        'temp' => false,
        'skin' => 'elastic',
        'language' => 'ko_KR',
        'timezone' => 'Asia/Seoul',
        'page' => 1,
        'sort_col' => 'date',
        'sort_order' => 'DESC',
    );
    
    // Save session
    session_write_close();
    
    // Set cookie for persistent login
    setcookie('roundcube_sessauth', session_id() . md5('demo'), time() + 3600, '/webmail/');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Demo Session Created</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 50px;
                background: #f0f0f0;
            }
            .box {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 500px;
                margin: 0 auto;
            }
            h1 { color: #28a745; }
            .info {
                background: #e7f3ff;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
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
            code {
                background: #f4f4f4;
                padding: 2px 5px;
                border-radius: 3px;
            }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>✓ 데모 세션 생성 완료!</h1>
            <div class="info">
                <p>사용자: <strong>demo@example.com</strong></p>
                <p>세션 ID: <code>' . session_id() . '</code></p>
                <p>사용자 ID: <code>' . $user_id . '</code></p>
            </div>
            <p>이제 웹메일 인터페이스에 접속할 수 있습니다.</p>
            <a href="./?_task=mail&_mbox=INBOX" class="btn">웹메일 열기</a>
            
            <div style="margin-top: 30px; color: #666;">
                <p><small>참고: IMAP 서버가 없으므로 실제 메일은 표시되지 않습니다.</small></p>
            </div>
        </div>
    </body>
    </html>';
} else {
    echo "Failed to create user in database.";
}