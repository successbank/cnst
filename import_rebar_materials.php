<?php
// 철근재질 데이터 임포트 스크립트
require_once 'db.php';

// 철근재질 데이터 (철근재질.xlsx 파일 내용)
$materials = [
    ['material_name' => 'SD300', 'price_per_kg' => 30, 'description' => 'SD300 철근 재질'],
    ['material_name' => 'SD400', 'price_per_kg' => 0, 'description' => 'SD400 철근 재질 (기본)'],
    ['material_name' => 'SD400W', 'price_per_kg' => 50, 'description' => 'SD400W 철근 재질'],
    ['material_name' => 'SD400S', 'price_per_kg' => 50, 'description' => 'SD400S 철근 재질'],
    ['material_name' => 'SD500', 'price_per_kg' => 40, 'description' => 'SD500 철근 재질'],
    ['material_name' => 'SD500W', 'price_per_kg' => 90, 'description' => 'SD500W 철근 재질'],
    ['material_name' => 'SD500S', 'price_per_kg' => 90, 'description' => 'SD500S 철근 재질'],
    ['material_name' => 'SD600', 'price_per_kg' => 80, 'description' => 'SD600 철근 재질'],
    ['material_name' => 'SD600S', 'price_per_kg' => 130, 'description' => 'SD600S 철근 재질'],
];

try {
    // 기존 데이터 삭제 (필요시) - 외래키 제약이 있으므로 DELETE 사용
    $pdo->exec("DELETE FROM rebar_materials");

    // rebar_materials 테이블에 price_per_kg 컬럼 추가 (없는 경우)
    $check_column = $pdo->query("SHOW COLUMNS FROM rebar_materials LIKE 'price_per_kg'");
    if ($check_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE rebar_materials ADD COLUMN price_per_kg DECIMAL(10,2) DEFAULT 0 AFTER material_name");
        echo "price_per_kg 컬럼 추가됨\n";
    }

    // 데이터 삽입
    $stmt = $pdo->prepare("
        INSERT INTO rebar_materials (material_name, price_per_kg, description, display_order)
        VALUES (:material_name, :price_per_kg, :description, :display_order)
    ");

    $display_order = 0;
    foreach ($materials as $material) {
        $display_order += 10;
        $stmt->execute([
            ':material_name' => $material['material_name'],
            ':price_per_kg' => $material['price_per_kg'],
            ':description' => $material['description'],
            ':display_order' => $display_order
        ]);
        echo "임포트됨: {$material['material_name']} - {$material['price_per_kg']}원/kg\n";
    }

    echo "\n총 " . count($materials) . "개의 재질 데이터가 성공적으로 임포트되었습니다.\n";

    // 확인
    $result = $pdo->query("SELECT * FROM rebar_materials ORDER BY display_order");
    echo "\n현재 데이터베이스 내용:\n";
    echo "ID | 재질명 | kg당 단가 | 설명\n";
    echo str_repeat("-", 60) . "\n";
    while ($row = $result->fetch()) {
        echo sprintf("%2d | %-8s | %6.0f원 | %s\n",
            $row['id'],
            $row['material_name'],
            $row['price_per_kg'],
            $row['description']
        );
    }

} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>