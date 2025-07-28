<?php
// 데이터베이스 연결
$host = '211.248.112.67';
$db   = 'successbank';
$user = 'successbank';
$pass = 'Aksvmf1212!!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}

$product_id = 1009;

// 제품 정보 가져오기
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name 
    FROM products p 
    JOIN product_categories pc ON p.category_code = pc.category_code 
    WHERE p.id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

echo "=== 제품 정보 ===\n";
echo "ID: " . $product['id'] . "\n";
echo "제품명: " . $product['product_name'] . "\n";
echo "카테고리 코드: " . $product['category_code'] . "\n";
echo "카테고리 이름: " . $product['category_name'] . "\n";
echo "규격: " . $product['specifications'] . "\n";

// 철근 규격 확인
$spec_name = preg_match('/^(D\d+)/', $product['specifications'], $matches) ? $matches[1] : null;
echo "\n규격명 추출: " . ($spec_name ?: "없음") . "\n";

// 철근 스펙 정보 확인
if ($spec_name) {
    $stmt = $pdo->prepare("SELECT * FROM rebar_specifications WHERE spec_name = ? AND is_active = TRUE");
    $stmt->execute([$spec_name]);
    $rebar_spec = $stmt->fetch();
    
    if ($rebar_spec) {
        echo "\n=== 철근 규격 정보 ===\n";
        echo "ID: " . $rebar_spec['id'] . "\n";
        echo "규격명: " . $rebar_spec['spec_name'] . "\n";
        echo "직경: " . $rebar_spec['diameter'] . "mm\n";
        echo "단위중량: " . $rebar_spec['unit_weight'] . "kg/m\n";
    } else {
        echo "\n철근 규격 정보를 찾을 수 없습니다.\n";
    }
}

// 재질 정보 확인
echo "\n=== 재질 정보 ===\n";
$stmt = $pdo->query("SELECT * FROM rebar_materials WHERE is_active = TRUE ORDER BY display_order");
$materials = $stmt->fetchAll();
foreach ($materials as $material) {
    echo $material['material_code'] . " - " . $material['material_name'] . " (추가단가: " . $material['additional_price'] . "원)\n";
}
?>