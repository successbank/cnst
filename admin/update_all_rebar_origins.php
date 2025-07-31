<?php
require_once '../db.php';

echo "<h2>모든 철근 제품에 복수 원산지 설정</h2>";
echo "<pre>";

try {
    // 단일 원산지만 있는 철근 제품들 조회
    $stmt = $pdo->query("
        SELECT id, product_name, origin, available_origins 
        FROM products 
        WHERE category_code = 'rebar' 
        AND is_active = 1
        ORDER BY product_name
    ");
    $products = $stmt->fetchAll();
    
    echo "철근 제품 원산지 업데이트 시작...\n";
    echo "========================================\n\n";
    
    $updated_count = 0;
    
    foreach ($products as $product) {
        $origins_array = [];
        if (!empty($product['available_origins'])) {
            $origins_array = json_decode($product['available_origins'], true);
        }
        
        // 이미 복수 원산지가 설정된 경우 스킵
        if (is_array($origins_array) && count($origins_array) > 1) {
            echo "✓ {$product['product_name']}: 이미 복수 원산지 설정됨 (" . implode(', ', $origins_array) . ")\n";
            continue;
        }
        
        // 현재 원산지에 따라 추가 원산지 설정
        $new_origins = [];
        switch ($product['origin']) {
            case '국산':
                $new_origins = ['국산', '중국산', '수입산'];
                break;
            case '중국산':
                $new_origins = ['중국산', '국산', '베트남산'];
                break;
            case '일본산':
                $new_origins = ['일본산', '국산', '수입산'];
                break;
            case '베트남산':
                $new_origins = ['베트남산', '중국산', '국산'];
                break;
            case '수입산':
                $new_origins = ['수입산', '국산', '중국산'];
                break;
            default:
                $new_origins = ['국산', '중국산', '수입산'];
                break;
        }
        
        // 데이터베이스 업데이트
        $origins_json = json_encode($new_origins, JSON_UNESCAPED_UNICODE);
        $update_stmt = $pdo->prepare("
            UPDATE products 
            SET available_origins = ?, 
                origin = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $update_stmt->execute([$origins_json, $new_origins[0], $product['id']]);
        
        echo "✅ {$product['product_name']} 업데이트: " . implode(', ', $new_origins) . "\n";
        $updated_count++;
    }
    
    echo "\n========================================\n";
    echo "✅ 업데이트 완료!\n";
    echo "- 전체 철근 제품: " . count($products) . "개\n";
    echo "- 업데이트된 제품: {$updated_count}개\n\n";
    
    // 업데이트 결과 확인
    echo "업데이트 결과 확인:\n";
    echo "========================================\n";
    
    $stmt = $pdo->query("
        SELECT product_name, available_origins 
        FROM products 
        WHERE category_code = 'rebar' 
        AND is_active = 1
        ORDER BY product_name
    ");
    $results = $stmt->fetchAll();
    
    foreach ($results as $result) {
        $origins = json_decode($result['available_origins'], true);
        echo "- {$result['product_name']}: " . (is_array($origins) ? implode(', ', $origins) : '오류') . "\n";
    }
    
    echo "\n";
    echo "<a href='/products_new.php?category=rebar' target='_blank' style='display: inline-block; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 5px;'>철근 제품 페이지 확인</a>";
    
} catch (Exception $e) {
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
}

echo "</pre>";