<?php
require_once 'db.php';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_code = 'c-beam'");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "=== C형강 제품 총 개수: {$count['count']}개 ===\n\n";

    // Sample
    $stmt = $pdo->prepare("
        SELECT id, product_name, specification, specification_weight,
               available_materials, calculation_type, has_calculator
        FROM products
        WHERE category_code = 'c-beam'
        ORDER BY id
        LIMIT 5
    ");
    $stmt->execute();

    echo "=== C-beam Products Sample (First 5) ===\n\n";

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
        }
        echo "---\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>