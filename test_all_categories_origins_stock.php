<?php
require_once 'db.php';

echo "<h2>모든 카테고리 원산지 및 재고 상태 테스트</h2>";

// 각 카테고리별로 대표 제품에 복수 원산지/재고 상태 설정
$test_products = [
    // H형강
    ['category' => 'h-beam', 'name' => 'H형강 100×100', 
     'origins' => ['국산', '일본산', '중국산'], 
     'stock_types' => ['일반재고', '장기재고']],
    
    // 강판
    ['category' => 'steel-plate', 'name' => '일반 강판 10T', 
     'origins' => ['국산', '중국산'], 
     'stock_types' => ['일반재고', '중고']],
    
    // 앵글
    ['category' => 'angle', 'name' => '앵글 50×50×6', 
     'origins' => ['국산', '베트남산', '수입산'], 
     'stock_types' => ['일반재고', '장기재고', '중고']],
    
    // 채널
    ['category' => 'channel', 'name' => '채널 100×50×5×7.5', 
     'origins' => ['국산'], 
     'stock_types' => ['일반재고']],
    
    // 환봉
    ['category' => 'round-bar', 'name' => '환봉 20φ', 
     'origins' => ['국산', '중국산', '일본산'], 
     'stock_types' => ['일반재고', '장기재고']],
    
    // 평철
    ['category' => 'flat-bar', 'name' => '평철 6×25', 
     'origins' => ['국산', '수입산'], 
     'stock_types' => ['일반재고', '중고']],
    
    // 사각파이프
    ['category' => 'square-pipe', 'name' => '사각파이프 50×50×2.3T', 
     'origins' => ['국산', '중국산'], 
     'stock_types' => ['일반재고', '장기재고']]
];

echo "<h3>1. 테스트 데이터 설정</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>카테고리</th>";
echo "<th>제품명</th>";
echo "<th>원산지 설정</th>";
echo "<th>재고 상태 설정</th>";
echo "<th>결과</th>";
echo "</tr>";

foreach ($test_products as $test) {
    // 제품 찾기
    $stmt = $pdo->prepare("
        SELECT id, product_name 
        FROM products 
        WHERE category_code = ? 
        AND product_name LIKE ?
        AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$test['category'], '%' . $test['name'] . '%']);
    $product = $stmt->fetch();
    
    if ($product) {
        // 원산지와 재고 상태 업데이트
        $origins_json = json_encode($test['origins'], JSON_UNESCAPED_UNICODE);
        $stock_types_json = json_encode($test['stock_types'], JSON_UNESCAPED_UNICODE);
        
        $update_stmt = $pdo->prepare("
            UPDATE products 
            SET available_origins = ?,
                stock_types = ?,
                origin = ?
            WHERE id = ?
        ");
        $update_stmt->execute([
            $origins_json,
            $stock_types_json,
            $test['origins'][0], // 첫 번째 원산지를 기본값으로
            $product['id']
        ]);
        
        echo "<tr>";
        echo "<td>{$test['category']}</td>";
        echo "<td>{$product['product_name']}</td>";
        echo "<td>" . implode(', ', $test['origins']) . "</td>";
        echo "<td>" . implode(', ', $test['stock_types']) . "</td>";
        echo "<td style='color: green;'>✅ 업데이트 완료</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>{$test['category']}</td>";
        echo "<td>{$test['name']}</td>";
        echo "<td colspan='3' style='color: red;'>제품을 찾을 수 없음</td>";
        echo "</tr>";
    }
}
echo "</table>";

// 업데이트된 제품 목록
echo "<h3>2. 업데이트된 제품 확인</h3>";
$stmt = $pdo->query("
    SELECT p.id, p.product_name, p.category_code, pc.category_name, 
           p.available_origins, p.stock_types
    FROM products p
    JOIN product_categories pc ON p.category_code = pc.category_code
    WHERE p.available_origins LIKE '%,%'
    OR p.stock_types LIKE '%,%'
    ORDER BY pc.display_order, p.id
    LIMIT 20
");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>ID</th>";
echo "<th>카테고리</th>";
echo "<th>제품명</th>";
echo "<th>원산지</th>";
echo "<th>재고 상태</th>";
echo "<th>상세 페이지</th>";
echo "</tr>";

while ($row = $stmt->fetch()) {
    $origins = json_decode($row['available_origins'], true);
    $stock_types = json_decode($row['stock_types'], true);
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['category_name']}</td>";
    echo "<td>{$row['product_name']}</td>";
    echo "<td>" . (is_array($origins) ? implode(', ', $origins) : $row['available_origins']) . "</td>";
    echo "<td>" . (is_array($stock_types) ? implode(', ', $stock_types) : $row['stock_types']) . "</td>";
    echo "<td><a href='/product_detail.php?id={$row['id']}' target='_blank'>보기</a></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>3. 테스트 링크</h3>";
echo "<ul>";
echo "<li><a href='/admin/admin_origin_stock.php' target='_blank'>관리자 - 원산지 재고 형식 관리</a></li>";
echo "<li><a href='/products_new.php?category=h-beam' target='_blank'>H형강 제품 목록</a></li>";
echo "<li><a href='/products_new.php?category=steel-plate' target='_blank'>강판 제품 목록</a></li>";
echo "<li><a href='/products_new.php?category=angle' target='_blank'>앵글 제품 목록</a></li>";
echo "<li><a href='/products_new.php?category=round-bar' target='_blank'>환봉 제품 목록</a></li>";
echo "</ul>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; padding: 20px; }";
echo "h2, h3 { color: #333; }";
echo "table { margin: 20px 0; }";
echo "a { color: #1976d2; text-decoration: none; }";
echo "a:hover { text-decoration: underline; }";
echo "</style>";
?>