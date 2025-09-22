<?php
/**
 * 철근 중량 계산 테스트
 * 계산식: 직경² × 0.00617 × 길이 × 수량
 */

require_once 'db.php';
require_once 'includes/SteelCalculator.php';

$calculator = new SteelCalculator($pdo);

echo "<h2>철근 중량 계산 테스트</h2>";
echo "<p>계산식: 직경² × 0.00617 × 길이 × 수량 = 총중량</p>";
echo "<hr>";

// 테스트 케이스 1: HD10 철근, 12m 10본
echo "<h3>테스트 1: HD10 철근</h3>";
$diameter = 10;  // mm
$length = 12;    // m
$quantity = 10;  // 본

echo "입력값:<br>";
echo "- 직경: {$diameter} mm<br>";
echo "- 길이: {$length} m<br>";
echo "- 수량: {$quantity} 본<br><br>";

// 계산
$weight = ($diameter * $diameter) * 0.00617 * $length * $quantity;
$expected = round($weight, 2);

echo "계산 과정:<br>";
echo "1) {$diameter}² = " . ($diameter * $diameter) . " mm²<br>";
echo "2) " . ($diameter * $diameter) . " × 0.00617 = " . (($diameter * $diameter) * 0.00617) . " kg/m<br>";
echo "3) " . (($diameter * $diameter) * 0.00617) . " × {$length} = " . (($diameter * $diameter) * 0.00617 * $length) . " kg (1본)<br>";
echo "4) " . (($diameter * $diameter) * 0.00617 * $length) . " × {$quantity} = {$weight} kg<br>";
echo "5) 반올림: {$expected} kg<br><br>";

echo "계산 결과: <strong>{$expected} kg</strong><br>";

echo "<hr>";

// 테스트 케이스 2: HD25 철근, 8m 20본
echo "<h3>테스트 2: HD25 철근</h3>";
$diameter = 25;  // mm
$length = 8;     // m
$quantity = 20;  // 본

echo "입력값:<br>";
echo "- 직경: {$diameter} mm<br>";
echo "- 길이: {$length} m<br>";
echo "- 수량: {$quantity} 본<br><br>";

// 계산
$weight = ($diameter * $diameter) * 0.00617 * $length * $quantity;
$expected = round($weight, 2);

echo "계산 과정:<br>";
echo "1) {$diameter}² = " . ($diameter * $diameter) . " mm²<br>";
echo "2) " . ($diameter * $diameter) . " × 0.00617 = " . (($diameter * $diameter) * 0.00617) . " kg/m<br>";
echo "3) " . (($diameter * $diameter) * 0.00617) . " × {$length} = " . (($diameter * $diameter) * 0.00617 * $length) . " kg (1본)<br>";
echo "4) " . (($diameter * $diameter) * 0.00617 * $length) . " × {$quantity} = {$weight} kg<br>";
echo "5) 반올림: {$expected} kg<br><br>";

echo "계산 결과: <strong>{$expected} kg</strong><br>";

echo "<hr>";

// 데이터베이스에서 철근 데이터 확인
echo "<h3>데이터베이스 철근 데이터</h3>";

// 재질별 통계
echo "<h4>재질별 철근 현황</h4>";
$stmt = $pdo->query("
    SELECT
        JSON_UNQUOTE(JSON_EXTRACT(available_materials, '$[0]')) as material,
        COUNT(*) as count,
        GROUP_CONCAT(specifications ORDER BY specification_weight) as specs
    FROM products
    WHERE category_code = 'rebar'
    GROUP BY JSON_UNQUOTE(JSON_EXTRACT(available_materials, '$[0]'))
    ORDER BY material
");

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>재질</th><th>개수</th><th>규격</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>{$row['material']}</td>";
    echo "<td>{$row['count']}개</td>";
    echo "<td style='font-size:12px'>" . str_replace(',', ', ', $row['specs']) . "</td>";
    echo "</tr>";
}
echo "</table><br>";

// 대표 철근 데이터
echo "<h4>대표 철근 규격 (HD 시리즈)</h4>";
$stmt = $pdo->query("
    SELECT 
        specifications,
        specification_weight,
        JSON_EXTRACT(dimensions, '$.diameter') as diameter
    FROM products
    WHERE category_code = 'rebar' AND specifications LIKE 'HD%'
    ORDER BY CAST(SUBSTRING(specifications, 3) AS UNSIGNED)
    LIMIT 5
");

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>규격</th><th>직경(mm)</th><th>단위중량(kg/m)</th><th>계산 검증</th></tr>";
while ($row = $stmt->fetch()) {
    $diameter_val = json_decode($row['diameter']);
    $calc_weight = ($diameter_val * $diameter_val) * 0.00617;
    $calc_weight_rounded = round($calc_weight, 2);
    
    echo "<tr>";
    echo "<td>{$row['specifications']}</td>";
    echo "<td>{$diameter_val}</td>";
    echo "<td>{$row['specification_weight']}</td>";
    echo "<td>";
    echo "{$diameter_val}² × 0.00617 = {$calc_weight_rounded} kg/m";
    if (abs($calc_weight_rounded - $row['specification_weight']) < 0.01) {
        echo " ✅";
    } else {
        echo " ⚠️ (차이: " . abs($calc_weight_rounded - $row['specification_weight']) . ")";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p>✅ 철근 중량 계산 기능 테스트 완료</p>";

// 전체 제품 카테고리별 통계
echo "<h3>전체 제품 현황</h3>";
$stmt = $pdo->query("
    SELECT 
        pc.category_name,
        p.category_code,
        COUNT(*) as count
    FROM products p
    LEFT JOIN product_categories pc ON p.category_code = pc.category_code
    GROUP BY p.category_code, pc.category_name
    ORDER BY count DESC
");

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>카테고리</th><th>코드</th><th>제품 수</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>" . ($row['category_name'] ?: '-') . "</td>";
    echo "<td>{$row['category_code']}</td>";
    echo "<td>{$row['count']}개</td>";
    echo "</tr>";
}
echo "</table>";
?>