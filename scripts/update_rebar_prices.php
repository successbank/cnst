<?php
require_once dirname(__DIR__) . '/db.php';

// 철근 제품 가격을 ton당에서 kg당으로 변환
// 850,000원/ton → 850원/kg

$price_updates = [
    'D10' => 850,
    'D13' => 850,
    'D16' => 850,
    'D19' => 850,
    'D22' => 850,
    'D25' => 850,
    'D29' => 860,
    'D32' => 860,
    'D35' => 870,
    'D38' => 870,
    'D41' => 880,
    'D51' => 890
];

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE products SET price = ? WHERE product_name = ? AND category_code = 'rebar'");
    
    foreach ($price_updates as $spec => $price) {
        $product_name = '철근 ' . $spec;
        $result = $stmt->execute([$price, $product_name]);
        
        if ($result) {
            echo "Updated {$product_name}: {$price}원/kg\n";
        }
    }
    
    $pdo->commit();
    echo "\n가격 업데이트 완료\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>