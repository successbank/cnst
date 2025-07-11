<?php
require_once 'db.php';

// UTF-8 설정
$pdo->exec("SET NAMES utf8mb4");

$product_configs = [
    [
        'json_file' => '114/6/channel_steel_data.json',
        'category_code' => 'channel',
        'product_prefix' => 'ㄷ형강',
        'description_prefix' => 'ㄷ형강(찬넬)'
    ],
    [
        'json_file' => '114/6/c_beam_data.json',
        'category_code' => 'c-beam',
        'product_prefix' => 'C형강',
        'description_prefix' => 'C형강'
    ],
    [
        'json_file' => '114/6/round_bar_data.json',
        'category_code' => 'round-bar',
        'product_prefix' => '환봉',
        'description_prefix' => '환봉'
    ],
    [
        'json_file' => '114/6/flat_bar_data.json',
        'category_code' => 'flat-bar',
        'product_prefix' => '평철',
        'description_prefix' => '평철'
    ]
];

$total_success = 0;
$total_error = 0;

foreach ($product_configs as $config) {
    echo "\n=== Processing {$config['product_prefix']} ===\n";
    
    // JSON 데이터 읽기
    $json_data = file_get_contents($config['json_file']);
    $products = json_decode($json_data, true);
    
    if (!$products) {
        echo "Failed to read {$config['json_file']}\n";
        continue;
    }
    
    echo "Found " . count($products) . " products\n";
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($products as $product) {
        $specification = $product['specification'];
        $unit_weight = $product['unit_weight'];
        
        // × 문자 사용, T 제거
        $spec_formatted = str_replace(['*', 'T', 't'], ['×', '', ''], $specification);
        
        // 제품명 생성
        if ($config['category_code'] === 'round-bar') {
            // 환봉은 직경만 표시
            $product_name = $config['product_prefix'] . " " . $spec_formatted;
        } else if ($config['category_code'] === 'flat-bar') {
            // 평철은 두께×폭
            $product_name = $config['product_prefix'] . " " . $spec_formatted;
        } else {
            // ㄷ형강, C형강은 첫 두 숫자 사용
            $parts = explode('×', $spec_formatted);
            if (count($parts) >= 2) {
                $product_name = $config['product_prefix'] . " " . $parts[0] . "×" . $parts[1];
            } else {
                $product_name = $config['product_prefix'] . " " . $spec_formatted;
            }
        }
        
        // description 생성
        $description = $config['description_prefix'] . " 규격: " . $spec_formatted . ", 단중: " . $unit_weight . "kg/m";
        
        try {
            // 단위중량 테이블에 추가
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO unit_weights (specification, unit_weight, is_active) 
                VALUES (?, ?, 1)
            ");
            $stmt->execute([$spec_formatted, $unit_weight]);
            
            // 제품이 이미 존재하는지 확인
            $stmt = $pdo->prepare("SELECT id FROM products WHERE specifications = ? AND category_code = ?");
            $stmt->execute([$spec_formatted, $config['category_code']]);
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
                    ?, ?, ?, 
                    ?, ?, 'SS400', 'TON', 1, 
                    'in_stock', 1, 0, 6,
                    830, 830
                )
            ");
            
            $weight_str = $unit_weight . 'kg/m';
            $stmt->execute([
                $config['category_code'],
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
    
    echo "Subtotal - Success: $success_count, Errors: $error_count\n";
    $total_success += $success_count;
    $total_error += $error_count;
}

echo "\n=== Total Import Complete ===\n";
echo "Total successfully imported: $total_success products\n";
echo "Total errors: $total_error\n";
?>