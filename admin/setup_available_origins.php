<?php
require_once '../db.php';

echo "<h2>복수 원산지 기능 설정</h2>";
echo "<pre>";

try {
    // 1. available_origins 컬럼 확인 및 추가
    echo "1. available_origins 컬럼 확인 및 추가\n";
    echo "========================================\n";
    
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('available_origins', $columns)) {
        echo "available_origins 컬럼이 없습니다. 추가합니다...\n";
        $pdo->exec("ALTER TABLE products ADD COLUMN available_origins TEXT DEFAULT NULL COMMENT '사용 가능한 원산지 목록 (JSON 형식)' AFTER origin");
        echo "✓ available_origins 컬럼 추가 완료\n";
    } else {
        echo "✓ available_origins 컬럼이 이미 존재합니다.\n";
    }
    
    // 2. 기존 데이터 마이그레이션
    echo "\n2. 기존 데이터 마이그레이션\n";
    echo "========================================\n";
    
    // 기존 origin 값을 available_origins에 JSON 배열로 저장
    $stmt = $pdo->prepare("
        UPDATE products 
        SET available_origins = CONCAT('[\"', origin, '\"]') 
        WHERE origin IS NOT NULL 
        AND origin != '' 
        AND (available_origins IS NULL OR available_origins = '')
    ");
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "✓ {$affected}개 제품의 원산지를 available_origins로 마이그레이션\n";
    
    // 3. 테스트 데이터 생성 (몇 개 제품에 복수 원산지 설정)
    echo "\n3. 테스트 데이터 생성\n";
    echo "========================================\n";
    
    // 철근 카테고리의 일부 제품에 복수 원산지 설정
    $test_data = [
        ['국산', '중국산', '일본산'],
        ['국산', '베트남산'],
        ['국산', '중국산', '바레인산'],
        ['국산', '수입산']
    ];
    
    $stmt = $pdo->query("SELECT id, product_name FROM products WHERE category_code = 'rebar' AND is_active = 1 LIMIT 4");
    $rebar_products = $stmt->fetchAll();
    
    foreach ($rebar_products as $index => $product) {
        if (isset($test_data[$index])) {
            $origins_json = json_encode($test_data[$index], JSON_UNESCAPED_UNICODE);
            $update_stmt = $pdo->prepare("
                UPDATE products 
                SET available_origins = ?, origin = ?
                WHERE id = ?
            ");
            $update_stmt->execute([$origins_json, $test_data[$index][0], $product['id']]);
            echo "✓ {$product['product_name']}에 복수 원산지 설정: " . implode(', ', $test_data[$index]) . "\n";
        }
    }
    
    // 4. 현재 데이터 확인
    echo "\n4. 복수 원산지가 설정된 제품 확인\n";
    echo "========================================\n";
    
    $stmt = $pdo->query("
        SELECT product_name, origin, available_origins 
        FROM products 
        WHERE available_origins LIKE '%,%' 
        AND is_active = 1 
        LIMIT 10
    ");
    $products = $stmt->fetchAll();
    
    if (count($products) > 0) {
        foreach ($products as $product) {
            $origins = json_decode($product['available_origins'], true);
            echo "- {$product['product_name']}: " . implode(', ', $origins) . "\n";
        }
    } else {
        echo "복수 원산지가 설정된 제품이 없습니다.\n";
    }
    
    echo "\n========================================\n";
    echo "✅ 복수 원산지 기능 설정이 완료되었습니다!\n\n";
    echo "테스트 방법:\n";
    echo "1. 관리자 페이지: <a href='admin_origin_stock.php' target='_blank'>/admin/admin_origin_stock.php</a>\n";
    echo "2. 카테고리 선택 후 제품별로 여러 원산지를 체크하여 저장\n";
    
} catch (Exception $e) {
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
}

echo "</pre>";