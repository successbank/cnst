<?php
require_once 'db.php';

// JSON 데이터 읽기
$json_data = file_get_contents('114/3/lightweight_h_beam_data.json');
$products = json_decode($json_data, true);

if (!$products) {
    die("Failed to read JSON data\n");
}

echo "Starting import of " . count($products) . " lightweight H-beam products...\n";

$success_count = 0;
$error_count = 0;

// UTF-8 설정
$pdo->exec("SET NAMES utf8mb4");

foreach ($products as $product) {
    $specification = $product['specification'];
    $unit_weight = $product['unit_weight'];
    
    // LHB 제거하고 × 문자 사용
    $spec_formatted = str_replace(['LHB ', '*'], ['', '×'], $specification);
    
    // 제품명 생성
    $parts = explode('×', $spec_formatted);
    if (count($parts) >= 2) {
        $product_name = "경량 H형강 " . $parts[0] . "×" . $parts[1];
    } else {
        $product_name = "경량 H형강 " . $spec_formatted;
    }
    
    // description 생성 (H형강 형식 참고)
    $description = "경량 H형강 규격: " . $spec_formatted . ", 단중: " . $unit_weight . "kg/m";
    
    try {
        // 단위중량 테이블에 추가
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO unit_weights (specification, unit_weight, is_active) 
            VALUES (?, ?, 1)
        ");
        $stmt->execute([$spec_formatted, $unit_weight]);
        
        // 제품 추가
        $stmt = $pdo->prepare("
            INSERT INTO products (
                category_code, product_name, specifications, 
                description, weight, material, unit, min_order_qty, 
                stock_status, is_active, view_count, base_length,
                min_price, max_price
            ) VALUES (
                'light-h-beam', ?, ?, 
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