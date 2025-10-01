<?php
require_once 'db.php';

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Standard 12 materials
    $standard_materials = [
        'SS400', 'SS400/A36', 'SHN400', 'SS490', 'SS540',
        'SM400A', 'SM400B', 'SHN490', 'SM490A', 'SM490B',
        'SM490YA', 'SM490YB'
    ];
    $materials_json = json_encode($standard_materials);

    // Get existing products
    $stmt = $pdo->query("SELECT specification FROM products WHERE category_code = 'channel'");
    $existing_specs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_specs[] = str_replace('×', '*', $row['specification']);
    }

    echo "=== ㄷ형강 제품 임포트 시작 ===\n\n";
    echo "기존 제품: " . count($existing_specs) . "개\n\n";

    // Product data from image
    $products = [
        ['75*40*5*7', 6.92],
        ['100*50*5*7.5', 9.36],  // 기존 제품
        ['125*65*6*8', 13.40],
        ['150*75*6.5*10', 18.60],
        ['150*75*9*12.5', 24.00],
        ['200*80*7.5*11', 24.60],
        ['200*90*8*13.5', 30.90],
        ['250*90*9*13', 34.60],
        ['250*90*11*14.5', 40.20],
        ['300*90*9*13', 38.10],
        ['300*90*10*15.5', 43.80],
        ['380*100*10.5*16', 54.50],
        ['380*100*13*16.5', 62.00],
        ['380*100*13*20', 67.30]
    ];

    $added_count = 0;
    $skipped_count = 0;

    foreach ($products as $product) {
        $spec = $product[0];
        $weight = $product[1];

        // Check if already exists
        if (in_array($spec, $existing_specs)) {
            $skipped_count++;
            echo "SKIP: {$spec} - 이미 존재\n";
            continue;
        }

        $product_name = "ㄷ형강 " . str_replace('*', '×', $spec);

        $stmt = $pdo->prepare("
            INSERT INTO products
            (product_name, specification, specification_weight,
             category_code, calculation_type, available_materials, has_calculator)
            VALUES (?, ?, ?, 'channel', 'linear', ?, 1)
        ");
        $stmt->execute([$product_name, str_replace('*', '×', $spec), $weight, $materials_json]);

        $added_count++;
        echo "ADD:  {$spec} | {$weight} kg/m | ID: " . $pdo->lastInsertId() . "\n";
    }

    echo "\n=== 임포트 완료 ===\n";
    echo "추가된 제품: {$added_count}개\n";
    echo "건너뛴 제품: {$skipped_count}개\n";
    echo "총 제품 수: " . (count($existing_specs) + $added_count) . "개\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}
?>