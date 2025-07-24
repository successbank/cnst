<?php
/**
 * Fix for missing PHP intl extension
 * Define missing constants to prevent fatal errors
 */

if (!defined('INTL_IDNA_VARIANT_UTS46')) {
    define('INTL_IDNA_VARIANT_UTS46', 1);
}

if (!defined('IDNA_NONTRANSITIONAL_TO_ASCII')) {
    define('IDNA_NONTRANSITIONAL_TO_ASCII', 16);
}

if (!defined('IDNA_NONTRANSITIONAL_TO_UNICODE')) {
    define('IDNA_NONTRANSITIONAL_TO_UNICODE', 32);
}

// Mock idn_to_ascii function if not exists
if (!function_exists('idn_to_ascii')) {
    function idn_to_ascii($domain, $options = 0, $variant = 1, &$idna_info = null) {
        // Simple passthrough for demo mode
        return $domain;
    }
}

// Mock idn_to_utf8 function if not exists
if (!function_exists('idn_to_utf8')) {
    function idn_to_utf8($domain, $options = 0, $variant = 1, &$idna_info = null) {
        // Simple passthrough for demo mode
        return $domain;
    }
}