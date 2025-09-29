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
    $type = $_GET['type'] ?? 'specs';
    $specName = $_GET['spec_name'] ?? null;

    $calculator = new RebarPriceCalculator($pdo);

    switch ($type) {
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