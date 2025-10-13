<?php
/**
 * 인증 토큰 관리 - Remember Me 기능
 * Selector-Validator 패턴 사용
 */

/**
 * Remember Me 토큰 생성
 * @param int $member_id 회원 ID
 * @param int $duration 유효 시간(초)
 * @return array|false ['selector' => string, 'validator' => string] 또는 false
 */
function createRememberToken($member_id, $duration = 2592000) { // 기본 30일
    global $pdo;

    try {
        // 1. Selector 생성 (12자리, 데이터베이스 조회용)
        $selector = bin2hex(random_bytes(16));

        // 2. Validator 생성 (32자리, 인증용)
        $validator = bin2hex(random_bytes(32));

        // 3. Validator 해시화 (데이터베이스 저장용)
        $hashed_validator = password_hash($validator, PASSWORD_DEFAULT);

        // 4. 만료 시간 계산
        $expires_at = date('Y-m-d H:i:s', time() + $duration);

        // 5. IP 및 User Agent 수집
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // 6. 데이터베이스에 저장
        $stmt = $pdo->prepare("
            INSERT INTO auth_tokens (member_id, selector, hashed_validator, expires_at, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $member_id,
            $selector,
            $hashed_validator,
            $expires_at,
            $ip_address,
            $user_agent
        ]);

        return [
            'selector' => $selector,
            'validator' => $validator
        ];

    } catch(PDOException $e) {
        error_log("Remember token creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Remember Me 토큰 검증
 * @param string $selector 토큰 선택자
 * @param string $validator 토큰 검증자
 * @return int|false 회원 ID 또는 false
 */
function verifyRememberToken($selector, $validator) {
    global $pdo;

    try {
        // 1. Selector로 토큰 조회
        $stmt = $pdo->prepare("
            SELECT id, member_id, hashed_validator, expires_at
            FROM auth_tokens
            WHERE selector = ?
        ");
        $stmt->execute([$selector]);
        $token = $stmt->fetch();

        if (!$token) {
            return false;
        }

        // 2. 만료 확인
        if (strtotime($token['expires_at']) < time()) {
            // 만료된 토큰 삭제
            deleteRememberToken($selector);
            return false;
        }

        // 3. Validator 검증
        if (!password_verify($validator, $token['hashed_validator'])) {
            // 검증 실패 시 보안을 위해 토큰 삭제
            deleteRememberToken($selector);
            return false;
        }

        // 4. 검증 성공 - 토큰 갱신 (단일 사용 원칙)
        deleteRememberToken($selector);

        return (int)$token['member_id'];

    } catch(PDOException $e) {
        error_log("Remember token verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Remember Me 토큰 삭제
 * @param string $selector 토큰 선택자
 * @return bool
 */
function deleteRememberToken($selector) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE selector = ?");
        $stmt->execute([$selector]);
        return true;
    } catch(PDOException $e) {
        error_log("Remember token deletion error: " . $e->getMessage());
        return false;
    }
}

/**
 * 회원의 모든 Remember Me 토큰 삭제 (로그아웃 시)
 * @param int $member_id 회원 ID
 * @return bool
 */
function deleteAllRememberTokens($member_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE member_id = ?");
        $stmt->execute([$member_id]);
        return true;
    } catch(PDOException $e) {
        error_log("All tokens deletion error: " . $e->getMessage());
        return false;
    }
}

/**
 * Remember Me 쿠키 설정
 * @param string $selector 토큰 선택자
 * @param string $validator 토큰 검증자
 * @param int $duration 유효 시간(초)
 * @return bool
 */
function setRememberCookie($selector, $validator, $duration = 2592000) {
    $cookie_value = $selector . ':' . $validator;
    $expires = time() + $duration;

    // 보안 쿠키 설정
    return setcookie(
        'remember_me',
        $cookie_value,
        [
            'expires' => $expires,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']), // HTTPS에서만 전송
            'httponly' => true, // JavaScript 접근 불가
            'samesite' => 'Strict' // CSRF 방어
        ]
    );
}

/**
 * Remember Me 쿠키 삭제
 * @return bool
 */
function deleteRememberCookie() {
    return setcookie(
        'remember_me',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

/**
 * Remember Me 쿠키에서 토큰 파싱
 * @return array|false ['selector' => string, 'validator' => string] 또는 false
 */
function parseRememberCookie() {
    if (!isset($_COOKIE['remember_me'])) {
        return false;
    }

    $parts = explode(':', $_COOKIE['remember_me'], 2);

    if (count($parts) !== 2) {
        return false;
    }

    return [
        'selector' => $parts[0],
        'validator' => $parts[1]
    ];
}

/**
 * 만료된 토큰 정리 (크론잡으로 실행 권장)
 * @return int 삭제된 토큰 수
 */
function cleanupExpiredTokens() {
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    } catch(PDOException $e) {
        error_log("Token cleanup error: " . $e->getMessage());
        return 0;
    }
}

/**
 * 회원의 세션 설정 가져오기
 * @param int $member_id 회원 ID
 * @return array
 */
function getMemberSessionPreferences($member_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT session_duration, remember_me_enabled
            FROM member_session_preferences
            WHERE member_id = ?
        ");
        $stmt->execute([$member_id]);
        $prefs = $stmt->fetch();

        if (!$prefs) {
            return [
                'session_duration' => 0,
                'remember_me_enabled' => 1
            ];
        }

        return $prefs;

    } catch(PDOException $e) {
        error_log("Get session preferences error: " . $e->getMessage());
        return [
            'session_duration' => 0,
            'remember_me_enabled' => 1
        ];
    }
}

/**
 * 회원의 세션 설정 저장
 * @param int $member_id 회원 ID
 * @param int $session_duration 세션 유지 시간(초), 0=기본값, -1=종일
 * @param bool $remember_me_enabled 로그인 유지 활성화
 * @return bool
 */
function saveMemberSessionPreferences($member_id, $session_duration, $remember_me_enabled = true) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO member_session_preferences (member_id, session_duration, remember_me_enabled)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                session_duration = VALUES(session_duration),
                remember_me_enabled = VALUES(remember_me_enabled),
                updated_at = CURRENT_TIMESTAMP
        ");

        $stmt->execute([
            $member_id,
            $session_duration,
            $remember_me_enabled ? 1 : 0
        ]);

        return true;

    } catch(PDOException $e) {
        error_log("Save session preferences error: " . $e->getMessage());
        return false;
    }
}

/**
 * 세션 지속 시간 옵션
 * @return array
 */
function getSessionDurationOptions() {
    return [
        3600 => '1시간',
        10800 => '3시간',
        21600 => '6시간',
        43200 => '12시간',
        86400 => '24시간',
        604800 => '7일',
        2592000 => '30일',
        -1 => '종일 (오전 0시까지)'
    ];
}

/**
 * 종일 옵션의 실제 초 계산
 * @return int
 */
function calculateAllDayDuration() {
    $now = time();
    $midnight = strtotime('tomorrow 00:00:00');
    return $midnight - $now;
}
?>
