<?php
/**
 * 부등변ㄱ형강 데이터 입력 스크립트
 * 데이터 출처: /114/5/부등변ㄱ형강.xlsx
 */

require_once 'db.php';

// 부등변ㄱ형강 데이터 (15개 규격)
$unequal_angle_data = [
    // 재질 정보가 있는 제품들
    ['spec' => '50×30×3T', 'weight' => 1.83, 'material' => 'SS400'],
    ['spec' => '90×75×9T', 'weight' => 11.0, 'materials' => ['SS400', 'A36']],
    ['spec' => '100×75×7T', 'weight' => 9.32, 'material' => 'SS490'],
    ['spec' => '100×75×10T', 'weight' => 13.0, 'material' => 'SS540'],
    ['spec' => '125×75×7T', 'weight' => 10.7, 'material' => 'SM400A'],
    ['spec' => '125×75×10T', 'weight' => 14.9, 'material' => 'SM400B'],
    ['spec' => '125×75×13T', 'weight' => 19.1, 'material' => 'SM490A'],
    ['spec' => '125×90×10T', 'weight' => 16.1, 'material' => 'SM490B'],
    ['spec' => '125×90×13T', 'weight' => 20.6, 'material' => 'SM490YA'],
    ['spec' => '150×90×9T', 'weight' => 16.4, 'material' => 'SM490YB'],
    // 재질 정보가 없는 제품들 (SS400 기본값)
    ['spec' => '150×90×12T', 'weight' => 21.5],
    ['spec' => '150×100×9T', 'weight' => 17.1],
    ['spec' => '150×100×12T', 'weight' => 22.4],
    ['spec' => '150×100×15T', 'weight' => 27.7],
];

$success_count = 0;
$skip_count = 0;
$error_count = 0;

echo "<h2>부등변ㄱ형강 데이터 입력 시작</h2>";
echo "<pre>";

// 카테고리 추가 (없으면)
try {
    $check_cat = $pdo->prepare("SELECT id FROM product_categories WHERE category_code = 'unequal-angle'");
    $check_cat->execute();
    if (!$check_cat->fetch()) {
        $pdo->exec("
            INSERT INTO product_categories (category_code, category_name, is_active)
            VALUES ('unequal-angle', '부등변ㄱ형강', 1)
        ");
        echo "카테고리 'unequal-angle' 추가 완료\n";
    }
} catch (Exception $e) {
    echo "카테고리 추가 실패: " . $e->getMessage() . "\n";
}

foreach ($unequal_angle_data as $item) {
    $spec = $item['spec'];
    $weight = $item['weight'];

    // 재질 정보 처리
    if (isset($item['materials'])) {
        $materials = $item['materials'];
    } elseif (isset($item['material'])) {
        $materials = [$item['material']];
    } else {
        $materials = ['SS400']; // 기본값
    }

    // 이미 존재하는지 확인
    $check_stmt = $pdo->prepare("SELECT id FROM products WHERE specifications = ? AND category_code = 'unequal-angle'");
    $check_stmt->execute([$spec]);

    if ($check_stmt->fetch()) {
        echo "SKIP: 부등변ㄱ형강 {$spec} - 이미 존재함\n";
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
                'unequal-angle',
                :product_name,
                :specifications,
                '비대칭 L자형 구조용 강재로, 특수한 각도와 강도가 필요한 구조물에 사용됩니다.',
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
            ':product_name' => '부등변ㄱ형강 ' . $spec,
            ':specifications' => $spec,
            ':unit_weight_data' => json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
            ':available_materials' => json_encode($materials, JSON_UNESCAPED_UNICODE),
            ':specification' => $spec,
            ':specification_weight' => $weight
        ]);

        echo "SUCCESS: 부등변ㄱ형강 {$spec} 입력 완료 (단위중량: {$weight}kg/m)\n";
        $success_count++;

    } catch (Exception $e) {
        echo "ERROR: 부등변ㄱ형강 {$spec} 입력 실패 - " . $e->getMessage() . "\n";
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

// 전체 부등변ㄱ형강 개수 확인
$count_stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE category_code = 'unequal-angle'");
$total_count = $count_stmt->fetchColumn();
echo "<p>데이터베이스 내 전체 부등변ㄱ형강 제품 수: <strong>{$total_count}개</strong></p>";
?>