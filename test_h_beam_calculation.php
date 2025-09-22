<?php
/**
 * H형강 중량 계산 테스트
 * /114/2/2.txt 계산식 검증
 */

require_once 'db.php';
require_once 'includes/SteelCalculator.php';

$calculator = new SteelCalculator($pdo);

echo "<h2>H형강 중량 계산 테스트</h2>";
echo "<p>계산식: 단위중량 × 길이 = 1본중량 (소수점 첫째자리 반올림) → 1본중량 × 총본수 = 총중량</p>";
echo "<hr>";

// 테스트 케이스 1: 100×100×6×8, 11미터 10본
echo "<h3>테스트 1: H형강 100×100×6×8</h3>";
$unitWeight = 17.2;
$length = 11;
$quantity = 10;

echo "입력값:<br>";
echo "- 단위중량: {$unitWeight} kg/m<br>";
echo "- 길이: {$length} m<br>";
echo "- 수량: {$quantity} 본<br><br>";

// 계산
$result = $calculator->calculateBeamWeight($unitWeight, $length, $quantity, true);
$expectedPerPiece = round($unitWeight * $length); // 189
$expectedTotal = $expectedPerPiece * $quantity; // 1890

echo "계산 과정:<br>";
echo "1) {$unitWeight} × {$length} = " . ($unitWeight * $length) . " kg<br>";
echo "2) 반올림: " . ($unitWeight * $length) . " → {$expectedPerPiece} kg (본당 중량)<br>";
echo "3) {$expectedPerPiece} × {$quantity} = {$expectedTotal} kg (총 중량)<br><br>";

echo "계산 결과: <strong>{$result} kg</strong><br>";
echo "예상 결과: <strong>{$expectedTotal} kg</strong><br>";
echo "테스트 결과: " . ($result == $expectedTotal ? "✅ 성공" : "❌ 실패") . "<br>";

echo "<hr>";

// 테스트 케이스 2: 125×125×6.5×9, 12미터 9본
echo "<h3>테스트 2: H형강 125×125×6.5×9</h3>";
$unitWeight = 23.8;
$length = 12;
$quantity = 9;

echo "입력값:<br>";
echo "- 단위중량: {$unitWeight} kg/m<br>";
echo "- 길이: {$length} m<br>";
echo "- 수량: {$quantity} 본<br><br>";

// 계산
$result = $calculator->calculateBeamWeight($unitWeight, $length, $quantity, true);
$expectedPerPiece = round($unitWeight * $length); // 286
$expectedTotal = $expectedPerPiece * $quantity; // 2574

echo "계산 과정:<br>";
echo "1) {$unitWeight} × {$length} = " . ($unitWeight * $length) . " kg<br>";
echo "2) 반올림: " . ($unitWeight * $length) . " → {$expectedPerPiece} kg (본당 중량)<br>";
echo "3) {$expectedPerPiece} × {$quantity} = {$expectedTotal} kg (총 중량)<br><br>";

echo "계산 결과: <strong>{$result} kg</strong><br>";
echo "예상 결과: <strong>{$expectedTotal} kg</strong><br>";
echo "테스트 결과: " . ($result == $expectedTotal ? "✅ 성공" : "❌ 실패") . "<br>";

echo "<hr>";

// formatCalculationResult 함수 테스트
echo "<h3>계산 결과 포맷팅 테스트</h3>";
$formatted = $calculator->formatCalculationResult(17.2, 11, 10);

echo "<pre>";
print_r($formatted);
echo "</pre>";

echo "<h4>계산 단계:</h4>";
echo "<ol>";
foreach ($formatted['calculation_steps'] as $step => $description) {
    echo "<li>{$step}: {$description}</li>";
}
echo "</ol>";

echo "<hr>";

// 데이터베이스에서 H형강 데이터 조회
echo "<h3>데이터베이스 H형강 데이터 확인 (상위 10개)</h3>";
$stmt = $pdo->query("
    SELECT product_name, specifications, specification_weight
    FROM products
    WHERE category_code = 'h-beam'
    ORDER BY specification_weight
    LIMIT 10
");

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>제품명</th><th>규격</th><th>단위중량(kg/m)</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>{$row['product_name']}</td>";
    echo "<td>{$row['specifications']}</td>";
    echo "<td>{$row['specification_weight']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p>✅ H형강 중량 계산 기능 테스트 완료</p>";
?>