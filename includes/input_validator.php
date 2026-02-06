<?php
/**
 * 입력값 검증 - SQL 인젝션 및 자동화 공격 패턴 탐지
 */

function containsSuspiciousPattern($value) {
    if (!is_string($value) || strlen($value) === 0) {
        return false;
    }

    $patterns = [
        // SQL 인젝션 기본 패턴
        '/\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER)\b.*\b(FROM|INTO|TABLE|WHERE)\b/i',
        // 시간 지연 공격 (Blind SQLi)
        '/DBMS_PIPE|RECEIVE_MESSAGE|PG_SLEEP|WAITFOR\s+DELAY|BENCHMARK\s*\(/i',
        '/\bsleep\s*\(\d+\)/i',
        // 문자열 조작 함수 악용
        '/\bCHR\s*\(\d+\)/i',
        '/\bORD\s*\(/i',
        '/\bEXEC\s*\(/i',
        '/\bXOR\s*\(/i',
        // 조건식 인젝션
        '/(\'|")\s*(OR|AND)\s+\d+\s*[=<>]/i',
        // SQL 주석
        '/--\s*$/m',
        '/\/\*.*\*\//s',
        // Acunetix/sqlmap 시그니처
        '/acunetix|sqlmap|nmap|nikto|havij/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value)) {
            return true;
        }
    }
    return false;
}

function validateQuoteInput($fields) {
    foreach ($fields as $name => $value) {
        if (is_string($value) && containsSuspiciousPattern($value)) {
            return ['valid' => false, 'message' => '허용되지 않는 입력값이 포함되어 있습니다.'];
        }
    }
    return ['valid' => true];
}

/**
 * 세션 기반 Rate Limiting
 * @param string $key 제한 키 (예: 'quote_submit')
 * @param int $maxAttempts 시간 윈도우 내 최대 허용 횟수
 * @param int $windowSeconds 시간 윈도우 (초)
 * @return bool true이면 허용, false이면 제한 초과
 */
function checkRateLimit($key = 'quote_submit', $maxAttempts = 3, $windowSeconds = 60) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $sessionKey = $key . '_times';
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = [];
    }

    $now = time();
    // 윈도우 밖의 오래된 기록 제거
    $_SESSION[$sessionKey] = array_filter(
        $_SESSION[$sessionKey],
        fn($t) => ($now - $t) < $windowSeconds
    );

    if (count($_SESSION[$sessionKey]) >= $maxAttempts) {
        return false;
    }

    $_SESSION[$sessionKey][] = $now;
    return true;
}
