<?php
require_once 'db.php';

try {
    // UTF-8 설정
    $pdo->exec("SET NAMES utf8mb4");
    
    // 카테고리별 제품 설정
    $product_configs = [
        [
            'json_file' => '114/7/deck_plate_data.json',
            'category_code' => 'deck-plate',
            'category_name' => '데크플레이트',
            'product_prefix' => '데크플레이트',
            'description_prefix' => '데크플레이트',
            'unit_weight_type' => 'per_sqm' // 단중이 kg/㎡ 단위
        ],
        [
            'json_file' => '114/7/rail_data.json',
            'category_code' => 'rail',
            'category_name' => '레일',
            'product_prefix' => '레일',
            'description_prefix' => '레일',
            'unit_weight_type' => 'per_meter' // 단중이 kg/m 단위
        ],
        [
            'json_file' => '114/7/square_pipe_data.json',
            'category_code' => 'square-pipe',
            'category_name' => '사각파이프',
            'product_prefix' => '사각파이프',
            'description_prefix' => '사각파이프',
            'unit_weight_type' => 'per_meter' // 단중이 kg/m 단위
        ],
        [
            'json_file' => '114/7/sheet_pile_data.json',
            'category_code' => 'sheet-pile',
            'category_name' => '쉬트파일',
            'product_prefix' => '쉬트파일',
            'description_prefix' => '쉬트파일',
            'unit_weight_type' => 'per_meter' // 단중이 kg/m 단위
        ],
        [
            'json_file' => '114/7/steel_plate_data.json',
            'category_code' => 'steel-plate',
            'category_name' => '철판',
            'product_prefix' => '철판',
            'description_prefix' => '철판',
            'unit_weight_type' => 'per_piece' // 단중이 kg/장 단위
        ]
    ];
    
    $total_imported = 0;
    
    foreach ($product_configs as $config) {
        echo "\n처리 중: " . $config['category_name'] . "\n";
        
        // 카테고리 확인 및 생성
        $check = $pdo->prepare("SELECT id FROM product_categories WHERE category_code = ?");
        $check->execute([$config['category_code']]);
        $category = $check->fetch();
        
        if (!$category) {
            // 카테고리 생성
            $insert_category = $pdo->prepare("
                INSERT INTO product_categories (category_code, category_name, created_at) 
                VALUES (?, ?, NOW())
            ");
            $insert_category->execute([$config['category_code'], $config['category_name']]);
            $category_id = $pdo->lastInsertId();
            echo "카테고리 '" . $config['category_name'] . "' 생성됨\n";
        } else {
            $category_id = $category['id'];
            echo "기존 카테고리 '" . $config['category_name'] . "' 사용\n";
        }
        
        // JSON 파일 읽기
        $json_content = file_get_contents($config['json_file']);
        $products = json_decode($json_content, true);
        
        if (!$products) {
            echo "JSON 파일 읽기 실패: " . $config['json_file'] . "\n";
            continue;
        }
        
        // 카테고리 코드 가져오기
        $cat_code_check = $pdo->prepare("SELECT category_code FROM product_categories WHERE id = ?");
        $cat_code_check->execute([$category_id]);
        $category_code = $cat_code_check->fetchColumn();
        
        // 중복 확인을 위해 기존 제품명 가져오기
        $existing_check = $pdo->prepare("
            SELECT product_name FROM products WHERE category_code = ?
        ");
        $existing_check->execute([$category_code]);
        $existing_names = $existing_check->fetchAll(PDO::FETCH_COLUMN);
        
        // 제품 삽입
        $insert_stmt = $pdo->prepare("
            INSERT INTO products (
                category_code, product_name, description, specifications, 
                weight, material, price, 
                min_price, max_price, base_length
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        $imported_count = 0;
        $skipped_count = 0;
        
        foreach ($products as $product) {
            $spec = $product['specification'];
            $unit_weight = $product['unit_weight'];
            
            // 제품명 생성
            $product_name = $config['product_prefix'] . " " . $spec;
            
            // 중복 확인
            if (in_array($product_name, $existing_names)) {
                $skipped_count++;
                continue;
            }
            
            // description 생성 (단위 포함)
            $unit_text = match($config['unit_weight_type']) {
                'per_sqm' => 'kg/㎡',
                'per_piece' => 'kg/장',
                default => 'kg/m'
            };
            
            $description = $config['description_prefix'] . " 규격: " . $spec . ", 단중: " . $unit_weight . $unit_text;
            
            // 기본 길이 설정 (철판과 데크플레이트는 길이 개념이 다름)
            $base_length = in_array($config['unit_weight_type'], ['per_piece', 'per_sqm']) ? 1 : 6;
            
            $values = [
                $category_code,
                $product_name,
                $description,
                $spec,
                $unit_weight . $unit_text,  // weight 필드에 단위 포함
                'SS400',  // 기본 재질
                830,      // 기준단가
                830,      // 최저단가
                830,      // 최대단가
                $base_length
            ];
            
            try {
                $insert_stmt->execute($values);
                $imported_count++;
            } catch (PDOException $e) {
                echo "오류 발생 (제품: $product_name): " . $e->getMessage() . "\n";
            }
        }
        
        echo $config['category_name'] . " 카테고리: " . $imported_count . "개 제품 추가됨";
        if ($skipped_count > 0) {
            echo " (중복 건너뜀: " . $skipped_count . "개)";
        }
        echo "\n";
        
        $total_imported += $imported_count;
    }
    
    echo "\n전체 작업 완료! 총 " . $total_imported . "개 제품이 추가되었습니다.\n";
    
} catch (PDOException $e) {
    echo "데이터베이스 오류: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "오류: " . $e->getMessage() . "\n";
}
?>