<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$pageTitle = '철근 제품 설정';

// 추가 스타일 정의
$additionalStyles = '
.setup-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.setup-result {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    font-family: monospace;
    white-space: pre-wrap;
    line-height: 1.6;
}

.success-msg {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
}

.error-msg {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
}

.action-buttons {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.btn-primary {
    background: #1A237E;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary:hover {
    background: #283593;
}

.btn-secondary {
    background: #666;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-secondary:hover {
    background: #555;
}
';

require_once 'admin_head.php';

$result = '';
$success = false;

// 설정 실행
if (isset($_POST['execute'])) {
    try {
        ob_start();
        
        // 1. 철근 카테고리가 이미 있는지 확인
        $stmt = $pdo->prepare("SELECT id FROM product_categories WHERE category_code = 'rebar'");
        $stmt->execute();
        $existingCategory = $stmt->fetch();
        
        if ($existingCategory) {
            $categoryId = $existingCategory['id'];
            echo "철근 카테고리가 이미 존재합니다. (ID: $categoryId)\n";
        } else {
            // 철근 카테고리 추가
            $stmt = $pdo->prepare("
                INSERT INTO product_categories (category_code, category_name, display_order, is_active) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                'rebar',
                '철근',
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
            $stmt = $pdo->prepare("SELECT id FROM products WHERE category_code = 'rebar' AND product_name = ?");
            $stmt->execute([$productName]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // 기존 제품 업데이트
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET specifications = ?, unit_weight = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->execute([$specifications, $unitWeight, $description, $existing['id']]);
                echo "- $productName 업데이트됨\n";
                $updateCount++;
            } else {
                // 신규 제품 추가 (korean_name 컬럼이 없으므로 제거)
                $stmt = $pdo->prepare("
                    INSERT INTO products (category_code, product_name, specifications, unit_weight, unit, description, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute(['rebar', $productName, $specifications, $unitWeight, 'TON', $description, 1]);
                echo "- $productName 추가됨\n";
                $insertCount++;
            }
        }
        
        echo "\n=============================\n";
        echo "철근 제품 설정 완료!\n";
        echo "- 신규 추가: {$insertCount}개\n";
        echo "- 업데이트: {$updateCount}개\n";
        echo "=============================\n";
        
        $result = ob_get_clean();
        $success = true;
        
    } catch (PDOException $e) {
        $result = ob_get_clean();
        $result .= "\n\n오류 발생: " . $e->getMessage();
        $success = false;
    }
}
?>

<div class="page-header">
    <h1>철근 제품 설정</h1>
    <p>철근 카테고리와 제품을 자동으로 설정합니다.</p>
</div>

<div class="setup-section">
    <?php if (!$result): ?>
        <h2>철근 제품 설정 안내</h2>
        <p>이 기능은 다음 작업을 수행합니다:</p>
        <ul style="margin: 20px 0; padding-left: 30px;">
            <li>철근 카테고리 생성 (이미 있는 경우 건너뜀)</li>
            <li>unit_weight 컬럼 추가 (이미 있는 경우 건너뜀)</li>
            <li>D10 ~ D41까지 11개 철근 제품 등록</li>
            <li>각 제품의 직경 및 단위중량 정보 설정</li>
        </ul>
        
        <form method="POST" style="margin-top: 30px;">
            <button type="submit" name="execute" value="1" class="btn-primary">철근 제품 설정 실행</button>
        </form>
    <?php else: ?>
        <?php if ($success): ?>
            <div class="success-msg">
                <strong>✓ 설정이 완료되었습니다!</strong>
            </div>
        <?php else: ?>
            <div class="error-msg">
                <strong>✗ 오류가 발생했습니다.</strong>
            </div>
        <?php endif; ?>
        
        <div class="setup-result"><?php echo htmlspecialchars($result); ?></div>
        
        <div class="action-buttons">
            <a href="../products.php" target="_blank" class="btn-primary">사용자 제품 페이지 확인</a>
            <a href="admin_products_integrated.php" class="btn-secondary">관리자 제품관리</a>
            <a href="setup_rebar.php" class="btn-secondary">다시 실행</a>
        </div>
    <?php endif; ?>
</div>

<div class="setup-section">
    <h2>철근 제품 정보</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f5f5f5;">
                <th style="padding: 10px; border: 1px solid #ddd;">제품명</th>
                <th style="padding: 10px; border: 1px solid #ddd;">직경(mm)</th>
                <th style="padding: 10px; border: 1px solid #ddd;">단위중량(kg/m)</th>
                <th style="padding: 10px; border: 1px solid #ddd;">설명</th>
            </tr>
        </thead>
        <tbody>
            <tr><td style="padding: 8px; border: 1px solid #ddd;">D10</td><td style="padding: 8px; border: 1px solid #ddd;">9.53</td><td style="padding: 8px; border: 1px solid #ddd;">0.56</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D10 (SD400)</td></tr>
            <tr style="background: #f9f9f9;"><td style="padding: 8px; border: 1px solid #ddd;">D13</td><td style="padding: 8px; border: 1px solid #ddd;">12.7</td><td style="padding: 8px; border: 1px solid #ddd;">0.995</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D13 (SD400)</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd;">D16</td><td style="padding: 8px; border: 1px solid #ddd;">15.9</td><td style="padding: 8px; border: 1px solid #ddd;">1.56</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D16 (SD400)</td></tr>
            <tr style="background: #f9f9f9;"><td style="padding: 8px; border: 1px solid #ddd;">D19</td><td style="padding: 8px; border: 1px solid #ddd;">19.1</td><td style="padding: 8px; border: 1px solid #ddd;">2.25</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D19 (SD400)</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd;">D22</td><td style="padding: 8px; border: 1px solid #ddd;">22.2</td><td style="padding: 8px; border: 1px solid #ddd;">3.04</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D22 (SD400)</td></tr>
            <tr style="background: #f9f9f9;"><td style="padding: 8px; border: 1px solid #ddd;">D25</td><td style="padding: 8px; border: 1px solid #ddd;">25.4</td><td style="padding: 8px; border: 1px solid #ddd;">3.98</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D25 (SD400)</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd;">D29</td><td style="padding: 8px; border: 1px solid #ddd;">28.6</td><td style="padding: 8px; border: 1px solid #ddd;">5.04</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D29 (SD400)</td></tr>
            <tr style="background: #f9f9f9;"><td style="padding: 8px; border: 1px solid #ddd;">D32</td><td style="padding: 8px; border: 1px solid #ddd;">31.8</td><td style="padding: 8px; border: 1px solid #ddd;">6.23</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D32 (SD400)</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd;">D35</td><td style="padding: 8px; border: 1px solid #ddd;">34.9</td><td style="padding: 8px; border: 1px solid #ddd;">7.51</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D35 (SD400)</td></tr>
            <tr style="background: #f9f9f9;"><td style="padding: 8px; border: 1px solid #ddd;">D38</td><td style="padding: 8px; border: 1px solid #ddd;">38.1</td><td style="padding: 8px; border: 1px solid #ddd;">8.95</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D38 (SD400)</td></tr>
            <tr><td style="padding: 8px; border: 1px solid #ddd;">D41</td><td style="padding: 8px; border: 1px solid #ddd;">41.3</td><td style="padding: 8px; border: 1px solid #ddd;">10.5</td><td style="padding: 8px; border: 1px solid #ddd;">건축용 이형철근 D41 (SD400)</td></tr>
        </tbody>
    </table>
</div>

<?php require_once 'admin_tail.php'; ?>