<?php
require_once 'db.php';

echo "<h2>모든 제품 카테고리의 원산지 및 재고 상태 초기화</h2>";

// 1. 현재 상태 확인
echo "<h3>1. 현재 카테고리별 제품 현황</h3>";
$stmt = $pdo->query("
    SELECT 
        pc.category_code,
        pc.category_name,
        COUNT(p.id) as total_products,
        COUNT(CASE WHEN p.available_origins IS NOT NULL THEN 1 END) as has_origins,
        COUNT(CASE WHEN p.stock_types IS NOT NULL THEN 1 END) as has_stock_types
    FROM product_categories pc
    LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
    WHERE pc.is_active = 1
    GROUP BY pc.category_code, pc.category_name
    ORDER BY pc.display_order
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>카테고리</th><th>전체 제품</th><th>원산지 설정</th><th>재고상태 설정</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>{$row['category_name']} ({$row['category_code']})</td>";
    echo "<td>{$row['total_products']}</td>";
    echo "<td>{$row['has_origins']}</td>";
    echo "<td>{$row['has_stock_types']}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. available_origins가 NULL인 제품 초기화
echo "<h3>2. 원산지 데이터 초기화</h3>";
$stmt = $pdo->prepare("
    UPDATE products 
    SET available_origins = JSON_ARRAY(origin)
    WHERE available_origins IS NULL 
    AND origin IS NOT NULL 
    AND origin != ''
");
$stmt->execute();
$affected = $stmt->rowCount();
echo "<p>✅ {$affected}개 제품의 available_origins를 현재 origin 값으로 초기화</p>";

// origin이 NULL인 제품은 '국산'으로 설정
$stmt = $pdo->prepare("
    UPDATE products 
    SET origin = '국산',
        available_origins = '[\"국산\"]'
    WHERE (origin IS NULL OR origin = '')
    AND is_active = 1
");
$stmt->execute();
$affected = $stmt->rowCount();
echo "<p>✅ {$affected}개 제품의 origin을 '국산'으로 설정</p>";

// 3. stock_types가 NULL인 제품 초기화
echo "<h3>3. 재고 상태 데이터 초기화</h3>";
$stmt = $pdo->prepare("
    UPDATE products 
    SET stock_types = '[\"일반재고\"]'
    WHERE stock_types IS NULL 
    OR stock_types = ''
");
$stmt->execute();
$affected = $stmt->rowCount();
echo "<p>✅ {$affected}개 제품의 stock_types를 '일반재고'로 초기화</p>";

// 4. 카테고리별 샘플 데이터 설정
echo "<h3>4. 카테고리별 샘플 데이터 설정</h3>";

$sample_data = [
    // H형강
    ['category' => 'h-beam', 'product_name' => 'H형강 100×100', 
     'origins' => '["국산", "일본산"]', 
     'stock_types' => '["일반재고", "장기재고"]'],
    
    // 강판
    ['category' => 'steel-plate', 'product_name' => '일반 강판 6T', 
     'origins' => '["국산", "중국산", "일본산"]', 
     'stock_types' => '["일반재고", "중고"]'],
    
    // 앵글
    ['category' => 'angle', 'product_name' => '앵글 50×50×6', 
     'origins' => '["국산", "베트남산"]', 
     'stock_types' => '["일반재고", "장기재고", "중고"]'],
    
    // 채널
    ['category' => 'channel', 'product_name' => '채널 100×50×5×7.5', 
     'origins' => '["국산", "수입산"]', 
     'stock_types' => '["일반재고"]'],
    
    // 환봉
    ['category' => 'round-bar', 'product_name' => '환봉 10φ', 
     'origins' => '["국산", "중국산"]', 
     'stock_types' => '["일반재고", "장기재고"]']
];

foreach ($sample_data as $data) {
    $stmt = $pdo->prepare("
        UPDATE products 
        SET available_origins = ?,
            stock_types = ?
        WHERE category_code = ? 
        AND product_name = ?
        LIMIT 1
    ");
    $stmt->execute([
        $data['origins'],
        $data['stock_types'],
        $data['category'],
        $data['product_name']
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ {$data['product_name']} 업데이트 완료</p>";
    }
}

// 5. 최종 결과 확인
echo "<h3>5. 최종 결과</h3>";
$stmt = $pdo->query("
    SELECT 
        pc.category_code,
        pc.category_name,
        COUNT(p.id) as total_products,
        COUNT(CASE WHEN p.available_origins IS NOT NULL THEN 1 END) as has_origins,
        COUNT(CASE WHEN p.stock_types IS NOT NULL THEN 1 END) as has_stock_types
    FROM product_categories pc
    LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
    WHERE pc.is_active = 1
    GROUP BY pc.category_code, pc.category_name
    ORDER BY pc.display_order
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>카테고리</th><th>전체 제품</th><th>원산지 설정</th><th>재고상태 설정</th></tr>";
while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>{$row['category_name']} ({$row['category_code']})</td>";
    echo "<td>{$row['total_products']}</td>";
    echo "<td style='color: " . ($row['has_origins'] == $row['total_products'] ? 'green' : 'red') . "'>{$row['has_origins']}</td>";
    echo "<td style='color: " . ($row['has_stock_types'] == $row['total_products'] ? 'green' : 'red') . "'>{$row['has_stock_types']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='/admin/admin_origin_stock.php' target='_blank'>관리자 페이지에서 확인하기</a></p>";
?>