<?php
require_once 'db.php';

try {
    // Connect to database
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Define the standard materials for H-beam products
    $standard_materials = [
        'SS400',        // Default (first item)
        'SS400/A36',
        'SHN400',
        'SS490',
        'SS540',
        'SM400A',
        'SM400B',
        'SHN490',
        'SM490A',
        'SM490B',
        'SM490YA',
        'SM490YB'
    ];

    // Convert to JSON
    $materials_json = json_encode($standard_materials);

    // Update all regular H-beam products (category_code = 'h-beam')
    // NOT updating light-h-beam products
    $stmt = $pdo->prepare("
        UPDATE products
        SET available_materials = ?
        WHERE category_code = 'h-beam'
    ");

    $stmt->execute([$materials_json]);

    $affected_rows = $stmt->rowCount();

    echo "=== H형강 재질 업데이트 완료 ===\n";
    echo "업데이트된 제품 수: $affected_rows 개\n";
    echo "새로운 재질 목록: " . implode(', ', $standard_materials) . "\n";
    echo "기본값: SS400\n\n";

    // Verify the update
    echo "=== 업데이트 검증 ===\n";
    $stmt = $pdo->prepare("
        SELECT id, product_name, available_materials
        FROM products
        WHERE category_code = 'h-beam'
        ORDER BY id
        LIMIT 5
    ");
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $materials = json_decode($row['available_materials'], true);
        echo "ID {$row['id']}: {$row['product_name']}\n";
        echo "  재질: " . implode(', ', array_slice($materials, 0, 3)) . "... (총 " . count($materials) . "개)\n";
    }

    // Check product ID 447 specifically
    echo "\n=== Product ID 447 확인 ===\n";
    $stmt = $pdo->prepare("
        SELECT id, product_name, available_materials
        FROM products
        WHERE id = 447
    ");
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $materials = json_decode($product['available_materials'], true);
        echo "ID: {$product['id']}\n";
        echo "제품명: {$product['product_name']}\n";
        echo "재질 목록: " . implode(', ', $materials) . "\n";
        echo "기본 재질: " . $materials[0] . "\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>