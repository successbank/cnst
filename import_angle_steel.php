<?php
require_once 'db.php';

// JSON 데이터 읽기
$json_data = file_get_contents('114/5/angle_steel_data.json');
$products = json_decode($json_data, true);

if (!$products) {
    die("Failed to read JSON data\n");
}

echo "Starting import of " . count($products) . " angle steel products...\n";

$success_count = 0;
$error_count = 0;

// UTF-8 설정
$pdo->exec("SET NAMES utf8mb4");

foreach ($products as $product) {
    $specification = $product['specification'];
    $unit_weight = $product['unit_weight'];
    $type = $product['type'];
    
    // × 문자 사용, T 제거
    $spec_formatted = str_replace(['*', 'T'], ['×', ''], $specification);
    
    // 제품명 생성
    if ($type === 'equal') {
        // 등변 ㄱ형강
        $parts = explode('×', $spec_formatted);
        if (count($parts) >= 2) {
            $product_name = "ㄱ형강 " . $parts[0] . "×" . $parts[1];
        } else {
            $product_name = "ㄱ형강 " . $spec_formatted;
        }
        $description = "ㄱ형강(등변) 규격: " . $spec_formatted . ", 단중: " . $unit_weight . "kg/m";
    } else {
        // 부등변 ㄱ형강
        $parts = explode('×', $spec_formatted);
        if (count($parts) >= 2) {
            $product_name = "부등변 ㄱ형강 " . $parts[0] . "×" . $parts[1];
        } else {
            $product_name = "부등변 ㄱ형강 " . $spec_formatted;
        }
        $description = "부등변 ㄱ형강 규격: " . $spec_formatted . ", 단중: " . $unit_weight . "kg/m";
    }
    
    try {
        // 단위중량 테이블에 추가
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO unit_weights (specification, unit_weight, is_active) 
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$spec_formatted, $unit_weight]);
        
        // 제품이 이미 존재하는지 확인
        $stmt = $pdo->prepare("SELECT id FROM products WHERE specifications = ? AND category_code = 'angle'");
        $stmt->execute([$spec_formatted]);
        if ($stmt->fetchColumn()) {
            echo "- Product already exists: $spec_formatted\n";
            continue;
        }
        
        // 제품 추가
        $stmt = $pdo->prepare("
            INSERT INTO products (
                category_code, product_name, specifications, 
                description, weight, material, unit, min_order_qty, 
                stock_status, is_active, view_count, base_length,
                min_price, max_price
            ) VALUES (
                'angle', ?, ?, 
                ?, ?, 'SS400', 'TON', 1, 
                'in_stock', 1, 0, 6,
                830, 830
            )
        ");
        
        $weight_str = $unit_weight . 'kg/m';
        $stmt->execute([
            $product_name, 
            $spec_formatted, 
            $description,
            $weight_str
        ]);
        
        $success_count++;
        echo "✓ Added: $product_name - $spec_formatted\n";
        
    } catch (PDOException $e) {
        $error_count++;
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Import Complete ===\n";
echo "Successfully imported: $success_count products\n";
echo "Errors: $error_count\n";
?>