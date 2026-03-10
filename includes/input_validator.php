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

/**
 * IP 기반 Rate Limiting (세션 우회 방지)
 * 파일 기반으로 IP별 요청 횟수 추적 (WAF와 동일 방식)
 * @param string $key 제한 키 (예: 'product_quote')
 * @param int $maxAttempts 시간 윈도우 내 최대 허용 횟수
 * @param int $windowSeconds 시간 윈도우 (초)
 * @return bool true이면 허용, false이면 제한 초과
 */
function checkIpRateLimit($key, $maxAttempts = 10, $windowSeconds = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $dir = '/tmp/rate_limits/';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $file = $dir . md5($key . '_' . $ip) . '.json';
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $now = time();
    $data = array_filter($data ?? [], fn($t) => ($now - $t) < $windowSeconds);
    if (count($data) >= $maxAttempts) return false;
    $data[] = $now;
    file_put_contents($file, json_encode(array_values($data)), LOCK_EX);
    return true;
}

/**
 * validateQuoteInput의 범용 alias
 * 견적 외 게시판(위탁판매 등)에서도 동일한 검증 로직 사용
 */
function validateFormInput($fields) {
    return validateQuoteInput($fields);
}

/**
 * 스팸/봇 제출 탐지
 * 테스트 이메일, 테스트 전화번호, 숫자만 이름 등 자동화 스팸 패턴 탐지
 * @param array $data 검사할 폼 데이터 (email, phone, writer, company 등)
 * @return array ['spam' => bool, 'reason' => string]
 */
function isSpamSubmission($data) {
    // 테스트 이메일 도메인 차단
    $email = $data['email'] ?? $data['contact_email'] ?? '';
    if (!empty($email)) {
        $spamEmailPatterns = [
            '/\.tst$/i',
            '/\.test$/i',
            '/@example\.(com|org|net)$/i',
            '/@test\./i',
            '/sample@email/i',
        ];
        foreach ($spamEmailPatterns as $pattern) {
            if (preg_match($pattern, $email)) {
                return ['spam' => true, 'reason' => '유효하지 않은 이메일 주소입니다.'];
            }
        }
    }

    // 테스트 전화번호 패턴 차단
    $phone = $data['phone'] ?? $data['contact_phone'] ?? '';
    if (!empty($phone)) {
        $spamPhonePatterns = [
            '/555-666/',
            '/555-555/',
            '/000-000/',
            '/123-456/',
        ];
        foreach ($spamPhonePatterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return ['spam' => true, 'reason' => '유효하지 않은 연락처입니다.'];
            }
        }
    }

    // writer가 숫자만인 경우 차단
    $writer = $data['writer'] ?? '';
    if (!empty($writer) && preg_match('/^\d+$/', $writer)) {
        return ['spam' => true, 'reason' => '작성자명을 올바르게 입력해주세요.'];
    }

    // 회사명에 알려진 스캐너 시그니처
    $company = $data['company'] ?? $data['company_name'] ?? '';
    if (!empty($company) && preg_match('/acunetix|sqlmap|nmap|nikto|havij|nessus/i', $company)) {
        return ['spam' => true, 'reason' => '허용되지 않는 입력값입니다.'];
    }

    return ['spam' => false, 'reason' => ''];
}
