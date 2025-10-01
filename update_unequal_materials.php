<?php
require_once 'db.php';

try {
    // Connect to database
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Define the standard materials for unequal-angle products
    $standard_materials = [
        'SS400',
        'SS400/A36',
        'SS490',
        'SS540',
        'SM400A',
        'SM400B',
        'SM490A',
        'SM490B',
        'SM490YA',
        'SM490YB'
    ];

    // Convert to JSON
    $materials_json = json_encode($standard_materials);

    // Update all unequal-angle products
    $stmt = $pdo->prepare("
        UPDATE products
        SET available_materials = ?
        WHERE category_code = 'unequal-angle'
    ");

    $stmt->execute([$materials_json]);

    $affected_rows = $stmt->rowCount();

    echo "=== Update Complete ===\n";
    echo "Updated $affected_rows unequal-angle products\n";
    echo "New materials list: " . implode(', ', $standard_materials) . "\n\n";

    // Verify the update
    echo "=== Verification ===\n";
    $stmt = $pdo->prepare("
        SELECT id, product_name, available_materials
        FROM products
        WHERE category_code = 'unequal-angle'
        ORDER BY id
        LIMIT 3
    ");
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $materials = json_decode($row['available_materials'], true);
        echo "ID {$row['id']}: {$row['product_name']}\n";
        echo "  Materials: " . implode(', ', $materials) . "\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>