<?php
// 경량H형강 데이터를 기존 products 테이블에 통합
require_once 'db.php';

try {
    $pdo = getDB();

    // 1. product_categories에 경량H형강 카테고리 확인/추가
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE category_code = 'light-h-beam'");
    $stmt->execute();
    $category = $stmt->fetch();

    if (!$category) {
        $stmt = $pdo->prepare("INSERT INTO product_categories (category_code, category_name, display_order) VALUES ('light-h-beam', '경량H형강', 5)");
        $stmt->execute();
        echo "경량H형강 카테고리 추가 완료\n";
    } else {
        echo "경량H형강 카테고리 이미 존재\n";
    }

    // 2. products 테이블에 경량H형강 부모 제품 확인/추가
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_code = 'light-h-beam' AND is_parent = 1");
    $stmt->execute();
    $parent = $stmt->fetch();

    if (!$parent) {
        // 부모 제품 생성
        $stmt = $pdo->prepare("
            INSERT INTO products (
                category_code, product_name, display_name, description,
                is_parent, has_variants, has_calculator, status,
                created_at
            ) VALUES (
                'light-h-beam', '경량H형강', '경량H형강',
                '중소형 건축물에 적합한 경제적인 경량 H형강입니다.',
                1, 1, 1, 'active',
                NOW()
            )
        ");
        $stmt->execute();
        $parent_id = $pdo->lastInsertId();
        echo "경량H형강 부모 제품 생성 완료 (ID: $parent_id)\n";
    } else {
        $parent_id = $parent['id'];
        echo "경량H형강 부모 제품 이미 존재 (ID: $parent_id)\n";
    }

    // 3. products_light_h_beam 데이터를 products 테이블로 이관
    $stmt = $pdo->query("SELECT * FROM products_light_h_beam ORDER BY unit_weight");
    $light_h_beams = $stmt->fetchAll();

    // 기존 경량H형강 변형 제품 삭제
    $stmt = $pdo->prepare("DELETE FROM products WHERE category_code = 'light-h-beam' AND is_parent = 0");
    $stmt->execute();
    echo "기존 경량H형강 변형 제품 삭제 완료\n";

    // 새로운 변형 제품 추가
    $insert_stmt = $pdo->prepare("
        INSERT INTO products (
            category_code, parent_id, product_name, display_name,
            specifications, unit_weight, is_parent, has_variants,
            status, created_at
        ) VALUES (
            'light-h-beam', ?, ?, ?, ?, ?, 0, 0, 'active', NOW()
        )
    ");

    $count = 0;
    foreach ($light_h_beams as $item) {
        $display_name = $item['product_name'] . ' ' . $item['specification'];
        $insert_stmt->execute([
            $parent_id,
            $item['product_name'],
            $display_name,
            $item['specification'],
            $item['unit_weight']
        ]);
        $count++;
    }

    echo "총 {$count}개의 경량H형강 변형 제품 추가 완료\n";

    // 4. 부모 제품의 단중 데이터 업데이트
    $unit_weight_data = [];
    foreach ($light_h_beams as $item) {
        $unit_weight_data[$item['specification']] = $item['unit_weight'];
    }

    $stmt = $pdo->prepare("
        UPDATE products
        SET unit_weight_data = ?,
            available_sizes = ?
        WHERE id = ?
    ");

    $available_sizes = array_keys($unit_weight_data);

    $stmt->execute([
        json_encode($unit_weight_data),
        json_encode($available_sizes),
        $parent_id
    ]);

    echo "경량H형강 부모 제품 데이터 업데이트 완료\n";

    // 5. 확인
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM products WHERE category_code = 'light-h-beam'");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "\n최종 경량H형강 제품 수: " . $result['cnt'] . "개\n";

} catch (PDOException $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>