<?php
/**
 * 제품별 수량 단위 판정
 *
 * 기존 자동계산 화면(product_detail_calc.php)이 제품마다 보여주던 수량 단위를
 * 신규 견적요청 화면과 서버 검증에서 그대로 쓰기 위한 단일 기준이다.
 *
 * 원본 규칙 (product_detail_calc.php)
 *   47행  : $calculation_type = $product['parent_calculation_type'] ?? $product['calculation_type'];
 *   714행 : if ($calculation_type === 'linear')
 *   807행 :   <label>단위 (<?= $product['category_code'] === 'rebar' ? 'TON/BD' : '본' ?>)</label>
 *   814행 : else <label>수량 (장)</label>
 *
 * 즉 판정 기준은 다음 둘뿐이다.
 *   1) 부모 제품을 상속한 calculation_type ('linear' 인지 아닌지)
 *   2) category_code 가 'rebar' 인지
 * price_unit('kg'/'piece')과 products.unit 컬럼은 단위 표시에 관여하지 않는다.
 * (products.unit 은 914건 중 746건이 비어 있고 'kg/m' 같은 비단위 값이 섞여 있어 쓸 수 없다)
 *
 * 주의: category_code 로 단위를 매핑하면 안 된다.
 *       deck-plate 카테고리에는 sheet 제품과 linear 제품이 함께 들어 있다.
 *
 * ※ product_detail_calc.php 의 807/814행이 바뀌면 이 파일도 함께 고쳐야 두 화면이 갈라지지 않는다.
 *
 * 문서: dev_docs/PRD_product_quote_v2.md
 */

if (!function_exists('productQuantityUnit')) {
    /**
     * 제품 행을 받아 수량 단위 정보를 돌려준다.
     *
     * @param array $product products 조회 결과. 다음 키를 참조한다.
     *                       category_code, calculation_type, parent_calculation_type(선택)
     * @return array {
     *     @type string $label   입력칸 라벨 ('단위' 또는 '수량') - 기존 화면 문구 그대로
     *     @type string $display 화면에 보이는 단위 문자열 ('본', '장', 'TON/BD')
     *     @type string $value   DB 에 저장할 단위 값 ('본', '장', 'TON')
     *     @type bool   $decimal 소수 입력 허용 여부 (철근 톤 단위만 true)
     *     @type string $help    입력 도움말
     * }
     */
    function productQuantityUnit(array $product)
    {
        // 부모 제품이 있으면 부모의 계산 타입을 상속한다 (기존 화면과 동일)
        $calculationType = $product['parent_calculation_type'] ?? ($product['calculation_type'] ?? '');
        $categoryCode    = $product['category_code'] ?? '';

        if ($calculationType === 'linear') {
            if ($categoryCode === 'rebar') {
                // 철근은 톤 단위로 입력받는다. 화면 문구는 기존 계산기와 동일하게 'TON/BD'.
                // 저장값은 'TON' 으로 분리한다 ('TON/BD' 는 허용 단위 목록에 없어 EA 로 강등된다).
                return [
                    'label'   => '단위',
                    'display' => 'TON/BD',
                    'value'   => 'TON',
                    'decimal' => true,
                    'help'    => '철근은 톤(TON) 단위로 입력해 주세요.',
                ];
            }

            return [
                'label'   => '단위',
                'display' => '본',
                'value'   => '본',
                'decimal' => false,
                'help'    => '필요한 본수를 입력해 주세요.',
            ];
        }

        // 판재류(sheet 등)
        return [
            'label'   => '수량',
            'display' => '장',
            'value'   => '장',
            'decimal' => false,
            'help'    => '필요한 장수를 입력해 주세요.',
        ];
    }
}
