<?php
/**
 * 회원가입 아이디 실시간 중복체크 엔드포인트
 * - POST + CSRF 토큰 필수, Rate Limit 적용 (아이디 열거 공격 방지)
 * - 응답: {"available": bool, "message": string}
 */
require_once '../db.php';
require_once '../includes/csrf.php';
require_once '../includes/input_validator.php';

header('Content-Type: application/json; charset=utf-8');

// 이미 로그인한 회원은 사용 대상 아님
if (isset($_SESSION['member_id'])) {
    echo json_encode(['available' => false, 'message' => '이미 로그인된 상태입니다.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['available' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

// CSRF 검증
if (!verifyCsrfToken(false)) {
    http_response_code(403);
    echo json_encode(['available' => false, 'message' => '잘못된 요청입니다. 페이지를 새로고침 해주세요.']);
    exit;
}

// Rate Limit: 60초 내 30회 (디바운스된 타이핑 기준 충분)
if (!checkRateLimit('check_userid', 30, 60)) {
    http_response_code(429);
    echo json_encode(['available' => false, 'message' => '요청이 너무 많습니다. 잠시 후 다시 시도해주세요.']);
    exit;
}

$user_id = trim($_POST['user_id'] ?? '');

// 형식 검증 (register.php와 동일 규칙: 영문/숫자 4~20자)
if (strlen($user_id) < 4 || strlen($user_id) > 20) {
    echo json_encode(['available' => false, 'message' => '아이디는 4~20자여야 합니다.']);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9]+$/', $user_id)) {
    echo json_encode(['available' => false, 'message' => '아이디는 영문자와 숫자만 사용 가능합니다.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE user_id = ?");
    $stmt->execute([$user_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['available' => false, 'message' => '이미 사용중인 아이디입니다.']);
    } else {
        echo json_encode(['available' => true, 'message' => '사용 가능한 아이디입니다.']);
    }
} catch (PDOException $e) {
    error_log('check_userid error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['available' => false, 'message' => '확인 중 오류가 발생했습니다. 다시 시도해주세요.']);
}
