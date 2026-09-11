<?php
/**
 * 견적 장바구니 항목 삭제
 *
 * ids 는 배열(ids[]) 또는 콤마 구분 문자열 모두 허용한다.
 * 소유자 검증은 QuoteCart::remove() 안에서 WHERE 조건으로 수행된다.
 *
 * 문서: dev_docs/PRD_product_quote_v2.md (7.3절)
 */

require_once '../db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/csrf.php';
require_once '../includes/input_validator.php';
require_once '../includes/QuoteCart.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!verifyCsrfToken(false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '보안 토큰이 유효하지 않습니다. 새로고침 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Rate limit (5분 내 60회)
if (!checkRateLimit('cart_remove', 60, 300)) {
    echo json_encode(['success' => false, 'message' => '요청이 너무 많습니다. 잠시 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDB();

    // ids 는 배열 또는 콤마 문자열
    $raw = $_POST['ids'] ?? ($_POST['id'] ?? '');
    if (is_array($raw)) {
        $ids = $raw;
    } else {
        $ids = explode(',', (string)$raw);
    }

    $ids = array_values(array_filter(array_map('intval', $ids), function ($v) {
        return $v > 0;
    }));

    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => '삭제할 항목을 선택해 주세요.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 한 번에 삭제 가능한 최대 개수 (장바구니 상한과 동일)
    if (count($ids) > QuoteCart::MAX_ITEMS) {
        $ids = array_slice($ids, 0, QuoteCart::MAX_ITEMS);
    }

    $deleted = QuoteCart::remove($pdo, $ids);

    echo json_encode([
        'success'    => true,
        'message'    => $deleted > 0 ? $deleted . '개 항목을 삭제했습니다.' : '삭제할 항목을 찾을 수 없습니다.',
        'deleted'    => $deleted,
        'cart_count' => QuoteCart::count($pdo),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('cart_remove.php 실패: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '삭제에 실패했습니다. 잠시 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
}
