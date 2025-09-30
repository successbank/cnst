<?php
/**
 * 철근 옵션 조회 API
 *
 * GET 파라미터:
 * - type: specs | origins | lengths
 * - spec_name: 규격 (lengths 조회 시 필수)
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db.php';
require_once '../includes/RebarPriceCalculator.php';

try {
    $type = $_GET['type'] ?? 'all';
    $specName = $_GET['spec_name'] ?? null;

    $calculator = new RebarPriceCalculator($pdo);

    switch ($type) {
        case 'all':
            // 모든 데이터를 한 번에 반환 (가격 정보가 있는 규격만)
            $specsWithPrice = $calculator->getSpecsWithPrice();

            // 모든 규격 정보 (rebar_length_data 기준)
            $allSpecs = $calculator->getAvailableSpecs();

            // 각 규격별 사용 가능한 길이 정보
            $lengthsBySpec = [];
            foreach ($allSpecs as $spec) {
                $lengthsBySpec[$spec] = $calculator->getAvailableLengths($spec);
            }

            $data = [
                'specs' => $specsWithPrice,  // 가격 정보가 있는 규격 (D10, D13, D16)
                'all_specs' => $allSpecs,    // 모든 규격
                'origins' => $calculator->getAvailableOrigins(),
                'lengths' => $lengthsBySpec  // 규격별 길이 배열
            ];
            break;

        case 'specs':
            $data = $calculator->getAvailableSpecs();
            break;

        case 'origins':
            $data = $calculator->getAvailableOrigins($specName);
            break;

        case 'lengths':
            if (!$specName) {
                throw new Exception('규격을 선택해주세요');
            }
            $data = $calculator->getAvailableLengths($specName);
            break;

        default:
            throw new Exception('잘못된 요청 타입');
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}