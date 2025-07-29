<?php
require_once 'db.php';

try {
    echo "<h2>전선관(Conduit Pipe) 계산식 데이터 상세 분석</h2>";
    
    // 1. 전선관 제품 목록과 단위중량 매칭
    echo "<h3>1. 전선관 제품과 단위중량 매칭 현황</h3>";
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.product_name,
            p.specifications,
            p.price,
            p.unit,
            uw.unit_weight,
            CASE 
                WHEN uw.unit_weight IS NOT NULL THEN 'O'
                ELSE 'X'
            END as has_weight
        FROM products p
        LEFT JOIN unit_weights uw ON p.specifications = uw.specification AND uw.is_active = 1
        WHERE p.category_code = 'conduit-pipe' 
        AND p.is_active = 1
        ORDER BY p.id
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>총 전선관 제품 수: " . count($products) . "개</p>";
    
    // 매칭 통계
    $matched = 0;
    $unmatched = 0;
    foreach ($products as $product) {
        if ($product['unit_weight']) {
            $matched++;
        } else {
            $unmatched++;
        }
    }
    
    echo "<p>단위중량 매칭된 제품: " . $matched . "개</p>";
    echo "<p>단위중량 없는 제품: " . $unmatched . "개</p>";
    echo "<p>매칭률: " . ($matched > 0 ? round($matched / count($products) * 100, 1) : 0) . "%</p>";
    
    // 제품 목록 표시
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr style='background-color: #e0e0e0;'>";
    echo "<th>ID</th>";
    echo "<th>제품명</th>";
    echo "<th>규격</th>";
    echo "<th>가격</th>";
    echo "<th>단위</th>";
    echo "<th>단위중량</th>";
    echo "<th>매칭</th>";
    echo "</tr>";
    
    $display_count = 0;
    foreach ($products as $product) {
        if ($display_count++ >= 20) break;
        
        $row_style = $product['unit_weight'] ? '' : ' style="background-color: #ffe0e0;"';
        echo "<tr$row_style>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . htmlspecialchars($product['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['specifications']) . "</td>";
        echo "<td>" . ($product['price'] ? number_format($product['price']) : '-') . "</td>";
        echo "<td>" . ($product['unit'] ?: 'TON') . "</td>";
        echo "<td>" . ($product['unit_weight'] ? number_format($product['unit_weight'], 2) : '-') . "</td>";
        echo "<td align='center'>" . $product['has_weight'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. 전선관 규격 패턴 분석
    echo "<h3>2. 전선관 규격 패턴 (단위중량이 없는 것들)</h3>";
    echo "<p>단위중량이 등록되지 않은 전선관 규격들:</p>";
    echo "<ul>";
    $unmatched_count = 0;
    foreach ($products as $product) {
        if (!$product['unit_weight'] && $product['specifications']) {
            if ($unmatched_count++ >= 10) {
                echo "<li>... 외 " . ($unmatched - 10) . "개</li>";
                break;
            }
            echo "<li>" . htmlspecialchars($product['specifications']) . " (제품: " . htmlspecialchars($product['product_name']) . ")</li>";
        }
    }
    echo "</ul>";
    
    // 3. 단위중량 테이블에 있는 전선관 관련 데이터
    echo "<h3>3. 단위중량 테이블의 전선관 관련 데이터</h3>";
    
    // 전선관 제품의 규격 패턴을 기반으로 검색
    $spec_parts = [];
    foreach ($products as $product) {
        if ($product['specifications']) {
            // 규격에서 숫자 추출
            if (preg_match('/(\d+)/', $product['specifications'], $matches)) {
                $spec_parts[] = $matches[1];
            }
        }
    }
    $spec_parts = array_unique($spec_parts);
    
    if (!empty($spec_parts)) {
        $conditions = [];
        foreach (array_slice($spec_parts, 0, 10) as $part) {
            $conditions[] = "specification LIKE '%" . $part . "%'";
        }
        
        $query = "
            SELECT specification, unit_weight 
            FROM unit_weights 
            WHERE is_active = 1 
            AND (" . implode(' OR ', $conditions) . ")
            ORDER BY specification
            LIMIT 20
        ";
        
        $stmt2 = $pdo->prepare($query);
        $stmt2->execute();
        $related_weights = $stmt2->fetchAll();
        
        if (count($related_weights) > 0) {
            echo "<p>관련 가능한 단위중량 데이터:</p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>규격</th><th>단위중량 (kg/m)</th></tr>";
            foreach ($related_weights as $rw) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($rw['specification']) . "</td>";
                echo "<td>" . number_format($rw['unit_weight'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // 4. 계산식 정보
    echo "<h3>4. 전선관 계산식</h3>";
    echo "<div style='background-color: #f0f0f0; padding: 15px; border: 1px solid #ccc;'>";
    echo "<h4>전선관 중량 계산:</h4>";
    echo "<ul>";
    echo "<li>1본 중량 = 단위중량(kg/m) × 길이(m)</li>";
    echo "<li>총 중량 = 1본 중량 × 수량(본)</li>";
    echo "</ul>";
    echo "<h4>전선관 금액 계산:</h4>";
    echo "<ul>";
    echo "<li>기준단가가 TON 단위인 경우: 금액 = (총 중량 ÷ 1000) × 기준단가</li>";
    echo "<li>기준단가가 개당 단위인 경우: 금액 = 수량 × 기준단가</li>";
    echo "</ul>";
    echo "</div>";
    
    // 5. 상세페이지 링크
    echo "<h3>5. 전선관 제품 상세페이지 링크</h3>";
    echo "<ul>";
    $link_count = 0;
    foreach ($products as $product) {
        if ($link_count++ >= 5) break;
        echo "<li>";
        echo "<a href='product_detail.php?id=" . $product['id'] . "' target='_blank'>";
        echo $product['id'] . ": " . htmlspecialchars($product['product_name']);
        echo " (" . htmlspecialchars($product['specifications']) . ")";
        echo $product['unit_weight'] ? " - 단위중량: " . $product['unit_weight'] . "kg/m" : " - 단위중량 없음";
        echo "</a>";
        echo "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "데이터베이스 오류: " . $e->getMessage();
}
?>