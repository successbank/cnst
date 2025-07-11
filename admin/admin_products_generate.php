<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_products_integrated.php?tab=generate");
    exit;
}

$product_type = $_POST['product_type'] ?? '';

try {
    // 조건 설정
    $where = ["uw.is_active = 1"];
    $params = [];
    
    if ($product_type) {
        $where[] = "uw.product_type = ?";
        $params[] = $product_type;
    }
    
    $whereClause = implode(" AND ", $where);
    
    // 생성할 단중표 데이터 가져오기
    $stmt = $pdo->prepare("
        SELECT uw.* 
        FROM unit_weights uw
        LEFT JOIN products p ON p.specifications = uw.specification
        WHERE $whereClause AND p.id IS NULL
    ");
    $stmt->execute($params);
    $weights = $stmt->fetchAll();
    
    $count = 0;
    $errors = [];
    
    foreach ($weights as $weight) {
        try {
            // 카테고리 코드 결정
            $category_map = [
                'H형강' => 'h-beam',
                'I형강' => 'i-beam',
                'ㄱ형강' => 'angle',
                'ㄷ형강' => 'channel',
                '환봉' => 'round-bar',
                '평철' => 'flat-bar',
                'C형강' => 'c-beam',
                '사각파이프' => 'square-pipe',
                '원형파이프' => 'round-pipe',
                '레일' => 'rail',
                '강널말뚝' => 'sheet-pile',
                '스테인레스' => 'stainless'
            ];
            
            $category_code = $category_map[$weight['product_type']] ?? 'etc';
            
            // 제품명 생성
            $product_name = $weight['product_type'] . ' ' . $weight['specification'];
            
            // 설명 생성
            $description = $weight['product_type'] . ' 규격: ' . $weight['specification'];
            $description .= ', 단위중량: ' . $weight['unit_weight'] . 'kg/m';
            if ($weight['material']) {
                $description .= ', 재질: ' . $weight['material'];
            }
            
            // 제품 생성
            $stmt = $pdo->prepare("
                INSERT INTO products (
                    category_code, product_name, specifications, 
                    description, unit, origin, manufacturer, is_active, created_at
                ) VALUES (?, ?, ?, ?, 'TON', '대한민국', '포스코', 1, NOW())
            ");
            
            $stmt->execute([
                $category_code, 
                $product_name, 
                $weight['specification'],
                $description
            ]);
            
            $count++;
            
        } catch (PDOException $e) {
            $errors[] = $weight['specification'] . ': ' . $e->getMessage();
        }
    }
    
    // 결과 메시지 설정
    $message = "generated&count=$count";
    if (!empty($errors)) {
        $_SESSION['generation_errors'] = $errors;
    }
    
    header("Location: admin_products_integrated.php?tab=generate&message=$message");
    exit;
    
} catch (PDOException $e) {
    header("Location: admin_products_integrated.php?tab=generate&message=error");
    exit;
}
?>