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
            'product_type' => '데크플레이트'
        ],
        [
            'json_file' => '114/7/rail_data.json',
            'category_code' => 'rail',
            'category_name' => '레일',
            'product_type' => '레일'
        ],
        [
            'json_file' => '114/7/square_pipe_data.json',
            'category_code' => 'square-pipe',
            'category_name' => '사각파이프',
            'product_type' => '사각파이프'
        ],
        [
            'json_file' => '114/7/sheet_pile_data.json',
            'category_code' => 'sheet-pile',
            'category_name' => '쉬트파일',
            'product_type' => '쉬트파일'
        ],
        [
            'json_file' => '114/7/steel_plate_data.json',
            'category_code' => 'steel-plate',
            'category_name' => '철판',
            'product_type' => '철판'
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
            echo "카테고리 '" . $config['category_name'] . "' 생성됨\n";
        } else {
            echo "기존 카테고리 '" . $config['category_name'] . "' 사용\n";
        }
        
        // JSON 파일 읽기
        $json_content = file_get_contents($config['json_file']);
        $products = json_decode($json_content, true);
        
        if (!$products) {
            echo "JSON 파일 읽기 실패: " . $config['json_file'] . "\n";
            continue;
        }
        
        // 중복 확인을 위해 기존 제품명 가져오기
        $existing_check = $pdo->prepare("
            SELECT product_name FROM products WHERE category_code = ?
        ");
        $existing_check->execute([$config['category_code']]);
        $existing_names = $existing_check->fetchAll(PDO::FETCH_COLUMN);
        
        // unit_weights에서 기존 스펙 확인
        $existing_specs_check = $pdo->prepare("
            SELECT specification FROM unit_weights WHERE product_type = ?
        ");
        $existing_specs_check->execute([$config['product_type']]);
        $existing_specs = $existing_specs_check->fetchAll(PDO::FETCH_COLUMN);
        
        // 제품 삽입 준비
        $insert_product = $pdo->prepare("
            INSERT INTO products (
                category_code, product_name, specifications, 
                description, material, price, 
                min_price, max_price, base_length
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        
        // unit_weight 삽입 준비
        $insert_weight = $pdo->prepare("
            INSERT INTO unit_weights (
                product_type, specification, unit_weight, material
            ) VALUES (
                ?, ?, ?, ?
            )
        ");
        
        $imported_count = 0;
        $skipped_count = 0;
        
        foreach ($products as $product) {
            $spec = $product['specification'];
            $unit_weight = $product['unit_weight'];
            
            // 제품명 생성
            $product_name = $config['product_type'] . " " . $spec;
            
            // 중복 확인
            if (in_array($product_name, $existing_names)) {
                $skipped_count++;
                continue;
            }
            
            // description 생성
            $description = $config['product_type'] . " 규격: " . $spec . ", 단중: " . $unit_weight . "kg/m";
            
            // unit_weights 테이블에 먼저 삽입 (없는 경우에만)
            if (!in_array($spec, $existing_specs)) {
                try {
                    $insert_weight->execute([
                        $config['product_type'],
                        $spec,
                        $unit_weight,
                        'SS400'
                    ]);
                    $existing_specs[] = $spec; // 배열에 추가
                } catch (PDOException $e) {
                    // 이미 존재하는 경우 무시
                }
            }
            
            // products 테이블에 삽입
            $values = [
                $config['category_code'],
                $product_name,
                $spec,
                $description,
                'SS400',  // 기본 재질
                830,      // 기준단가
                830,      // 최저단가
                830,      // 최대단가
                6         // 기준길이 (철판, 데크플레이트는 다를 수 있음)
            ];
            
            try {
                $insert_product->execute($values);
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