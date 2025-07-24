<?php
/**
 * Standalone demo UI for Roundcube
 * Shows the mail interface without IMAP
 */

// Override session checks
define('RCMAIL_DEMO_MODE', true);
$_GET['_task'] = 'mail';

// Start session
session_start();

// Set minimal required session data
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'demo@example.com';
$_SESSION['task'] = 'mail';
$_SESSION['request_token'] = 'demo_token';
$_SESSION['auth_time'] = time();

// Load Roundcube
define('INSTALL_PATH', realpath(__DIR__) . '/');
require_once 'program/include/iniset.php';

// Override IMAP connection class
class rcube_imap_demo extends rcube_imap {
    public function connect($host, $user, $pass, $port = 143, $use_ssl = null) {
        return true;
    }
    
    public function connected() {
        return true;
    }
    
    public function get_error_code() {
        return 0;
    }
    
    public function get_error_str() {
        return '';
    }
    
    public function list_folders($root = '', $name = '*', $filter = null, $rights = null, $skip_sort = false) {
        return array('INBOX', 'Drafts', 'Sent', 'Trash', 'Junk');
    }
    
    public function get_folder($folder = null) {
        return 'INBOX';
    }
    
    public function count($folder = null, $mode = 'ALL', $force = false, $status = true) {
        return 0;
    }
    
    public function list_messages($folder = null, $page = null, $sort = null) {
        // Return empty message list structure
        $result = new rcube_result_index($folder, '');
        return $result;
    }
    
    public function get_message_headers($uid, $folder = null, $force = false) {
        return new rcube_message_header();
    }
    
    public function get_quota($folder = null) {
        return false;
    }
}

// Initialize Roundcube with demo mode
$RCMAIL = rcmail::get_instance(0, $GLOBALS['env']);

// Create demo user
$user = new rcube_user(1, array(
    'user_id' => 1,
    'username' => 'demo@example.com',
    'mail_host' => 'localhost',
    'created' => date('Y-m-d H:i:s'),
    'language' => 'ko_KR'
));

$RCMAIL->set_user($user);

// Replace storage with demo storage
$RCMAIL->storage = new rcube_imap_demo($RCMAIL);
$RCMAIL->storage->set_folder('INBOX');

// Set output type
$RCMAIL->output = new rcmail_output_html($RCMAIL->task, false);

// Set required variables
$RCMAIL->output->set_env('task', 'mail');
$RCMAIL->output->set_env('action', '');
$RCMAIL->output->set_env('comm_path', './');
$RCMAIL->output->set_env('mailbox', 'INBOX');
$RCMAIL->output->set_env('quota', false);
$RCMAIL->output->set_env('delimiter', '.');
$RCMAIL->output->set_env('threading', false);
$RCMAIL->output->set_env('threads', false);
$RCMAIL->output->set_env('preview_pane', true);
$RCMAIL->output->set_env('messages', array());
$RCMAIL->output->set_env('exists', 0);

// Add demo notice
$RCMAIL->output->show_message('데모 모드: UI 확인용입니다. 실제 메일 서버와 연결되어 있지 않습니다.', 'notice');

// Send the mail page
$RCMAIL->output->send('mail');