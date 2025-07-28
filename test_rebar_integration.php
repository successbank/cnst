<?php
// product_detail.php 철근 통합 테스트
require_once 'db.php';

echo "=== product_detail.php 철근 통합 테스트 ===\n\n";

// 1. 제품 ID 1008 확인
$product_id = 1008;
echo "[1] 제품 정보 확인 (ID: $product_id)\n";

$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name 
    FROM products p 
    JOIN product_categories pc ON p.category_code = pc.category_code 
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if ($product) {
    echo "제품명: " . $product['product_name'] . "\n";
    echo "규격: " . $product['specifications'] . "\n";
    echo "카테고리 코드: " . $product['category_code'] . "\n";
    echo "카테고리명: " . $product['category_name'] . "\n\n";
} else {
    echo "제품을 찾을 수 없습니다.\n\n";
    exit;
}

// 2. 철근 카테고리 확인
echo "[2] 철근 카테고리 확인\n";
$is_rebar = ($product['category_code'] === 'rebar' || 
             $product['category_code'] === '114' || 
             $product['category_code'] == 114 ||
             strpos(strtolower($product['category_name']), '철근') !== false);

echo "철근 제품 여부: " . ($is_rebar ? "예" : "아니오") . "\n\n";

if ($is_rebar) {
    // 3. 규격명 추출
    echo "[3] 규격 정보 확인\n";
    $spec_name = '';
    if (preg_match('/^(D\d+)/', $product['specifications'], $matches)) {
        $spec_name = $matches[1];
    }
    echo "추출된 규격명: $spec_name\n";
    
    // 4. 철근 규격 정보 조회
    if ($spec_name) {
        $stmt = $pdo->prepare("
            SELECT rs.*, rp.unit_price 
            FROM rebar_specifications rs
            LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id AND rp.is_active = TRUE
            WHERE rs.spec_name = ? AND rs.is_active = TRUE
        ");
        $stmt->execute([$spec_name]);
        $rebar_spec = $stmt->fetch();
        
        if ($rebar_spec) {
            echo "규격 ID: " . $rebar_spec['id'] . "\n";
            echo "직경: " . $rebar_spec['diameter'] . "mm\n";
            echo "단위중량: " . $rebar_spec['unit_weight'] . "kg/m\n";
            echo "기준단가: " . ($rebar_spec['unit_price'] ?: '미설정') . "원/kg\n\n";
            
            // 5. 길이 정보 확인
            echo "[4] 길이 정보 확인\n";
            $stmt = $pdo->prepare("
                SELECT rl.*, rs.unit_weight 
                FROM rebar_length_info rl
                JOIN rebar_specifications rs ON rl.spec_id = rs.id
                WHERE rl.spec_id = ?
                ORDER BY rl.length
            ");
            $stmt->execute([$rebar_spec['id']]);
            $rebar_lengths = $stmt->fetchAll();
            
            echo "사용 가능한 길이:\n";
            foreach ($rebar_lengths as $length) {
                echo "- {$length['length']}m (톤당 {$length['pieces_per_ton']}본)\n";
            }
            echo "\n";
            
            // 6. 재질 정보 확인
            echo "[5] 재질 정보 확인\n";
            $stmt = $pdo->query("
                SELECT * FROM rebar_materials 
                WHERE is_active = TRUE 
                ORDER BY display_order
            ");
            $rebar_materials = $stmt->fetchAll();
            
            echo "사용 가능한 재질:\n";
            foreach ($rebar_materials as $material) {
                echo "- {$material['material_name']} (추가단가: +{$material['additional_price']}원/kg)\n";
            }
            echo "\n";
            
            // 7. 계산 예시
            echo "[6] 계산 예시 (D10, 8m, SD500, 5톤)\n";
            echo "================================================\n";
            
            $test_ton = 5;
            $test_length = 8;
            $test_material_price = 30; // SD500
            
            // 8m 길이 정보 찾기
            $length_info = null;
            foreach ($rebar_lengths as $l) {
                if ($l['length'] == $test_length) {
                    $length_info = $l;
                    break;
                }
            }
            
            if ($length_info && $rebar_spec) {
                $pieces_per_ton = $length_info['pieces_per_ton'];
                $unit_weight = $rebar_spec['unit_weight'];
                $base_price = $rebar_spec['unit_price'] ?: 0;
                
                // 계산
                $actual_quantity = $test_ton * $pieces_per_ton;
                $weight_per_piece = $unit_weight * $test_length;
                $total_weight = $weight_per_piece * $actual_quantity;
                $final_price = $base_price + $test_material_price;
                $total_price = $total_weight * $final_price;
                
                echo "입력:\n";
                echo "- 톤수: {$test_ton}톤\n";
                echo "- 길이: {$test_length}m\n";
                echo "- 재질: SD500 (추가단가 {$test_material_price}원/kg)\n\n";
                
                echo "계산 과정:\n";
                echo "1. 실제 본수 = {$test_ton} × {$pieces_per_ton} = {$actual_quantity}본\n";
                echo "2. 본당 중량 = {$unit_weight} × {$test_length} = {$weight_per_piece}kg\n";
                echo "3. 총 중량 = {$weight_per_piece} × {$actual_quantity} = {$total_weight}kg\n";
                echo "4. 적용 단가 = {$base_price} + {$test_material_price} = {$final_price}원/kg\n";
                echo "5. 총 금액 = {$total_weight} × {$final_price} = " . number_format($total_price) . "원\n";
            }
            
        } else {
            echo "철근 규격 정보를 찾을 수 없습니다.\n";
        }
    }
} else {
    echo "철근 제품이 아닙니다.\n";
}

echo "\n=== 테스트 완료 ===\n";
echo "URL: http://211.248.112.67:1112/product_detail.php?id=1008\n";
?>