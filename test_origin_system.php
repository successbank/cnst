<?php
require_once 'db.php';

echo "<h2>원산지 시스템 작동 테스트</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .section { margin: 20px 0; padding: 20px; background: #f5f5f5; border-radius: 8px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #e0e0e0; }
    .origin-badge { padding: 3px 8px; margin: 2px; background: #e3f2fd; border-radius: 12px; display: inline-block; font-size: 12px; }
</style>";

try {
    // 1. 데이터베이스 구조 확인
    echo "<div class='section'>";
    echo "<h3>1. 데이터베이스 구조 확인</h3>";
    
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('available_origins', $columns)) {
        echo "<p class='success'>✓ available_origins 컬럼이 존재합니다.</p>";
    } else {
        echo "<p class='error'>✗ available_origins 컬럼이 없습니다.</p>";
    }
    echo "</div>";
    
    // 2. 복수 원산지가 설정된 제품 확인
    echo "<div class='section'>";
    echo "<h3>2. 복수 원산지가 설정된 제품</h3>";
    
    $stmt = $pdo->query("
        SELECT id, product_name, origin, available_origins, category_code 
        FROM products 
        WHERE available_origins IS NOT NULL 
        AND available_origins LIKE '%,%'
        LIMIT 10
    ");
    $products = $stmt->fetchAll();
    
    if (count($products) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>제품명</th><th>카테고리</th><th>기본 원산지</th><th>사용 가능한 원산지</th></tr>";
        foreach ($products as $product) {
            $origins = json_decode($product['available_origins'], true);
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['product_name']}</td>";
            echo "<td>{$product['category_code']}</td>";
            echo "<td>{$product['origin']}</td>";
            echo "<td>";
            if (is_array($origins)) {
                foreach ($origins as $origin) {
                    echo "<span class='origin-badge'>{$origin}</span>";
                }
            } else {
                echo $product['available_origins'];
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>복수 원산지가 설정된 제품이 없습니다.</p>";
    }
    echo "</div>";
    
    // 3. 제품 페이지 URL 생성
    echo "<div class='section'>";
    echo "<h3>3. 테스트 링크</h3>";
    
    // 철근 카테고리 제품 확인
    $stmt = $pdo->query("
        SELECT id, product_name, available_origins 
        FROM products 
        WHERE category_code = 'rebar' 
        AND available_origins LIKE '%,%'
        LIMIT 1
    ");
    $rebar = $stmt->fetch();
    
    if ($rebar) {
        echo "<p class='success'>✓ 테스트 가능한 철근 제품 발견: {$rebar['product_name']}</p>";
        echo "<p>→ <a href='products_new.php?category=rebar' target='_blank'>철근 카테고리 제품 보기</a></p>";
    }
    
    echo "<p>→ <a href='admin/admin_origin_stock.php' target='_blank'>관리자 원산지 관리 페이지</a></p>";
    echo "<p>→ <a href='products_new.php' target='_blank'>전체 제품 페이지</a></p>";
    echo "</div>";
    
    // 4. AJAX 엔드포인트 테스트
    echo "<div class='section'>";
    echo "<h3>4. AJAX 엔드포인트 테스트</h3>";
    
    // 카테고리별 제품 가져오기 테스트
    $test_url = "http://localhost:1112/admin/ajax/get_products_by_category.php?category=rebar";
    $response = @file_get_contents($test_url);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "<p class='success'>✓ AJAX 엔드포인트 정상 작동 (제품 수: {$data['count']}개)</p>";
        } else {
            echo "<p class='error'>✗ AJAX 응답은 있으나 데이터 오류</p>";
        }
    } else {
        echo "<p class='error'>✗ AJAX 엔드포인트 접근 불가</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>오류 발생: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// 5. 현재 설정 확인
echo "<div class='section'>";
echo "<h3>5. 현재 설정 요약</h3>";
echo "<ul>";
echo "<li>데이터베이스: project1_db</li>";
echo "<li>원산지 옵션: 국산, 중국산, 일본산, 베트남산, 바레인산, 수입산</li>";
echo "<li>재고 상태: 일반(normal), 장기재고(long_term), 중고(used)</li>";
echo "</ul>";
echo "</div>";
?>