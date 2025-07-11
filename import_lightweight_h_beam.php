<?php
require_once 'db.php';

// Read the JSON data
$json_data = file_get_contents('114/3/lightweight_h_beam_data.json');
$products = json_decode($json_data, true);

if (!$products) {
    die("Failed to read JSON data\n");
}

echo "Starting import of " . count($products) . " lightweight H-beam products...\n";

$success_count = 0;
$error_count = 0;

foreach ($products as $product) {
    $specification = $product['specification'];
    $unit_weight = $product['unit_weight'];
    
    // Parse specification to get dimensions
    // Example: LHB 150*100*3.2*4.5 -> 150×100×3.2×4.5
    $spec_formatted = str_replace(['LHB ', '*'], ['', '×'], $specification);
    
    // Extract dimensions for product name
    $parts = explode('×', $spec_formatted);
    if (count($parts) >= 2) {
        $product_name = "경량 H형강 " . $parts[0] . "×" . $parts[1];
    } else {
        $product_name = "경량 H형강 " . $spec_formatted;
    }
    
    try {
        // Check if unit weight exists
        $stmt = $pdo->prepare("SELECT id FROM unit_weights WHERE specification = ? AND is_active = 1");
        $stmt->execute([$spec_formatted]);
        $unit_weight_id = $stmt->fetchColumn();
        
        if (!$unit_weight_id) {
            // Insert unit weight
            $stmt = $pdo->prepare("
                INSERT INTO unit_weights (specification, unit_weight, is_active, created_at) 
                VALUES (?, ?, 1, NOW())
            ");
            $stmt->execute([$spec_formatted, $unit_weight]);
            echo "Added unit weight for: $spec_formatted ($unit_weight kg/m)\n";
        }
        
        // Check if product already exists
        $stmt = $pdo->prepare("SELECT id FROM products WHERE specifications = ? AND category_code = 'light-h-beam'");
        $stmt->execute([$spec_formatted]);
        $existing_product = $stmt->fetchColumn();
        
        if (!$existing_product) {
            // Insert product
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    category_code, product_name, specifications, 
                    weight, material, unit, min_order_qty, 
                    stock_status, is_active, created_at, 
                    view_count, base_length
                ) VALUES (
                    'light-h-beam', ?, ?, 
                    ?, 'SS400', 'TON', 1, 
                    'in_stock', 1, NOW(), 
                    0, 6
                )
            ");
            
            $weight_str = $unit_weight . 'kg/m';
            $stmt->execute([$product_name, $spec_formatted, $weight_str]);
            
            $success_count++;
            echo "✓ Added product: $product_name - $spec_formatted\n";
        } else {
            echo "- Product already exists: $spec_formatted\n";
        }
        
    } catch (PDOException $e) {
        $error_count++;
        echo "✗ Error adding $product_name: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Import Complete ===\n";
echo "Successfully imported: $success_count products\n";
echo "Errors: $error_count\n";
?>