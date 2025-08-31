<?php
require_once '../db.php';

// 카테고리 매핑 및 규격별 표시 여부
$category_mapping = [
    'C형강' => ['code' => 'c-beam', 'show_spec' => true],
    '부등변ㄱ형강' => ['code' => 'unequal-angle', 'show_spec' => true],
    '사각파이프' => ['code' => 'square-pipe', 'show_spec' => true],
    'BS파이프' => ['code' => 'bs-pipe', 'show_spec' => true],
    'KS파이프' => ['code' => 'ks-pipe', 'show_spec' => true],
    '구조관' => ['code' => 'structural-pipe', 'show_spec' => true],
    '강관파일' => ['code' => 'steel-pipe-pile', 'show_spec' => true],
    '데크플레이트' => ['code' => 'deck-plate', 'show_spec' => true],
    '레일' => ['code' => 'rail', 'show_spec' => true],
    '복공판' => ['code' => 'temporary-deck', 'show_spec' => true],
    '쉬트파일' => ['code' => 'sheet-pile', 'show_spec' => true],
    '압력배관' => ['code' => 'pressure-pipe', 'show_spec' => true],
    '전선관' => ['code' => 'conduit-pipe', 'show_spec' => true],
    '단관비계' => ['code' => 'scaffold-pipe', 'show_spec' => true]
];

// 계산 유형 결정
$linear_products = ['c-beam', 'unequal-angle', 'square-pipe', 'bs-pipe', 'ks-pipe', 
                   'structural-pipe', 'steel-pipe-pile', 'rail', 'pressure-pipe', 
                   'conduit-pipe', 'scaffold-pipe'];
$sheet_products = ['deck-plate', 'temporary-deck'];

// 간단한 CSV 형식으로 Excel 데이터 시뮬레이션
function simulateExcelData($korean_name) {
    $sample_data = [
        'C형강' => [
            '75*45*15*1.6T' => 2.13,
            '100*50*20*2.3T' => 3.72,
            '125*60*30*2.3T' => 5.16,
            '150*75*30*2.3T' => 6.45,
            '175*75*30*2.3T' => 7.39,
            '200*80*30*3.2T' => 11.3,
            '250*80*30*3.2T' => 13.6
        ],
        '부등변ㄱ형강' => [
            '75*50*6T' => 5.23,
            '90*75*6T' => 6.85,
            '90*75*9T' => 10.0,
            '100*75*7T' => 8.79,
            '100*75*10T' => 12.2,
            '125*75*7T' => 10.2,
            '125*75*10T' => 14.3,
            '125*90*10T' => 15.5,
            '150*90*9T' => 15.0,
            '150*90*12T' => 19.7,
            '150*100*10T' => 17.9,
            '150*100*12T' => 21.2
        ],
        '사각파이프' => [
            '12*12*0.7T' => 0.243,
            '16*16*1.0T' => 0.461,
            '19*19*1.2T' => 0.665,
            '25*25*1.2T' => 0.889,
            '25*25*1.6T' => 1.17,
            '30*30*1.6T' => 1.42,
            '40*40*1.6T' => 1.92,
            '40*40*2.3T' => 2.69,
            '50*50*1.6T' => 2.42,
            '50*50*2.3T' => 3.41,
            '50*50*3.2T' => 4.58,
            '60*60*2.3T' => 4.13,
            '60*60*3.2T' => 5.58,
            '75*75*2.3T' => 5.21,
            '75*75*3.2T' => 7.08,
            '100*100*3.2T' => 9.58,
            '100*100*4.5T' => 13.2,
            '125*125*4.5T' => 16.7,
            '150*150*4.5T' => 20.2,
            '150*150*6.0T' => 26.5
        ]
    ];
    
    return $sample_data[$korean_name] ?? [];
}

try {
    echo "<pre>\n";
    echo "Excel 데이터 임포트 및 규격별 제품 생성 시작...\n\n";
    
    foreach ($category_mapping as $korean_name => $config) {
        $category_code = $config['code'];
        $show_spec = $config['show_spec'];
        
        // 시뮬레이션 데이터 가져오기
        $excel_data = simulateExcelData($korean_name);
        
        if (empty($excel_data)) {
            echo "- {$korean_name}: 데이터 없음\n";
            continue;
        }
        
        // 단위중량 데이터 준비
        $unit_weight_data = [];
        $specifications = [];
        $materials = ['SS400']; // 기본 재질
        
        foreach ($excel_data as $spec => $weight) {
            $unit_weight_data[$spec] = ['SS400' => $weight];
            $specifications[] = $spec;
        }
        
        // 계산 유형 결정
        $calculation_type = in_array($category_code, $linear_products) ? 'linear' : 
                           (in_array($category_code, $sheet_products) ? 'sheet' : 'linear');
        
        // 부모 제품 확인 또는 생성
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
                ) VALUES (?, ?, ?, ?, ?, ?, 1, ?, 1)
            ");
            
            $stmt->execute([
                $category_code,
                $korean_name,
                $calculation_type,
                json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
                json_encode($materials, JSON_UNESCAPED_UNICODE),
                json_encode($specifications, JSON_UNESCAPED_UNICODE),
                $show_spec ? 'by_specification' : 'single'
            ]);
            
            $parent_id = $pdo->lastInsertId();
            echo "✓ {$korean_name} 부모 제품 생성 완료\n";
        } else {
            // 부모 제품 업데이트
            $stmt = $pdo->prepare("
                UPDATE products SET
                    unit_weight_data = ?,
                    available_materials = ?,
                    available_sizes = ?,
                    has_calculator = 1,
                    display_mode = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
                json_encode($materials, JSON_UNESCAPED_UNICODE),
                json_encode($specifications, JSON_UNESCAPED_UNICODE),
                $show_spec ? 'by_specification' : 'single',
                $parent_id
            ]);
            echo "✓ {$korean_name} 부모 제품 업데이트 완료\n";
        }
        
        // 규격별 제품 생성 (show_spec이 true인 경우)
        if ($show_spec) {
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
                $product_name = $korean_name . ' ' . $specification;
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
                    $calculation_type,
                    json_encode($spec_unit_weight_data, JSON_UNESCAPED_UNICODE),
                    json_encode($materials, JSON_UNESCAPED_UNICODE),
                    json_encode([$specification], JSON_UNESCAPED_UNICODE)
                ]);
                
                $created_count++;
            }
            
            echo "  → {$created_count}개 규격별 제품 생성\n";
        }
    }
    
    echo "\n데이터 임포트 완료!\n";
    echo "</pre>\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "<a href='../products.php'>제품 목록 페이지로 이동</a>";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>