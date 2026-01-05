<?php

/**
 * Demo Mode Plugin
 * 
 * This plugin allows Roundcube to work without a real IMAP server
 * for demonstration purposes.
 */
class demo_mode extends rcube_plugin
{
    public $task = '.*';
    private $demo_folders = array('INBOX', 'Drafts', 'Sent', 'Trash', 'Junk');
    
    function init()
    {
        $this->add_hook('authenticate', array($this, 'authenticate'));
        $this->add_hook('storage_connect', array($this, 'storage_connect'));
        $this->add_hook('storage_init', array($this, 'storage_init'));
        $this->add_hook('storage_folders', array($this, 'storage_folders'));
        $this->add_hook('list_messages', array($this, 'list_messages'));
        $this->add_hook('message_load', array($this, 'message_load'));
        $this->add_hook('template_object_loginform', array($this, 'loginform'));
    }
    
    function authenticate($args)
    {
        // Always authenticate successfully in demo mode
        if (!empty($args['user'])) {
            $args['valid'] = true;
            $args['abort'] = true;
            
            // Create user session
            $rcmail = rcmail::get_instance();
            $user = rcube_user::query($args['user'], $args['host']);
            
            if (!$user) {
                $user = rcube_user::create($args['user'], $args['host']);
            }
            
            if ($user) {
                $rcmail->set_user($user);
                $_SESSION['user_id'] = $user->ID;
                $_SESSION['username'] = $args['user'];
                $_SESSION['storage_host'] = $args['host'];
                $_SESSION['password'] = $rcmail->encrypt($args['pass']);
                
                // Set demo flag
                $_SESSION['demo_mode'] = true;
            }
        }
        
        return $args;
    }
    
    function storage_connect($args)
    {
        if ($_SESSION['demo_mode']) {
            $args['abort'] = true;
        }
        return $args;
    }
    
    function storage_init($args)
    {
        if ($_SESSION['demo_mode']) {
            $args['abort'] = true;
        }
        return $args;
    }
    
    function storage_folders($args)
    {
        if ($_SESSION['demo_mode']) {
            $args['folders'] = $this->demo_folders;
        }
        return $args;
    }
    
    function list_messages($args)
    {
        if ($_SESSION['demo_mode']) {
            // Return demo messages
            $args['messages'] = array();
            $demo_messages = array(
                array(
                    'uid' => 1,
                    'subject' => '환영합니다! Roundcube 웹메일입니다',
                    'from' => 'admin@example.com',
                    'to' => $_SESSION['username'],
                    'date' => date('Y-m-d H:i:s'),
                    'size' => 1024,
                    'flags' => array('SEEN')
                ),
                array(
                    'uid' => 2,
                    'subject' => '데모 메시지 - 회의 일정 안내',
                    'from' => 'meeting@example.com',
                    'to' => $_SESSION['username'],
                    'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'size' => 2048,
                    'flags' => array()
                ),
                array(
                    'uid' => 3,
                    'subject' => '중요: 시스템 점검 안내',
                    'from' => 'system@example.com',
                    'to' => $_SESSION['username'],
                    'date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                    'size' => 1536,
                    'flags' => array('FLAGGED')
                )
            );
            
            foreach ($demo_messages as $msg) {
                $args['messages'][] = (object)$msg;
            }
            
            $args['count'] = count($args['messages']);
            $args['exists'] = true;
        }
        return $args;
    }
    
    function message_load($args)
    {
        if ($_SESSION['demo_mode']) {
            $args['object']->headers = new stdClass();
            $args['object']->headers->subject = '데모 메시지';
            $args['object']->headers->from = 'demo@example.com';
            $args['object']->headers->to = $_SESSION['username'];
            $args['object']->headers->date = date('r');
            $args['object']->body = '이것은 데모 메시지입니다. 실제 메일 서버와 연결되어 있지 않습니다.';
        }
        return $args;
    }
    
    function loginform($args)
    {
        $args['content'] = str_replace(
            '</form>',
            '<div class="demo-notice" style="margin-top:10px;padding:10px;background:#d4edda;border:1px solid #c3e6cb;color:#155724;border-radius:4px;">
                <strong>데모 모드:</strong> 아무 이메일/비밀번호로 로그인 가능합니다.<br>
                UI 확인용이며 실제 메일 서버와 연결되어 있지 않습니다.
            </div></form>',
            $args['content']
        );
        return $args;
    }
}