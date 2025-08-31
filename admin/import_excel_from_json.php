<?php
require_once '../db.php';

// 카테고리 매핑 및 규격별 표시 여부
$category_mapping = [
    'C형강' => ['code' => 'c-beam', 'show_spec' => true, 'json_path' => '../114/6/c_beam_data.json'],
    '부등변ㄱ형강' => ['code' => 'unequal-angle', 'show_spec' => true, 'json_path' => '../114/5/angle_steel_data.json', 'filter' => 'unequal'],
    '사각파이프' => ['code' => 'square-pipe', 'show_spec' => true, 'json_path' => '../114/7/square_pipe_data.json'],
    'BS파이프' => ['code' => 'bs-pipe', 'show_spec' => true, 'json_path' => '../114/8/bs_pipe_data.json'],
    'KS파이프' => ['code' => 'ks-pipe', 'show_spec' => true, 'json_path' => '../114/8/ks_pipe_data.json'],
    '구조관' => ['code' => 'structural-pipe', 'show_spec' => true, 'json_path' => '../114/8/structural_pipe_data.json'],
    '강관파일' => ['code' => 'steel-pipe-pile', 'show_spec' => true, 'json_path' => '../114/8/steel_pipe_pile_data.json'],
    '데크플레이트' => ['code' => 'deck-plate', 'show_spec' => true, 'json_path' => '../114/7/deck_plate_data.json'],
    '레일' => ['code' => 'rail', 'show_spec' => true, 'json_path' => '../114/7/rail_data.json'],
    '복공판' => ['code' => 'temporary-deck', 'show_spec' => true, 'json_path' => '../114/8/temporary_deck_data.json'],
    '쉬트파일' => ['code' => 'sheet-pile', 'show_spec' => true, 'json_path' => '../114/7/sheet_pile_data.json'],
    '압력배관' => ['code' => 'pressure-pipe', 'show_spec' => true, 'json_path' => '../114/8/pressure_pipe_data.json'],
    '전선관' => ['code' => 'conduit-pipe', 'show_spec' => true, 'json_path' => '../114/8/conduit_pipe_data.json'],
    '단관비계' => ['code' => 'scaffold-pipe', 'show_spec' => true, 'json_path' => '../114/8/scaffold_pipe_data.json'],
    'I형강' => ['code' => 'i-beam', 'show_spec' => true, 'json_path' => '../114/4/i_beam_data.json'],
    '경량H형강' => ['code' => 'lightweight-h-beam', 'show_spec' => true, 'json_path' => '../114/3/lightweight_h_beam_data.json'],
    'ㄱ형강' => ['code' => 'angle-steel', 'show_spec' => true, 'json_path' => '../114/5/angle_steel_data.json', 'filter' => 'equal'],
    'ㄷ형강' => ['code' => 'channel-steel', 'show_spec' => true, 'json_path' => '../114/6/channel_steel_data.json'],
    '평철' => ['code' => 'flat-bar', 'show_spec' => true, 'json_path' => '../114/6/flat_bar_data.json'],
    '환봉' => ['code' => 'round-bar', 'show_spec' => true, 'json_path' => '../114/6/round_bar_data.json'],
    '철판' => ['code' => 'steel-plate', 'show_spec' => true, 'json_path' => '../114/7/steel_plate_data.json']
];

// 계산 유형 결정
$linear_products = ['c-beam', 'unequal-angle', 'square-pipe', 'bs-pipe', 'ks-pipe', 
                   'structural-pipe', 'steel-pipe-pile', 'rail', 'pressure-pipe', 
                   'conduit-pipe', 'scaffold-pipe', 'i-beam', 'lightweight-h-beam',
                   'angle-steel', 'channel-steel', 'flat-bar', 'round-bar'];
$sheet_products = ['deck-plate', 'temporary-deck', 'steel-plate'];

// JSON 파일에서 데이터 로드
function loadJsonData($json_path, $filter = null) {
    if (!file_exists($json_path)) {
        return [];
    }
    
    $json_content = file_get_contents($json_path);
    $data = json_decode($json_content, true);
    
    if (!$data) {
        return [];
    }
    
    $result = [];
    foreach ($data as $item) {
        // 필터가 있고 type 필드가 있는 경우 필터링
        if ($filter !== null && isset($item['type']) && $item['type'] !== $filter) {
            continue;
        }
        
        $specification = $item['specification'];
        $unit_weight = $item['unit_weight'];
        
        // 기본 재질은 SS400으로 설정
        $material = isset($item['material']) ? $item['material'] : 'SS400';
        
        if (!isset($result[$specification])) {
            $result[$specification] = [];
        }
        $result[$specification][$material] = $unit_weight;
    }
    
    return $result;
}

try {
    echo "<pre>\n";
    echo "JSON 파일 기반 Excel 데이터 임포트 시작...\n\n";
    
    foreach ($category_mapping as $korean_name => $config) {
        $category_code = $config['code'];
        $show_spec = $config['show_spec'];
        $json_path = $config['json_path'];
        $filter = isset($config['filter']) ? $config['filter'] : null;
        
        // JSON 파일에서 데이터 로드
        $unit_weight_data = loadJsonData($json_path, $filter);
        
        if (empty($unit_weight_data)) {
            echo "- {$korean_name}: 데이터 없음 또는 파일 없음 ({$json_path})\n";
            continue;
        }
        
        // 규격 및 재질 목록 추출
        $specifications = array_keys($unit_weight_data);
        $materials = [];
        foreach ($unit_weight_data as $spec_data) {
            $materials = array_unique(array_merge($materials, array_keys($spec_data)));
        }
        if (empty($materials)) {
            $materials = ['SS400']; // 기본 재질
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
            echo "✓ {$korean_name} 부모 제품 생성 완료 (규격: " . count($specifications) . "개)\n";
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
            echo "✓ {$korean_name} 부모 제품 업데이트 완료 (규격: " . count($specifications) . "개)\n";
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
                    json_encode(array_keys($weight_data), JSON_UNESCAPED_UNICODE),
                    json_encode([$specification], JSON_UNESCAPED_UNICODE)
                ]);
                
                $created_count++;
            }
            
            echo "  → {$created_count}개 규격별 제품 생성\n";
        }
    }
    
    echo "\nJSON 파일 기반 데이터 임포트 완료!\n";
    echo "</pre>\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "<a href='../products.php'>제품 목록 페이지로 이동</a>";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>