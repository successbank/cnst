<?php
require_once '../db.php';

// 판재류 제품 샘플 데이터
$plate_products = [
    'deck-plate' => [
        'name' => '데크플레이트',
        'type' => 'sheet',
        'data' => [
            '0.5T*600*2400' => 14.1,
            '0.5T*600*3000' => 17.7,
            '0.5T*600*3600' => 21.2,
            '0.6T*600*2400' => 17.0,
            '0.6T*600*3000' => 21.2,
            '0.6T*600*3600' => 25.4,
            '0.8T*600*2400' => 22.6,
            '0.8T*600*3000' => 28.3,
            '0.8T*600*3600' => 33.9,
            '1.0T*600*2400' => 28.3,
            '1.0T*600*3000' => 35.3,
            '1.0T*600*3600' => 42.4,
            '1.2T*600*2400' => 33.9,
            '1.2T*600*3000' => 42.4,
            '1.2T*600*3600' => 50.9
        ]
    ],
    'temporary-deck' => [
        'name' => '복공판',
        'type' => 'sheet',
        'data' => [
            '22T*914*1829' => 290.0,
            '22T*1219*2438' => 515.0,
            '22T*1524*3048' => 805.0,
            '25T*914*1829' => 330.0,
            '25T*1219*2438' => 585.0,
            '25T*1524*3048' => 915.0,
            '32T*914*1829' => 422.0,
            '32T*1219*2438' => 749.0,
            '32T*1524*3048' => 1171.0,
            '38T*914*1829' => 501.0,
            '38T*1219*2438' => 889.0,
            '38T*1524*3048' => 1389.0
        ]
    ],
    'rail' => [
        'name' => '레일',
        'type' => 'linear',
        'data' => [
            '12kg/m' => 12.0,
            '15kg/m' => 15.0,
            '22kg/m' => 22.4,
            '30kg/m' => 30.1,
            '37kg/m' => 37.1,
            '40kg/m' => 40.6,
            '50kg/m' => 50.4,
            '60kg/m' => 60.8
        ]
    ]
];

try {
    echo "<pre>\n";
    echo "판재류 제품 데이터 추가 시작...\n\n";
    
    foreach ($plate_products as $category_code => $product_data) {
        // 단위중량 데이터 준비
        $unit_weight_data = [];
        $specifications = [];
        $materials = ['SS400']; // 기본 재질
        
        foreach ($product_data['data'] as $spec => $weight) {
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
                $product_data['name'],
                $product_data['type'],
                json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
                json_encode($materials, JSON_UNESCAPED_UNICODE),
                json_encode($specifications, JSON_UNESCAPED_UNICODE)
            ]);
            
            $parent_id = $pdo->lastInsertId();
            echo "✓ {$product_data['name']} 부모 제품 생성 완료\n";
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
                $product_data['name'],
                $product_data['type'],
                json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
                json_encode($materials, JSON_UNESCAPED_UNICODE),
                json_encode($specifications, JSON_UNESCAPED_UNICODE),
                $parent_id
            ]);
            echo "✓ {$product_data['name']} 부모 제품 업데이트 완료\n";
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
            $product_name = $product_data['name'] . ' ' . $specification;
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
                $product_data['type'],
                json_encode($spec_unit_weight_data, JSON_UNESCAPED_UNICODE),
                json_encode($materials, JSON_UNESCAPED_UNICODE),
                json_encode([$specification], JSON_UNESCAPED_UNICODE)
            ]);
            
            $created_count++;
        }
        
        echo "  → {$created_count}개 규격별 제품 생성\n";
    }
    
    echo "\n판재류 제품 데이터 추가 완료!\n";
    echo "</pre>\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "<a href='../products.php'>제품 목록 페이지로 이동</a>";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>