<?php
/**
 * 계산식 테스트 API
 * 관리자용
 */

session_start();
require_once '../../db.php';
require_once '../admin_check.php';
require_once '../../includes/CalculationEngine.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('잘못된 요청 데이터');
    }

    $formulaExpression = $data['formula_expression'] ?? null;
    $testData = $data['test_data'] ?? [];

    if (!$formulaExpression) {
        throw new Exception('계산식을 입력해주세요');
    }

    // 계산 엔진으로 테스트
    $engine = new CalculationEngine($pdo);
    $result = $engine->testFormula($formulaExpression, $testData);

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}