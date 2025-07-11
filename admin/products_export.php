<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

// 카테고리 코드 받기
$category_code = $_GET['category'] ?? '';

// 카테고리별 조건 설정
$where = ["1=1"];
$params = [];

if ($category_code) {
    $where[] = "p.category_code = ?";
    $params[] = $category_code;
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

// CSV 파일명 설정
$filename = 'products_' . ($category_code ?: 'all') . '_' . date('Ymd_His') . '.csv';

// CSV 헤더 설정
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM 추가 (Excel에서 한글 깨짐 방지)
echo "\xEF\xBB\xBF";

// 출력 스트림 열기
$output = fopen('php://output', 'w');

// 헤더 행 작성
$headers = [
    'ID',
    '카테고리코드',
    '카테고리명',
    '제품명',
    '제품코드',
    '규격',
    '설명',
    '가격',
    '단위',
    '최소주문수량',
    '재고상태',
    '원산지',
    '제조사',
    '치수',
    '중량',
    '재질',
    '단위중량(kg/m)',
    '특징',
    '배송정보',
    '추천제품',
    '활성화',
    '조회수',
    '등록일'
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
        $product['description'] ?? '',
        $product['price'] ?? '',
        $product['unit'] ?? '',
        $product['min_order_qty'] ?? 1,
        $product['stock_status'] ?? 'in_stock',
        $product['origin'] ?? '',
        $product['manufacturer'] ?? '',
        $product['dimensions'] ?? '',
        $product['weight'] ?? '',
        $product['material'] ?? '',
        $product['unit_weight'] ?? '',
        $product['features'] ?? '',
        $product['delivery_info'] ?? '',
        $product['is_featured'] ? 'Y' : 'N',
        $product['is_active'] ? 'Y' : 'N',
        $product['view_count'] ?? 0,
        $product['created_at'] ?? ''
    ];
    fputcsv($output, $row);
}

fclose($output);
exit;
?>