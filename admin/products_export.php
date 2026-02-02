<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

/**
 * JSON 배열을 쉼표로 구분된 문자열로 변환
 * 예: ["SS275","SS400"] → "SS275,SS400"
 */
function jsonArrayToCommaSeparated($json) {
    if (empty($json)) return '';
    $arr = json_decode($json, true);
    if (!is_array($arr)) return '';
    return implode(',', $arr);
}

/**
 * JSON 객체를 세미콜론 형식으로 변환
 * 예: {"SS400":"100","SM490A":"200"} → "SS400:100;SM490A:200"
 */
function jsonObjectToSemicolonFormat($json) {
    if (empty($json)) return '';
    $obj = json_decode($json, true);
    if (!is_array($obj)) return '';
    $parts = [];
    foreach ($obj as $key => $value) {
        $parts[] = $key . ':' . $value;
    }
    return implode(';', $parts);
}

// 카테고리 코드 받기
$category_code = $_GET['category'] ?? '';

// 선택된 제품 ID 받기 (콤마 구분)
$selected_ids_param = $_GET['ids'] ?? '';
$selected_ids = [];
if ($selected_ids_param) {
    $selected_ids = array_filter(explode(',', $selected_ids_param), function($v) {
        return is_numeric(trim($v));
    });
    $selected_ids = array_map('intval', $selected_ids);
}

// 카테고리별 조건 설정
$where = ["1=1"];
$params = [];

if ($category_code) {
    $where[] = "p.category_code = ?";
    $params[] = $category_code;
}

// 선택된 제품만 필터링
if (!empty($selected_ids)) {
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $where[] = "p.id IN ($placeholders)";
    $params = array_merge($params, $selected_ids);
}

$whereClause = implode(" AND ", $where);

// 제품 데이터 조회
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name,
           uw.unit_weight
    FROM products p
    JOIN product_categories pc ON p.category_code = pc.category_code
    LEFT JOIN unit_weights uw ON p.specifications = uw.specification
    WHERE $whereClause
    ORDER BY p.id ASC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// CSV 파일명 설정 - 카테고리명 포함
$category_name = '전체';
if ($category_code) {
    $stmt_cat = $pdo->prepare("SELECT category_name FROM product_categories WHERE category_code = ?");
    $stmt_cat->execute([$category_code]);
    $cat = $stmt_cat->fetch();
    if ($cat) {
        $category_name = $cat['category_name'];
    }
}

// 선택 다운로드인 경우 파일명에 표시
$selection_suffix = !empty($selected_ids) ? '_선택' . count($selected_ids) . '건' : '';
$filename = 'products_' . $category_name . $selection_suffix . '_' . date('Ymd_His') . '.csv';

// CSV 헤더 설정
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM 추가 (Excel에서 한글 깨짐 방지)
echo "\xEF\xBB\xBF";

// 출력 스트림 열기
$output = fopen('php://output', 'w');

// 헤더 행 작성 (36개 컬럼)
$headers = [
    'ID',                    // 0
    '카테고리코드',          // 1
    '카테고리명',            // 2 (읽기전용)
    '제품명',                // 3 (필수)
    '제품코드',              // 4
    '규격',                  // 5 (필수)
    '설명',                  // 6
    '가격',                  // 7
    '가격단위',              // 8 (신규: kg/piece)
    '단위',                  // 9
    '최소주문수량',          // 10
    '재고상태',              // 11
    '원산지',                // 12
    '제조사',                // 13
    '치수',                  // 14
    '중량',                  // 15
    '재질',                  // 16
    '단위중량',              // 17 (신규: specification_weight)
    '계산기유형',            // 18 (신규: linear/sheet/piece)
    '계산기보유',            // 19 (신규: Y/N)
    '사용가능재질',          // 20 (신규: 쉼표 구분)
    '사용가능원산지',        // 21 (신규: 쉼표 구분)
    '재질별추가가격',        // 22 (신규: 재질:가격;...)
    '원산지별추가가격',      // 23 (신규: 원산지:가격;...)
    '특징',                  // 24
    '배송정보',              // 25
    '추천제품',              // 26 (Y/N)
    '활성화',                // 27 (Y/N)
    '기본길이',              // 28 (신규)
    '최소길이',              // 29 (신규)
    '최대길이',              // 30 (신규)
    '표준길이',              // 31 (신규)
    '홈페이지표시',          // 32 (신규: Y/N)
    '조회수',                // 33 (읽기전용)
    '등록일',                // 34 (읽기전용)
    '삭제(Y)'                // 35 (삭제시 Y 입력)
];
fputcsv($output, $headers);

// 데이터 행 작성
foreach ($products as $product) {
    $row = [
        $product['id'],
        $product['category_code'],
        $product['category_name'],
        $product['product_name'],
        $product['product_code'] ?? '',
        $product['specifications'] ?? '',
        str_replace(["\r\n", "\r", "\n"], '{{NEWLINE}}', $product['description'] ?? ''),  // 개행 → 플레이스홀더
        $product['price'] ?? '',
        $product['price_unit'] ?? 'kg',
        $product['unit'] ?? '',
        $product['min_order_qty'] ?? 1,
        $product['stock_status'] ?? 'in_stock',
        $product['origin'] ?? '',
        $product['manufacturer'] ?? '',
        $product['dimensions'] ?? '',
        $product['weight'] ?? '',
        $product['material'] ?? '',
        $product['specification_weight'] ?? '',
        $product['calculation_type'] ?? 'linear',
        ($product['has_calculator'] ?? 0) ? 'Y' : 'N',
        jsonArrayToCommaSeparated($product['available_materials'] ?? ''),
        jsonArrayToCommaSeparated($product['available_origins'] ?? ''),
        jsonObjectToSemicolonFormat($product['material_price_data'] ?? ''),
        jsonObjectToSemicolonFormat($product['origin_price_data'] ?? ''),
        $product['features'] ?? '',
        $product['delivery_info'] ?? '',
        ($product['is_featured'] ?? 0) ? 'Y' : 'N',
        ($product['is_active'] ?? 1) ? 'Y' : 'N',
        $product['base_length'] ?? 6,
        $product['min_length'] ?? '',
        $product['max_length'] ?? '',
        $product['standard_length'] ?? '',
        ($product['show_on_homepage'] ?? 1) ? 'Y' : 'N',
        $product['view_count'] ?? 0,
        $product['created_at'] ?? '',
        ''  // 삭제(Y) - 빈 값으로 내보내기
    ];
    fputcsv($output, $row);
}

fclose($output);
exit;
?>