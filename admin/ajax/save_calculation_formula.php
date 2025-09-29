<?php
/**
 * 계산식 저장/수정 API
 * 관리자용
 */

session_start();
require_once '../../db.php';
require_once '../admin_check.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('잘못된 요청 데이터');
    }

    $formulaId = $data['id'] ?? null;
    $formulaName = $data['formula_name'] ?? '';
    $categoryCode = $data['category_code'] ?? null;
    $productId = $data['product_id'] ?? null;
    $description = $data['description'] ?? '';
    $formulaExpression = $data['formula_expression'] ?? null;
    $parameters = $data['parameters'] ?? [];
    $roundingRule = $data['rounding_rule'] ?? 'round_2';

    // 유효성 검사
    if (!$formulaName) {
        throw new Exception('계산식 이름을 입력해주세요');
    }

    if (!$formulaExpression) {
        throw new Exception('계산식을 입력해주세요');
    }

    // JSON 변환
    if (is_array($formulaExpression)) {
        $formulaExpression = json_encode($formulaExpression, JSON_UNESCAPED_UNICODE);
    }

    $pdo->beginTransaction();

    try {
        if ($formulaId) {
            // 기존 계산식 수정
            // 히스토리 저장
            $historyStmt = $pdo->prepare("
                INSERT INTO calculation_history
                (formula_id, version, formula_expression, parameters_snapshot, changed_by, change_description)
                SELECT id,
                       COALESCE((SELECT MAX(version) FROM calculation_history WHERE formula_id = ?), 0) + 1,
                       formula_expression,
                       (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                           'parameter_name', parameter_name,
                           'parameter_label', parameter_label,
                           'parameter_type', parameter_type,
                           'default_value', default_value
                       )) FROM calculation_parameters WHERE formula_id = ?),
                       ?,
                       ?
                FROM calculation_formulas WHERE id = ?
            ");
            $historyStmt->execute([
                $formulaId,
                $formulaId,
                $_SESSION['admin_id'] ?? null,
                $data['change_description'] ?? '계산식 수정',
                $formulaId
            ]);

            // 계산식 업데이트
            $stmt = $pdo->prepare("
                UPDATE calculation_formulas
                SET formula_name = ?,
                    description = ?,
                    formula_expression = ?,
                    rounding_rule = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $formulaName,
                $description,
                $formulaExpression,
                $roundingRule,
                $formulaId
            ]);

            // 기존 파라미터 삭제
            $pdo->prepare("DELETE FROM calculation_parameters WHERE formula_id = ?")->execute([$formulaId]);

        } else {
            // 신규 계산식 생성
            $stmt = $pdo->prepare("
                INSERT INTO calculation_formulas
                (formula_name, category_code, product_id, description, formula_expression, rounding_rule, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $formulaName,
                $categoryCode,
                $productId,
                $description,
                $formulaExpression,
                $roundingRule,
                $_SESSION['admin_id'] ?? null
            ]);

            $formulaId = $pdo->lastInsertId();
        }

        // 파라미터 저장
        if (!empty($parameters)) {
            $paramStmt = $pdo->prepare("
                INSERT INTO calculation_parameters
                (formula_id, parameter_name, parameter_label, parameter_type, source_field,
                 default_value, min_value, max_value, step_value, unit, is_required, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($parameters as $index => $param) {
                $paramStmt->execute([
                    $formulaId,
                    $param['parameter_name'],
                    $param['parameter_label'],
                    $param['parameter_type'] ?? 'number',
                    $param['source_field'] ?? null,
                    $param['default_value'] ?? null,
                    $param['min_value'] ?? null,
                    $param['max_value'] ?? null,
                    $param['step_value'] ?? null,
                    $param['unit'] ?? null,
                    $param['is_required'] ?? 1,
                    $index + 1
                ]);
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'formula_id' => $formulaId,
            'message' => '계산식이 저장되었습니다'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}