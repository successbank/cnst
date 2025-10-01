<?php
require_once 'db.php';

try {
    // Connect to database
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Update calculation_type for H-beam products
    $stmt = $pdo->prepare("
        UPDATE products
        SET calculation_type = 'linear'
        WHERE category_code = 'h-beam'
    ");

    $stmt->execute();
    $affected_rows = $stmt->rowCount();

    echo "=== H형강 Calculation Type 업데이트 완료 ===\n";
    echo "업데이트된 제품 수: $affected_rows 개\n";
    echo "변경 내용: piece → linear\n\n";

    // Verify the update
    echo "=== 업데이트 검증 ===\n";
    $stmt = $pdo->prepare("
        SELECT id, product_name, calculation_type, specification_weight
        FROM products
        WHERE category_code = 'h-beam'
        ORDER BY id
        LIMIT 5
    ");
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID {$row['id']}: {$row['product_name']}\n";
        echo "  Calculation Type: {$row['calculation_type']}\n";
        echo "  단위중량: {$row['specification_weight']} kg/m\n";
    }

    // Check product ID 447 specifically
    echo "\n=== Product ID 447 확인 ===\n";
    $stmt = $pdo->prepare("
        SELECT id, product_name, calculation_type, specification_weight
        FROM products
        WHERE id = 447
    ");
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        echo "ID: {$product['id']}\n";
        echo "제품명: {$product['product_name']}\n";
        echo "Calculation Type: {$product['calculation_type']}\n";
        echo "단위중량: {$product['specification_weight']} kg/m\n";
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>