<?php
require_once 'db.php';

echo "<h2>다양한 카테고리 제품 테스트 데이터 설정</h2>";

// 각 카테고리에서 실제 제품 찾아서 설정
$categories_to_test = [
    'h-beam' => ['국산', '일본산', '중국산'],
    'steel-plate' => ['국산', '중국산'],
    'angle' => ['국산', '베트남산', '수입산'],
    'channel' => ['국산', '일본산'],
    'round-bar' => ['국산', '중국산', '일본산'],
    'flat-bar' => ['국산', '수입산'],
    'square-pipe' => ['국산', '중국산'],
    'c-beam' => ['국산', '베트남산'],
    'i-beam' => ['국산', '일본산', '중국산']
];

$stock_types_options = [
    ['일반재고'],
    ['일반재고', '장기재고'],
    ['일반재고', '중고'],
    ['일반재고', '장기재고', '중고']
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>카테고리</th>";
echo "<th>제품명</th>";
echo "<th>원산지</th>";
echo "<th>재고 상태</th>";
echo "<th>상세 페이지</th>";
echo "</tr>";

foreach ($categories_to_test as $category => $origins) {
    // 각 카테고리에서 첫 번째 활성 제품 가져오기
    $stmt = $pdo->prepare("
        SELECT p.id, p.product_name, pc.category_name
        FROM products p
        JOIN product_categories pc ON p.category_code = pc.category_code
        WHERE p.category_code = ? 
        AND p.is_active = 1
        ORDER BY p.id
        LIMIT 1
    ");
    $stmt->execute([$category]);
    $product = $stmt->fetch();
    
    if ($product) {
        // 랜덤하게 재고 상태 선택
        $stock_types = $stock_types_options[array_rand($stock_types_options)];
        
        // 업데이트
        $origins_json = json_encode($origins, JSON_UNESCAPED_UNICODE);
        $stock_types_json = json_encode($stock_types, JSON_UNESCAPED_UNICODE);
        
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
            $origins[0],
            $product['id']
        ]);
        
        echo "<tr>";
        echo "<td>{$product['category_name']}</td>";
        echo "<td>{$product['product_name']}</td>";
        echo "<td>" . implode(', ', $origins) . "</td>";
        echo "<td>" . implode(', ', $stock_types) . "</td>";
        echo "<td><a href='http://211.248.112.67:1112/product_detail.php?id={$product['id']}' target='_blank'>보기</a></td>";
        echo "</tr>";
    }
}

echo "</table>";

// 카테고리별 통계
echo "<h3>카테고리별 복수 원산지/재고 상태 보유 현황</h3>";
$stmt = $pdo->query("
    SELECT 
        pc.category_name,
        pc.category_code,
        COUNT(p.id) as total_products,
        COUNT(CASE WHEN p.available_origins LIKE '%,%' THEN 1 END) as multi_origin_count,
        COUNT(CASE WHEN p.stock_types LIKE '%,%' THEN 1 END) as multi_stock_count
    FROM product_categories pc
    LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
    WHERE pc.is_active = 1
    GROUP BY pc.category_code, pc.category_name
    HAVING total_products > 0
    ORDER BY pc.display_order
");

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>카테고리</th>";
echo "<th>전체 제품</th>";
echo "<th>복수 원산지</th>";
echo "<th>복수 재고상태</th>";
echo "<th>목록 페이지</th>";
echo "</tr>";

while ($row = $stmt->fetch()) {
    echo "<tr>";
    echo "<td>{$row['category_name']}</td>";
    echo "<td>{$row['total_products']}</td>";
    echo "<td>{$row['multi_origin_count']}</td>";
    echo "<td>{$row['multi_stock_count']}</td>";
    echo "<td><a href='http://211.248.112.67:1112/products_new.php?category={$row['category_code']}' target='_blank'>보기</a></td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>✅ 모든 카테고리에 원산지 및 재고 상태 기능이 적용되었습니다.</strong></p>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; padding: 20px; }";
echo "h2, h3 { color: #333; }";
echo "table { margin: 20px 0; }";
echo "a { color: #1976d2; text-decoration: none; }";
echo "a:hover { text-decoration: underline; }";
echo "</style>";
?>