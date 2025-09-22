<?php
/**
 * ㄱ형강 및 부등변ㄱ형강 중량 계산 테스트
 * 계산식: 소수점 둘째자리까지 계산
 */

require_once 'db.php';
require_once 'includes/SteelCalculator.php';

$calculator = new SteelCalculator($pdo);

echo "<h2>ㄱ형강/부등변ㄱ형강 중량 계산 테스트</h2>";
echo "<p>계산식: 단위중량 × 길이 = 1본중량 (소수점 둘째자리) → 1본중량 × 총본수 = 총중량</p>";
echo "<hr>";

// 테스트 케이스 1: ㄱ형강 25×25×3T, 8미터 14본
echo "<h3>테스트 1: ㄱ형강 25×25×3T</h3>";
$unitWeight = 1.12;
$length = 8;
$quantity = 14;

echo "입력값:<br>";
echo "- 단위중량: {$unitWeight} kg/m<br>";
echo "- 길이: {$length} m<br>";
echo "- 수량: {$quantity} 본<br><br>";

// 계산
$result = $calculator->calculateAngleWeight($unitWeight, $length, $quantity, true);
$expectedPerPiece = round($unitWeight * $length, 2); // 8.96
$expectedTotal = round($expectedPerPiece * $quantity, 2); // 125.44

echo "계산 과정:<br>";
echo "1) {$unitWeight} × {$length} = " . ($unitWeight * $length) . " kg<br>";
echo "2) 소수점 둘째자리: {$expectedPerPiece} kg (본당 중량)<br>";
echo "3) {$expectedPerPiece} × {$quantity} = {$expectedTotal} kg (총 중량)<br><br>";

echo "계산 결과: <strong>{$result} kg</strong><br>";
echo "예상 결과: <strong>{$expectedTotal} kg</strong><br>";
echo "테스트 결과: " . ($result == $expectedTotal ? "✅ 성공" : "❌ 실패") . "<br>";

echo "<hr>";

// 테스트 케이스 2: 부등변ㄱ형강 50×30×3T, 9미터 9본
echo "<h3>테스트 2: 부등변ㄱ형강 50×30×3T</h3>";
$unitWeight = 1.83;
$length = 9;
$quantity = 9;

echo "입력값:<br>";
echo "- 단위중량: {$unitWeight} kg/m<br>";
echo "- 길이: {$length} m<br>";
echo "- 수량: {$quantity} 본<br><br>";

// 계산
$result = $calculator->calculateAngleWeight($unitWeight, $length, $quantity, true);
$expectedPerPiece = round($unitWeight * $length, 2); // 16.47
$expectedTotal = round($expectedPerPiece * $quantity, 2); // 148.23

echo "계산 과정:<br>";
echo "1) {$unitWeight} × {$length} = " . ($unitWeight * $length) . " kg<br>";
echo "2) 소수점 둘째자리: {$expectedPerPiece} kg (본당 중량)<br>";
echo "3) {$expectedPerPiece} × {$quantity} = {$expectedTotal} kg (총 중량)<br><br>";

echo "계산 결과: <strong>{$result} kg</strong><br>";
echo "예상 결과: <strong>{$expectedTotal} kg</strong><br>";
echo "테스트 결과: " . ($result == $expectedTotal ? "✅ 성공" : "❌ 실패") . "<br>";

echo "<hr>";

// 데이터베이스에서 데이터 확인
echo "<h3>데이터베이스 확인</h3>";

echo "<h4>ㄱ형강 (상위 5개)</h4>";
$stmt = $pdo->query("
    SELECT product_name, specifications, specification_weight
    FROM products
    WHERE category_code = 'angle'
    ORDER BY specification_weight
    LIMIT 5
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
echo "</table><br>";

echo "<h4>부등변ㄱ형강 (상위 5개)</h4>";
$stmt = $pdo->query("
    SELECT product_name, specifications, specification_weight
    FROM products
    WHERE category_code = 'unequal-angle'
    ORDER BY specification_weight
    LIMIT 5
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
echo "<p>✅ ㄱ형강/부등변ㄱ형강 중량 계산 기능 테스트 완료</p>";

// 전체 제품 카테고리 확인
echo "<h3>전체 제품 카테고리</h3>";
$stmt = $pdo->query("
    SELECT category_code, COUNT(*) as count
    FROM products
    GROUP BY category_code
    ORDER BY category_code
");

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>카테고리 코드</th><th>제품 수</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>{$row['category_code']}</td>";
    echo "<td>{$row['count']}개</td>";
    echo "</tr>";
}
echo "</table>";
?>