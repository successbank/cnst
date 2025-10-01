<?php
/**
 * 철근 가격 정보 없음 기능 테스트
 */

require_once 'db.php';
require_once 'html/includes/RebarPriceCalculator.php';

echo "=== 철근 가격 정보 없음 기능 테스트 ===\n\n";

$calculator = new RebarPriceCalculator($pdo);

// 1. 가격 정보가 있는 규격 (D10)
echo "1. 가격 정보 있는 규격 (D10)\n";
echo "--------------------------------\n";
try {
    $result = $calculator->calculate('D10', 8.0, 1, '포항', 'SD400');
    echo "✓ 성공: 총 금액 " . number_format($result['total_price']) . "원\n\n";
} catch (Exception $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n\n";
}

// 2. 가격 정보가 없는 규격 (D19)
echo "2. 가격 정보 없는 규격 (D19)\n";
echo "--------------------------------\n";
try {
    $result = $calculator->calculate('D19', 8.0, 1, '포항', 'SD400');
    echo "✗ 예상치 못한 성공\n\n";
} catch (Exception $e) {
    echo "✓ 예상대로 오류 발생:\n";
    echo "   " . $e->getMessage() . "\n\n";
}

// 3. 가격 정보가 없는 규격 (D25)
echo "3. 가격 정보 없는 규격 (D25)\n";
echo "--------------------------------\n";
try {
    $result = $calculator->calculate('D25', 8.0, 1, '포항', 'SD400');
    echo "✗ 예상치 못한 성공\n\n";
} catch (Exception $e) {
    echo "✓ 예상대로 오류 발생:\n";
    echo "   " . $e->getMessage() . "\n\n";
}

// 4. 사용 가능한 규격 확인
echo "4. 사용 가능한 규격 목록\n";
echo "--------------------------------\n";
$specs = $calculator->getAvailableSpecs();
echo "가격 정보 있는 규격: " . implode(', ', $specs) . "\n\n";

// 5. DB에서 모든 규격 확인
echo "5. DB의 모든 철근 규격\n";
echo "--------------------------------\n";
$stmt = $pdo->query("
    SELECT DISTINCT spec_name,
           (SELECT COUNT(*) FROM rebar_prices WHERE spec_name = rld.spec_name) as has_price
    FROM rebar_length_data rld
    ORDER BY CAST(SUBSTRING(spec_name, 2) AS UNSIGNED)
");

echo "규격        | 가격정보\n";
echo "------------|----------\n";
while ($row = $stmt->fetch()) {
    $status = $row['has_price'] > 0 ? '✓ 있음' : '✗ 없음 → 모달';
    echo sprintf("%-12s| %s\n", $row['spec_name'], $status);
}

echo "\n=== 테스트 완료 ===\n";
echo "\n🔗 테스트 URL:\n";
echo "http://211.248.112.67:1112/rebar_calculator.php\n";
echo "\n📝 테스트 시나리오:\n";
echo "1. D10, D13, D16 선택 → 정상 계산\n";
echo "2. D19, D22, D25 등 선택 → 모달 팝업 (전화번호 표시)\n";