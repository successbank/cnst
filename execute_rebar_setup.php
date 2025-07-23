<?php
require_once 'db.php';

try {
    echo "<h2>철근 카테고리 및 제품 설정</h2>";
    echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 8px;'>";
    
    // 1. 철근 카테고리가 이미 있는지 확인
    $stmt = $pdo->prepare("SELECT id FROM product_categories WHERE name = 'rebar'");
    $stmt->execute();
    $existingCategory = $stmt->fetch();
    
    if ($existingCategory) {
        $categoryId = $existingCategory['id'];
        echo "철근 카테고리가 이미 존재합니다. (ID: $categoryId)\n";
    } else {
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
        
        $categoryId = $pdo->lastInsertId();
        echo "철근 카테고리가 생성되었습니다. (ID: $categoryId)\n";
    }
    
    // 2. unit_weight 컬럼 추가 (없는 경우에만)
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN unit_weight DECIMAL(10,3) DEFAULT NULL COMMENT '단위중량(kg/m)' AFTER specifications");
        echo "unit_weight 컬럼이 추가되었습니다.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "unit_weight 컬럼이 이미 존재합니다.\n";
        } else {
            throw $e;
        }
    }
    
    // 3. 철근 제품 데이터
    $rebarProducts = [
        ['D10', '이형철근 D10', '직경: 9.53mm, 단위중량: 0.56kg/m', 0.56, '건축용 이형철근 D10 (SD400)'],
        ['D13', '이형철근 D13', '직경: 12.7mm, 단위중량: 0.995kg/m', 0.995, '건축용 이형철근 D13 (SD400)'],
        ['D16', '이형철근 D16', '직경: 15.9mm, 단위중량: 1.56kg/m', 1.56, '건축용 이형철근 D16 (SD400)'],
        ['D19', '이형철근 D19', '직경: 19.1mm, 단위중량: 2.25kg/m', 2.25, '건축용 이형철근 D19 (SD400)'],
        ['D22', '이형철근 D22', '직경: 22.2mm, 단위중량: 3.04kg/m', 3.04, '건축용 이형철근 D22 (SD400)'],
        ['D25', '이형철근 D25', '직경: 25.4mm, 단위중량: 3.98kg/m', 3.98, '건축용 이형철근 D25 (SD400)'],
        ['D29', '이형철근 D29', '직경: 28.6mm, 단위중량: 5.04kg/m', 5.04, '건축용 이형철근 D29 (SD400)'],
        ['D32', '이형철근 D32', '직경: 31.8mm, 단위중량: 6.23kg/m', 6.23, '건축용 이형철근 D32 (SD400)'],
        ['D35', '이형철근 D35', '직경: 34.9mm, 단위중량: 7.51kg/m', 7.51, '건축용 이형철근 D35 (SD400)'],
        ['D38', '이형철근 D38', '직경: 38.1mm, 단위중량: 8.95kg/m', 8.95, '건축용 이형철근 D38 (SD400)'],
        ['D41', '이형철근 D41', '직경: 41.3mm, 단위중량: 10.5kg/m', 10.5, '건축용 이형철근 D41 (SD400)']
    ];
    
    // 4. 철근 제품 추가
    echo "\n철근 제품 추가 중...\n";
    $insertCount = 0;
    $updateCount = 0;
    
    foreach ($rebarProducts as $product) {
        list($productName, $koreanName, $specifications, $unitWeight, $description) = $product;
        
        // 제품이 이미 있는지 확인
        $stmt = $pdo->prepare("SELECT id FROM products WHERE category_id = ? AND product_name = ?");
        $stmt->execute([$categoryId, $productName]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // 기존 제품 업데이트
            $stmt = $pdo->prepare("
                UPDATE products 
                SET korean_name = ?, specifications = ?, unit_weight = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([$koreanName, $specifications, $unitWeight, $description, $existing['id']]);
            echo "- $productName 업데이트됨\n";
            $updateCount++;
        } else {
            // 신규 제품 추가
            $stmt = $pdo->prepare("
                INSERT INTO products (category_id, product_name, korean_name, specifications, unit_weight, unit, description, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$categoryId, $productName, $koreanName, $specifications, $unitWeight, 'TON', $description, 1]);
            echo "- $productName 추가됨\n";
            $insertCount++;
        }
    }
    
    echo "\n=============================\n";
    echo "철근 제품 설정 완료!\n";
    echo "- 신규 추가: {$insertCount}개\n";
    echo "- 업데이트: {$updateCount}개\n";
    echo "=============================\n";
    echo "</pre>";
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='products.php' style='padding: 10px 20px; background: #1A237E; color: white; text-decoration: none; border-radius: 6px; display: inline-block;'>제품 페이지로 이동</a>";
    echo " ";
    echo "<a href='admin/admin_products_integrated.php' style='padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 6px; display: inline-block;'>관리자 제품관리로 이동</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "</pre>";
    echo "<div style='background: #fee; padding: 15px; border-radius: 8px; color: #c00;'>";
    echo "오류 발생: " . $e->getMessage() . "<br>";
    echo "SQL State: " . $e->getCode();
    echo "</div>";
}
?>