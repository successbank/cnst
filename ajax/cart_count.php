<?php
/**
 * 견적 장바구니 건수 조회 (헤더 뱃지용)
 *
 * 조회 전용이므로 CSRF 검증을 하지 않는다.
 * QuoteCart::count() 는 내부적으로 owner(false) 를 사용하므로
 * 단순 조회만으로 비회원에게 쿠키가 발급되지 않는다.
 *
 * 문서: dev_docs/PRD_product_quote_v2.md (7.3절)
 */

require_once '../db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/QuoteCart.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = getDB();

    echo json_encode([
        'success'    => true,
        'cart_count' => QuoteCart::count($pdo),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('cart_count.php 실패: ' . $e->getMessage());
    // 뱃지 조회 실패가 화면을 깨뜨리지 않도록 0 으로 응답한다.
    echo json_encode(['success' => false, 'cart_count' => 0, 'message' => '장바구니 정보를 불러오지 못했습니다.'], JSON_UNESCAPED_UNICODE);
}
