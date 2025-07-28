<?php
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/rebar_unit_weights.php';

// 철근 규격별 제품 정보
$rebar_products = [
    'D10' => [
        'name' => '철근 D10',
        'specs' => '직경: 9.53mm, 단위중량: 0.56kg/m',
        'desc' => '소형 구조물, 슬래브 배근용 철근'
    ],
    'D13' => [
        'name' => '철근 D13',
        'specs' => '직경: 12.7mm, 단위중량: 0.995kg/m',
        'desc' => '일반 구조물, 기초 배근용 철근'
    ],
    'D16' => [
        'name' => '철근 D16',
        'specs' => '직경: 15.9mm, 단위중량: 1.56kg/m',
        'desc' => '중형 구조물, 보·기둥 배근용 철근'
    ],
    'D19' => [
        'name' => '철근 D19',
        'specs' => '직경: 19.1mm, 단위중량: 2.25kg/m',
        'desc' => '중형 구조물, 보·기둥 주근용 철근'
    ],
    'D22' => [
        'name' => '철근 D22',
        'specs' => '직경: 22.2mm, 단위중량: 3.04kg/m',
        'desc' => '대형 구조물, 보·기둥 주근용 철근'
    ],
    'D25' => [
        'name' => '철근 D25',
        'specs' => '직경: 25.4mm, 단위중량: 3.98kg/m',
        'desc' => '대형 구조물, 교량·댐 등 특수구조물용 철근'
    ],
    'D29' => [
        'name' => '철근 D29',
        'specs' => '직경: 28.6mm, 단위중량: 5.04kg/m',
        'desc' => '특수 구조물, 교량 주형보용 철근'
    ],
    'D32' => [
        'name' => '철근 D32',
        'specs' => '직경: 31.8mm, 단위중량: 6.23kg/m',
        'desc' => '특수 구조물, 대형 교량·댐용 철근'
    ],
    'D35' => [
        'name' => '철근 D35',
        'specs' => '직경: 34.9mm, 단위중량: 7.51kg/m',
        'desc' => '초대형 구조물, 특수 토목공사용 철근'
    ],
    'D38' => [
        'name' => '철근 D38',
        'specs' => '직경: 38.1mm, 단위중량: 8.95kg/m',
        'desc' => '초대형 구조물, 특수 토목공사용 철근'
    ],
    'D41' => [
        'name' => '철근 D41',
        'specs' => '직경: 41.3mm, 단위중량: 10.5kg/m',
        'desc' => '초대형 특수 구조물용 철근'
    ],
    'D51' => [
        'name' => '철근 D51',
        'specs' => '직경: 50.8mm, 단위중량: 15.9kg/m',
        'desc' => '초대형 특수 구조물용 철근'
    ]
];

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT INTO products (
            product_name, 
            category_code, 
            specifications, 
            description, 
            unit, 
            stock_status,
            is_active,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $count = 0;
    foreach ($rebar_products as $spec => $product) {
        $result = $stmt->execute([
            $product['name'],
            'rebar',
            $product['specs'],
            $product['desc'],
            '톤',
            'on_order',
            1
        ]);
        
        if ($result) {
            $count++;
            echo "추가됨: {$product['name']}\n";
        }
    }
    
    $pdo->commit();
    echo "\n총 {$count}개의 철근 제품이 추가되었습니다.\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>