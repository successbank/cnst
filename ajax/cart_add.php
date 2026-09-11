<?php
/**
 * 견적 장바구니 담기
 *
 * 제품 상세페이지(견적요청 모드)에서 원산지/재질/길이/수량을 선택해 담는다.
 * 클라이언트가 보낸 제품명·규격은 신뢰하지 않고 product_id 로 products 를 재조회해 서버에서 채운다.
 * 원산지·재질은 해당 제품의 available_origins / available_materials 화이트리스트로 검증한다.
 *
 * 문서: dev_docs/PRD_product_quote_v2.md (7.3절)
 */

// db.php 가 세션 쿠키 보안 옵션을 설정하므로 세션 시작보다 먼저 로드한다.
require_once '../db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/csrf.php';
require_once '../includes/input_validator.php';
require_once '../includes/QuoteCart.php';
require_once '../includes/product_unit.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// CSRF 검증
if (!verifyCsrfToken(false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '보안 토큰이 유효하지 않습니다. 새로고침 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Rate limit (5분 내 30회)
if (!checkRateLimit('cart_add', 30, 300)) {
    echo json_encode(['success' => false, 'message' => '요청이 너무 많습니다. 잠시 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * JSON 컬럼을 문자열 목록으로 변환한다. 값이 없으면 빈 배열.
 */
function cartAddJsonList($raw)
{
    if ($raw === null || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    $list = [];
    foreach ($decoded as $v) {
        if (is_scalar($v)) {
            $v = trim((string)$v);
            if ($v !== '') {
                $list[] = $v;
            }
        }
    }
    return $list;
}

try {
    $pdo = getDB();

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => '제품 정보가 올바르지 않습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 제품 재조회 (판매중인 제품만) + 부모 제품의 재질 목록 동시 조회
    $stmt = $pdo->prepare("
        SELECT p.id, p.product_name, p.specification, p.parent_product_id,
               p.category_code, p.calculation_type,
               p.available_origins, p.available_materials,
               p.min_length, p.max_length, p.standard_length,
               pp.available_origins   AS parent_available_origins,
               pp.available_materials AS parent_available_materials,
               pp.calculation_type    AS parent_calculation_type
        FROM products p
        LEFT JOIN products pp ON p.parent_product_id = pp.id
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => '판매 중인 제품이 아닙니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 허용 목록은 화면(product_detail_v2.php)이 제시하는 목록과 정확히 같아야 한다.
    // 그 목록은 기존 자동계산 화면(product_detail_calc.php)의 규칙을 그대로 따른다.
    //   원산지: 해당 제품의 available_origins 만 사용 (부모 상속 없음)
    //   재질  : 부모 제품이 있으면 부모의 available_materials 를 사용
    $allowedOrigins   = cartAddJsonList($product['available_origins']);
    $allowedMaterials = cartAddJsonList(
        $product['parent_available_materials'] ?? $product['available_materials']
    );

    // 원산지 검증 (목록이 비어 있으면 빈 값만 허용)
    $origin = isset($_POST['origin']) ? trim((string)$_POST['origin']) : '';
    if ($origin !== '') {
        if (empty($allowedOrigins) || !in_array($origin, $allowedOrigins, true)) {
            echo json_encode(['success' => false, 'message' => '선택하신 원산지는 이 제품에서 사용할 수 없습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // 재질 검증
    $material = isset($_POST['material']) ? trim((string)$_POST['material']) : '';
    if ($material !== '') {
        if (empty($allowedMaterials) || !in_array($material, $allowedMaterials, true)) {
            echo json_encode(['success' => false, 'message' => '선택하신 재질은 이 제품에서 사용할 수 없습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // 수량 검증 (0 초과 999999 이하)
    $quantityRaw = isset($_POST['quantity']) ? trim((string)$_POST['quantity']) : '';
    if ($quantityRaw === '' || !is_numeric($quantityRaw)) {
        echo json_encode(['success' => false, 'message' => '수량을 숫자로 입력해 주세요.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $quantity = (float)$quantityRaw;
    if ($quantity <= 0 || $quantity > 999999) {
        echo json_encode(['success' => false, 'message' => '수량은 0보다 크고 999999 이하여야 합니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 길이 검증 (선택 입력, 값이 있으면 0 초과 10000 이하)
    $lengthRaw = isset($_POST['length_value']) ? trim((string)$_POST['length_value']) : '';
    $length_value = null;
    if ($lengthRaw !== '') {
        if (!is_numeric($lengthRaw)) {
            echo json_encode(['success' => false, 'message' => '길이를 숫자로 입력해 주세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $length_value = (float)$lengthRaw;
        if ($length_value <= 0 || $length_value > 10000) {
            echo json_encode(['success' => false, 'message' => '길이는 0보다 크고 10000 이하여야 합니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $length_unit = QuoteCart::normalizeLengthUnit($_POST['length_unit'] ?? '');

    // 판재류(선형이 아닌 제품)에는 길이 항목 자체가 없다.
    // 직접 POST 로 길이를 보내도 저장하지 않는다.
    $effectiveCalcType = $product['parent_calculation_type'] ?? ($product['calculation_type'] ?? '');
    if ($effectiveCalcType !== 'linear') {
        $length_value = null;
    }

    // 수량 단위는 사용자가 고르는 값이 아니라 제품 속성이다.
    // 기존 자동계산 화면과 같은 규칙으로 서버에서 다시 판정하고 클라이언트 값은 쓰지 않는다.
    $unitInfo      = productQuantityUnit($product);
    $quantity_unit = QuoteCart::normalizeQuantityUnit($unitInfo['value']);

    // 본/장 단위는 정수만 허용한다 (기존 화면도 정수 입력이었다)
    if (!$unitInfo['decimal'] && fmod($quantity, 1) !== 0.0) {
        echo json_encode([
            'success' => false,
            'message' => '수량은 ' . $unitInfo['display'] . ' 단위로 정수만 입력할 수 있습니다.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 제품이 길이 선택지를 미터 단위 목록으로 제공하는 경우에는 단위를 M 으로 고정한다.
    // 화면에서도 M 고정으로 렌더하지만, 직접 POST 로 mm 를 보내 '6m 선택 → 6mm 저장' 이
    // 되는 것을 서버에서도 막아야 한다.
    if ($length_unit !== 'M'
        && ($product['min_length'] > 0 || $product['max_length'] > 0 || $product['standard_length'] > 0)) {
        $length_unit = 'M';
    }

    // 항목별 요청사항 (500자 제한)
    $note = isset($_POST['note']) ? trim((string)$_POST['note']) : '';
    if ($note !== '') {
        $note = mb_substr($note, 0, 500);
    }

    QuoteCart::add($pdo, [
        'product_id'    => (int)$product['id'],
        'product_name'  => $product['product_name'],
        'product_spec'  => $product['specification'],
        'origin'        => $origin,
        'material'      => $material,
        'length_value'  => $length_value === null ? '' : $length_value,
        'length_unit'   => $length_unit,
        'quantity'      => $quantity,
        'quantity_unit' => $quantity_unit,
        'note'          => $note,
    ]);

    echo json_encode([
        'success'    => true,
        'message'    => '장바구니에 담았습니다.',
        'cart_count' => QuoteCart::count($pdo),
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // PDOException 은 RuntimeException 의 하위 클래스이므로 반드시 먼저 잡아야 한다.
    // 그렇지 않으면 아래 catch 에서 DB 오류 원문이 그대로 응답에 실린다.
    error_log('cart_add.php DB 오류: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '장바구니에 담지 못했습니다. 잠시 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $e) {
    // QuoteCart 가 던지는 사용자 안내용 예외 (최대 개수 초과 등)
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('cart_add.php 실패: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '장바구니 담기에 실패했습니다. 잠시 후 다시 시도해 주세요.'], JSON_UNESCAPED_UNICODE);
}
