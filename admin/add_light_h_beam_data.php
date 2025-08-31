<?php
require_once '../db.php';

// 경량 H형강 샘플 데이터
$light_h_beam_data = [
    'name' => '경량H형강',
    'type' => 'linear',
    'data' => [
        '100*50*3.2*4.5' => 7.45,
        '125*60*3.2*4.5' => 8.96,
        '150*75*3.2*4.5' => 11.2,
        '175*90*3.2*5' => 14.4,
        '200*100*3.2*5' => 16.7,
        '250*125*3.2*5' => 21.3,
        '250*125*4.5*6' => 27.3,
        '300*150*4.5*6' => 33.0,
        '300*150*4.5*9' => 40.8,
        '350*175*4.5*7' => 41.4,
        '350*175*4.5*9' => 47.2,
        '400*200*4.5*7' => 47.9,
        '400*200*6*9' => 63.0,
        '450*200*6*9' => 69.3,
        '500*200*6*9' => 75.6,
        '500*200*7*12' => 88.7,
        '600*200*7*12' => 101.3,
        '600*200*8*14' => 117.0
    ]
];

try {
    echo "<pre>\n";
    echo "경량 H형강 데이터 추가 시작...\n\n";
    
    $category_code = 'light-h-beam';
    
    // 단위중량 데이터 준비
    $unit_weight_data = [];
    $specifications = [];
    $materials = ['SS400']; // 기본 재질
    
    foreach ($light_h_beam_data['data'] as $spec => $weight) {
        $unit_weight_data[$spec] = ['SS400' => $weight];
        $specifications[] = $spec;
    }
    
    // 부모 제품 확인
    $check_stmt = $pdo->prepare("SELECT id FROM products WHERE category_code = ? AND parent_product_id IS NULL LIMIT 1");
    $check_stmt->execute([$category_code]);
    $parent_id = $check_stmt->fetchColumn();
    
    if (!$parent_id) {
        // 부모 제품 생성
        $stmt = $pdo->prepare("
            INSERT INTO products (
                category_code, 
                product_name, 
                calculation_type,
                unit_weight_data,
                available_materials,
                available_sizes,
                has_calculator,
                display_mode,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, 1, 'by_specification', 1)
        ");
        
        $stmt->execute([
            $category_code,
            $light_h_beam_data['name'],
            $light_h_beam_data['type'],
            json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
            json_encode($materials, JSON_UNESCAPED_UNICODE),
            json_encode($specifications, JSON_UNESCAPED_UNICODE)
        ]);
        
        $parent_id = $pdo->lastInsertId();
        echo "✓ 경량H형강 부모 제품 생성 완료\n";
    } else {
        // 부모 제품 업데이트
        $stmt = $pdo->prepare("
            UPDATE products SET
                product_name = ?,
                calculation_type = ?,
                unit_weight_data = ?,
                available_materials = ?,
                available_sizes = ?,
                has_calculator = 1,
                display_mode = 'by_specification'
            WHERE id = ?
        ");
        
        $stmt->execute([
            $light_h_beam_data['name'],
            $light_h_beam_data['type'],
            json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
            json_encode($materials, JSON_UNESCAPED_UNICODE),
            json_encode($specifications, JSON_UNESCAPED_UNICODE),
            $parent_id
        ]);
        echo "✓ 경량H형강 부모 제품 업데이트 완료\n";
    }
    
    // 규격별 제품 생성
    $created_count = 0;
    
    foreach ($unit_weight_data as $specification => $weight_data) {
        // 기존 제품 확인
        $check_spec_stmt = $pdo->prepare("
            SELECT id FROM products 
            WHERE parent_product_id = ? AND specification = ?
        ");
        $check_spec_stmt->execute([$parent_id, $specification]);
        
        if ($check_spec_stmt->fetchColumn()) {
            continue; // 이미 존재함
        }
        
        // 제품명 생성
        $product_name = $light_h_beam_data['name'] . ' ' . $specification;
        $default_weight = reset($weight_data);
        
        // 규격별 제품 생성
        $insert_stmt = $pdo->prepare("
            INSERT INTO products (
                category_code,
                parent_product_id,
                product_name,
                specification,
                specification_weight,
                calculation_type,
                unit_weight_data,
                available_materials,
                available_sizes,
                has_calculator,
                display_mode,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'single', 1)
        ");
        
        $spec_unit_weight_data = [$specification => $weight_data];
        
        $insert_stmt->execute([
            $category_code,
            $parent_id,
            $product_name,
            $specification,
            $default_weight,
            $light_h_beam_data['type'],
            json_encode($spec_unit_weight_data, JSON_UNESCAPED_UNICODE),
            json_encode($materials, JSON_UNESCAPED_UNICODE),
            json_encode([$specification], JSON_UNESCAPED_UNICODE)
        ]);
        
        $created_count++;
    }
    
    echo "  → {$created_count}개 규격별 제품 생성\n";
    echo "\n경량H형강 데이터 추가 완료!\n";
    echo "</pre>\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "<a href='../products.php'>제품 목록 페이지로 이동</a>";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>