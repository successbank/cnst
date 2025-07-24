<?php
/**
 * Direct bypass for Roundcube demo mode
 * This creates a session without IMAP authentication
 */

// Start session first
session_start();

// Load Roundcube
define('INSTALL_PATH', realpath(__DIR__) . '/');
require_once 'program/include/iniset.php';

// Initialize application
$RCMAIL = rcmail::get_instance();

// Check if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: ./?_task=mail');
    exit;
}

// Create demo user if not exists
$user = rcube_user::query('demo@example.com', 'localhost');
if (!$user) {
    $user = rcube_user::create('demo@example.com', 'localhost');
}

if ($user) {
    // Set session variables directly
    $_SESSION['user_id'] = $user->ID;
    $_SESSION['username'] = 'demo@example.com';
    $_SESSION['storage_host'] = 'localhost';
    $_SESSION['password'] = $RCMAIL->encrypt('demo');
    $_SESSION['login_time'] = time();
    $_SESSION['task'] = 'mail';
    $_SESSION['request_token'] = md5(uniqid(rand(), true));
    
    // Set authentication timestamp
    $_SESSION['auth_time'] = time();
    $_SESSION['temp'] = false;
    
    // Set user in RCMAIL
    $RCMAIL->set_user($user);
    
    // Create fake storage session to prevent IMAP checks
    $_SESSION['storage_connected'] = true;
    $_SESSION['storage_cache'] = array();
    $_SESSION['folders'] = array('INBOX', 'Drafts', 'Sent', 'Trash', 'Junk');
    
    // Important: Skip IMAP by setting these flags
    $_SESSION['imap_connected'] = true;
    $_SESSION['imap_host'] = 'demo';
    
    echo "세션 생성 완료. 메일 화면으로 이동합니다...<br>";
    echo "<a href='./?_task=mail'>메일 화면으로 이동</a>";
    echo "<script>setTimeout(function(){ window.location.href = './?_task=mail'; }, 1000);</script>";
    exit;
} else {
    echo "Failed to create demo session";
}