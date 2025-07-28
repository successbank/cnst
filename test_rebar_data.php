<?php
require_once 'db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Check rebar specifications
    echo "=== Rebar Specifications ===\n";
    $stmt = $pdo->query("SELECT * FROM rebar_specifications WHERE is_active = 1 ORDER BY id");
    $specs = $stmt->fetchAll();
    echo "Total specifications: " . count($specs) . "\n\n";
    
    foreach ($specs as $spec) {
        echo "ID: {$spec['id']}, Name: {$spec['spec_name']}, Unit Weight: {$spec['unit_weight']}\n";
    }
    
    // Check D10 spec specifically
    echo "\n=== D10 Specification ===\n";
    $stmt = $pdo->prepare("SELECT * FROM rebar_specifications WHERE spec_name = ? AND is_active = 1");
    $stmt->execute(['D10']);
    $d10_spec = $stmt->fetch();
    
    if ($d10_spec) {
        echo "D10 Spec ID: {$d10_spec['id']}\n";
        
        // Check length info for D10
        echo "\n=== D10 Length Info ===\n";
        $stmt = $pdo->prepare("SELECT * FROM rebar_length_info WHERE spec_id = ? ORDER BY length");
        $stmt->execute([$d10_spec['id']]);
        $lengths = $stmt->fetchAll();
        echo "Total length records: " . count($lengths) . "\n\n";
        
        foreach ($lengths as $length) {
            echo "Length: {$length['length']}m, Pieces/ton: {$length['pieces_per_ton']}, ";
            echo "Total weight: {$length['total_weight']}kg, Weight/piece: {$length['weight_per_piece']}kg\n";
        }
    } else {
        echo "D10 specification not found\n";
    }
    
    // Check materials
    echo "\n=== Rebar Materials ===\n";
    $stmt = $pdo->query("SELECT * FROM rebar_materials WHERE is_active = 1 ORDER BY display_order");
    $materials = $stmt->fetchAll();
    echo "Total materials: " . count($materials) . "\n\n";
    
    foreach ($materials as $mat) {
        echo "ID: {$mat['id']}, ";
        echo "Code: {$mat['material_code']}, Additional price: {$mat['additional_price']}원/kg\n";
    }
    
    // Check products with both category codes
    echo "\n=== Rebar Products (category_code = 'rebar') ===\n";
    $stmt = $pdo->query("SELECT id, product_name, price, category_code FROM products WHERE category_code = 'rebar' AND is_active = 1 ORDER BY product_name");
    $products = $stmt->fetchAll();
    echo "Total rebar products: " . count($products) . "\n\n";
    
    foreach ($products as $prod) {
        echo "ID: {$prod['id']}, Name: {$prod['product_name']}, Price: {$prod['price']}원/kg\n";
    }
    
    echo "\n=== Rebar Products (category_code = '114') ===\n";
    $stmt = $pdo->query("SELECT id, product_name, price, category_code FROM products WHERE category_code = '114' AND is_active = 1 ORDER BY product_name");
    $products = $stmt->fetchAll();
    echo "Total rebar products: " . count($products) . "\n\n";
    
    foreach ($products as $prod) {
        echo "ID: {$prod['id']}, Name: {$prod['product_name']}, Price: {$prod['price']}원/kg\n";
    }
    
    // Check specific product
    echo "\n=== Product ID 1010 ===\n";
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([1010]);
    $product = $stmt->fetch();
    if ($product) {
        echo "Product name: {$product['product_name']}\n";
        echo "Category code: {$product['category_code']}\n";
        echo "Price: {$product['price']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>