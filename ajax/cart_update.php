<?php
/**
 * 견적 장바구니 항목 수정 (수량·단위·길이·요청사항)
 *
 * 소유자 검증은 QuoteCart::update() 안에서 WHERE 조건으로 수행되므로
 * 타인 항목 id 를 보내도 변경되지 않는다.
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
if (!checkRateLimit('cart_update', 60, 300)) {
    echo json_encode(['success' => false, 'message' => '요청이 너무 많습니다. 잠시 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDB();

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '수정할 항목이 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $fields = [];

    // 수량 (0 초과 999999 이하)
    if (isset($_POST['quantity'])) {
        $quantityRaw = trim((string)$_POST['quantity']);
        if ($quantityRaw === '' || !is_numeric($quantityRaw)) {
            echo json_encode(['success' => false, 'message' => '수량을 숫자로 입력해 주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $quantity = (float)$quantityRaw;
        if ($quantity <= 0 || $quantity > 999999) {
            echo json_encode(['success' => false, 'message' => '수량은 0보다 크고 999999 이하여야 합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $fields['quantity'] = $quantity;
    }

    // 수량 단위는 제품 속성이므로 수정 대상이 아니다.
    // 담기 시점에 ajax/cart_add.php 가 제품 기준으로 판정해 저장한 값을 그대로 유지한다.
    // (화면에도 단위 변경 UI 가 없다. 직접 POST 로 바꾸는 경로만 차단한다)

    // 길이 (빈 값이면 NULL, 값이 있으면 0 초과 10000 이하)
    if (isset($_POST['length_value'])) {
        $lengthRaw = trim((string)$_POST['length_value']);
        if ($lengthRaw === '') {
            $fields['length_value'] = null;
        } else {
            if (!is_numeric($lengthRaw)) {
                echo json_encode(['success' => false, 'message' => '길이를 숫자로 입력해 주세요.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $length_value = (float)$lengthRaw;
            if ($length_value <= 0 || $length_value > 10000) {
                echo json_encode(['success' => false, 'message' => '길이는 0보다 크고 10000 이하여야 합니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $fields['length_value'] = $length_value;
        }
    }

    // 길이 단위
    if (isset($_POST['length_unit'])) {
        $fields['length_unit'] = QuoteCart::normalizeLengthUnit($_POST['length_unit']);
    }

    // 항목별 요청사항 (500자 제한, 빈 값이면 NULL)
    if (isset($_POST['note'])) {
        $note = trim((string)$_POST['note']);
        $fields['note'] = ($note === '') ? null : mb_substr($note, 0, 500);
    }

    if (empty($fields)) {
        echo json_encode(['success' => false, 'message' => '수정할 내용이 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $changed = QuoteCart::update($pdo, $id, $fields);
    if (!$changed) {
        // 소유자가 아니거나 값이 동일해 실제 변경이 없는 경우
        echo json_encode(['success' => false, 'message' => '수정할 항목을 찾을 수 없거나 변경된 내용이 없습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success'    => true,
        'message'    => '수정되었습니다.',
        'cart_count' => QuoteCart::count($pdo),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('cart_update.php 실패: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '수정에 실패했습니다. 잠시 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
}
