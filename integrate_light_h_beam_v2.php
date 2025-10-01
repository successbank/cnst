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

    // 2. 기존 경량H형강 제품 삭제
    $stmt = $pdo->prepare("DELETE FROM products WHERE category_code = 'light-h-beam'");
    $stmt->execute();
    echo "기존 경량H형강 제품 삭제 완료\n";

    // 3. products_light_h_beam 데이터를 products 테이블로 이관
    $stmt = $pdo->query("SELECT * FROM products_light_h_beam ORDER BY unit_weight");
    $light_h_beams = $stmt->fetchAll();

    // 단중 데이터와 사이즈 목록 준비
    $unit_weight_data = [];
    $available_sizes = [];

    foreach ($light_h_beams as $item) {
        $unit_weight_data[$item['specification']] = $item['unit_weight'];
        $available_sizes[] = $item['specification'];
    }

    // 4. 부모 제품 생성 (계산기 기능 포함)
    $stmt = $pdo->prepare("
        INSERT INTO products (
            category_code,
            product_name,
            description,
            has_calculator,
            is_active,
            display_mode,
            unit_weight_data,
            available_sizes,
            calculation_type,
            created_at
        ) VALUES (
            'light-h-beam',
            '경량H형강',
            '중소형 건축물에 적합한 경제적인 경량 H형강입니다.',
            1,
            1,
            'by_specification',
            ?,
            ?,
            'linear',
            NOW()
        )
    ");

    $stmt->execute([
        json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
        json_encode($available_sizes, JSON_UNESCAPED_UNICODE)
    ]);

    $parent_id = $pdo->lastInsertId();
    echo "경량H형강 부모 제품 생성 완료 (ID: $parent_id)\n";

    // 5. 개별 제품들 추가 (선택사항 - 필요시)
    $insert_stmt = $pdo->prepare("
        INSERT INTO products (
            category_code,
            parent_product_id,
            product_name,
            specifications,
            weight_per_meter,
            is_active,
            display_mode,
            created_at
        ) VALUES (
            'light-h-beam', ?, ?, ?, ?, 1, 'single', NOW()
        )
    ");

    $count = 0;
    foreach ($light_h_beams as $item) {
        $product_name = '경량H형강 ' . $item['specification'];
        $insert_stmt->execute([
            $parent_id,
            $product_name,
            $item['specification'],
            $item['unit_weight']
        ]);
        $count++;
    }

    echo "총 {$count}개의 경량H형강 개별 제품 추가 완료\n";

    // 6. 확인
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM products WHERE category_code = 'light-h-beam'");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "\n최종 경량H형강 제품 수: " . $result['cnt'] . "개\n";

    // 7. 테스트 URL 출력
    echo "\n테스트 URL:\n";
    echo "http://211.248.112.67:1112/products_new.php?category=light-h-beam\n";
    echo "http://211.248.112.67:1112/product_detail.php?id={$parent_id}\n";

} catch (PDOException $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>