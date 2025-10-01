<?php
require_once 'db.php';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Light H-beam Products ===\n\n";

    // Check calculation types
    $stmt = $pdo->prepare("
        SELECT calculation_type, COUNT(*) as cnt
        FROM products
        WHERE category_code = 'light-h-beam'
        GROUP BY calculation_type
    ");
    $stmt->execute();

    echo "Calculation Types:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['calculation_type']}: {$row['cnt']} products\n";
    }

    // Check sample products
    echo "\nSample Products:\n";
    $stmt = $pdo->prepare("
        SELECT id, product_name, calculation_type, specification_weight
        FROM products
        WHERE category_code = 'light-h-beam'
        ORDER BY id
        LIMIT 10
    ");
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Name: {$row['product_name']}\n";
        echo "  Type: {$row['calculation_type']}, Weight: {$row['specification_weight']} kg\n";
    }

    // Total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE category_code = 'light-h-beam'");
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nTotal light H-beam products: {$total['total']}\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>