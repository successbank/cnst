<?php
require_once 'db.php';

try {
    // Connect to database
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check regular H-beam products (category_code = 'h-beam')
    $stmt = $pdo->prepare("
        SELECT id, product_name, specification, available_materials, category_code
        FROM products
        WHERE category_code = 'h-beam'
        ORDER BY id
        LIMIT 10
    ");
    $stmt->execute();

    echo "=== Regular H-beam Products (category_code='h-beam') ===\n\n";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}\n";
        echo "Name: {$row['product_name']}\n";
        echo "Spec: {$row['specification']}\n";

        $materials = json_decode($row['available_materials'], true);
        if (is_array($materials)) {
            echo "Available Materials: " . implode(', ', $materials) . "\n";
        } else {
            echo "Available Materials: {$row['available_materials']}\n";
        }
        echo "---\n";
    }

    // Count total regular H-beam products
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM products
        WHERE category_code = 'h-beam'
    ");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal regular H-beam products: {$total['total']}\n";

    // Check product ID 447 specifically
    echo "\n=== Product ID 447 Details ===\n";
    $stmt = $pdo->prepare("
        SELECT id, product_name, specification, available_materials, category_code
        FROM products
        WHERE id = 447
    ");
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        echo "ID: {$product['id']}\n";
        echo "Name: {$product['product_name']}\n";
        echo "Category: {$product['category_code']}\n";
        echo "Spec: {$product['specification']}\n";
        $materials = json_decode($product['available_materials'], true);
        if (is_array($materials)) {
            echo "Available Materials: " . implode(', ', $materials) . "\n";
        } else {
            echo "Available Materials: {$product['available_materials']}\n";
        }
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>