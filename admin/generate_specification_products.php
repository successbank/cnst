<?php
require_once '../db.php';

// 규격별로 제품을 생성할 카테고리 목록
// 계산기가 있는 모든 제품들을 포함
$specification_categories = [
    'angle',        // ㄱ형강
    'channel',      // ㄷ형강
    'flat-bar',     // 평철
    'round-bar',    // 환봉
    'steel-plate'   // HR철판
];

try {
    echo "<pre>\n";
    echo "규격별 제품 생성 시작...\n\n";
    
    foreach ($specification_categories as $category_code) {
        // 부모 제품 조회
        $stmt = $pdo->prepare("
            SELECT * FROM products 
            WHERE category_code = ? AND parent_product_id IS NULL 
            LIMIT 1
        ");
        $stmt->execute([$category_code]);
        $parent_product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$parent_product) {
            echo "✗ {$category_code}: 부모 제품을 찾을 수 없습니다.\n";
            continue;
        }
        
        // 부모 제품의 display_mode 업데이트
        $update_stmt = $pdo->prepare("
            UPDATE products 
            SET display_mode = 'by_specification' 
            WHERE id = ?
        ");
        $update_stmt->execute([$parent_product['id']]);
        
        // unit_weight_data 파싱
        $unit_weight_data = json_decode($parent_product['unit_weight_data'], true);
        $available_materials = json_decode($parent_product['available_materials'], true);
        
        if (!$unit_weight_data) {
            echo "✗ {$category_code}: 단위중량 데이터가 없습니다.\n";
            continue;
        }
        
        $created_count = 0;
        
        // 각 규격별로 제품 생성
        foreach ($unit_weight_data as $specification => $weight_by_material) {
            // 기본 재질의 단위중량 가져오기
            $default_weight = reset($weight_by_material);
            
            // 기존 제품 확인
            $check_stmt = $pdo->prepare("
                SELECT id FROM products 
                WHERE parent_product_id = ? AND specification = ?
            ");
            $check_stmt->execute([$parent_product['id'], $specification]);
            
            if ($check_stmt->fetchColumn()) {
                continue; // 이미 존재함
            }
            
            // 제품명 생성
            $product_name = $parent_product['product_name'] . ' ' . $specification;
            
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
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'single', 1)
            ");
            
            // 해당 규격만의 unit_weight_data 생성
            $spec_unit_weight_data = [$specification => $weight_by_material];
            
            $insert_stmt->execute([
                $category_code,
                $parent_product['id'],
                $product_name,
                $specification,
                $default_weight,
                $parent_product['calculation_type'],
                json_encode($spec_unit_weight_data, JSON_UNESCAPED_UNICODE),
                json_encode($available_materials, JSON_UNESCAPED_UNICODE),
                json_encode([$specification], JSON_UNESCAPED_UNICODE),
                1 // has_calculator
            ]);
            
            $created_count++;
        }
        
        echo "✓ {$parent_product['product_name']}: {$created_count}개 규격별 제품 생성 완료\n";
    }
    
    echo "\n규격별 제품 생성 완료!\n";
    echo "</pre>\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "<a href='../products.php'>제품 목록 페이지로 이동</a>";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>