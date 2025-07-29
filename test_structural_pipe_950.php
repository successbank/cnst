<?php
require_once 'db.php';

echo "<h2>구조관 제품 ID 950 정보 확인</h2>";

// 제품 정보 확인
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name 
    FROM products p 
    JOIN product_categories pc ON p.category_code = pc.category_code 
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([950]);
$product = $stmt->fetch();

if ($product) {
    echo "<h3>제품 정보</h3>";
    echo "<p>제품명: " . htmlspecialchars($product['product_name']) . "</p>";
    echo "<p>규격: " . htmlspecialchars($product['specifications']) . "</p>";
    echo "<p>카테고리: " . $product['category_code'] . " - " . $product['category_name'] . "</p>";
    echo "<p>기준단가: " . ($product['price'] ? number_format($product['price']) . "원" : "가격문의") . "</p>";
    
    // 해당 규격의 단위중량 확인
    $stmt2 = $pdo->prepare("
        SELECT unit_weight 
        FROM unit_weights 
        WHERE specification = ? AND is_active = 1
    ");
    $stmt2->execute([$product['specifications']]);
    $unit_weight = $stmt2->fetch();
    
    if ($unit_weight) {
        echo "<p>단위중량: " . $unit_weight['unit_weight'] . " kg/m</p>";
    } else {
        echo "<p>단위중량: 등록되지 않음</p>";
    }
    
    // 구조관 전체 규격 목록
    echo "<h3>구조관 규격 목록 (단위중량 포함)</h3>";
    $stmt3 = $pdo->prepare("
        SELECT specification, unit_weight 
        FROM unit_weights 
        WHERE is_active = 1 
        AND (
            specification LIKE '%*%*%T' OR
            specification LIKE '%*%*%*%' OR
            specification LIKE '%×%×%'
        )
        ORDER BY specification
        LIMIT 10
    ");
    $stmt3->execute();
    $specs = $stmt3->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>규격</th><th>단위중량 (kg/m)</th></tr>";
    foreach ($specs as $spec) {
        $selected = ($spec['specification'] == $product['specifications']) ? ' style="background-color: #ffff99;"' : '';
        echo "<tr$selected>";
        echo "<td>" . htmlspecialchars($spec['specification']) . "</td>";
        echo "<td>" . number_format($spec['unit_weight'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><a href='product_detail.php?id=950' target='_blank'>제품 상세페이지 확인</a></p>";
} else {
    echo "<p>제품을 찾을 수 없습니다.</p>";
}
?>