<?php
/**
 * 제품 중량 계산 API
 * 일반 사용자용
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../db.php';
require_once '../includes/CalculationEngine.php';

try {
    // POST 데이터 받기
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('잘못된 요청 데이터');
    }

    $productId = $data['product_id'] ?? null;
    $parameters = $data['parameters'] ?? [];
    $detailed = $data['detailed'] ?? false;

    if (!$productId) {
        throw new Exception('제품 ID가 필요합니다');
    }

    // 계산 엔진 실행
    $engine = new CalculationEngine($pdo);

    if ($detailed) {
        $result = $engine->calculateDetailed($productId, $parameters);
    } else {
        $result = $engine->calculate($productId, $parameters);
    }

    // 가격 계산 (있는 경우)
    if ($result['success']) {
        $productStmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $productStmt->execute([$productId]);
        $product = $productStmt->fetch();

        if ($product && $product['price']) {
            $weight = $result['result']['total_weight'];
            $result['result']['estimated_price'] = $weight * floatval($product['price']);
        }
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}