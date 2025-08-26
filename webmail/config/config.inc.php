<?php

/* Local configuration for Roundcube Webmail */

$config = [];

// Database connection string (DSN) for read+write operations
$config['db_dsnw'] = 'mysql://user:userpassword@project5_mysql/roundcube';

// IMAP server configuration
$config['imap_host'] = 'localhost:143';

// SMTP server configuration  
$config['smtp_host'] = 'localhost:25';
$config['smtp_user'] = '';
$config['smtp_pass'] = '';

// General configuration
$config['support_url'] = '';
$config['des_key'] = 'rcmail-!24ByteDESkey*Str';
$config['product_name'] = 'Roundcube Webmail';
$config['skin'] = 'elastic';
$config['language'] = 'ko_KR';

// Session configuration
$config['session_storage'] = 'db';
$config['session_lifetime'] = 10;

// Disable installer for security
$config['enable_installer'] = false;

// Plugins
$config['plugins'] = array(
    'archive',
    'no_imap',
);

// Disable IDN support (temporary fix for missing intl extension)
$config['use_idn'] = false;

// Demo mode settings
$config['auto_create_user'] = true;
$config['mail_pagesize'] = 50;
$config['addressbook_pagesize'] = 50;

// Skip IMAP authentication errors for demo
$config['debug_level'] = 0;
$config['imap_debug'] = false;
$config['smtp_debug'] = false;
$config['sql_debug'] = false;

// IMAP connection options to bypass SSL errors
$config['imap_conn_options'] = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ),
);

// Use localhost for demo (GreenMail test server)
$config['imap_host'] = 'localhost:143';
$config['smtp_host'] = 'localhost:3025';

// Authentication settings
$config['login_lc'] = 2; // lowercase username and domain
$config['login_autocomplete'] = 2;
$config['login_username_filter'] = 'email';

// Skip IMAP capabilities check
$config['imap_force_caps'] = true;
$config['imap_disabled_caps'] = array();

// Allow login without IMAP for demo
$config['default_host'] = 'localhost';
$config['default_port'] = 143;
$config['imap_auth_type'] = 'PLAIN';
$config['imap_delimiter'] = '.';
$config['imap_timeout'] = 1;
$config['imap_auth_cid'] = null;
$config['imap_auth_pw'] = null;

// Message settings for demo
$config['enable_spellcheck'] = false;
$config['show_images'] = 2; // always show images