<?php
require_once 'db.php';

// 철판류 제품 데이터
$steel_plates = [
    [
        'product_name' => '열연 철판(coil sheet)',
        'specifications' => "4' × 8'",
        'unit_weight' => 150.0,  // 예시 단위중량 (kg/장)
        'description' => '열연 강판 코일 시트'
    ],
    [
        'product_name' => '열연 철판(sheet)',
        'specifications' => "8' × 20'",
        'unit_weight' => 600.0,
        'description' => '열연 강판 시트'
    ],
    [
        'product_name' => '무늬 철판',
        'specifications' => "4' × 8'",
        'unit_weight' => 155.0,
        'description' => '무늬가 있는 철판'
    ],
    [
        'product_name' => '망철판',
        'specifications' => "4' × 8'",
        'unit_weight' => 120.0,
        'description' => '망 형태의 철판'
    ],
    [
        'product_name' => '냉연 철판',
        'specifications' => "4' × 8'",
        'unit_weight' => 150.0,
        'description' => '냉연 강판'
    ],
    [
        'product_name' => '아연도금 철판',
        'specifications' => "4' × 8'",
        'unit_weight' => 155.0,
        'description' => '아연 도금 처리된 철판'
    ],
    [
        'product_name' => '복공판(일반 고강도)',
        'specifications' => '',
        'unit_weight' => 500.0,
        'description' => '일반 고강도 복공판'
    ],
    [
        'product_name' => '복공판(M&A 코팅)',
        'specifications' => '',
        'unit_weight' => 520.0,
        'description' => 'M&A 코팅 처리된 복공판'
    ],
    [
        'product_name' => '복공판(환가-그레이팅)',
        'specifications' => '',
        'unit_weight' => 480.0,
        'description' => '환가 그레이팅 복공판'
    ]
];

echo "철판류 제품 등록 시작...\n\n";

$total_inserted = 0;

// 기존 제품 삭제 (선택사항)
// $pdo->exec("DELETE FROM products WHERE category_code = 'steel-plates'");

foreach ($steel_plates as $index => $plate) {
    // 제품 코드 생성
    $product_code = 'SP-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

    // 단위중량 데이터 JSON 생성 (단일 사양)
    $unit_weight_data = json_encode([
        'default' => ['기본' => $plate['unit_weight']]
    ], JSON_UNESCAPED_UNICODE);

    // 제품 등록
    $sql = "INSERT INTO products (
        category_code,
        product_name,
        product_code,
        specifications,
        specification,
        description,
        unit_weight_data,
        calculation_type,
        has_calculator,
        price,
        unit,
        stock_status,
        is_active,
        created_at
    ) VALUES (
        'steel-plates',
        :product_name,
        :product_code,
        :specifications,
        :specifications,
        :description,
        :unit_weight_data,
        'sheet',
        1,
        1000,
        '장',
        'in_stock',
        1,
        NOW()
    )";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':product_name' => $plate['product_name'],
            ':product_code' => $product_code,
            ':specifications' => $plate['specifications'],
            ':description' => $plate['description'],
            ':unit_weight_data' => $unit_weight_data
        ]);

        $total_inserted++;

        $display_name = $plate['product_name'];
        if (!empty($plate['specifications'])) {
            $display_name .= ' ' . $plate['specifications'];
        }

        echo "✓ {$display_name} 등록 완료\n";

    } catch (PDOException $e) {
        echo "✗ {$plate['product_name']} 등록 실패: " . $e->getMessage() . "\n";
    }
}

echo "\n총 {$total_inserted}개 제품이 등록되었습니다.\n";

// 등록된 제품 확인
echo "\n=== 등록된 철판류 제품 목록 ===\n";
$check_sql = "SELECT product_name, specifications, unit_weight_data
              FROM products
              WHERE category_code = 'steel-plates'
              ORDER BY id";

$stmt = $pdo->query($check_sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    $display_name = $row['product_name'];
    if (!empty($row['specifications'])) {
        $display_name .= ' ' . $row['specifications'];
    }

    $unit_data = json_decode($row['unit_weight_data'], true);
    $weight = $unit_data['default']['기본'] ?? 0;

    echo sprintf("%-40s: %.1f kg/장\n", $display_name, $weight);
}

echo "\n철판류 제품 등록이 완료되었습니다.\n";
?>