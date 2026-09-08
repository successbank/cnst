<?php
/**
 * CSRF 토큰 보호 공통 함수
 */

/**
 * CSRF 토큰 생성
 */
function generateCsrfToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $now = time();
    // 토큰이 없거나 1시간 만료된 경우 재생성
    if (empty($_SESSION["csrf_token"]) || empty($_SESSION["csrf_token_time"]) || ($now - $_SESSION["csrf_token_time"]) > 3600) {
        // 직전 토큰 보관: 재생성 이전에 열어둔 탭의 폼 제출을 1세대까지 허용 (장시간 글 작성 대응)
        if (!empty($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token_prev"] = $_SESSION["csrf_token"];
        }
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        $_SESSION["csrf_token_time"] = $now;
    }
    return $_SESSION["csrf_token"];
}

/**
 * CSRF hidden 필드 출력
 */
function csrfField() {
    $token = generateCsrfToken();
    return "<input type=\"hidden\" name=\"csrf_token\" value=\"" . htmlspecialchars($token) . "\">";
}

/**
 * CSRF 토큰 검증
 * @param bool $die 실패 시 die 할지 여부
 * @return bool
 */
function verifyCsrfToken($die = true) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // POST, PUT, DELETE 요청만 검증
    if (!in_array($_SERVER["REQUEST_METHOD"], ["POST", "PUT", "DELETE"])) {
        return true;
    }
    
    // post_max_size 초과 시 PHP가 $_POST를 통째로 비워 토큰까지 사라짐
    // → CSRF 오류로 위장되므로 정확한 원인을 구분해서 안내
    $contentLength = (int)($_SERVER["CONTENT_LENGTH"] ?? 0);
    if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_POST) && empty($_FILES) && $contentLength > 0) {
        $postMaxBytes = parseIniSize(ini_get("post_max_size"));
        if ($postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            if ($die) {
                http_response_code(413);
                die(json_encode(["error" => "전송 데이터가 서버 허용 크기(" . ini_get("post_max_size") . ")를 초과했습니다. 본문에 포함된 이미지 크기를 줄여주세요."], JSON_UNESCAPED_UNICODE));
            }
            return false;
        }
    }

    $token = null;

    // 1. POST body에서 확인
    if (isset($_POST["csrf_token"])) {
        $token = $_POST["csrf_token"];
    }
    // 2. HTTP 헤더에서 확인 (AJAX 요청용)
    elseif (isset($_SERVER["HTTP_X_CSRF_TOKEN"])) {
        $token = $_SERVER["HTTP_X_CSRF_TOKEN"];
    }

    $valid = !empty($token) && !empty($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token);

    // 재생성 직전 토큰 1세대까지 허용 (1시간 이상 열어둔 폼 제출 대응)
    if (!$valid && !empty($token) && !empty($_SESSION["csrf_token_prev"])) {
        $valid = hash_equals($_SESSION["csrf_token_prev"], $token);
    }

    if (!$valid) {
        if ($die) {
            http_response_code(403);
            die(json_encode(["error" => "CSRF 토큰이 유효하지 않습니다."]));
        }
        return false;
    }

    return true;
}

/**
 * php.ini 크기 표기(20M, 1G 등)를 바이트로 변환
 */
function parseIniSize($size) {
    $size = trim((string)$size);
    if ($size === "" || $size === "-1") {
        return 0;
    }
    $unit = strtoupper(substr($size, -1));
    $value = (float)$size;
    switch ($unit) {
        case "G": return (int)($value * 1073741824);
        case "M": return (int)($value * 1048576);
        case "K": return (int)($value * 1024);
        default:  return (int)$value;
    }
}
