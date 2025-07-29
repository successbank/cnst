<?php
require_once 'db.php';

try {
    
    echo "<h2>구조관(Structural Pipe) 제품 목록 및 단위중량</h2>";
    
    // 구조관 카테고리 제품 조회
    $stmt = $pdo->prepare("
        SELECT p.id, p.product_name, p.specifications, p.category_code, p.price
        FROM products p
        WHERE p.category_code = 'structural-pipe' AND p.is_active = 1
        ORDER BY p.id
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>총 " . count($products) . "개의 구조관 제품이 있습니다.</p>";
    
    // 단위중량 테이블 데이터 조회
    $stmt2 = $pdo->prepare("
        SELECT specification, unit_weight 
        FROM unit_weights 
        WHERE is_active = 1 
        ORDER BY specification
    ");
    $stmt2->execute();
    $unit_weights = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>단위중량 테이블 (unit_weights)</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>규격(specification)</th><th>단위중량(unit_weight) kg/m</th></tr>";
    foreach ($unit_weights as $uw) {
        echo "<tr><td>" . htmlspecialchars($uw['specification']) . "</td><td>" . $uw['unit_weight'] . "</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>구조관 제품 목록</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>제품명</th><th>규격</th><th>단위중량</th></tr>";
    
    foreach ($products as $product) {
        // 해당 제품의 단위중량 찾기
        $unit_weight = null;
        foreach ($unit_weights as $uw) {
            if ($uw['specification'] == $product['specifications']) {
                $unit_weight = $uw['unit_weight'];
                break;
            }
        }
        
        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['specifications']) . "</td>";
        echo "<td>" . ($unit_weight ? $unit_weight . " kg/m" : "없음") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "데이터베이스 오류: " . $e->getMessage();
}
?>