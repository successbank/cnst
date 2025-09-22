<?php
/**
 * ㄱ형강(등변앵글) 데이터 입력 스크립트
 * 데이터 출처: /114/5/ㄱ형강.xlsx
 */

require_once 'db.php';

// ㄱ형강 데이터 (43개 규격)
$angle_steel_data = [
    // 재질 정보가 있는 제품들
    ['spec' => '25×25×3T', 'weight' => 1.12, 'material' => 'SS400'],
    ['spec' => '30×30×3T', 'weight' => 1.36, 'materials' => ['SS400', 'A36']],
    ['spec' => '40×40×3T', 'weight' => 1.83, 'material' => 'SS490'],
    ['spec' => '40×40×4T', 'weight' => 2.42, 'material' => 'SS540'],
    ['spec' => '40×40×5T', 'weight' => 2.95, 'material' => 'SM400A'],
    ['spec' => '45×45×4T', 'weight' => 2.74, 'material' => 'SM400B'],
    ['spec' => '50×50×4T', 'weight' => 3.06, 'material' => 'SM490A'],
    ['spec' => '50×50×6T', 'weight' => 4.43, 'material' => 'SM490B'],
    ['spec' => '60×60×4T', 'weight' => 3.68, 'material' => 'SM490YA'],
    ['spec' => '60×60×5T', 'weight' => 4.55, 'material' => 'SM490YB'],
    // 재질 정보가 없는 제품들 (SS400 기본값)
    ['spec' => '60×60×6T', 'weight' => 5.37],
    ['spec' => '65×65×6T', 'weight' => 5.91],
    ['spec' => '65×65×8T', 'weight' => 7.66],
    ['spec' => '70×70×6T', 'weight' => 6.38],
    ['spec' => '75×75×6T', 'weight' => 6.85],
    ['spec' => '75×75×9T', 'weight' => 9.96],
    ['spec' => '75×75×12T', 'weight' => 13.0],
    ['spec' => '80×80×6T', 'weight' => 7.32],
    ['spec' => '80×80×7T', 'weight' => 8.48],
    ['spec' => '90×90×6T', 'weight' => 8.28],
    ['spec' => '90×90×7T', 'weight' => 9.59],
    ['spec' => '90×90×9T', 'weight' => 12.1],
    ['spec' => '90×90×10T', 'weight' => 13.3],
    ['spec' => '90×90×13T', 'weight' => 17.0],
    ['spec' => '100×100×7T', 'weight' => 10.7],
    ['spec' => '100×100×10T', 'weight' => 14.9],
    ['spec' => '100×100×13T', 'weight' => 19.1],
    ['spec' => '120×120×8T', 'weight' => 14.7],
    ['spec' => '130×130×9T', 'weight' => 17.9],
    ['spec' => '130×130×12T', 'weight' => 23.4],
    ['spec' => '130×130×15T', 'weight' => 28.8],
    ['spec' => '150×150×10T', 'weight' => 22.9],
    ['spec' => '150×150×12T', 'weight' => 27.3],
    ['spec' => '150×150×15T', 'weight' => 33.6],
    ['spec' => '150×150×19T', 'weight' => 41.9],
    ['spec' => '175×175×12T', 'weight' => 31.8],
    ['spec' => '175×175×15T', 'weight' => 39.4],
    ['spec' => '200×200×15T', 'weight' => 45.3],
    ['spec' => '200×200×20T', 'weight' => 59.7],
    ['spec' => '200×200×25T', 'weight' => 73.6],
    ['spec' => '250×250×25T', 'weight' => 93.7],
    ['spec' => '250×250×35T', 'weight' => 128.0],
];

$success_count = 0;
$skip_count = 0;
$error_count = 0;

echo "<h2>ㄱ형강(등변앵글) 데이터 입력 시작</h2>";
echo "<pre>";

// 카테고리 추가 (없으면)
try {
    $check_cat = $pdo->prepare("SELECT id FROM product_categories WHERE category_code = 'angle'");
    $check_cat->execute();
    if (!$check_cat->fetch()) {
        $pdo->exec("
            INSERT INTO product_categories (category_code, category_name, is_active)
            VALUES ('angle', 'ㄱ형강(등변앵글)', 1)
        ");
        echo "카테고리 'angle' 추가 완료\n";
    }
} catch (Exception $e) {
    echo "카테고리 추가 실패: " . $e->getMessage() . "\n";
}

foreach ($angle_steel_data as $item) {
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
    $check_stmt = $pdo->prepare("SELECT id FROM products WHERE specifications = ? AND category_code = 'angle'");
    $check_stmt->execute([$spec]);

    if ($check_stmt->fetch()) {
        echo "SKIP: ㄱ형강 {$spec} - 이미 존재함\n";
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
                'angle',
                :product_name,
                :specifications,
                '구조물의 보강, 프레임, 브라켓 등에 사용되는 L자형 구조용 강재입니다.',
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
            ':product_name' => 'ㄱ형강 ' . $spec,
            ':specifications' => $spec,
            ':unit_weight_data' => json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
            ':available_materials' => json_encode($materials, JSON_UNESCAPED_UNICODE),
            ':specification' => $spec,
            ':specification_weight' => $weight
        ]);

        echo "SUCCESS: ㄱ형강 {$spec} 입력 완료 (단위중량: {$weight}kg/m)\n";
        $success_count++;

    } catch (Exception $e) {
        echo "ERROR: ㄱ형강 {$spec} 입력 실패 - " . $e->getMessage() . "\n";
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

// 전체 ㄱ형강 개수 확인
$count_stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE category_code = 'angle'");
$total_count = $count_stmt->fetchColumn();
echo "<p>데이터베이스 내 전체 ㄱ형강 제품 수: <strong>{$total_count}개</strong></p>";
?>