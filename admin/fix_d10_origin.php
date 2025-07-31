<?php
require_once '../db.php';

// D10의 원산지를 국산, 중국산, 일본산으로 수정
$origins = ['국산', '중국산', '일본산'];
$origins_json = json_encode($origins, JSON_UNESCAPED_UNICODE);

$stmt = $pdo->prepare("
    UPDATE products 
    SET available_origins = ?, 
        origin = '국산',
        updated_at = NOW()
    WHERE product_name = '철근 D10' 
    AND category_code = 'rebar'
");
$stmt->execute([$origins_json]);

echo "철근 D10 원산지 수정 완료: " . implode(', ', $origins);