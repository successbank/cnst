<?php
/**
 * Demo entry point for Roundcube without IMAP
 */

// Set demo mode flag
define('DEMO_MODE', true);

// Include Roundcube
require_once 'program/include/iniset.php';

// Initialize Roundcube
$RCMAIL = rcmail::get_instance(0, isset($GLOBALS['env']) ? $GLOBALS['env'] : null);

// Override authentication
if (!empty($_POST['_user'])) {
    // Create demo session
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = $_POST['_user'];
    $_SESSION['storage_host'] = 'demo';
    $_SESSION['password'] = $RCMAIL->encrypt('demo');
    
    // Create user if not exists
    $user = rcube_user::query($_POST['_user'], 'demo');
    if (!$user) {
        $user = rcube_user::create($_POST['_user'], 'demo');
    }
    
    // Redirect to mail interface
    header('Location: demo.php?_task=mail');
    exit;
}

// Check if logged in
if (!empty($_SESSION['user_id'])) {
    $_GET['_task'] = 'mail';
    $RCMAIL->set_task('mail');
    
    // Show mail interface without IMAP
    $OUTPUT = $RCMAIL->json_init();
    $OUTPUT->set_pagetitle($RCMAIL->gettext('mailbox'));
    
    // Add demo notice
    $OUTPUT->show_message('데모 모드: UI 확인용입니다. 실제 메일 서버와 연결되어 있지 않습니다.', 'notice');
    
    // Send mail page
    $OUTPUT->send('mail');
} else {
    // Show login page
    $OUTPUT = $RCMAIL->json_init();
    $OUTPUT->set_pagetitle($RCMAIL->gettext('login'));
    $OUTPUT->send('login');
}