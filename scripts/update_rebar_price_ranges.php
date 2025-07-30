<?php
require_once dirname(__DIR__) . '/db.php';

// 철근 제품의 실제 단가범위 업데이트
// 각 규격별로 실제 시장 가격 범위를 반영

$price_ranges = [
    'D10' => ['price' => 850, 'min' => 800, 'max' => 900],
    'D13' => ['price' => 850, 'min' => 800, 'max' => 900],
    'D16' => ['price' => 850, 'min' => 800, 'max' => 900],
    'D19' => ['price' => 850, 'min' => 800, 'max' => 900],
    'D22' => ['price' => 850, 'min' => 800, 'max' => 900],
    'D25' => ['price' => 850, 'min' => 800, 'max' => 900],
    'D29' => ['price' => 860, 'min' => 810, 'max' => 910],
    'D32' => ['price' => 860, 'min' => 810, 'max' => 910],
    'D35' => ['price' => 870, 'min' => 820, 'max' => 920],
    'D38' => ['price' => 870, 'min' => 820, 'max' => 920],
    'D41' => ['price' => 880, 'min' => 830, 'max' => 930],
    'D51' => ['price' => 890, 'min' => 840, 'max' => 940]
];

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE products SET price = ?, min_price = ?, max_price = ? WHERE product_name = ? AND category_code = 'rebar'");
    
    foreach ($price_ranges as $spec => $prices) {
        $product_name = '철근 ' . $spec;
        $result = $stmt->execute([
            $prices['price'], 
            $prices['min'], 
            $prices['max'], 
            $product_name
        ]);
        
        if ($result) {
            echo "Updated {$product_name}: ";
            echo "기준 {$prices['price']}원, ";
            echo "최저 {$prices['min']}원, ";
            echo "최대 {$prices['max']}원\n";
        }
    }
    
    $pdo->commit();
    echo "\n실제 단가범위 업데이트 완료\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>