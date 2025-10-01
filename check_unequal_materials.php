<?php
require_once 'db.php';

try {
    // Connect to database
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check unequal-angle products and their materials
    $stmt = $pdo->prepare("
        SELECT id, product_name, specification, available_materials
        FROM products
        WHERE category_code = 'unequal-angle'
        ORDER BY id
        LIMIT 10
    ");
    $stmt->execute();

    echo "=== Unequal-Angle Products Current Materials ===\n\n";

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

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>