<?php
require_once 'db.php';

try {
    $pdo = getDB();
    
    // 철근 제품 중 하나를 선택해서 데이터 확인
    $sql = "SELECT id, product_name, weight_per_meter, length_data, pieces_per_ton 
            FROM products 
            WHERE category_code = 'rebar' AND product_name LIKE '%D10%'
            LIMIT 1";
    
    $stmt = $pdo->query($sql);
    $product = $stmt->fetch();
    
    if ($product) {
        echo "제품명: " . $product['product_name'] . "\n";
        echo "ID: " . $product['id'] . "\n";
        echo "미터당 중량: " . $product['weight_per_meter'] . "kg\n\n";
        
        // JSON 데이터 파싱
        $length_data = json_decode($product['length_data'], true);
        $pieces_data = json_decode($product['pieces_per_ton'], true);
        
        echo "길이별 데이터 (처음 5개):\n";
        echo str_pad("길이(m)", 10) . str_pad("본중(kg)", 12) . str_pad("톤당본수", 12) . "\n";
        echo str_repeat("-", 34) . "\n";
        
        $count = 0;
        foreach ($length_data as $length => $data) {
            if ($count >= 5) break;
            echo str_pad($data['length'], 10) . 
                 str_pad($data['weight_per_piece'], 12) . 
                 str_pad($data['pieces_per_ton'], 12) . "\n";
            $count++;
        }
        
        echo "\n전체 데이터 개수: " . count($length_data) . "개\n";
    } else {
        echo "철근 D10 제품을 찾을 수 없습니다.\n";
    }
    
} catch (Exception $e) {
    echo "오류: " . $e->getMessage() . "\n";
}
?>
EOF < /dev/null
