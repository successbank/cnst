<?php
/**
 * 철근 가격 계산 API
 *
 * POST 파라미터:
 * - spec_name: 규격 (D10, D13 등)
 * - length: 길이 (m)
 * - quantity: 수량 (번들)
 * - origin: 원산지 (기본: 포항)
 * - material: 재질 (기본: SD400)
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db.php';
require_once '../includes/RebarPriceCalculator.php';

try {
    // POST 데이터 받기
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        // GET 방식도 지원
        $data = $_GET;
    }

    $specName = $data['spec_name'] ?? null;
    $length = isset($data['length']) ? floatval($data['length']) : null;
    $quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;
    $origin = $data['origin'] ?? '포항';
    $material = $data['material'] ?? 'SD400';

    // 유효성 검사
    if (!$specName) {
        throw new Exception('규격을 선택해주세요');
    }

    if (!$length || $length <= 0) {
        throw new Exception('유효한 길이를 입력해주세요');
    }

    if ($quantity <= 0) {
        throw new Exception('유효한 수량을 입력해주세요');
    }

    // 계산 실행
    $calculator = new RebarPriceCalculator($pdo);
    $result = $calculator->calculate($specName, $length, $quantity, $origin, $material);

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}