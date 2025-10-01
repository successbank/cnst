<?php
/**
 * 계산 엔진 테스트 스크립트
 */

require_once 'db.php';
require_once 'html/includes/FormulaParser.php';
require_once 'html/includes/CalculationEngine.php';

echo "=== 계산 엔진 테스트 시작 ===\n\n";

// 1. FormulaParser 테스트
echo "1. FormulaParser 기본 테스트\n";
echo "----------------------------\n";

$parser = new FormulaParser();

// 1-1. 기본 사칙연산
try {
    $result = $parser->parse('2 + 3 * 4', []);
    echo "✓ 2 + 3 * 4 = $result (예상: 14)\n";
    assert($result == 14, "사칙연산 오류");
} catch (Exception $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n";
}

// 1-2. 괄호
try {
    $result = $parser->parse('(2 + 3) * 4', []);
    echo "✓ (2 + 3) * 4 = $result (예상: 20)\n";
    assert($result == 20, "괄호 처리 오류");
} catch (Exception $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n";
}

// 1-3. 변수 치환
try {
    $result = $parser->parse('diameter * diameter * 0.00617', ['diameter' => 10]);
    echo "✓ 10² × 0.00617 = $result (예상: 0.617)\n";
    assert(abs($result - 0.617) < 0.001, "변수 치환 오류");
} catch (Exception $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n";
}

// 1-4. 복잡한 수식
try {
    $result = $parser->parse('(10 * 10) * 0.00617 * 8 * 1', []);
    echo "✓ 철근 D10 8m 1본 = $result kg\n";
} catch (Exception $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. CalculationEngine 테스트
echo "2. CalculationEngine 테스트\n";
echo "----------------------------\n";

$engine = new CalculationEngine($pdo);

// 2-1. 철근 제품 찾기
$stmt = $pdo->query("SELECT id, product_name, category_code FROM products WHERE category_code = 'rebar' LIMIT 1");
$rebarProduct = $stmt->fetch();

if ($rebarProduct) {
    echo "테스트 제품: {$rebarProduct['product_name']} (ID: {$rebarProduct['id']})\n";

    // 2-2. 계산 실행
    try {
        $result = $engine->calculate($rebarProduct['id'], [
            'length' => 8,
            'quantity' => 10
        ]);

        if ($result['success']) {
            echo "✓ 계산 성공!\n";
            echo "  - 공식: " . $result['result']['formula_name'] . "\n";
            echo "  - 총 중량: " . $result['result']['total_weight'] . " kg\n";
            echo "  - 1본 중량: " . $result['result']['weight_per_piece'] . " kg\n";
        } else {
            echo "✗ 계산 실패\n";
        }
    } catch (Exception $e) {
        echo "✗ 오류: " . $e->getMessage() . "\n";
    }

    // 2-3. 상세 계산
    echo "\n상세 계산:\n";
    try {
        $result = $engine->calculateDetailed($rebarProduct['id'], [
            'length' => 8,
            'quantity' => 10
        ]);

        if ($result['success']) {
            echo "✓ 계산식: " . $result['result']['formula_expression'] . "\n";
            echo "  - 사용된 변수: " . json_encode($result['result']['variables_used'], JSON_UNESCAPED_UNICODE) . "\n";
            if (isset($result['result']['intermediate']['weight_per_piece'])) {
                echo "  - 1본 중량: " . $result['result']['intermediate']['weight_per_piece'] . " kg\n";
            }
            echo "  - 총 중량: " . $result['result']['total_weight'] . " kg\n";
        }
    } catch (Exception $e) {
        echo "✗ 오류: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ 철근 제품이 없습니다. 테스트를 건너뜁니다.\n";
}

echo "\n";

// 3. H형강 테스트
echo "3. H형강 계산 테스트\n";
echo "----------------------------\n";

$stmt = $pdo->query("SELECT id, product_name, category_code FROM products WHERE category_code = 'h-beam' LIMIT 1");
$hbeamProduct = $stmt->fetch();

if ($hbeamProduct) {
    echo "테스트 제품: {$hbeamProduct['product_name']} (ID: {$hbeamProduct['id']})\n";

    try {
        $result = $engine->calculate($hbeamProduct['id'], [
            'length' => 6,
            'quantity' => 5
        ]);

        if ($result['success']) {
            echo "✓ 계산 성공!\n";
            echo "  - 공식: " . $result['result']['formula_name'] . "\n";
            echo "  - 총 중량: " . $result['result']['total_weight'] . " kg\n";
            echo "  - 1본 중량: " . $result['result']['weight_per_piece'] . " kg\n";
        }
    } catch (Exception $e) {
        echo "✗ 오류: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ H형강 제품이 없습니다. 테스트를 건너뜁니다.\n";
}

echo "\n";

// 4. 계산식 테스트
echo "4. 계산식 직접 테스트\n";
echo "----------------------------\n";

$testFormula = [
    'type' => 'expression',
    'expression' => '(diameter * diameter) * 0.00617 * length * quantity',
    'rounding' => [
        'final' => ['decimals' => 2, 'method' => 'round']
    ]
];

$testData = [
    'diameter' => 10,
    'length' => 8,
    'quantity' => 10
];

try {
    $result = $engine->testFormula($testFormula, $testData);
    if ($result['success']) {
        echo "✓ 테스트 성공: {$result['result']} kg\n";
        echo "  - 수식: {$result['expression']}\n";
        echo "  - 입력: " . json_encode($testData) . "\n";
    } else {
        echo "✗ 테스트 실패: {$result['error']}\n";
    }
} catch (Exception $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. 데이터베이스 상태 확인
echo "5. 데이터베이스 상태\n";
echo "----------------------------\n";

$tables = [
    'calculation_formulas' => '계산식',
    'calculation_parameters' => '파라미터',
    'calculation_constants' => '상수',
    'calculation_history' => '히스토리'
];

foreach ($tables as $table => $name) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
    $count = $stmt->fetchColumn();
    echo "✓ $name ($table): $count 행\n";
}

echo "\n=== 테스트 완료 ===\n";
echo "\n접속 URL:\n";
echo "- 관리자 계산식 관리: http://211.248.112.67:1112/admin/admin_calculation_formulas.php\n";
echo "- 제품 페이지: http://211.248.112.67:1112/products_new.php?category=rebar\n";