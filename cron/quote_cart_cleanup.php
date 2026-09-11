#!/usr/bin/env php
<?php
/**
 * 비회원 견적 장바구니 정리 CRON 스크립트
 *
 * 비회원 장바구니는 쿠키(qcart, 30일)로만 식별되므로 쿠키가 만료되면 주인을 찾을 수 없는
 * 고아 행이 된다. 보관 기간(QuoteCart::COOKIE_DAYS)을 넘긴 비회원(cart_token) 행을 삭제한다.
 * 회원 장바구니(member_id)는 삭제 대상이 아니다.
 *
 * 사용법 (crontab 설정 예시 - 등록은 별도 작업):
 * # 매일 새벽 3시 30분 실행
 * 30 3 * * * docker exec project1_php php /var/www/html/cron/quote_cart_cleanup.php >> /home/cnst/www/html/webservice/logs/quote_cart_cleanup.log 2>&1
 *
 * 문서: dev_docs/PRD_product_quote_v2.md
 */

// CLI에서만 실행 허용
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// 타임존 설정
date_default_timezone_set('Asia/Seoul');

$basePath = dirname(__DIR__);
require_once $basePath . '/db.php';
require_once $basePath . '/includes/QuoteCart.php';

// 보관 일수 (QuoteCart 의 쿠키 유효기간과 동일하게 맞춘다)
$retentionDays = QuoteCart::COOKIE_DAYS;

// 한 번에 삭제할 행 수 (대량 삭제 시 락 시간을 줄이기 위해 분할 삭제)
$batchSize = 500;

echo "[" . date('Y-m-d H:i:s') . "] 비회원 견적 장바구니 정리 시작 (보관 {$retentionDays}일)\n";

try {
    $pdo = getDB();

    // 삭제 대상 건수 확인
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM quote_cart_items
        WHERE cart_token IS NOT NULL
          AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$retentionDays]);
    $target = (int)$stmt->fetchColumn();

    if ($target === 0) {
        echo "[" . date('Y-m-d H:i:s') . "] 삭제 대상이 없습니다. 종료.\n";
        exit(0);
    }

    echo "[" . date('Y-m-d H:i:s') . "] 삭제 대상: {$target}건\n";

    $delStmt = $pdo->prepare("
        DELETE FROM quote_cart_items
        WHERE cart_token IS NOT NULL
          AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        LIMIT {$batchSize}
    ");

    $deleted = 0;
    while (true) {
        $delStmt->execute([$retentionDays]);
        $rows = $delStmt->rowCount();
        $deleted += $rows;

        if ($rows < $batchSize) {
            break;
        }
    }

    echo "[" . date('Y-m-d H:i:s') . "] 삭제 완료: {$deleted}건\n";
    exit(0);

} catch (Throwable $e) {
    error_log('quote_cart_cleanup.php 실패: ' . $e->getMessage());
    echo "[" . date('Y-m-d H:i:s') . "] 오류가 발생해 정리를 중단했습니다. 상세 내용은 에러 로그를 확인하세요.\n";
    exit(1);
}
