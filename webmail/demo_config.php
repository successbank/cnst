<?php
/**
 * Demo mode configuration override
 */

// Override IMAP login to always succeed for demo
if (!empty($_POST['_user']) && !empty($_POST['_pass'])) {
    // Create fake IMAP connection class
    class demo_imap_generic {
        public $error = false;
        public $errornum = 0;
        public $conn = true;
        
        function connect($host, $user, $pass, $options = array()) {
            return true;
        }
        
        function connected() {
            return true;
        }
        
        function closeConnection() {
            return true;
        }
        
        function capability($cap = null) {
            return array('IMAP4', 'IMAP4rev1', 'NAMESPACE', 'QUOTA');
        }
        
        function getNamespace() {
            return array(
                'personal' => array(array('', '.')),
                'shared' => array(),
                'other' => array()
            );
        }
        
        function listMailboxes($ref, $mailbox) {
            return array('INBOX', 'Drafts', 'Sent', 'Trash', 'Junk');
        }
        
        function getHierarchyDelimiter() {
            return '.';
        }
        
        function select($mailbox) {
            return true;
        }
        
        function countMessages($mailbox) {
            return 3;
        }
        
        function countUnseen($mailbox) {
            return 1;
        }
        
        function search($mailbox, $criteria) {
            return array(1, 2, 3);
        }
        
        function fetchHeaders($mailbox, $id) {
            $headers = new stdClass();
            $headers->subject = 'Demo Message ' . $id;
            $headers->from = 'demo@example.com';
            $headers->to = $_SESSION['username'];
            $headers->date = date('r');
            return $headers;
        }
        
        function fetchHeader($mailbox, $id) {
            return $this->fetchHeaders($mailbox, $id);
        }
        
        function fetchStructure($mailbox, $id) {
            $struct = new stdClass();
            $struct->type = 'text';
            $struct->subtype = 'plain';
            return $struct;
        }
        
        function handlePartBody($mailbox, $id, $part, $mode) {
            return 'This is a demo message. The webmail interface is working correctly!';
        }
    }
    
    // Replace IMAP connection with demo connection
    $_SESSION['demo_mode'] = true;
}