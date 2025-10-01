<?php
/**
 * 철근 가격 계산기 테스트
 */

require_once 'db.php';
require_once 'html/includes/RebarPriceCalculator.php';

echo "=== 철근 가격 계산기 테스트 ===\n\n";

$calculator = new RebarPriceCalculator($pdo);

// 1. 사용 가능한 규격 조회
echo "1. 사용 가능한 규격\n";
echo "-------------------\n";
$specs = $calculator->getAvailableSpecs();
echo "규격: " . implode(', ', $specs) . "\n\n";

// 2. 원산지 조회
echo "2. 원산지 목록\n";
echo "-------------------\n";
$origins = $calculator->getAvailableOrigins();
echo "원산지: " . implode(', ', $origins) . "\n\n";

// 3. D10 길이 목록 조회
echo "3. D10 사용 가능한 길이 (처음 5개)\n";
echo "-------------------\n";
$lengths = $calculator->getAvailableLengths('D10');
foreach (array_slice($lengths, 0, 5) as $item) {
    echo sprintf(
        "- %.1fm: %.2fkg/본, %d본/번들, %.1fkg/번들\n",
        $item['length'],
        $item['piece_weight'],
        $item['pieces_per_length'],
        $item['weight_per_ton']
    );
}
echo "\n";

// 4. 가격 계산 테스트
echo "4. 가격 계산 테스트\n";
echo "-------------------\n";
echo "조건: D10, 8m, 10번들, 포항, SD400\n\n";

try {
    $result = $calculator->calculate('D10', 8.0, 10, '포항', 'SD400');

    if ($result['success']) {
        echo "✓ 계산 성공!\n\n";

        echo "[가격 정보]\n";
        echo sprintf("- 기준단가: %s원/톤\n", number_format($result['pricing']['base_price']));
        echo sprintf("- 원산지 추가: %s원/톤\n", number_format($result['pricing']['origin_surcharge']));
        echo sprintf("- 재질 추가: %s원/톤\n", number_format($result['pricing']['material_surcharge']));
        echo sprintf("- 최종 단가: %s원/톤\n\n", number_format($result['pricing']['unit_price']));

        echo "[중량 정보]\n";
        echo sprintf("- 1본 중량: %.2fkg\n", $result['weight']['weight_per_piece']);
        echo sprintf("- 번들당 본수: %d본\n", $result['weight']['pieces_per_bundle']);
        echo sprintf("- 번들당 중량: %.1fkg\n", $result['weight']['weight_per_bundle']);
        echo sprintf("- 총 중량: %.1fkg (%.3f톤)\n\n", $result['weight']['total_weight_kg'], $result['weight']['total_weight_ton']);

        echo "[가격 계산]\n";
        echo sprintf("- 번들당 가격: %s원\n", number_format($result['price_per_bundle']));
        echo sprintf("- 총 금액: %s원\n", number_format($result['total_price']));
        echo sprintf("- 부가세(10%%): %s원\n", number_format($result['total_price'] * 0.1));
        echo sprintf("- 부가세 포함: %s원\n\n", number_format($result['total_price'] * 1.1));
    } else {
        echo "✗ 계산 실패\n";
    }
} catch (Exception $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n";
}

// 5. 견적서 형식 출력
echo "5. 견적서 형식\n";
echo "-------------------\n";
try {
    $quote = $calculator->generateQuote('D10', 8.0, 10, '포항', 'SD400');

    foreach ($quote as $key => $value) {
        echo sprintf("%-15s : %s\n", $key, $value);
    }
} catch (Exception $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n";
}

echo "\n=== 테스트 완료 ===\n";
echo "\nAPI 테스트:\n";
echo "curl -X POST http://211.248.112.67:1112/ajax/calculate_rebar_price.php \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"spec_name\":\"D10\",\"length\":8,\"quantity\":10}'\n";