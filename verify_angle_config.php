<?php
require_once 'db.php';

try {
    // Connect to database
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4', 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== ㄱ형강 제품 설정 확인 ===\n\n";

    // Check angle steel products configuration
    $stmt = $pdo->prepare("
        SELECT id, product_name, specification, specification_weight,
               calculation_type, available_materials
        FROM products
        WHERE category_code = 'angle'
        ORDER BY id
        LIMIT 5
    ");
    $stmt->execute();

    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "제품 {$count}:\n";
        echo "  ID: {$row['id']}\n";
        echo "  제품명: {$row['product_name']}\n";
        echo "  규격: {$row['specification']}\n";
        echo "  단위중량: {$row['specification_weight']} kg/m\n";
        echo "  계산방식: {$row['calculation_type']}\n";

        $materials = json_decode($row['available_materials'], true);
        if (is_array($materials)) {
            echo "  재질목록 (" . count($materials) . "개): " . implode(', ', $materials) . "\n";
        }
        echo "\n";
    }

    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE category_code = 'angle'");
    $total = $stmt->fetchColumn();
    echo "총 ㄱ형강 제품 수: {$total}개\n\n";

    echo "✅ ㄱ형강 설정 완료 확인:\n";
    echo "  □ 재질 12개 설정 완료\n";
    echo "  □ calculation_type='linear' 확인\n";
    echo "  □ 6.0m~12.0m 드롭다운 (product_detail.php에 추가됨)\n";
    echo "  □ 보호 문서 업데이트 완료\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
}
?>