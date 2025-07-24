<?php
/**
 * Demo login page for Roundcube
 * Bypasses IMAP authentication for demo purposes
 */

// Set demo mode
define('DEMO_MODE', true);

// Load Roundcube
require_once 'program/include/iniset.php';

$RCMAIL = rcmail::get_instance(0, $GLOBALS['env']);

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['_user'])) {
    $username = $_POST['_user'];
    $password = $_POST['_pass'];
    
    // Create or get user
    $user = rcube_user::query($username, 'demo');
    if (!$user) {
        $user = rcube_user::create($username, 'demo');
    }
    
    if ($user) {
        // Set session
        $_SESSION['user_id'] = $user->ID;
        $_SESSION['username'] = $username;
        $_SESSION['storage_host'] = 'demo';
        $_SESSION['password'] = $RCMAIL->encrypt($password);
        $_SESSION['demo_mode'] = true;
        
        $RCMAIL->set_user($user);
        
        // Redirect to mail
        header('Location: ./?_task=mail');
        exit;
    }
}

// Show regular login page
header('Location: ./');
exit;