<?php
require_once 'db.php';

try {
    // 구조관 제품이 사용하는 규격 확인
    echo "<h2>구조관 제품의 규격과 단위중량 매칭 현황</h2>";
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.product_name,
            p.specifications,
            uw.unit_weight,
            CASE 
                WHEN uw.unit_weight IS NOT NULL THEN 'O'
                ELSE 'X'
            END as matched
        FROM products p
        LEFT JOIN unit_weights uw ON p.specifications = uw.specification AND uw.is_active = 1
        WHERE p.category_code = 'structural-pipe' 
        AND p.is_active = 1
        ORDER BY p.id
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>구조관 카테고리 제품 수: " . count($products) . "개</p>";
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #e0e0e0;'>";
    echo "<th>제품ID</th>";
    echo "<th>제품명</th>";
    echo "<th>규격</th>";
    echo "<th>단위중량 (kg/m)</th>";
    echo "<th>매칭</th>";
    echo "</tr>";
    
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['specifications']) . "</td>";
        echo "<td align='right'>" . ($product['unit_weight'] ? number_format($product['unit_weight'], 2) : '-') . "</td>";
        echo "<td align='center'>" . $product['matched'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 요약 정보
    $matched_count = 0;
    $unmatched_count = 0;
    foreach ($products as $product) {
        if ($product['unit_weight']) {
            $matched_count++;
        } else {
            $unmatched_count++;
        }
    }
    
    echo "<h3>요약</h3>";
    echo "<p>매칭된 제품: " . $matched_count . "개</p>";
    echo "<p>매칭되지 않은 제품: " . $unmatched_count . "개</p>";
    echo "<p>매칭률: " . ($matched_count > 0 ? round($matched_count / count($products) * 100, 1) : 0) . "%</p>";
    
} catch (PDOException $e) {
    echo "데이터베이스 오류: " . $e->getMessage();
}
?>