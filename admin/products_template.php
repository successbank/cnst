<?php
/**
 * CSV 템플릿 다운로드
 * 빈 CSV 템플릿 또는 카테고리별 템플릿 다운로드
 */
session_start();
require_once '../db.php';
require_once 'admin_check.php';

// 카테고리 코드 받기
$category_code = $_GET['category'] ?? '';
$include_sample = isset($_GET['sample']);

// 카테고리명 조회
$category_name = '전체';
if ($category_code) {
    $stmt = $pdo->prepare("SELECT category_name FROM product_categories WHERE category_code = ?");
    $stmt->execute([$category_code]);
    $cat = $stmt->fetch();
    if ($cat) {
        $category_name = $cat['category_name'];
    }
}

// CSV 파일명 설정
$filename = 'products_template_' . $category_name . '_' . date('Ymd') . '.csv';

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
    'ID',                    // 0 - 신규 등록시 비워두기
    '카테고리코드',          // 1 (필수)
    '카테고리명',            // 2 (읽기전용)
    '제품명',                // 3 (필수)
    '제품코드',              // 4
    '규격',                  // 5 (필수)
    '설명',                  // 6
    '가격',                  // 7
    '가격단위',              // 8 (kg/piece)
    '단위',                  // 9
    '최소주문수량',          // 10
    '재고상태',              // 11 (in_stock/out_of_stock)
    '원산지',                // 12
    '제조사',                // 13
    '치수',                  // 14
    '중량',                  // 15
    '재질',                  // 16
    '단위중량',              // 17 (kg/m)
    '계산기유형',            // 18 (linear/sheet/piece)
    '계산기보유',            // 19 (Y/N)
    '사용가능재질',          // 20 (쉼표 구분)
    '사용가능원산지',        // 21 (쉼표 구분)
    '재질별추가가격',        // 22 (재질:가격;...)
    '원산지별추가가격',      // 23 (원산지:가격;...)
    '특징',                  // 24
    '배송정보',              // 25
    '추천제품',              // 26 (Y/N)
    '활성화',                // 27 (Y/N)
    '기본길이',              // 28
    '최소길이',              // 29
    '최대길이',              // 30
    '표준길이',              // 31
    '홈페이지표시',          // 32 (Y/N)
    '조회수',                // 33 (읽기전용)
    '등록일',                // 34 (읽기전용)
    '삭제(Y)'                // 35 (삭제시 Y)
];
fputcsv($output, $headers);

// 샘플 데이터 행 (선택적)
if ($include_sample) {
    $sample = [
        '',                           // ID (신규)
        $category_code ?: 'h-beam',   // 카테고리코드
        $category_name ?: 'H빔',      // 카테고리명
        '샘플 제품명',                 // 제품명
        'SAMPLE-001',                 // 제품코드
        '100x100x6x8',                // 규격
        '샘플 설명입니다.',            // 설명
        '100000',                     // 가격
        'kg',                         // 가격단위
        'TON',                        // 단위
        '1',                          // 최소주문수량
        'in_stock',                   // 재고상태
        '국산',                       // 원산지
        '현대제철',                   // 제조사
        '100x100',                    // 치수
        '17.2',                       // 중량
        'SS275',                      // 재질
        '17.2',                       // 단위중량(kg/m)
        'linear',                     // 계산기유형
        'Y',                          // 계산기보유
        'SS275,SS400,SM490A',         // 사용가능재질
        '국산,중국산',                // 사용가능원산지
        'SS275:0;SS400:50;SM490A:100',// 재질별추가가격
        '국산:0;중국산:-100',         // 원산지별추가가격
        '고품질 제품',                // 특징
        '2-3일 내 배송',              // 배송정보
        'N',                          // 추천제품
        'Y',                          // 활성화
        '6',                          // 기본길이
        '0.5',                        // 최소길이
        '12',                         // 최대길이
        '6',                          // 표준길이
        'Y',                          // 홈페이지표시
        '0',                          // 조회수
        '',                           // 등록일
        ''                            // 삭제
    ];
    fputcsv($output, $sample);
}

// 카테고리 코드가 있으면 빈 행에 카테고리 정보만 채움
if ($category_code && !$include_sample) {
    $template_row = array_fill(0, 36, '');
    $template_row[1] = $category_code;  // 카테고리코드
    $template_row[2] = $category_name;  // 카테고리명
    fputcsv($output, $template_row);
}

fclose($output);
exit;
