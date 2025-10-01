<?php
require_once 'db.php';

try {
    // Connect to database
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check round-bar products and their materials
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM products
        WHERE category_code = 'round-bar'
    ");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "=== 환봉 제품 총 개수: {$count['count']}개 ===\n\n";

    $stmt = $pdo->prepare("
        SELECT id, product_name, specification, specification_weight, available_materials, calculation_type, has_calculator
        FROM products
        WHERE category_code = 'round-bar'
        ORDER BY id
        LIMIT 10
    ");
    $stmt->execute();

    echo "=== Round-Bar Products Sample (First 10) ===\n\n";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}\n";
        echo "Name: {$row['product_name']}\n";
        echo "Spec: {$row['specification']}\n";
        echo "Weight: {$row['specification_weight']} kg/m\n";
        echo "Calculation Type: {$row['calculation_type']}\n";
        echo "Has Calculator: {$row['has_calculator']}\n";

        $materials = json_decode($row['available_materials'], true);
        if (is_array($materials)) {
            echo "Available Materials (" . count($materials) . "): " . implode(', ', $materials) . "\n";
        } else {
            echo "Available Materials: {$row['available_materials']}\n";
        }
        echo "---\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>