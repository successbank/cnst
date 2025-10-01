<?php
require_once 'db.php';

try {
    // Connect to database
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Standard 12 materials for I-beam products
    $standard_materials = [
        'SS400', 'SS400/A36', 'SHN400', 'SS490', 'SS540',
        'SM400A', 'SM400B', 'SHN490', 'SM490A', 'SM490B',
        'SM490YA', 'SM490YB'
    ];
    $materials_json = json_encode($standard_materials);

    // Update all I-beam products
    $stmt = $pdo->prepare("
        UPDATE products
        SET available_materials = ?
        WHERE category_code = 'i-beam'
    ");
    $stmt->execute([$materials_json]);

    $count = $stmt->rowCount();
    echo "✅ I형강 재질 업데이트 완료: {$count}개 제품\n";
    echo "재질 목록 (12개): " . implode(', ', $standard_materials) . "\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}
?>