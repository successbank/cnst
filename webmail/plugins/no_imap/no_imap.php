<?php

/**
 * No IMAP Plugin - Skip IMAP connections for demo mode
 */
class no_imap extends rcube_plugin
{
    public $task = 'mail|settings|addressbook|utils';
    
    function init()
    {
        $this->add_hook('storage_init', array($this, 'storage_init'));
        $this->add_hook('storage_connect', array($this, 'storage_connect'));
        $this->add_hook('storage_connected', array($this, 'storage_connected'));
        $this->add_hook('message_list', array($this, 'message_list'));
    }
    
    function storage_init($args)
    {
        if (!empty($_SESSION['user_id'])) {
            $args['abort'] = true;
        }
        return $args;
    }
    
    function storage_connect($args)
    {
        if (!empty($_SESSION['user_id'])) {
            $args['abort'] = true;
            $args['success'] = true;
        }
        return $args;
    }
    
    function storage_connected($args)
    {
        $args['return'] = true;
        return $args;
    }
    
    function message_list($args)
    {
        // Return empty message list
        $args['messages'] = array();
        $args['count'] = 0;
        return $args;
    }
}