<?php
require_once 'db.php';
require_once 'includes/rebar_unit_weights.php';
include 'head.php';
?>

<style>
.rebar-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.rebar-header {
    text-align: center;
    margin-bottom: 40px;
}

.rebar-header h1 {
    font-size: 2.5em;
    color: #333;
    margin-bottom: 10px;
}

.section-title {
    font-size: 1.8em;
    color: #0066cc;
    margin: 30px 0 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #0066cc;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.product-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.product-name {
    font-size: 1.5em;
    color: #333;
    margin-bottom: 15px;
    font-weight: bold;
}

.product-spec {
    background: #f0f0f0;
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    margin-bottom: 15px;
    font-weight: bold;
    color: #0066cc;
}

.product-info {
    margin: 10px 0;
    color: #666;
}

.product-price {
    font-size: 1.2em;
    color: #d32f2f;
    font-weight: bold;
    margin-top: 15px;
}

.stock-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.9em;
    margin-top: 10px;
}

.stock-status.on-order {
    background: #4caf50;
    color: white;
}

.specs-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.specs-table th,
.specs-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.specs-table th {
    background: #0066cc;
    color: white;
    font-weight: bold;
}

.specs-table tr:hover {
    background: #f5f5f5;
}

.calculator-badge {
    background: #ff6b6b;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.85em;
    display: inline-block;
    margin-left: 10px;
}

.summary-box {
    background: #e3f2fd;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    text-align: center;
}

.summary-box h2 {
    color: #1565c0;
    margin-bottom: 10px;
}
</style>

<div class="rebar-container">
    <div class="rebar-header">
        <h1>철근 제품 목록</h1>
        <p>충남스틸에서 공급하는 고품질 철근 제품을 확인하세요</p>
    </div>

    <?php
    try {
        // 철근 카테고리 정보 조회
        $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE category_code = 'rebar'");
        $stmt->execute();
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category && $category['is_active']) {
            ?>
            <div class="summary-box">
                <h2>철근 카테고리 정보</h2>
                <p>카테고리명: <strong><?php echo htmlspecialchars($category['category_name']); ?></strong></p>
                <p>카테고리 코드: <strong><?php echo htmlspecialchars($category['category_code']); ?></strong></p>
            </div>
            <?php
        }
        
        // 철근 제품 조회
        $stmt = $pdo->prepare("
            SELECT p.*
            FROM products p 
            WHERE p.category_code = 'rebar' AND p.is_active = 1
            ORDER BY 
                CAST(SUBSTRING(p.specifications, 2) AS UNSIGNED) ASC
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($products) > 0) {
            ?>
            <h2 class="section-title">등록된 철근 제품 (총 <?php echo count($products); ?>개)</h2>
            
            <div class="products-grid">
                <?php foreach ($products as $product) { 
                    $unit_weight = isset($rebar_unit_weights[$product['specifications']]) 
                        ? $rebar_unit_weights[$product['specifications']] 
                        : null;
                ?>
                    <div class="product-card">
                        <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <span class="product-spec"><?php echo htmlspecialchars($product['specifications']); ?></span>
                        
                        <?php if ($unit_weight): ?>
                            <div class="product-info">
                                <strong>단위중량:</strong> <?php echo $unit_weight; ?> kg/m
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <strong>단위:</strong> <?php echo htmlspecialchars($product['unit']); ?>
                        </div>
                        
                        <div class="product-price">
                            <?php echo number_format($product['price']); ?>원/<?php echo htmlspecialchars($product['unit']); ?>
                        </div>
                        
                        <?php if ($product['stock_status'] == 'on_order'): ?>
                            <span class="stock-status on-order">주문 가능</span>
                        <?php endif; ?>
                        
                        <?php if ($product['has_calculator']): ?>
                            <span class="calculator-badge">중량계산기 제공</span>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div>
            
            <h2 class="section-title">철근 규격별 단위중량 정보</h2>
            
            <table class="specs-table">
                <thead>
                    <tr>
                        <th>규격</th>
                        <th>직경 (mm)</th>
                        <th>단위중량 (kg/m)</th>
                        <th>1톤당 수량 (m)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 직경 정보 (KS D 3504 기준)
                    $diameters = [
                        'D10' => 9.53,
                        'D13' => 12.7,
                        'D16' => 15.9,
                        'D19' => 19.1,
                        'D22' => 22.2,
                        'D25' => 25.4,
                        'D29' => 28.6,
                        'D32' => 31.8,
                        'D35' => 34.9,
                        'D38' => 38.1,
                        'D41' => 41.3,
                        'D51' => 50.8
                    ];
                    
                    foreach ($rebar_unit_weights as $spec => $weight) {
                        $diameter = isset($diameters[$spec]) ? $diameters[$spec] : '-';
                        $per_ton = $weight > 0 ? number_format(1000 / $weight, 1) : '-';
                    ?>
                        <tr>
                            <td><strong><?php echo $spec; ?></strong></td>
                            <td><?php echo $diameter; ?></td>
                            <td><?php echo $weight; ?></td>
                            <td><?php echo $per_ton; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            
        <?php } else { ?>
            <p>등록된 철근 제품이 없습니다.</p>
        <?php }
        
    } catch (PDOException $e) {
        echo '<p style="color: red;">데이터베이스 오류가 발생했습니다.</p>';
        error_log("Database error: " . $e->getMessage());
    }
    ?>
</div>

<?php include 'tail.php'; ?>