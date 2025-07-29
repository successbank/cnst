<?php
require_once 'db.php';

try {
    echo "<h2>전선관 계산식 데이터 분석</h2>";
    
    // 1. 전선관 제품 중 특정 제품의 상세 정보
    echo "<h3>1. 전선관 제품 예시 (첫 번째 제품)</h3>";
    $stmt = $pdo->prepare("
        SELECT p.*, pc.category_name
        FROM products p
        JOIN product_categories pc ON p.category_code = pc.category_code
        WHERE p.category_code = 'conduit-pipe' AND p.is_active = 1
        ORDER BY p.id
        LIMIT 1
    ");
    $stmt->execute();
    $sample_product = $stmt->fetch();
    
    if ($sample_product) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>속성</th><th>값</th></tr>";
        echo "<tr><td>제품 ID</td><td>" . $sample_product['id'] . "</td></tr>";
        echo "<tr><td>제품명</td><td>" . htmlspecialchars($sample_product['product_name']) . "</td></tr>";
        echo "<tr><td>규격</td><td>" . htmlspecialchars($sample_product['specifications']) . "</td></tr>";
        echo "<tr><td>카테고리</td><td>" . $sample_product['category_code'] . " - " . $sample_product['category_name'] . "</td></tr>";
        echo "<tr><td>가격</td><td>" . ($sample_product['price'] ? number_format($sample_product['price']) . "원" : "가격문의") . "</td></tr>";
        echo "<tr><td>단위</td><td>" . ($sample_product['unit'] ?: 'TON') . "</td></tr>";
        echo "</table>";
        
        // 해당 규격의 단위중량 확인
        if ($sample_product['specifications']) {
            $stmt2 = $pdo->prepare("
                SELECT unit_weight 
                FROM unit_weights 
                WHERE specification = ? AND is_active = 1
            ");
            $stmt2->execute([$sample_product['specifications']]);
            $unit_weight = $stmt2->fetch();
            
            if ($unit_weight) {
                echo "<p><strong>단위중량:</strong> " . $unit_weight['unit_weight'] . " kg/m</p>";
            } else {
                echo "<p><strong>단위중량:</strong> 등록되지 않음</p>";
            }
        }
    }
    
    // 2. 전선관 규격 패턴 분석
    echo "<h3>2. 전선관 제품들의 규격 패턴</h3>";
    $stmt3 = $pdo->prepare("
        SELECT DISTINCT specifications, COUNT(*) as count
        FROM products 
        WHERE category_code = 'conduit-pipe' AND is_active = 1
        AND specifications IS NOT NULL AND specifications != ''
        GROUP BY specifications
        ORDER BY count DESC
        LIMIT 20
    ");
    $stmt3->execute();
    $spec_patterns = $stmt3->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>규격</th><th>제품 수</th><th>단위중량 존재</th></tr>";
    
    foreach ($spec_patterns as $pattern) {
        // 각 규격의 단위중량 존재 여부 확인
        $stmt4 = $pdo->prepare("
            SELECT unit_weight 
            FROM unit_weights 
            WHERE specification = ? AND is_active = 1
        ");
        $stmt4->execute([$pattern['specifications']]);
        $uw = $stmt4->fetch();
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($pattern['specifications']) . "</td>";
        echo "<td>" . $pattern['count'] . "</td>";
        echo "<td>" . ($uw ? $uw['unit_weight'] . " kg/m" : "❌ 없음") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. 전선관과 관련된 모든 단위중량 검색
    echo "<h3>3. 단위중량 테이블에서 전선관 관련 데이터 검색</h3>";
    
    // 다양한 패턴으로 검색
    $patterns = [
        "전선관" => "specification LIKE '%전선관%'",
        "숫자×숫자 패턴" => "specification REGEXP '^[0-9]+×[0-9.]+$'",
        "숫자*숫자 패턴" => "specification REGEXP '^[0-9]+\\\\*[0-9.]+$'",
        "ST 포함" => "specification LIKE '%ST%'",
        "HI 포함" => "specification LIKE '%HI%'",
        "PE 포함" => "specification LIKE '%PE%'"
    ];
    
    foreach ($patterns as $pattern_name => $condition) {
        echo "<h4>" . $pattern_name . "</h4>";
        
        try {
            $query = "
                SELECT specification, unit_weight 
                FROM unit_weights 
                WHERE is_active = 1 AND " . $condition . "
                ORDER BY specification
                LIMIT 10
            ";
            
            $stmt5 = $pdo->prepare($query);
            $stmt5->execute();
            $results = $stmt5->fetchAll();
            
            if (count($results) > 0) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>규격</th><th>단위중량 (kg/m)</th></tr>";
                foreach ($results as $result) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($result['specification']) . "</td>";
                    echo "<td>" . number_format($result['unit_weight'], 2) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>해당 패턴으로 검색된 결과가 없습니다.</p>";
            }
        } catch (Exception $e) {
            echo "<p>검색 오류: " . $e->getMessage() . "</p>";
        }
    }
    
    // 4. 전선관 제품 상세페이지 링크
    echo "<h3>4. 전선관 제품 상세페이지 확인</h3>";
    $stmt6 = $pdo->prepare("
        SELECT id, product_name, specifications 
        FROM products 
        WHERE category_code = 'conduit-pipe' AND is_active = 1
        ORDER BY id
        LIMIT 5
    ");
    $stmt6->execute();
    $conduit_products = $stmt6->fetchAll();
    
    echo "<ul>";
    foreach ($conduit_products as $cp) {
        echo "<li>";
        echo "<a href='product_detail.php?id=" . $cp['id'] . "' target='_blank'>";
        echo htmlspecialchars($cp['product_name']) . " (" . htmlspecialchars($cp['specifications']) . ")";
        echo "</a>";
        echo "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "데이터베이스 오류: " . $e->getMessage();
}
?>