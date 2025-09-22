<?php
/**
 * /114/2/2.xls 파일의 H형강 데이터를 데이터베이스에 입력
 */

require_once 'db.php';

// H형강 데이터 배열 (2.xls 파일 기반)
$h_beam_data = [
    // 재질 정보가 있는 제품들
    ['spec' => '100×100×6×8', 'weight' => 17.2, 'material' => 'SS400'],
    ['spec' => '125×125×6.5×9', 'weight' => 23.8, 'materials' => ['SS400', 'A36']],
    ['spec' => '150×75×5×7', 'weight' => 14.0, 'material' => 'SHN400'],
    ['spec' => '148×100×6×9', 'weight' => 21.1, 'material' => 'SS490'],
    ['spec' => '150×150×7×10', 'weight' => 31.5, 'material' => 'SS540'],
    ['spec' => '198×99×4.5×7', 'weight' => 18.2, 'material' => 'SM400A'],
    ['spec' => '200×100×5.5×8', 'weight' => 21.3, 'material' => 'SM400B'],
    ['spec' => '194×150×6×9', 'weight' => 30.6, 'material' => 'SHN490'],
    ['spec' => '200×200×8×12', 'weight' => 49.9, 'material' => 'SM490A'],
    ['spec' => '200×204×12×12', 'weight' => 56.2, 'material' => 'SM490B'],
    ['spec' => '208×202×10×16', 'weight' => 65.7, 'material' => 'SM490YA'],
    ['spec' => '248×124×5×8', 'weight' => 25.7, 'material' => 'SM490YB'],
    // 재질 정보가 없는 제품들 (SS400 기본값 사용)
    ['spec' => '250×125×6×9', 'weight' => 29.6],
    ['spec' => '244×175×7×11', 'weight' => 44.1],
    ['spec' => '244×252×11×11', 'weight' => 64.4],
    ['spec' => '248×249×8×13', 'weight' => 66.5],
    ['spec' => '250×250×9×14', 'weight' => 72.4],
    ['spec' => '250×255×14×14', 'weight' => 82.2],
    ['spec' => '298×149×5.5×8', 'weight' => 32.0],
    ['spec' => '300×150×6.5×9', 'weight' => 36.7],
    ['spec' => '294×200×8×12', 'weight' => 56.8],
    ['spec' => '298×201×9×14', 'weight' => 65.4],
    ['spec' => '294×302×12×12', 'weight' => 84.5],
    ['spec' => '298×299×9×14', 'weight' => 87.0],
    ['spec' => '300×300×10×15', 'weight' => 94.0],
    ['spec' => '300×305×15×15', 'weight' => 106.0],
    ['spec' => '304×301×11×17', 'weight' => 106.0],
    ['spec' => '310×305×15×20', 'weight' => 130.0],
    ['spec' => '310×310×20×20', 'weight' => 142.0],
    ['spec' => '346×174×6×9', 'weight' => 41.4],
    ['spec' => '350×175×7×11', 'weight' => 49.6],
    ['spec' => '354×176×8×13', 'weight' => 57.8],
    ['spec' => '336×249×8×12', 'weight' => 69.2],
    ['spec' => '340×250×9×14', 'weight' => 79.7],
    ['spec' => '344×348×10×16', 'weight' => 115.0],
    ['spec' => '344×354×16×16', 'weight' => 131.0],
    ['spec' => '350×350×12×19', 'weight' => 137.0],
    ['spec' => '350×357×19×19', 'weight' => 156.0],
    ['spec' => '396×199×7×11', 'weight' => 56.6],
    ['spec' => '400×200×8×13', 'weight' => 66.0],
    ['spec' => '404×201×9×15', 'weight' => 75.5],
    ['spec' => '386×299×9×14', 'weight' => 94.3],
    ['spec' => '390×300×10×16', 'weight' => 107.0],
    ['spec' => '388×402×15×15', 'weight' => 140.0],
    ['spec' => '394×398×11×18', 'weight' => 147.0],
    ['spec' => '394×405×18×18', 'weight' => 168.0],
    ['spec' => '400×400×13×21', 'weight' => 172.0],
    ['spec' => '400×408×21×21', 'weight' => 197.0],
    ['spec' => '406×403×16×24', 'weight' => 200.0],
    ['spec' => '414×405×18×28', 'weight' => 232.0],
    ['spec' => '428×407×20×35', 'weight' => 283.0],
    ['spec' => '458×417×30×50', 'weight' => 415.0],
    ['spec' => '498×432×45×70', 'weight' => 605.0],
    ['spec' => '446×199×8×12', 'weight' => 66.2],
    ['spec' => '450×200×9×14', 'weight' => 76.0],
    ['spec' => '434×299×10×15', 'weight' => 106.0],
    ['spec' => '440×300×11×18', 'weight' => 124.0],
    ['spec' => '496×199×9×14', 'weight' => 79.5],
    ['spec' => '500×200×10×16', 'weight' => 89.6],
    ['spec' => '506×201×11×19', 'weight' => 103.0],
    ['spec' => '482×300×11×15', 'weight' => 114.0],
    ['spec' => '488×300×11×18', 'weight' => 128.0],
    ['spec' => '596×199×10×15', 'weight' => 94.6],
    ['spec' => '600×200×11×17', 'weight' => 106.0],
    ['spec' => '606×201×12×20', 'weight' => 120.0],
    ['spec' => '612×202×13×23', 'weight' => 134.0],
    ['spec' => '582×300×12×17', 'weight' => 137.0],
    ['spec' => '588×300×12×20', 'weight' => 151.0],
    ['spec' => '594×302×14×23', 'weight' => 175.0],
    ['spec' => '692×300×13×20', 'weight' => 166.0],
    ['spec' => '700×300×13×24', 'weight' => 185.0],
    ['spec' => '708×302×15×28', 'weight' => 215.0],
    ['spec' => '792×300×14×22', 'weight' => 191.0],
    ['spec' => '800×300×14×26', 'weight' => 210.0],
    ['spec' => '808×302×16×30', 'weight' => 241.0],
    ['spec' => '890×299×15×23', 'weight' => 213.0],
    ['spec' => '900×300×16×28', 'weight' => 243.0],
    ['spec' => '912×302×18×34', 'weight' => 286.0],
    ['spec' => '918×303×19×37', 'weight' => 307.0],
];

$success_count = 0;
$skip_count = 0;
$error_count = 0;

echo "<h2>H형강 데이터 입력 시작</h2>";
echo "<pre>";

foreach ($h_beam_data as $item) {
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
    $check_stmt = $pdo->prepare("SELECT id FROM products WHERE specifications = ? AND category_code = 'h-beam'");
    $check_stmt->execute([$spec]);

    if ($check_stmt->fetch()) {
        echo "SKIP: H형강 {$spec} - 이미 존재함\n";
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
                'h-beam',
                :product_name,
                :specifications,
                '건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.',
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
            ':product_name' => 'H형강 ' . $spec,
            ':specifications' => $spec,
            ':unit_weight_data' => json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
            ':available_materials' => json_encode($materials, JSON_UNESCAPED_UNICODE),
            ':specification' => $spec,
            ':specification_weight' => $weight
        ]);

        echo "SUCCESS: H형강 {$spec} 입력 완료 (단위중량: {$weight}kg/m)\n";
        $success_count++;

    } catch (Exception $e) {
        echo "ERROR: H형강 {$spec} 입력 실패 - " . $e->getMessage() . "\n";
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

// 전체 H형강 개수 확인
$count_stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE category_code = 'h-beam'");
$total_count = $count_stmt->fetchColumn();
echo "<p>데이터베이스 내 전체 H형강 제품 수: <strong>{$total_count}개</strong></p>";
?>