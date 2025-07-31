<?php
require_once '../db.php';

echo "<h2>제품 필터 기능 설정</h2>";
echo "<pre>";

try {
    // 1. products 테이블 구조 확인
    echo "1. products 테이블 구조 확인\n";
    echo "========================================\n";
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "현재 컬럼: " . implode(', ', $columns) . "\n\n";
    
    // 2. origin 컬럼 확인 및 추가
    echo "2. origin 컬럼 확인 및 추가\n";
    echo "========================================\n";
    if (!in_array('origin', $columns)) {
        echo "origin 컬럼이 없습니다. 추가합니다...\n";
        $pdo->exec("ALTER TABLE products ADD COLUMN origin VARCHAR(100) DEFAULT '국산' COMMENT '원산지' AFTER stock_status");
        echo "✓ origin 컬럼 추가 완료\n";
    } else {
        echo "✓ origin 컬럼이 이미 존재합니다.\n";
    }
    
    // 3. stock_type 컬럼 확인 및 추가
    echo "\n3. stock_type 컬럼 확인 및 추가\n";
    echo "========================================\n";
    if (!in_array('stock_type', $columns)) {
        echo "stock_type 컬럼이 없습니다. 추가합니다...\n";
        $pdo->exec("ALTER TABLE products ADD COLUMN stock_type VARCHAR(50) DEFAULT 'normal' COMMENT '재고 유형' AFTER stock_status");
        echo "✓ stock_type 컬럼 추가 완료\n";
    } else {
        echo "✓ stock_type 컬럼이 이미 존재합니다.\n";
    }
    
    // 4. 기존 데이터 마이그레이션
    echo "\n4. 기존 데이터 마이그레이션\n";
    echo "========================================\n";
    
    // origin이 NULL인 데이터를 '국산'으로 업데이트
    $stmt = $pdo->prepare("UPDATE products SET origin = '국산' WHERE origin IS NULL OR origin = ''");
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "✓ {$affected}개 제품의 원산지를 '국산'으로 설정\n";
    
    // stock_status가 있는 경우 stock_type으로 마이그레이션
    $stmt = $pdo->prepare("UPDATE products SET stock_type = 
        CASE 
            WHEN stock_status = 'long_term' THEN 'long_term'
            WHEN stock_status = 'used' THEN 'used'
            ELSE 'normal'
        END
        WHERE stock_type = 'normal'");
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "✓ {$affected}개 제품의 재고 상태 마이그레이션 완료\n";
    
    // 5. 인덱스 추가
    echo "\n5. 인덱스 추가\n";
    echo "========================================\n";
    try {
        $pdo->exec("ALTER TABLE products ADD INDEX idx_origin (origin)");
        echo "✓ origin 인덱스 추가 완료\n";
    } catch (Exception $e) {
        echo "- origin 인덱스가 이미 존재하거나 추가 실패\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE products ADD INDEX idx_stock_type (stock_type)");
        echo "✓ stock_type 인덱스 추가 완료\n";
    } catch (Exception $e) {
        echo "- stock_type 인덱스가 이미 존재하거나 추가 실패\n";
    }
    
    // 6. 테스트 데이터 생성
    echo "\n6. 테스트 데이터 생성\n";
    echo "========================================\n";
    
    // 몇 개 제품의 원산지와 재고 상태를 다양하게 설정
    $test_origins = ['중국산', '일본산', '베트남산', '바레인산', '수입산'];
    $test_stock_types = ['long_term', 'used'];
    
    // 랜덤하게 10개 제품 업데이트
    $stmt = $pdo->query("SELECT id FROM products WHERE is_active = 1 ORDER BY RAND() LIMIT 10");
    $products = $stmt->fetchAll();
    
    foreach ($products as $index => $product) {
        if ($index < 5) {
            // 처음 5개는 다양한 원산지로
            $origin = $test_origins[$index];
            $pdo->exec("UPDATE products SET origin = '{$origin}' WHERE id = {$product['id']}");
            echo "✓ 제품 ID {$product['id']}의 원산지를 '{$origin}'으로 설정\n";
        }
        if ($index >= 5 && $index < 8) {
            // 3개는 장기재고로
            $pdo->exec("UPDATE products SET stock_type = 'long_term' WHERE id = {$product['id']}");
            echo "✓ 제품 ID {$product['id']}를 장기재고로 설정\n";
        }
        if ($index >= 8) {
            // 2개는 중고로
            $pdo->exec("UPDATE products SET stock_type = 'used' WHERE id = {$product['id']}");
            echo "✓ 제품 ID {$product['id']}를 중고로 설정\n";
        }
    }
    
    // 7. 현재 데이터 통계
    echo "\n7. 현재 데이터 통계\n";
    echo "========================================\n";
    
    // 원산지별 통계
    $stmt = $pdo->query("SELECT origin, COUNT(*) as count FROM products WHERE is_active = 1 GROUP BY origin ORDER BY count DESC");
    $origins = $stmt->fetchAll();
    echo "원산지별 제품 수:\n";
    foreach ($origins as $origin) {
        echo "  - {$origin['origin']}: {$origin['count']}개\n";
    }
    
    // 재고 상태별 통계
    $stmt = $pdo->query("SELECT stock_type, COUNT(*) as count FROM products WHERE is_active = 1 GROUP BY stock_type ORDER BY count DESC");
    $stock_types = $stmt->fetchAll();
    echo "\n재고 상태별 제품 수:\n";
    foreach ($stock_types as $stock) {
        $label = [
            'normal' => '일반',
            'long_term' => '장기재고',
            'used' => '중고'
        ][$stock['stock_type']] ?? $stock['stock_type'];
        echo "  - {$label}: {$stock['count']}개\n";
    }
    
    echo "\n========================================\n";
    echo "✅ 모든 설정이 완료되었습니다!\n\n";
    echo "테스트 방법:\n";
    echo "1. 제품 페이지: <a href='../products_new.php' target='_blank'>/products_new.php</a>\n";
    echo "2. 관리자 제품군 관리: <a href='admin_product_groups.php' target='_blank'>/admin/admin_product_groups.php</a>\n";
    
} catch (Exception $e) {
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>