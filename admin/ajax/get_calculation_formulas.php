<?php
/**
 * 계산식 목록 조회 API
 * 관리자용
 */

session_start();
require_once '../../db.php';
require_once '../admin_check.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $categoryCode = $_GET['category_code'] ?? null;
    $productId = $_GET['product_id'] ?? null;

    $result = [
        'success' => true,
        'data' => []
    ];

    // 카테고리 계산식 조회
    if ($categoryCode) {
        $stmt = $pdo->prepare("
            SELECT cf.*,
                   (SELECT COUNT(*) FROM calculation_parameters WHERE formula_id = cf.id) as param_count
            FROM calculation_formulas cf
            WHERE cf.category_code = ? AND cf.product_id IS NULL AND cf.is_active = 1
            ORDER BY cf.display_order
        ");
        $stmt->execute([$categoryCode]);
        $categoryFormula = $stmt->fetch();

        if ($categoryFormula) {
            // 파라미터 조회
            $paramStmt = $pdo->prepare("
                SELECT * FROM calculation_parameters
                WHERE formula_id = ?
                ORDER BY display_order
            ");
            $paramStmt->execute([$categoryFormula['id']]);
            $categoryFormula['parameters'] = $paramStmt->fetchAll();

            $result['data']['category_formula'] = $categoryFormula;
        }
    }

    // 제품별 계산식 조회
    if ($productId) {
        $stmt = $pdo->prepare("
            SELECT cf.*,
                   (SELECT COUNT(*) FROM calculation_parameters WHERE formula_id = cf.id) as param_count
            FROM calculation_formulas cf
            WHERE cf.product_id = ? AND cf.is_active = 1
        ");
        $stmt->execute([$productId]);
        $productFormula = $stmt->fetch();

        if ($productFormula) {
            $paramStmt = $pdo->prepare("
                SELECT * FROM calculation_parameters
                WHERE formula_id = ?
                ORDER BY display_order
            ");
            $paramStmt->execute([$productFormula['id']]);
            $productFormula['parameters'] = $paramStmt->fetchAll();

            $result['data']['product_formula'] = $productFormula;
        }
    }

    // 카테고리의 모든 제품에 대한 커스텀 계산식 조회
    if ($categoryCode) {
        $stmt = $pdo->prepare("
            SELECT cf.*, p.product_name,
                   (SELECT COUNT(*) FROM calculation_parameters WHERE formula_id = cf.id) as param_count
            FROM calculation_formulas cf
            JOIN products p ON cf.product_id = p.id
            WHERE p.category_code = ? AND cf.is_active = 1
            ORDER BY p.product_name
        ");
        $stmt->execute([$categoryCode]);
        $result['data']['product_formulas'] = $stmt->fetchAll();
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}