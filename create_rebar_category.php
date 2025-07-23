<?php
require_once 'db.php';

try {
    // 철근 카테고리 추가
    $stmt = $pdo->prepare("
        INSERT INTO product_categories (name, display_name, description, icon, display_order, is_active) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        'rebar',
        '철근',
        '건축용 철근 - D10, D13, D16, D19, D22, D25, D29, D32, D35, D38, D41',
        'fas fa-bars',
        11,
        1
    ]);
    
    echo "철근 카테고리가 성공적으로 생성되었습니다.\n";
    
    // 생성된 카테고리 ID 가져오기
    $categoryId = $pdo->lastInsertId();
    echo "생성된 카테고리 ID: $categoryId\n";
    
} catch (PDOException $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>