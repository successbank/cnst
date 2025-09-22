<?php
/**
 * I형강 데이터 입력 스크립트
 * 데이터 출처: /114/product/I형강(빔).xlsx
 * 계산식: 소수점 첫째자리까지 계산
 */

require_once 'db.php';

// I형강 데이터 (21개 규격)
$i_beam_data = [
    ['spec' => '100×75×5×8', 'weight' => 13.9, 'material' => 'SS400'],
    ['spec' => '125×75×5.5×9.5', 'weight' => 16.1],
    ['spec' => '150×75×5.5×9.5', 'weight' => 17.1],
    ['spec' => '150×125×8.5×14', 'weight' => 36.2],
    ['spec' => '180×100×6×10', 'weight' => 23.6],
    ['spec' => '200×100×70×10', 'weight' => 26.0],
    ['spec' => '200×150×9×16', 'weight' => 50.4],
    ['spec' => '250×125×7.5×12.5', 'weight' => 38.3],
    ['spec' => '250×125×10×19', 'weight' => 55.5],
    ['spec' => '300×150×8×13', 'weight' => 48.3],
    ['spec' => '300×150×10×18.5', 'weight' => 65.5],
    ['spec' => '300×150×11.5×22', 'weight' => 76.8],
    ['spec' => '350×150×9×15', 'weight' => 58.5],
    ['spec' => '350×150×12×24', 'weight' => 87.2],
    ['spec' => '400×150×10×18', 'weight' => 72.0],
    ['spec' => '400×150×12.5×25', 'weight' => 95.8],
    ['spec' => '450×175×11×20', 'weight' => 91.7],
    ['spec' => '450×175×13×26', 'weight' => 115.0],
    ['spec' => '600×190×13×25', 'weight' => 133.0],
    ['spec' => '600×190×16×35', 'weight' => 176.0],
];

$success_count = 0;
$skip_count = 0;
$error_count = 0;

echo "<h2>I형강 데이터 입력 시작</h2>";
echo "<pre>";

// 기존 i-beam 데이터 확인 (이미 1개 있음)
$check_existing = $pdo->query("SELECT COUNT(*) FROM products WHERE category_code = 'i-beam'");
$existing_count = $check_existing->fetchColumn();
echo "기존 I형강 데이터: {$existing_count}개\n\n";

foreach ($i_beam_data as $item) {
    $spec = $item['spec'];
    $weight = $item['weight'];
    $materials = isset($item['material']) ? [$item['material']] : ['SS400'];

    // 이미 존재하는지 확인
    $check_stmt = $pdo->prepare("SELECT id FROM products WHERE specifications = ? AND category_code = 'i-beam'");
    $check_stmt->execute([$spec]);

    if ($check_stmt->fetch()) {
        echo "SKIP: I형강 {$spec} - 이미 존재함\n";
        $skip_count++;
        continue;
    }

    // unit_weight_data JSON 생성
    $unit_weight_data = [];
    foreach ($materials as $material) {
        $unit_weight_data[$spec][$material] = $weight;
    }

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
                specification_weight
            ) VALUES (
                'i-beam',
                :product_name,
                :specifications,
                '건축 구조물의 보와 기둥에 사용되는 I자형 구조용 강재입니다.',
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
                :specification_weight
            )
        ");

        $stmt->execute([
            ':product_name' => 'I형강 ' . $spec,
            ':specifications' => $spec,
            ':unit_weight_data' => json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
            ':available_materials' => json_encode($materials, JSON_UNESCAPED_UNICODE),
            ':specification' => $spec,
            ':specification_weight' => $weight
        ]);

        echo "SUCCESS: I형강 {$spec} 입력 완료 (단위중량: {$weight}kg/m)\n";
        $success_count++;

    } catch (Exception $e) {
        echo "ERROR: I형강 {$spec} 입력 실패 - " . $e->getMessage() . "\n";
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

// 전체 I형강 개수 확인
$count_stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE category_code = 'i-beam'");
$total_count = $count_stmt->fetchColumn();
echo "<p>데이터베이스 내 전체 I형강 제품 수: <strong>{$total_count}개</strong></p>";
?>