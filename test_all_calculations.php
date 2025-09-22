<?php
/**
 * 전체 제품 중량 계산 테스트
 */

require_once 'db.php';
require_once 'includes/SteelCalculator.php';

$calculator = new SteelCalculator($pdo);

echo "<h2>전체 제품 중량 계산 테스트</h2>";
echo "<hr>";

// 카테고리별 제품 통계
echo "<h3>📊 데이터베이스 제품 통계</h3>";
$stmt = $pdo->query("
    SELECT
        pc.category_code,
        pc.category_name,
        COUNT(p.id) as product_count,
        MIN(p.specification_weight) as min_weight,
        MAX(p.specification_weight) as max_weight
    FROM product_categories pc
    LEFT JOIN products p ON pc.category_code = p.category_code
    WHERE p.is_active = 1
    GROUP BY pc.category_code, pc.category_name
    ORDER BY product_count DESC
");

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr>";
echo "<th>카테고리</th>";
echo "<th>제품명</th>";
echo "<th>제품 수</th>";
echo "<th>최소 단위중량</th>";
echo "<th>최대 단위중량</th>";
echo "</tr>";

while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>{$row['category_code']}</td>";
    echo "<td>{$row['category_name']}</td>";
    echo "<td style='text-align:center'>{$row['product_count']}개</td>";
    echo "<td style='text-align:right'>" . number_format($row['min_weight'], 2) . " kg/m</td>";
    echo "<td style='text-align:right'>" . number_format($row['max_weight'], 2) . " kg/m</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

// 각 카테고리별 계산 테스트
echo "<h3>🧮 카테고리별 계산 테스트</h3>";

$test_cases = [
    [
        'category' => 'h-beam',
        'name' => 'H형강',
        'spec' => '100×100×6×8',
        'unit_weight' => 17.2,
        'length' => 11,
        'quantity' => 10,
        'rounding' => '정수 반올림'
    ],
    [
        'category' => 'angle',
        'name' => 'ㄱ형강',
        'spec' => '25×25×3T',
        'unit_weight' => 1.12,
        'length' => 8,
        'quantity' => 14,
        'rounding' => '소수점 둘째자리'
    ],
    [
        'category' => 'unequal-angle',
        'name' => '부등변ㄱ형강',
        'spec' => '50×30×3T',
        'unit_weight' => 1.83,
        'length' => 9,
        'quantity' => 9,
        'rounding' => '소수점 둘째자리'
    ],
    [
        'category' => 'i-beam',
        'name' => 'I형강',
        'spec' => '100×75×5×8',
        'unit_weight' => 13.9,
        'length' => 11,
        'quantity' => 9,
        'rounding' => '소수점 첫째자리'
    ]
];

foreach ($test_cases as $test) {
    echo "<div style='margin: 20px 0; padding: 15px; background: #f5f5f5; border-left: 4px solid #1428A0;'>";
    echo "<h4>{$test['name']} ({$test['category']}) - {$test['rounding']}</h4>";
    echo "<p>규격: {$test['spec']}, 단위중량: {$test['unit_weight']}kg/m, 길이: {$test['length']}m, 수량: {$test['quantity']}본</p>";

    // 계산 수행
    $specifications = ['unit_weight' => $test['unit_weight']];
    $result = $calculator->calculateWeight($test['category'], $specifications, $test['length'], $test['quantity']);

    echo "<p><strong>계산 결과: " . number_format($result, 2) . " kg</strong></p>";
    echo "</div>";
}

echo "<hr>";

// 전체 제품 개수 확인
$total_stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
$total = $total_stmt->fetchColumn();

echo "<h3>✅ 테스트 완료</h3>";
echo "<p>전체 등록 제품: <strong>{$total}개</strong></p>";

// 카테고리 목록
echo "<h4>등록된 카테고리</h4>";
echo "<ul>";
$cat_stmt = $pdo->query("SELECT DISTINCT category_code FROM products ORDER BY category_code");
while ($cat = $cat_stmt->fetch()) {
    $count = $pdo->query("SELECT COUNT(*) FROM products WHERE category_code = '{$cat['category_code']}'")->fetchColumn();
    echo "<li>{$cat['category_code']}: {$count}개</li>";
}
echo "</ul>";
?>