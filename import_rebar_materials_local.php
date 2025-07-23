<?php
// 로컬 환경용 DB 연결
try {
    $dsn = "mysql:host=127.0.0.1;port=3306;dbname=project1_db;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', 'rootpassword');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "데이터베이스 연결 성공\n";
} catch (PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

echo "=== 철근 재질 데이터 임포트 시작 ===\n";

// 먼저 테이블 생성 SQL 실행
$sql = file_get_contents('sql/add_rebar_materials.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        try {
            $pdo->exec($statement);
            echo "SQL 실행 성공: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            echo "SQL 실행 오류: " . $e->getMessage() . "\n";
        }
    }
}

// 철근 재질 데이터
$materials = [
    ['material_code' => 'SD300', 'material_name' => 'SD300', 'additional_price' => 30, 'display_order' => 1],
    ['material_code' => 'SD400', 'material_name' => 'SD400', 'additional_price' => 0, 'display_order' => 2],
    ['material_code' => 'SD400W', 'material_name' => 'SD400W', 'additional_price' => 50, 'display_order' => 3],
    ['material_code' => 'SD400S', 'material_name' => 'SD400S', 'additional_price' => 50, 'display_order' => 4],
    ['material_code' => 'SD500', 'material_name' => 'SD500', 'additional_price' => 40, 'display_order' => 5],
    ['material_code' => 'SD500W', 'material_name' => 'SD500W', 'additional_price' => 80, 'display_order' => 6],
    ['material_code' => 'SD500S', 'material_name' => 'SD500S', 'additional_price' => 80, 'display_order' => 7],
    ['material_code' => 'SD600', 'material_name' => 'SD600', 'additional_price' => 80, 'display_order' => 8],
    ['material_code' => 'SD600W', 'material_name' => 'SD600W', 'additional_price' => 120, 'display_order' => 9]
];

// 재질 데이터 삽입
$stmt = $pdo->prepare("
    INSERT INTO rebar_materials (material_code, material_name, additional_price, display_order, description) 
    VALUES (:material_code, :material_name, :additional_price, :display_order, :description)
    ON DUPLICATE KEY UPDATE 
        material_name = VALUES(material_name),
        additional_price = VALUES(additional_price),
        display_order = VALUES(display_order),
        updated_at = CURRENT_TIMESTAMP
");

foreach ($materials as $material) {
    $description = sprintf(
        "%s 규격 철근 (추가단가: %d원/kg)",
        $material['material_code'],
        $material['additional_price']
    );
    
    try {
        $stmt->execute([
            'material_code' => $material['material_code'],
            'material_name' => $material['material_name'],
            'additional_price' => $material['additional_price'],
            'display_order' => $material['display_order'],
            'description' => $description
        ]);
        echo "재질 추가/업데이트 성공: {$material['material_code']}\n";
    } catch (PDOException $e) {
        echo "재질 추가 오류 ({$material['material_code']}): " . $e->getMessage() . "\n";
    }
}

// 임포트 결과 확인
$count = $pdo->query("SELECT COUNT(*) FROM rebar_materials WHERE is_active = TRUE")->fetchColumn();
echo "\n=== 임포트 완료 ===\n";
echo "활성화된 재질 수: {$count}개\n";

// 재질 목록 출력
echo "\n재질 목록:\n";
$materials = $pdo->query("SELECT * FROM rebar_materials ORDER BY display_order")->fetchAll();
foreach ($materials as $mat) {
    echo sprintf("- %s: %s원/kg 추가\n", $mat['material_code'], $mat['additional_price']);
}
?>