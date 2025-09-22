<?php
/**
 * 철근 데이터 입력 스크립트
 * 데이터 출처: /114/9/철근.xlsx
 * 철근 계산식: 직경² × 0.00617 × 길이 × 수량
 */

require_once 'db.php';

// 철근 규격별 대표 데이터 (주요 규격만 입력)
$rebar_data = [
    // HD 시리즈 (고장력철근 SD400)
    ['spec' => 'HD10', 'diameter' => 10, 'weight' => 0.56, 'material' => 'SD400'],
    ['spec' => 'HD13', 'diameter' => 13, 'weight' => 1.00, 'material' => 'SD400'],
    ['spec' => 'HD16', 'diameter' => 16, 'weight' => 1.56, 'material' => 'SD400'],
    ['spec' => 'HD19', 'diameter' => 19, 'weight' => 2.25, 'material' => 'SD400'],
    ['spec' => 'HD22', 'diameter' => 22, 'weight' => 3.04, 'material' => 'SD400'],
    ['spec' => 'HD25', 'diameter' => 25, 'weight' => 3.98, 'material' => 'SD400'],
    ['spec' => 'HD29', 'diameter' => 29, 'weight' => 5.04, 'material' => 'SD400'],
    ['spec' => 'HD32', 'diameter' => 32, 'weight' => 6.23, 'material' => 'SD400'],
    ['spec' => 'HD35', 'diameter' => 35, 'weight' => 7.51, 'material' => 'SD400'],
    ['spec' => 'HD38', 'diameter' => 38, 'weight' => 8.95, 'material' => 'SD400'],
    ['spec' => 'HD41', 'diameter' => 41, 'weight' => 10.50, 'material' => 'SD400'],
    ['spec' => 'HD51', 'diameter' => 51, 'weight' => 15.90, 'material' => 'SD400'],

    // D 시리즈 (일반철근 SD300)
    ['spec' => 'D10', 'diameter' => 10, 'weight' => 0.56, 'material' => 'SD300'],
    ['spec' => 'D13', 'diameter' => 13, 'weight' => 1.00, 'material' => 'SD300'],
    ['spec' => 'D16', 'diameter' => 16, 'weight' => 1.56, 'material' => 'SD300'],
    ['spec' => 'D19', 'diameter' => 19, 'weight' => 2.25, 'material' => 'SD300'],
    ['spec' => 'D22', 'diameter' => 22, 'weight' => 3.04, 'material' => 'SD300'],
    ['spec' => 'D25', 'diameter' => 25, 'weight' => 3.98, 'material' => 'SD300'],

    // UHD 시리즈 (초고장력철근 SD600)
    ['spec' => 'UHD10', 'diameter' => 10, 'weight' => 0.56, 'material' => 'SD600'],
    ['spec' => 'UHD13', 'diameter' => 13, 'weight' => 1.00, 'material' => 'SD600'],
    ['spec' => 'UHD16', 'diameter' => 16, 'weight' => 1.56, 'material' => 'SD600'],
    ['spec' => 'UHD19', 'diameter' => 19, 'weight' => 2.25, 'material' => 'SD600'],
    ['spec' => 'UHD22', 'diameter' => 22, 'weight' => 3.04, 'material' => 'SD600'],
    ['spec' => 'UHD25', 'diameter' => 25, 'weight' => 3.98, 'material' => 'SD600'],

    // SHD 시리즈 (SD500)
    ['spec' => 'SHD10', 'diameter' => 10, 'weight' => 0.56, 'material' => 'SD500'],
    ['spec' => 'SHD13', 'diameter' => 13, 'weight' => 1.00, 'material' => 'SD500'],
    ['spec' => 'SHD16', 'diameter' => 16, 'weight' => 1.56, 'material' => 'SD500'],
];

$success_count = 0;
$skip_count = 0;
$error_count = 0;

echo "<h2>철근 데이터 입력 시작</h2>";
echo "<pre>";

// 기존 rebar 데이터 확인
$check_existing = $pdo->query("SELECT COUNT(*) FROM products WHERE category_code = 'rebar'");
$existing_count = $check_existing->fetchColumn();
echo "기존 철근 데이터: {$existing_count}개\n\n";

foreach ($rebar_data as $item) {
    $spec = $item['spec'];
    $diameter = $item['diameter'];
    $weight = $item['weight'];
    $material = $item['material'];

    // 이미 존재하는지 확인
    $check_stmt = $pdo->prepare("SELECT id FROM products WHERE specifications = ? AND category_code = 'rebar'");
    $check_stmt->execute([$spec]);

    if ($check_stmt->fetch()) {
        echo "SKIP: 철근 {$spec} - 이미 존재함\n";
        $skip_count++;
        continue;
    }

    // unit_weight_data JSON 생성
    $unit_weight_data = [
        $spec => [
            $material => $weight
        ]
    ];

    // SQL 실행
    try {
        $stmt = $pdo->prepare("
            INSERT INTO products (
                category_code,
                product_name,
                specifications,
                description,
                unit,
                min_order_qty,
                stock_status,
                is_active,
                calculation_type,
                unit_weight_data,
                available_materials,
                has_calculator,
                display_mode,
                specification,
                specification_weight,
                dimensions
            ) VALUES (
                'rebar',
                :product_name,
                :specifications,
                :description,
                'TON',
                1,
                'in_stock',
                1,
                'linear',
                :unit_weight_data,
                :available_materials,
                1,
                'single',
                :specification,
                :specification_weight,
                :dimensions
            )
        ");

        $description = sprintf(
            '%s - 직경 %dmm, 단위중량 %.2fkg/m. 콘크리트 구조물의 인장강도 보강용 철근입니다.',
            $material, $diameter, $weight
        );

        $stmt->execute([
            ':product_name' => '철근 ' . $spec,
            ':specifications' => $spec,
            ':description' => $description,
            ':unit_weight_data' => json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
            ':available_materials' => json_encode([$material], JSON_UNESCAPED_UNICODE),
            ':specification' => $spec,
            ':specification_weight' => $weight,
            ':dimensions' => json_encode(['diameter' => $diameter], JSON_UNESCAPED_UNICODE)
        ]);

        echo "SUCCESS: 철근 {$spec} 입력 완료 (직경: {$diameter}mm, 단위중량: {$weight}kg/m, 재질: {$material})\n";
        $success_count++;

    } catch (Exception $e) {
        echo "ERROR: 철근 {$spec} 입력 실패 - " . $e->getMessage() . "\n";
        $error_count++;
    }
}

echo "</pre>";
echo "<h3>입력 완료</h3>";
echo "<ul>";
echo "<li>성공: {$success_count}개</li>";
echo "<li>건너뜀: {$skip_count}개</li>";
echo "<li>실패: {$error_count}개</li>";
echo "</ul>";

// 전체 철근 개수 확인
$count_stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE category_code = 'rebar'");
$total_count = $count_stmt->fetchColumn();
echo "<p>데이터베이스 내 전체 철근 제품 수: <strong>{$total_count}개</strong></p>";

// 재질별 통계
echo "<h4>재질별 철근 통계</h4>";
$material_stats = $pdo->query("
    SELECT
        JSON_UNQUOTE(JSON_EXTRACT(available_materials, '$[0]')) as material,
        COUNT(*) as count,
        MIN(specification_weight) as min_weight,
        MAX(specification_weight) as max_weight
    FROM products
    WHERE category_code = 'rebar'
    GROUP BY JSON_UNQUOTE(JSON_EXTRACT(available_materials, '$[0]'))
    ORDER BY material
");

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>재질</th><th>개수</th><th>최소 단위중량</th><th>최대 단위중량</th></tr>";
while ($row = $material_stats->fetch()) {
    echo "<tr>";
    echo "<td>{$row['material']}</td>";
    echo "<td>{$row['count']}개</td>";
    echo "<td>{$row['min_weight']} kg/m</td>";
    echo "<td>{$row['max_weight']} kg/m</td>";
    echo "</tr>";
}
echo "</table>";
?>