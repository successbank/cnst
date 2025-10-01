<?php
require_once 'db.php';

try {
    // Connect to database
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First, find the category code for H-beam products
    $stmt = $pdo->prepare("
        SELECT DISTINCT category_code, COUNT(*) as cnt
        FROM products
        WHERE product_name LIKE '%H형강%' OR product_name LIKE '%H-beam%' OR product_name LIKE '%h형강%'
        GROUP BY category_code
    ");
    $stmt->execute();

    echo "=== H-beam Product Categories ===\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Category: {$row['category_code']}, Count: {$row['cnt']}\n";
    }
    echo "\n";

    // Check H-beam products and their materials
    $stmt = $pdo->prepare("
        SELECT id, product_name, specification, available_materials, category_code
        FROM products
        WHERE product_name LIKE '%H형강%' OR product_name LIKE '%H-beam%'
        ORDER BY id
        LIMIT 10
    ");
    $stmt->execute();

    echo "=== H-beam Products Current Materials ===\n\n";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}\n";
        echo "Name: {$row['product_name']}\n";
        echo "Category: {$row['category_code']}\n";
        echo "Spec: {$row['specification']}\n";

        $materials = json_decode($row['available_materials'], true);
        if (is_array($materials)) {
            echo "Available Materials: " . implode(', ', $materials) . "\n";
        } else {
            echo "Available Materials: {$row['available_materials']}\n";
        }
        echo "---\n";
    }

    // Count total H-beam products
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM products
        WHERE product_name LIKE '%H형강%' OR product_name LIKE '%H-beam%'
    ");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal H-beam products: {$total['total']}\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>