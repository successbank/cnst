<?php
/**
 * 견적 장바구니 제출 (견적요청 접수)
 *
 * 선택한 장바구니 항목을 product_quotes / product_quote_items 로 옮기고
 * 제출된 장바구니 행을 삭제한다. 전 과정은 하나의 트랜잭션으로 처리한다.
 *
 * 항목 내용은 클라이언트 값을 쓰지 않고 QuoteCart::itemsByIds() 로 서버에서 다시 읽는다.
 * 접수 메일은 commit 이후(트랜잭션 밖)에 발송하며, 메일 실패가 접수 실패가 되지 않는다.
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
require_once '../includes/ProductQuoteMailer.php';

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

// Rate limit (10분 내 5회)
// 세션 기준만 쓰면 쿠키를 버리고 재요청하는 것만으로 우회되어
// 임의 주소로 접수 메일을 반복 발송시킬 수 있으므로 IP 기준을 함께 적용한다.
if (!checkRateLimit('quote_submit_v2', 5, 600) || !checkIpRateLimit('quote_submit_v2', 5, 600)) {
    echo json_encode(['success' => false, 'message' => '견적요청이 너무 잦습니다. 잠시 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = null;

try {
    $pdo = getDB();

    // ── 제출 대상 항목 id 수집 ───────────────────────────────
    $rawIds = $_POST['ids'] ?? '';
    if (is_array($rawIds)) {
        $ids = $rawIds;
    } else {
        $ids = explode(',', (string)$rawIds);
    }
    $ids = array_values(array_filter(array_map('intval', $ids), function ($v) {
        return $v > 0;
    }));

    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => '견적요청할 항목을 선택해 주세요.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 서버에서 다시 읽는다 (소유자 조건 포함)
    $items = QuoteCart::itemsByIds($pdo, $ids);
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => '선택한 항목을 찾을 수 없습니다. 장바구니를 새로고침해 주세요.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 요청자 정보 결정 ─────────────────────────────────────
    $memberId = !empty($_SESSION['member_id']) ? (int)$_SESSION['member_id'] : null;

    $customer_name = isset($_POST['customer_name']) ? trim((string)$_POST['customer_name']) : '';
    $phone         = isset($_POST['phone']) ? trim((string)$_POST['phone']) : '';
    $email         = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $company       = isset($_POST['company']) ? trim((string)$_POST['company']) : '';
    $notes         = isset($_POST['notes']) ? trim((string)$_POST['notes']) : '';

    if ($memberId) {
        // 회원: 회원정보를 기본값으로 쓰되, 폼에서 온 값이 있으면 그 값을 우선한다.
        $stmt = $pdo->prepare("SELECT name, phone, email, company FROM members WHERE id = ? AND is_active = 1");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            // 탈퇴/비활성 회원이면 비회원 제출로 처리한다.
            $memberId = null;
        } else {
            if ($customer_name === '') { $customer_name = trim((string)$member['name']); }
            if ($phone === '')         { $phone         = trim((string)$member['phone']); }
            if ($email === '')         { $email         = trim((string)$member['email']); }
            if ($company === '')       { $company       = trim((string)$member['company']); }
        }
    }

    // 이름 필수
    if ($customer_name === '') {
        echo json_encode(['success' => false, 'message' => '이름을 입력해 주세요.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (mb_strlen($customer_name) > 100) {
        $customer_name = mb_substr($customer_name, 0, 100);
    }

    // 연락처 필수 + 형식 검증
    if ($phone === '') {
        echo json_encode(['success' => false, 'message' => '연락처를 입력해 주세요.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!preg_match('/^[0-9\-\+\s]{8,20}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => '연락처 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 이메일은 선택. 입력된 경우에만 형식 검증
    if ($email !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
            echo json_encode(['success' => false, 'message' => '이메일 형식이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if (mb_strlen($company) > 200) {
        $company = mb_substr($company, 0, 200);
    }
    if ($notes !== '') {
        $notes = mb_substr($notes, 0, 2000);
    }

    // ── products 요약 문자열 ("제품명 외 N건") ────────────────
    $firstName = (string)$items[0]['product_name'];
    $itemCount = count($items);
    $summary   = ($itemCount > 1)
        ? $firstName . ' 외 ' . ($itemCount - 1) . '건'
        : $firstName;
    if (mb_strlen($summary) > 500) {
        $summary = mb_substr($summary, 0, 500);
    }

    // ── 저장 (트랜잭션) ──────────────────────────────────────
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO product_quotes
            (member_id, customer_name, company, phone, email, products, notes,
             total_amount, status, source)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'pending', 'v2')
    ");
    $stmt->execute([
        $memberId,
        $customer_name,
        $company !== '' ? $company : null,
        $phone,
        $email !== '' ? $email : null,
        $summary,
        $notes !== '' ? $notes : null,
    ]);

    $quoteId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare("
        INSERT INTO product_quote_items
            (quote_id, product_id, product_name, product_spec, origin, material,
             length_value, length_unit, quantity, quantity_unit, unit_price, subtotal, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?)
    ");

    foreach ($items as $item) {
        $itemStmt->execute([
            $quoteId,
            !empty($item['product_id']) ? (int)$item['product_id'] : null,
            $item['product_name'],
            $item['product_spec'],
            $item['origin'],
            $item['material'],
            ($item['length_value'] === null || $item['length_value'] === '') ? null : $item['length_value'],
            !empty($item['length_unit']) ? $item['length_unit'] : 'M',
            $item['quantity'],
            !empty($item['quantity_unit']) ? $item['quantity_unit'] : 'EA',
            $item['note'],
        ]);
    }

    // 제출한 장바구니 행 삭제 (조회에 사용한 실제 id 만)
    $submittedIds = array_map(function ($it) {
        return (int)$it['id'];
    }, $items);
    $placeholders = implode(',', array_fill(0, count($submittedIds), '?'));
    $delStmt = $pdo->prepare("DELETE FROM quote_cart_items WHERE id IN ($placeholders)");
    $delStmt->execute($submittedIds);

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('submit_quote_cart.php 실패: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '견적요청 접수에 실패했습니다. 잠시 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 접수 메일 발송 (트랜잭션 밖, 실패해도 접수는 성공) ────────
try {
    ProductQuoteMailer::sendReceipt($pdo, $quoteId);
} catch (Throwable $e) {
    error_log('submit_quote_cart.php 접수메일 발송 실패(quote_id=' . $quoteId . '): ' . $e->getMessage());
}

echo json_encode([
    'success'  => true,
    'message'  => '견적요청이 접수되었습니다.',
    'quote_id' => $quoteId,
], JSON_UNESCAPED_UNICODE);
