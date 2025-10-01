<?php
// 경량H형강 계산기 적용 스크립트
// 엑셀 파일의 단중 데이터를 읽어서 제품에 적용

try {
    // project1_db 연결
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3306;dbname=project1_db;charset=utf8mb4",
        "root",
        "rootpassword"
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "데이터베이스 연결 성공\n";

    // 엑셀 데이터에서 가져온 단중 정보 (규격 => kg/m)
    $weight_data = [
        'LHB 53*104*3.2*3.2' => 6.4,
        'LHB 58*104*3.2*3.2' => 6.5,
        'LHB 75*75*3.2*3.2' => 5.5,
        'LHB 75*75*3.2*4.5' => 7.0,
        'LHB 100*75*3.2*4.5' => 7.6,
        'LHB 120*80*3.2*4.5' => 8.2,
        'LHB 100*100*3.2*3.2' => 7.1,
        'LHB 100*100*3.2*4.5' => 8.6,
        'LHB 100*125*3.2*4.5' => 9.2,
        'LHB 100*100*4.5*4.5' => 10.1,
        'LHB 100*150*3.2*4.5' => 9.8,
        'LHB 125*125*3.2*4.5' => 10.3,
        'LHB 125*125*4.5*4.5' => 12.5,
        'LHB 125*125*4.5*6.0' => 15.0,
        'LHB 125*150*4.5*4.5' => 13.2,
        'LHB 125*200*4.5*6.0' => 18.2,
        'LHB 150*100*4.5*4.5' => 12.5,
        'LHB 150*125*4.5*6.0' => 16.3,
        'LHB 150*150*4.5*4.5' => 13.8,
        'LHB 150*150*4.5*6.0' => 17.5,
        'LHB 175*125*4.5*6.0' => 17.5,
        'LHB 175*150*4.5*6.0' => 18.8,
        'LHB 175*175*4.5*6.0' => 20.0,
        'LHB 200*100*4.5*6.0' => 17.5,
        'LHB 200*125*4.5*6.0' => 18.8,
        'LHB 200*150*4.5*6.0' => 20.0,
        'LHB 200*175*6.0*6.0' => 23.0,
        'LHB 200*200*6.0*6.0' => 24.3,
        'LHB 200*200*6.0*8.0' => 29.2,
        'LHB 250*125*4.5*6.0' => 21.3,
        'LHB 250*150*6.0*8.0' => 28.0,
        'LHB 250*175*6.0*8.0' => 29.8,
        'LHB 250*200*6.0*8.0' => 31.7,
        'LHB 250*250*6.0*8.0' => 35.4,
        'LHB 250*250*8.0*10.0' => 47.9,
        'LHB 300*100*6.0*8.0' => 28.0,
        'LHB 300*125*6.0*8.0' => 29.8,
        'LHB 300*150*6.0*8.0' => 31.7,
        'LHB 300*175*6.0*8.0' => 33.5,
        'LHB 300*200*8.0*10.0' => 47.1,
        'LHB 300*250*8.0*10.0' => 50.8,
        'LHB 300*300*8.0*10.0' => 54.6,
        'LHB 300*300*8.0*12.0' => 61.6,
        'LHB 300*300*10.0*12.0' => 67.4,
        'LHB 350*150*8.0*10.0' => 44.0,
        'LHB 350*175*8.0*10.0' => 45.8,
        'LHB 350*200*8.0*10.0' => 47.7,
        'LHB 350*250*8.0*12.0' => 60.5,
        'LHB 350*350*10.0*12.0' => 75.1,
        'LHB 350*350*10.0*14.0' => 82.9,
        'LHB 400*150*8.0*10.0' => 47.7,
        'LHB 400*175*8.0*10.0' => 49.6,
        'LHB 400*200*8.0*12.0' => 60.5,
        'LHB 400*250*10.0*14.0' => 82.4,
        'LHB 400*300*10.0*14.0' => 89.4,
        'LHB 400*400*12.0*16.0' => 124.9,
        'LHB 450*175*10.0*12.0' => 67.8,
        'LHB 450*200*10.0*12.0' => 70.3
    ];

    // 1. 먼저 각 자식 제품에 specification_weight 업데이트
    $updated_products = 0;
    foreach ($weight_data as $spec => $weight) {
        $stmt = $pdo->prepare("
            UPDATE products
            SET specification_weight = :weight
            WHERE category_code = 'light-h-beam'
            AND specifications = :spec
            AND parent_product_id = 285
        ");
        $result = $stmt->execute([
            ':weight' => $weight,
            ':spec' => $spec
        ]);

        if ($stmt->rowCount() > 0) {
            $updated_products++;
            echo "✓ {$spec}: {$weight} kg/m 적용\n";
        }
    }

    echo "\n총 {$updated_products}개 제품의 단중 데이터 업데이트 완료\n\n";

    // 2. 부모 제품(ID 285)에 계산기 설정 적용
    // unit_weight_data JSON 생성
    $unit_weight_json = json_encode($weight_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // available_sizes 배열 생성
    $available_sizes = array_keys($weight_data);
    $available_sizes_json = json_encode($available_sizes, JSON_UNESCAPED_UNICODE);

    // 부모 제품 업데이트
    $stmt = $pdo->prepare("
        UPDATE products
        SET
            has_calculator = 1,
            calculation_type = 'linear',
            unit_weight_data = :unit_weight_data,
            available_sizes = :available_sizes
        WHERE id = 285
    ");

    $result = $stmt->execute([
        ':unit_weight_data' => $unit_weight_json,
        ':available_sizes' => $available_sizes_json
    ]);

    if ($result) {
        echo "✅ 경량H형강 부모 제품(ID 285) 계산기 설정 완료\n";
        echo "- has_calculator: 1\n";
        echo "- calculation_type: linear\n";
        echo "- 단중 데이터 규격 수: " . count($weight_data) . "개\n";
        echo "- 사용 가능한 규격 수: " . count($available_sizes) . "개\n";
    }

    // 3. 결과 확인
    echo "\n=== 설정 확인 ===\n";

    // 부모 제품 확인
    $stmt = $pdo->query("
        SELECT id, product_name, has_calculator, calculation_type,
               LENGTH(unit_weight_data) as data_length
        FROM products
        WHERE id = 285
    ");
    $parent = $stmt->fetch();

    echo "\n부모 제품 정보:\n";
    echo "- ID: {$parent['id']}\n";
    echo "- 제품명: {$parent['product_name']}\n";
    echo "- 계산기 활성화: " . ($parent['has_calculator'] ? "YES" : "NO") . "\n";
    echo "- 계산 타입: {$parent['calculation_type']}\n";
    echo "- 단중 데이터 크기: {$parent['data_length']} bytes\n";

    // 자식 제품 샘플 확인
    $stmt = $pdo->query("
        SELECT specifications, specification_weight
        FROM products
        WHERE category_code = 'light-h-beam'
        AND parent_product_id = 285
        AND specification_weight IS NOT NULL
        ORDER BY specifications
        LIMIT 5
    ");
    $samples = $stmt->fetchAll();

    echo "\n자식 제품 단중 샘플:\n";
    foreach ($samples as $sample) {
        echo "- {$sample['specifications']}: {$sample['specification_weight']} kg/m\n";
    }

    echo "\n✅ 경량H형강 계산기 설정이 완료되었습니다!\n";
    echo "H형강과 동일한 디자인과 기능으로 작동합니다.\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>