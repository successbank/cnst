<?php
require_once 'db.php';

// 테스트를 위해 몇 개 제품의 stock_types 업데이트
$test_data = [
    ['id' => 728, 'stock_types' => '["일반재고"]'],
    ['id' => 729, 'stock_types' => '["장기재고"]'],
    ['id' => 730, 'stock_types' => '["일반재고", "장기재고"]'],
    ['id' => 731, 'stock_types' => '["중고"]'],
    ['id' => 732, 'stock_types' => '["일반재고", "중고"]']
];

echo "<h2>재고 상태 테스트 데이터 설정</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>제품 ID</th><th>제품명</th><th>설정된 재고 상태</th></tr>";

foreach ($test_data as $data) {
    $stmt = $pdo->prepare("UPDATE products SET stock_types = ? WHERE id = ?");
    $stmt->execute([$data['stock_types'], $data['id']]);
    
    // 제품 정보 가져오기
    $stmt = $pdo->prepare("SELECT product_name FROM products WHERE id = ?");
    $stmt->execute([$data['id']]);
    $product = $stmt->fetch();
    
    if ($product) {
        $stock_types = json_decode($data['stock_types'], true);
        echo "<tr>";
        echo "<td>{$data['id']}</td>";
        echo "<td>{$product['product_name']}</td>";
        echo "<td>" . implode(', ', $stock_types) . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<h3>테스트 링크</h3>";
echo "<ul>";
echo "<li><a href='/admin/admin_origin_stock.php' target='_blank'>관리자 - 원산지 재고 형식 관리</a></li>";
echo "<li><a href='/product_detail.php?id=728' target='_blank'>제품 상세 - 일반재고</a></li>";
echo "<li><a href='/product_detail.php?id=729' target='_blank'>제품 상세 - 장기재고</a></li>";
echo "<li><a href='/product_detail.php?id=730' target='_blank'>제품 상세 - 일반재고, 장기재고</a></li>";
echo "<li><a href='/product_detail.php?id=731' target='_blank'>제품 상세 - 중고</a></li>";
echo "<li><a href='/product_detail.php?id=732' target='_blank'>제품 상세 - 일반재고, 중고</a></li>";
echo "</ul>";

// 전체 제품의 stock_types 현황 확인
echo "<h3>전체 제품 재고 상태 현황</h3>";
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN stock_types LIKE '%일반재고%' THEN 1 ELSE 0 END) as normal_count,
        SUM(CASE WHEN stock_types LIKE '%장기재고%' THEN 1 ELSE 0 END) as long_term_count,
        SUM(CASE WHEN stock_types LIKE '%중고%' THEN 1 ELSE 0 END) as used_count
    FROM products
    WHERE is_active = 1
");
$stats = $stmt->fetch();

echo "<p>전체 제품: {$stats['total']}개</p>";
echo "<p>일반재고 포함: {$stats['normal_count']}개</p>";
echo "<p>장기재고 포함: {$stats['long_term_count']}개</p>";
echo "<p>중고 포함: {$stats['used_count']}개</p>";
?>