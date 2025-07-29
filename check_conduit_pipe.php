<?php
require_once 'db.php';

try {
    echo "<h2>전선관(Conduit Pipe) 계산식에 사용되는 규격 및 단위중량</h2>";
    
    // 전선관 카테고리 제품 조회
    echo "<h3>1. 전선관 카테고리 제품 목록</h3>";
    $stmt = $pdo->prepare("
        SELECT p.id, p.product_name, p.specifications, p.category_code, p.price
        FROM products p
        WHERE p.category_code = 'conduit-pipe' AND p.is_active = 1
        ORDER BY p.id
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>총 " . count($products) . "개의 전선관 제품이 있습니다.</p>";
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th>ID</th><th>제품명</th><th>규격</th><th>가격</th>";
    echo "</tr>";
    
    $count = 0;
    foreach ($products as $product) {
        if ($count++ >= 10) break; // 처음 10개만 표시
        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['specifications']) . "</td>";
        echo "<td>" . ($product['price'] ? number_format($product['price']) . "원" : "가격문의") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 전선관 규격 패턴 분석
    echo "<h3>2. 전선관 규격 패턴 분석</h3>";
    $stmt2 = $pdo->prepare("
        SELECT DISTINCT specifications 
        FROM products 
        WHERE category_code = 'conduit-pipe' AND is_active = 1
        ORDER BY specifications
        LIMIT 20
    ");
    $stmt2->execute();
    $specs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>전선관 규격 예시:</p>";
    echo "<ul>";
    foreach ($specs as $spec) {
        echo "<li>" . htmlspecialchars($spec['specifications']) . "</li>";
    }
    echo "</ul>";
    
    // 전선관 관련 단위중량 조회
    echo "<h3>3. 전선관 규격별 단위중량 (unit_weights 테이블)</h3>";
    
    // 먼저 전선관 규격 패턴 확인
    $conduit_patterns = [];
    foreach ($products as $product) {
        if (!empty($product['specifications'])) {
            $conduit_patterns[] = $product['specifications'];
        }
    }
    
    if (!empty($conduit_patterns)) {
        // 전선관 규격과 매칭되는 단위중량 조회
        $placeholders = array_fill(0, min(10, count($conduit_patterns)), '?');
        $query = "
            SELECT specification, unit_weight 
            FROM unit_weights 
            WHERE specification IN (" . implode(',', $placeholders) . ")
            AND is_active = 1
            ORDER BY specification
        ";
        
        $stmt3 = $pdo->prepare($query);
        $stmt3->execute(array_slice($conduit_patterns, 0, 10));
        $unit_weights = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($unit_weights) > 0) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>규격</th><th>단위중량 (kg/m)</th>";
            echo "</tr>";
            
            foreach ($unit_weights as $uw) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($uw['specification']) . "</td>";
                echo "<td>" . number_format($uw['unit_weight'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>전선관 규격과 매칭되는 단위중량 데이터가 없습니다.</p>";
        }
    }
    
    // 전선관 규격 패턴으로 단위중량 조회
    echo "<h3>4. 전선관 관련 가능한 단위중량 패턴 검색</h3>";
    $stmt4 = $pdo->prepare("
        SELECT specification, unit_weight 
        FROM unit_weights 
        WHERE is_active = 1 
        AND (
            specification LIKE '%전선관%' OR
            specification LIKE '%ST%' OR
            specification LIKE '%HI%' OR
            specification LIKE '%PE%' OR
            specification LIKE '%CD%' OR
            specification REGEXP '[0-9]+\\\\*[0-9.]+' OR
            specification REGEXP '[0-9]+x[0-9.]+'
        )
        ORDER BY specification
        LIMIT 30
    ");
    $stmt4->execute();
    $possible_weights = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($possible_weights) > 0) {
        echo "<p>가능한 전선관 관련 단위중량 데이터:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>규격</th><th>단위중량 (kg/m)</th>";
        echo "</tr>";
        
        foreach ($possible_weights as $pw) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($pw['specification']) . "</td>";
            echo "<td>" . number_format($pw['unit_weight'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 전선관 제품 상세페이지의 계산식 정보
    echo "<h3>5. 전선관 계산식 정보</h3>";
    echo "<div style='background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-top: 10px;'>";
    echo "<p><strong>전선관 중량 계산식:</strong></p>";
    echo "<p>1본 중량 = 단위중량(kg/m) × 길이(m)</p>";
    echo "<p>총 중량 = 1본 중량 × 수량(본)</p>";
    echo "<br>";
    echo "<p><strong>전선관 금액 계산식:</strong></p>";
    echo "<p>1본 금액 = (1본 중량 × 기준단가) / 1000</p>";
    echo "<p>총 금액 = 1본 금액 × 수량(본)</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "데이터베이스 오류: " . $e->getMessage();
}
?>