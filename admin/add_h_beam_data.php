<?php
require_once '../db.php';

// H형강 및 I형강 샘플 데이터
$beam_products = [
    'h-beam' => [
        'name' => 'H형강',
        'type' => 'linear',
        'data' => [
            '100*100*6*8' => 17.2,
            '125*125*6.5*9' => 23.8,
            '150*150*7*10' => 31.5,
            '175*175*7.5*11' => 40.2,
            '200*200*8*12' => 49.9,
            '250*250*9*14' => 72.4,
            '300*300*10*15' => 94.0,
            '300*300*12*12' => 94.0,
            '350*350*12*19' => 137.0,
            '400*400*13*21' => 172.0,
            '450*400*18*28' => 233.0,
            '500*300*11*18' => 114.0,
            '600*300*12*20' => 137.0,
            '700*300*13*24' => 173.0,
            '800*300*14*26' => 202.0,
            '900*300*16*28' => 243.0
        ]
    ],
    'i-beam' => [
        'name' => 'I형강',
        'type' => 'linear',
        'data' => [
            '100*75*5*7' => 10.1,
            '125*75*5.5*7' => 11.9,
            '150*100*5.5*7' => 15.5,
            '180*100*6*8' => 18.9,
            '200*100*6.5*8.5' => 21.7,
            '220*110*7*9.2' => 26.7,
            '250*125*7.5*10' => 33.4,
            '300*150*8*11' => 43.1,
            '350*150*9*12' => 52.1,
            '400*150*10*13' => 61.7,
            '450*175*11*14' => 77.6,
            '500*200*12*16' => 99.5,
            '550*200*13*17' => 112.0,
            '600*200*14*18' => 125.0
        ]
    ]
];

try {
    echo "<pre>\n";
    echo "H형강 및 I형강 데이터 추가 시작...\n\n";
    
    foreach ($beam_products as $category_code => $product_data) {
        // 단위중량 데이터 준비
        $unit_weight_data = [];
        $specifications = [];
        $materials = ['SS400', 'SM490A', 'SM490B']; // 일반적인 재질들
        
        foreach ($product_data['data'] as $spec => $weight) {
            $unit_weight_data[$spec] = [
                'SS400' => $weight,
                'SM490A' => $weight,
                'SM490B' => $weight
            ];
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
            $default_weight = $weight_data['SS400'];
            
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
    
    echo "\nH형강 및 I형강 데이터 추가 완료!\n";
    echo "</pre>\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "<a href='../products.php'>제품 목록 페이지로 이동</a>";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>