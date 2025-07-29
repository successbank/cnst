<?php
require_once 'db.php';

try {
    echo "<h2>구조관 계산에 사용되는 DB 단위중량 정보</h2>";
    
    // 구조관 관련 단위중량만 조회 (구조관 패턴: 숫자*숫자*숫자T 형식)
    $stmt = $pdo->prepare("
        SELECT specification, unit_weight 
        FROM unit_weights 
        WHERE is_active = 1 
        AND (
            specification LIKE '%*%*%T' OR
            specification LIKE '%*%*%*%' OR
            specification LIKE '%×%×%'
        )
        ORDER BY specification
    ");
    $stmt->execute();
    $unit_weights = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>총 " . count($unit_weights) . "개의 구조관 단위중량 데이터가 있습니다.</p>";
    
    // 테이블로 표시
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>번호</th>";
    echo "<th>규격 (specification)</th>";
    echo "<th>단위중량 (unit_weight) kg/m</th>";
    echo "</tr>";
    
    $count = 1;
    foreach ($unit_weights as $uw) {
        echo "<tr>";
        echo "<td align='center'>" . $count++ . "</td>";
        echo "<td>" . htmlspecialchars($uw['specification']) . "</td>";
        echo "<td align='right'>" . number_format($uw['unit_weight'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 구조관 제품이 사용하는 규격 확인
    echo "<h3>구조관 제품이 실제 사용하는 규격</h3>";
    $stmt2 = $pdo->prepare("
        SELECT DISTINCT p.specifications, uw.unit_weight
        FROM products p
        LEFT JOIN unit_weights uw ON p.specifications = uw.specification AND uw.is_active = 1
        WHERE p.category_code = 'structural-pipe' 
        AND p.is_active = 1
        ORDER BY p.specifications
    ");
    $stmt2->execute();
    $product_specs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>제품 규격</th>";
    echo "<th>단위중량 (kg/m)</th>";
    echo "<th>상태</th>";
    echo "</tr>";
    
    foreach ($product_specs as $spec) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($spec['specifications']) . "</td>";
        echo "<td align='right'>" . ($spec['unit_weight'] ? number_format($spec['unit_weight'], 2) : '-') . "</td>";
        echo "<td>" . ($spec['unit_weight'] ? '✓ 연결됨' : '❌ 미연결') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "데이터베이스 오류: " . $e->getMessage();
}
?>