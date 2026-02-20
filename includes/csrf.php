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
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
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
    
    $token = null;
    
    // 1. POST body에서 확인
    if (isset($_POST["csrf_token"])) {
        $token = $_POST["csrf_token"];
    }
    // 2. HTTP 헤더에서 확인 (AJAX 요청용)
    elseif (isset($_SERVER["HTTP_X_CSRF_TOKEN"])) {
        $token = $_SERVER["HTTP_X_CSRF_TOKEN"];
    }
    
    if (empty($token) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $token)) {
        if ($die) {
            http_response_code(403);
            die(json_encode(["error" => "CSRF 토큰이 유효하지 않습니다."]));
        }
        return false;
    }
    
    return true;
}
