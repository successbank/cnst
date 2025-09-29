<?php
/**
 * 제품 계산 엔진 클래스
 * DB에 저장된 계산식을 사용하여 제품 중량/가격을 계산합니다.
 */

require_once __DIR__ . '/FormulaParser.php';

class CalculationEngine {
    private $pdo;
    private $parser;
    private $formulaCache = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->parser = new FormulaParser();

        // 상수 로드
        $this->loadConstants();
    }

    /**
     * 제품 계산 수행
     *
     * @param int $productId 제품 ID
     * @param array $userParams 사용자 입력 파라미터 (예: ['length' => 8, 'quantity' => 10])
     * @param string $categoryCode 카테고리 코드 (옵션, 성능 최적화용)
     * @return array 계산 결과
     */
    public function calculate($productId, $userParams, $categoryCode = null) {
        // 1. 제품 정보 조회
        $product = $this->getProduct($productId);
        if (!$product) {
            throw new Exception("제품을 찾을 수 없습니다: ID $productId");
        }

        $categoryCode = $categoryCode ?? $product['category_code'];

        // 2. 계산식 조회 (우선순위: 제품 > 카테고리)
        $formula = $this->getFormula($productId, $categoryCode);
        if (!$formula) {
            throw new Exception("계산식을 찾을 수 없습니다: 제품 ID $productId, 카테고리 $categoryCode");
        }

        // 3. 파라미터 구성
        $parameters = $this->getParameters($formula['id']);

        // 4. 변수 바인딩
        $variables = $this->bindVariables($product, $parameters, $userParams);

        // 5. 수식 실행
        $formulaData = json_decode($formula['formula_expression'], true);
        $expression = $formulaData['expression'];

        // round 함수 처리
        $expression = $this->preprocessExpression($expression, $variables);

        $result = $this->parser->parse($expression, $variables);

        // 6. 반올림 적용
        $result = $this->applyRounding($result, $formulaData);

        // 7. 결과 반환
        return [
            'success' => true,
            'result' => [
                'total_weight' => $result,
                'weight_per_piece' => isset($variables['quantity']) && $variables['quantity'] > 0
                    ? $result / $variables['quantity']
                    : $result,
                'formula_name' => $formula['formula_name'],
                'formula_id' => $formula['id'],
                'variables_used' => $variables
            ]
        ];
    }

    /**
     * 계산식에 따라 상세 결과 반환 (중간 단계 포함)
     */
    public function calculateDetailed($productId, $userParams, $categoryCode = null) {
        $product = $this->getProduct($productId);
        if (!$product) {
            throw new Exception("제품을 찾을 수 없습니다: ID $productId");
        }

        $categoryCode = $categoryCode ?? $product['category_code'];
        $formula = $this->getFormula($productId, $categoryCode);
        if (!$formula) {
            throw new Exception("계산식을 찾을 수 없습니다");
        }

        $parameters = $this->getParameters($formula['id']);
        $variables = $this->bindVariables($product, $parameters, $userParams);

        $formulaData = json_decode($formula['formula_expression'], true);
        $expression = $formulaData['expression'];

        // 중간 단계 계산
        $intermediate = [];

        // 1본 중량 계산 (quantity가 있는 경우)
        if (isset($variables['quantity'])) {
            $qty = $variables['quantity'];
            $variables['quantity'] = 1;
            $exprProcessed = $this->preprocessExpression($expression, $variables);
            $weightPerPiece = $this->parser->parse($exprProcessed, $variables);

            // 중간 반올림 적용
            if (isset($formulaData['rounding']['intermediate'])) {
                $roundRule = $formulaData['rounding']['intermediate'];
                $weightPerPiece = $this->applyRoundingRule($weightPerPiece, $roundRule);
            }

            $intermediate['weight_per_piece'] = $weightPerPiece;

            // 총 중량 = 1본 중량 × 수량
            $totalWeight = $weightPerPiece * $qty;
            $variables['quantity'] = $qty;
        } else {
            $exprProcessed = $this->preprocessExpression($expression, $variables);
            $totalWeight = $this->parser->parse($exprProcessed, $variables);
        }

        // 최종 반올림
        if (isset($formulaData['rounding']['final'])) {
            $roundRule = $formulaData['rounding']['final'];
            $totalWeight = $this->applyRoundingRule($totalWeight, $roundRule);
        }

        return [
            'success' => true,
            'result' => [
                'total_weight' => $totalWeight,
                'intermediate' => $intermediate,
                'formula_name' => $formula['formula_name'],
                'formula_expression' => $expression,
                'variables_used' => $variables,
                'parameters' => $parameters
            ]
        ];
    }

    /**
     * 계산식 조회 (캐싱 적용)
     */
    private function getFormula($productId, $categoryCode) {
        $cacheKey = "product_{$productId}_{$categoryCode}";

        if (isset($this->formulaCache[$cacheKey])) {
            return $this->formulaCache[$cacheKey];
        }

        // 1. 제품별 계산식 우선 조회
        $stmt = $this->pdo->prepare("
            SELECT * FROM calculation_formulas
            WHERE product_id = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        $formula = $stmt->fetch();

        // 2. 없으면 카테고리 계산식 조회
        if (!$formula) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM calculation_formulas
                WHERE category_code = ? AND product_id IS NULL AND is_active = 1
                ORDER BY display_order
                LIMIT 1
            ");
            $stmt->execute([$categoryCode]);
            $formula = $stmt->fetch();
        }

        if ($formula) {
            $this->formulaCache[$cacheKey] = $formula;
        }

        return $formula;
    }

    /**
     * 제품 정보 조회
     */
    private function getProduct($productId) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }

    /**
     * 파라미터 조회
     */
    private function getParameters($formulaId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM calculation_parameters
            WHERE formula_id = ?
            ORDER BY display_order
        ");
        $stmt->execute([$formulaId]);
        return $stmt->fetchAll();
    }

    /**
     * 변수 바인딩
     */
    private function bindVariables($product, $parameters, $userParams) {
        $variables = [];

        foreach ($parameters as $param) {
            $paramName = $param['parameter_name'];

            if ($param['parameter_type'] === 'product_field') {
                // 제품 정보에서 값 가져오기
                $sourceField = $param['source_field'] ?? $paramName;

                if (isset($product[$sourceField])) {
                    $variables[$paramName] = floatval($product[$sourceField]);
                } else {
                    // 필드가 없으면 제품명이나 specifications에서 추출 시도
                    $value = $this->extractValueFromProduct($product, $paramName, $sourceField);
                    if ($value !== null) {
                        $variables[$paramName] = $value;
                    } elseif ($param['is_required']) {
                        throw new Exception("제품 정보에서 {$paramName}을(를) 찾을 수 없습니다");
                    }
                }
            } elseif ($param['parameter_type'] === 'number' || $param['parameter_type'] === 'text') {
                // 사용자 입력값
                if (isset($userParams[$paramName])) {
                    $variables[$paramName] = floatval($userParams[$paramName]);
                } elseif ($param['default_value'] !== null) {
                    $variables[$paramName] = floatval($param['default_value']);
                } elseif ($param['is_required']) {
                    throw new Exception("필수 파라미터가 누락되었습니다: $paramName");
                }
            }
        }

        return $variables;
    }

    /**
     * 제품명/규격에서 값 추출
     */
    private function extractValueFromProduct($product, $paramName, $sourceField) {
        $text = $product['product_name'] . ' ' . ($product['specifications'] ?? '') . ' ' . ($product['specification'] ?? '');

        // diameter (직경) 추출: D10, Φ10, 직경10 등
        if ($paramName === 'diameter' || $sourceField === 'diameter') {
            if (preg_match('/D(\d+)|Φ(\d+)|직경\s*(\d+)/i', $text, $matches)) {
                return floatval($matches[1] ?? $matches[2] ?? $matches[3]);
            }
        }

        // thickness (두께) 추출
        if ($paramName === 'thickness' || $sourceField === 'thickness') {
            if (preg_match('/(\d+\.?\d*)T|두께\s*(\d+\.?\d*)/i', $text, $matches)) {
                return floatval($matches[1] ?? $matches[2]);
            }
        }

        // width (폭) 추출
        if ($paramName === 'width' || $sourceField === 'width') {
            if (preg_match('/폭\s*(\d+\.?\d*)|(\d+\.?\d*)\s*×/i', $text, $matches)) {
                return floatval($matches[1] ?? $matches[2]);
            }
        }

        return null;
    }

    /**
     * 상수 로드
     */
    private function loadConstants() {
        $stmt = $this->pdo->query("SELECT constant_name, constant_value FROM calculation_constants");
        $constants = [];

        while ($row = $stmt->fetch()) {
            $constants[$row['constant_name']] = floatval($row['constant_value']);
        }

        $this->parser->setConstants($constants);
    }

    /**
     * 반올림 적용
     */
    private function applyRounding($value, $formulaData) {
        if (isset($formulaData['rounding']['final'])) {
            $rule = $formulaData['rounding']['final'];
            return $this->applyRoundingRule($value, $rule);
        }

        return round($value, 2); // 기본값: 소수점 둘째자리
    }

    /**
     * 반올림 규칙 적용
     */
    private function applyRoundingRule($value, $rule) {
        $decimals = $rule['decimals'] ?? 2;
        $method = $rule['method'] ?? 'round';

        switch ($method) {
            case 'round':
                return round($value, $decimals);
            case 'ceil':
                $factor = pow(10, $decimals);
                return ceil($value * $factor) / $factor;
            case 'floor':
                $factor = pow(10, $decimals);
                return floor($value * $factor) / $factor;
            default:
                return round($value, $decimals);
        }
    }

    /**
     * 수식 전처리 (round 함수 등)
     */
    private function preprocessExpression($expression, $variables) {
        // round(value, decimals) 함수를 실제 값으로 치환
        $expression = preg_replace_callback(
            '/round\s*\(\s*([^,]+),\s*(\d+)\s*\)/',
            function($matches) use ($variables) {
                $innerExpr = trim($matches[1]);
                $decimals = intval($matches[2]);

                // 내부 수식 계산
                try {
                    $innerResult = $this->parser->parse($innerExpr, $variables);
                    $rounded = round($innerResult, $decimals);
                    return $rounded;
                } catch (Exception $e) {
                    // 계산 실패 시 원본 반환
                    return $matches[0];
                }
            },
            $expression
        );

        return $expression;
    }

    /**
     * 계산식 테스트 (관리자용)
     */
    public function testFormula($formulaExpression, $testData) {
        try {
            $formulaData = is_string($formulaExpression)
                ? json_decode($formulaExpression, true)
                : $formulaExpression;

            $expression = $formulaData['expression'];
            $result = $this->parser->parse($expression, $testData);

            // 반올림 적용
            $result = $this->applyRounding($result, $formulaData);

            return [
                'success' => true,
                'result' => $result,
                'expression' => $expression,
                'variables' => $testData
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}