<?php
/**
 * TOTP (Time-based One-Time Password) - RFC 6238
 * 순수 PHP 구현 (외부 라이브러리 없음)
 */

/**
 * Base32 인코딩 (RFC 4648)
 */
function base32Encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split($data) as $char) {
        $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $result = '';
    $chunks = str_split($binary, 5);
    foreach ($chunks as $chunk) {
        $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $result .= $alphabet[bindec($chunk)];
    }
    return $result;
}

/**
 * Base32 디코딩 (RFC 4648)
 */
function base32Decode(string $encoded): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $encoded = strtoupper(rtrim($encoded, '='));
    if ($encoded === '') {
        return '';
    }
    $binary = '';
    foreach (str_split($encoded) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) {
            return '';
        }
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $result = '';
    $chunks = str_split($binary, 8);
    foreach ($chunks as $chunk) {
        if (strlen($chunk) < 8) break;
        $result .= chr(bindec($chunk));
    }
    return $result;
}

/**
 * TOTP 시크릿 생성 (20바이트 = 160비트 → Base32 32자)
 */
function generateTotpSecret(): string {
    return base32Encode(random_bytes(20));
}

/**
 * TOTP 코드 계산
 */
function computeTotpCode(string $secret, int $timeCounter): string {
    $decodedSecret = base32Decode($secret);
    if ($decodedSecret === '') {
        return '';
    }

    // 8바이트 big-endian 타임스텝
    $msg = pack('N*', 0, $timeCounter);

    // HMAC-SHA1
    $hash = hash_hmac('sha1', $msg, $decodedSecret, true);

    // 동적 절단 (Dynamic Truncation)
    $offset = ord($hash[19]) & 0x0F;
    $code = (
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF)
    ) % 1000000;

    return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
}

/**
 * TOTP 코드 검증 (±window 허용)
 * @param string $secret Base32 인코딩된 시크릿
 * @param string $code 사용자가 입력한 6자리 코드
 * @param int $window 허용 윈도우 (기본 1 = ±30초, 총 90초)
 * @return bool
 */
function verifyTotpCode(string $secret, string $code, int $window = 1): bool {
    if (!preg_match('/^\d{6}$/', $code)) {
        return false;
    }
    $currentCounter = (int)floor(time() / 30);
    for ($i = -$window; $i <= $window; $i++) {
        $computed = computeTotpCode($secret, $currentCounter + $i);
        if ($computed !== '' && hash_equals($computed, $code)) {
            return true;
        }
    }
    return false;
}

/**
 * otpauth:// URI 생성 (QR 코드용)
 */
function getTotpUri(string $secret, string $username, string $issuer = '충남스틸'): string {
    $label = rawurlencode($issuer) . ':' . rawurlencode($username);
    $params = http_build_query([
        'secret' => $secret,
        'issuer' => $issuer,
        'algorithm' => 'SHA1',
        'digits' => '6',
        'period' => '30',
    ]);
    return 'otpauth://totp/' . $label . '?' . $params;
}

/**
 * 복구 코드 10개 생성
 * @return array ['plain' => [...], 'hashed' => [...]]
 */
function generateBackupCodes(int $count = 10): array {
    $plain = [];
    $hashed = [];
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 혼동 문자 제외 (I,O,0,1)
    for ($i = 0; $i < $count; $i++) {
        $code = '';
        for ($j = 0; $j < 8; $j++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $plain[] = $code;
        $hashed[] = password_hash($code, PASSWORD_BCRYPT);
    }
    return ['plain' => $plain, 'hashed' => $hashed];
}

/**
 * 복구 코드 검증 (1회용 - 사용 시 해당 코드 제거)
 * @param string $code 사용자 입력 코드
 * @param array &$hashedCodes bcrypt 해시 배열 (참조, 사용된 코드 제거됨)
 * @return bool
 */
function verifyBackupCode(string $code, array &$hashedCodes): bool {
    $code = strtoupper(trim($code));
    foreach ($hashedCodes as $index => $hash) {
        if (password_verify($code, $hash)) {
            unset($hashedCodes[$index]);
            $hashedCodes = array_values($hashedCodes);
            return true;
        }
    }
    return false;
}
