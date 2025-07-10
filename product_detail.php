<?php
session_start();
$currentPage = 'products';
$pageTitle = '제품 상세정보';
include 'head.php';
include 'db.php';

// 관리자 로그인 여부 확인
$is_admin = isset($_SESSION['admin_id']) && $_SESSION['admin_id'];

// 제품 ID 확인
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

// 조회수 증가
$stmt = $pdo->prepare("UPDATE products SET view_count = view_count + 1 WHERE id = ?");
$stmt->execute([$product_id]);

// 제품 정보 가져오기
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name 
    FROM products p 
    JOIN product_categories pc ON p.category_code = pc.category_code 
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// 단중표 정보 가져오기
$unit_weight = null;
if ($product && $product['specifications']) {
    $stmt = $pdo->prepare("
        SELECT * FROM unit_weights 
        WHERE specification = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$product['specifications']]);
    $unit_weight = $stmt->fetch();
}

if (!$product) {
    echo "<script>alert('제품을 찾을 수 없습니다.'); history.back();</script>";
    exit;
}

// 같은 카테고리의 다른 제품 가져오기
$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE category_code = ? AND id != ? AND is_active = 1 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute([$product['category_code'], $product_id]);
$related_products = $stmt->fetchAll();

// 제품 이미지 가져오기
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order");
$stmt->execute([$product_id]);
$product_images = $stmt->fetchAll();
?>

<style>
/* 제품 상세 페이지 스타일 */
.product-detail {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.breadcrumb {
    font-size: 14px;
    color: #666;
    margin-bottom: 30px;
}

.breadcrumb a {
    color: #666;
    text-decoration: none;
}

.breadcrumb a:hover {
    color: var(--primary-blue);
}

.product-detail-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.08);
}

/* 이미지 섹션 */
.product-images {
    position: relative;
}

.main-image {
    width: 100%;
    height: 500px;
    background: #f5f5f5;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 20px;
}

.main-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.image-thumbnails {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}

.thumbnail {
    width: 100%;
    height: 80px;
    background: #f5f5f5;
    border-radius: 8px;
    cursor: pointer;
    overflow: hidden;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.thumbnail:hover,
.thumbnail.active {
    border-color: var(--primary-blue);
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* 제품 정보 섹션 */
.product-info-detail {
    padding-top: 20px;
}

.product-category {
    font-size: 14px;
    color: var(--primary-blue);
    margin-bottom: 10px;
}

.product-title {
    font-size: 32px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
}

.product-meta {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.meta-item {
    font-size: 14px;
    color: #666;
}

.meta-item strong {
    color: #333;
}


/* 기준단가 스타일 */
.product-price-section {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    border: 1px solid #e9ecef;
}

.price-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
    font-weight: 600;
    text-align: center;
}

.price-range-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 15px;
}

.price-item {
    flex: 1;
    text-align: center;
    padding: 15px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.price-item.main {
    border: 2px solid var(--primary-blue);
    background: #f0f7ff;
}

.price-type {
    display: block;
    font-size: 12px;
    color: #666;
    margin-bottom: 8px;
    font-weight: 600;
}

.price-item.main .price-type {
    color: var(--primary-blue);
}

.price-value {
    font-size: 20px;
    font-weight: 700;
    color: #333;
}

.price-item.main .price-value {
    font-size: 24px;
    color: var(--primary-blue);
}

.min-price {
    color: #28a745;
}

.max-price {
    color: #dc3545;
}

.price-unit-info {
    text-align: center;
    font-size: 13px;
    color: #666;
    padding-top: 10px;
    border-top: 1px solid #e9ecef;
}

.price-value.quote-required {
    color: #ff6b35;
    font-size: 24px;
}

.price-notice {
    display: block;
    font-size: 13px;
    color: #888;
    font-weight: 400;
    margin-top: 8px;
}

.product-specs {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.product-specs h3 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
}

.spec-list {
    display: grid;
    gap: 15px;
}

.spec-item {
    display: grid;
    grid-template-columns: 150px 1fr;
    font-size: 15px;
}

.spec-label {
    color: #666;
    font-weight: 500;
}

.spec-value {
    color: #333;
}

.product-features {
    margin-bottom: 40px;
}

.product-features h3 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
}

.feature-list {
    list-style: none;
    padding: 0;
}

.feature-list li {
    padding: 8px 0;
    padding-left: 25px;
    position: relative;
    font-size: 15px;
    color: #666;
}

.feature-list li:before {
    content: '✓';
    position: absolute;
    left: 0;
    color: var(--primary-blue);
    font-weight: bold;
}

.product-actions {
    display: flex;
    gap: 15px;
    margin-top: 40px;
}

.btn-quote {
    flex: 1;
    padding: 18px 30px;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-quote:hover {
    background: #0F1F7A;
    transform: translateY(-2px);
}

.btn-secondary {
    padding: 18px 30px;
    background: white;
    color: var(--primary-blue);
    border: 2px solid var(--primary-blue);
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
}

.btn-secondary:hover {
    background: var(--primary-blue);
    color: white;
}

/* 배송 정보 */
.delivery-info {
    background: #FFF9E6;
    padding: 20px;
    border-radius: 8px;
    margin-top: 30px;
}

.delivery-info h4 {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.delivery-info p {
    font-size: 14px;
    color: #666;
    margin: 5px 0;
}

/* 관련 제품 섹션 */
.related-products {
    max-width: 1200px;
    margin: 80px auto 40px;
    padding: 0 20px;
}

.related-products h2 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    margin-bottom: 30px;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
}

.related-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    text-decoration: none;
}

.related-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.related-image {
    width: 100%;
    height: 180px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: #999;
}

.related-info {
    padding: 20px;
}

.related-info h3 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.related-info p {
    font-size: 14px;
    color: #666;
}

/* 중량 계산기 스타일 */
.weight-calculator {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.weight-calculator h3 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
}

.calculator-form {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.calc-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.calc-group {
    display: flex;
    flex-direction: column;
}

.calc-group label {
    font-size: 14px;
    font-weight: 600;
    color: #666;
    margin-bottom: 8px;
}

.calc-group select,
.calc-group input {
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    background: white;
}

.calc-group input[type="number"] {
    -moz-appearance: textfield;
}

.calc-group input[type="number"]::-webkit-outer-spin-button,
.calc-group input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.calc-result {
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    font-size: 15px;
}

.result-item .label {
    color: #666;
}

.result-item .value {
    font-weight: 600;
    color: #333;
}

.result-item.total {
    padding-top: 15px;
    border-top: 1px solid #e0e0e0;
    font-size: 18px;
}

.result-item.total .value {
    color: var(--primary-blue);
    font-size: 20px;
}

.result-divider {
    height: 1px;
    background: #e0e0e0;
    margin: 15px 0;
}

.result-item.total-price {
    background: #fff3cd;
    padding: 10px;
    border-radius: 6px;
    margin-top: 10px;
}

.result-item.total-price .label {
    color: #856404;
}

.result-item.total-price .value {
    font-size: 22px;
    color: #856404;
    font-weight: 700;
}

.price-notice-small {
    font-size: 12px;
    color: #666;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f0f0f0;
}

/* 새로운 가격 범위 표시 스타일 */
.price-range-display {
    text-align: center;
    padding: 20px;
    background: white;
    border-radius: 8px;
    border: 2px solid var(--primary-blue);
}

.price-range-text {
    font-size: 28px;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 10px;
}

.price-range-info {
    font-size: 14px;
    color: #666;
    margin-top: 10px;
}

/* 관리자 수정 버튼 */
.admin-edit-btn {
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.admin-edit-btn:hover {
    background: #e55a2b !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* 반응형 */
@media (max-width: 768px) {
    .product-detail-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .main-image {
        height: 300px;
    }
    
    .product-price-section {
        padding: 20px;
    }
    
    .price-range-container {
        flex-direction: column;
        gap: 10px;
    }
    
    .price-item {
        padding: 10px;
    }
    
    .price-value {
        font-size: 18px;
    }
    
    .price-item.main .price-value {
        font-size: 20px;
    }
    
    .price-type {
        font-size: 11px;
    }
    
    .product-actions {
        flex-direction: column;
    }
    
    .related-grid {
        grid-template-columns: 1fr;
    }
    
    .calc-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .admin-edit-btn {
        padding: 6px 12px !important;
        font-size: 12px !important;
    }
    
    .product-title {
        font-size: 20px !important;
    }
    
    .price-range-text {
        font-size: 20px;
    }
    
    .price-range-info {
        font-size: 12px;
    }
}
</style>

<div class="product-detail">
    <!-- 브레드크럼 -->
    <div class="breadcrumb">
        <a href="index.php">홈</a> > 
        <a href="products_new.php">제품소개</a> > 
        <a href="products_new.php?category=<?php echo $product['category_code']; ?>"><?php echo escape($product['category_name']); ?></a> > 
        <?php echo escape($product['product_name']); ?>
    </div>

    <div class="product-detail-content">
        <!-- 이미지 섹션 -->
        <div class="product-images">
            <div class="main-image" id="mainImage">
                <?php if ($product['main_image']): ?>
                    <img src="<?php echo escape($product['main_image']); ?>" alt="<?php echo escape($product['product_name']); ?>">
                <?php else: ?>
                    <div style="font-size: 120px; color: #ddd;">📦</div>
                <?php endif; ?>
            </div>
            
            <?php if (count($product_images) > 0): ?>
            <div class="image-thumbnails">
                <?php if ($product['main_image']): ?>
                    <div class="thumbnail active" onclick="changeImage('<?php echo escape($product['main_image']); ?>')">
                        <img src="<?php echo escape($product['main_image']); ?>" alt="메인 이미지">
                    </div>
                <?php endif; ?>
                <?php foreach ($product_images as $image): ?>
                    <div class="thumbnail" onclick="changeImage('<?php echo escape($image['image_url']); ?>')">
                        <img src="<?php echo escape($image['image_url']); ?>" alt="제품 이미지">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- 제품 정보 섹션 -->
        <div class="product-info-detail">
            <div class="product-category"><?php echo escape($product['category_name']); ?></div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <h1 class="product-title" style="margin: 0;"><?php echo escape($product['product_name']); ?></h1>
                <?php if ($is_admin): ?>
                <a href="admin/admin_products_edit.php?id=<?php echo $product_id; ?>" 
                   class="admin-edit-btn" 
                   target="_blank"
                   style="padding: 8px 16px; background: #ff6b35; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600;">
                    관리자 수정
                </a>
                <?php endif; ?>
            </div>
            
            <div class="product-meta">
                <div class="meta-item">
                    <strong>제품코드:</strong> <?php echo escape($product['product_code'] ?: 'P' . str_pad($product['id'], 5, '0', STR_PAD_LEFT)); ?>
                </div>
                <div class="meta-item">
                    <strong>조회수:</strong> <?php echo number_format($product['view_count']); ?>
                </div>
                <div class="meta-item">
                    <strong>재고상태:</strong> 
                    <?php 
                    switch($product['stock_status']) {
                        case 'in_stock': echo '<span style="color: #28a745;">재고 있음</span>'; break;
                        case 'out_of_stock': echo '<span style="color: #dc3545;">재고 없음</span>'; break;
                        case 'on_order': echo '<span style="color: #ffc107;">주문 가능</span>'; break;
                    }
                    ?>
                </div>
            </div>

            <?php if ($product['price'] && $product['price'] > 0): ?>
            <div class="product-price-section">
                <div class="price-label">가격범위</div>
                <?php 
                // 기준길이 가져오기 (없으면 6m 기본값)
                $base_length = isset($product['base_length']) ? $product['base_length'] : 6;
                
                // 최저단가 계산: min_price가 DB에 있으면 사용, 없으면 기준단가의 95%
                $min_price_per_ton = isset($product['min_price']) && $product['min_price'] > 0 
                    ? $product['min_price'] 
                    : $product['price'] * 0.95;
                
                // 최대단가 계산: max_price가 DB에 있으면 사용, 없으면 기준단가의 105%
                $max_price_per_ton = isset($product['max_price']) && $product['max_price'] > 0 
                    ? $product['max_price'] 
                    : $product['price'] * 1.05;
                
                // 단위중량이 있을 때만 계산 표시
                if ($unit_weight && $unit_weight['unit_weight'] > 0): 
                    // 최저/최대 금액 계산
                    // 공식: 단가 × 기준길이(m) × 단위중량(kg/m) ÷ 1000 = 금액
                    $min_total_price = $min_price_per_ton * $base_length * $unit_weight['unit_weight'] / 1000;
                    $max_total_price = $max_price_per_ton * $base_length * $unit_weight['unit_weight'] / 1000;
                ?>
                <div class="price-range-display">
                    <div class="price-range-text">
                        <?php echo number_format($min_total_price); ?> ~ <?php echo number_format($max_total_price); ?> 원 / <?php echo escape($product['unit'] ?: 'TON'); ?>
                    </div>
                    <div class="price-range-info">
                        기준길이 <?php echo $base_length; ?>m 기준 (단가: <?php echo number_format($min_price_per_ton); ?> ~ <?php echo number_format($max_price_per_ton); ?> 원/TON)
                    </div>
                </div>
                <?php else: ?>
                <!-- 단위중량이 없는 경우 기존 방식 표시 -->
                <div class="price-range-container">
                    <div class="price-item">
                        <span class="price-type">최저단가</span>
                        <div class="price-value min-price">
                            <?php echo number_format($min_price_per_ton); ?> 원
                        </div>
                    </div>
                    <div class="price-item main">
                        <span class="price-type">기준단가</span>
                        <div class="price-value">
                            <?php echo number_format($product['price']); ?> 원
                        </div>
                    </div>
                    <div class="price-item">
                        <span class="price-type">최대단가</span>
                        <div class="price-value max-price">
                            <?php echo number_format($max_price_per_ton); ?> 원
                        </div>
                    </div>
                </div>
                <div class="price-unit-info">단위: <?php echo escape($product['unit'] ?: 'TON'); ?></div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="product-price-section">
                <div class="price-label">기준단가</div>
                <div class="price-value quote-required">
                    견적문의
                    <span class="price-notice">가격은 수량 및 납기에 따라 달라질 수 있습니다.</span>
                </div>
            </div>
            <?php endif; ?>


            <div class="product-specs">
                <h3>제품 사양</h3>
                <div class="spec-list">
                    <div class="spec-item">
                        <span class="spec-label">규격</span>
                        <span class="spec-value"><?php echo escape($product['specifications']); ?></span>
                    </div>
                    <?php if ($product['dimensions']): ?>
                    <div class="spec-item">
                        <span class="spec-label">치수</span>
                        <span class="spec-value"><?php echo escape($product['dimensions']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($product['weight']): ?>
                    <div class="spec-item">
                        <span class="spec-label">중량</span>
                        <span class="spec-value"><?php echo escape($product['weight']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($unit_weight && $unit_weight['unit_weight']): ?>
                    <div class="spec-item">
                        <span class="spec-label">단위중량</span>
                        <span class="spec-value"><?php echo number_format($unit_weight['unit_weight'], 1); ?> kg/m</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($product['material']): ?>
                    <div class="spec-item">
                        <span class="spec-label">재질</span>
                        <span class="spec-value"><?php echo escape($product['material']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($product['unit']): ?>
                    <div class="spec-item">
                        <span class="spec-label">판매단위</span>
                        <span class="spec-value"><?php echo escape($product['unit']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($product['min_order_qty'] > 1): ?>
                    <div class="spec-item">
                        <span class="spec-label">최소주문수량</span>
                        <span class="spec-value"><?php echo number_format($product['min_order_qty']); ?> <?php echo escape($product['unit']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($product['origin']) && $product['origin']): ?>
                    <div class="spec-item">
                        <span class="spec-label">원산지</span>
                        <span class="spec-value"><?php echo escape($product['origin']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($product['manufacturer']) && $product['manufacturer']): ?>
                    <div class="spec-item">
                        <span class="spec-label">제조사</span>
                        <span class="spec-value"><?php echo escape($product['manufacturer']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($product['features']): ?>
            <div class="product-features">
                <h3>제품 특징</h3>
                <ul class="feature-list">
                    <?php 
                    $features = explode("\n", $product['features']);
                    foreach ($features as $feature):
                        if (trim($feature)):
                    ?>
                    <li><?php echo escape(trim($feature)); ?></li>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($unit_weight): ?>
            <!-- 길이/수량 선택 및 중량 계산 -->
            <div class="weight-calculator">
                <h3>수량 및 중량 계산</h3>
                <div class="calculator-form">
                    <div class="calc-row">
                        <div class="calc-group">
                            <label>길이(m)</label>
                            <select id="length" onchange="calculateWeight()">
                                <?php 
                                $base_length = isset($product['base_length']) ? $product['base_length'] : 6;
                                for ($i = 6; $i <= 12; $i++): 
                                ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i == $base_length ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>m
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="calc-group">
                            <label>수량(본)</label>
                            <input type="number" id="quantity" value="1" min="1" onchange="calculateWeight()">
                        </div>
                    </div>
                    
                    <div class="calc-result">
                        <div class="result-item">
                            <span class="label">1본 중량:</span>
                            <span class="value" id="pieceWeight">-</span>
                        </div>
                        <div class="result-item total">
                            <span class="label">총 중량:</span>
                            <span class="value" id="totalWeight">-</span>
                        </div>
                        <?php if ($product['price'] && $product['price'] > 0): ?>
                        <div class="result-divider"></div>
                        <div class="result-item">
                            <span class="label">기준단가:</span>
                            <span class="value"><?php echo number_format($product['price']); ?> 원/<?php echo escape($product['unit'] ?: 'TON'); ?></span>
                        </div>
                        <div class="result-item">
                            <span class="label">1본 금액:</span>
                            <span class="value" id="piecePrice">-</span>
                        </div>
                        <div class="result-item total-price">
                            <span class="label">예상 금액:</span>
                            <span class="value" id="totalPrice">-</span>
                        </div>
                        <div class="price-notice-small">
                            * 계산식: 단위중량(kg/m) × 길이(m) × 수량(본) × 기준단가(원/TON)
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="delivery-info">
                <h4>배송 정보</h4>
                <?php if ($product['delivery_info']): ?>
                    <?php echo nl2br(escape($product['delivery_info'])); ?>
                <?php else: ?>
                    <p>• 배송비: 착불 (지역별 상이)</p>
                    <p>• 배송기간: 주문 후 2~3일 (토요일, 공휴일 제외)</p>
                    <p>• 대량 주문 시 별도 협의</p>
                <?php endif; ?>
            </div>

            <div class="product-actions">
                <a href="quote_write.php?product=<?php echo urlencode($product['product_name']); ?>&product_id=<?php echo $product['id']; ?>" 
                   class="btn-quote" id="quoteLink">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"/>
                        <path d="M5 7h10M5 10h10M5 13h6"/>
                    </svg>
                    견적 문의하기
                </a>
                <a href="tel:041-532-6982" class="btn-secondary">
                    전화 문의
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 관련 제품 -->
<?php if (count($related_products) > 0): ?>
<div class="related-products">
    <h2>관련 제품</h2>
    <div class="related-grid">
        <?php foreach ($related_products as $related): ?>
        <a href="product_detail.php?id=<?php echo $related['id']; ?>" class="related-card">
            <div class="related-image">
                <?php if ($related['main_image']): ?>
                    <img src="<?php echo escape($related['main_image']); ?>" alt="<?php echo escape($related['product_name']); ?>">
                <?php else: ?>
                    📦
                <?php endif; ?>
            </div>
            <div class="related-info">
                <h3><?php echo escape($related['product_name']); ?></h3>
                <p><?php echo escape($related['specifications']); ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
function changeImage(imageUrl) {
    document.getElementById('mainImage').innerHTML = '<img src="' + imageUrl + '" alt="제품 이미지">';
    
    // 썸네일 활성화 상태 변경
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
}

<?php if ($unit_weight): ?>
// 중량 계산 함수
function calculateWeight() {
    const unitWeight = <?php echo $unit_weight['unit_weight']; ?>;
    const length = parseFloat(document.getElementById('length').value);
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    <?php if ($product['price'] && $product['price'] > 0): ?>
    const pricePerTon = <?php echo $product['price']; ?>;
    <?php endif; ?>
    
    // 1본 중량 계산 (소수점 첫째자리 반올림)
    const pieceWeight = Math.round(unitWeight * length);
    
    // 총 중량 계산
    const totalWeight = pieceWeight * quantity;
    
    // 결과 표시
    document.getElementById('pieceWeight').textContent = pieceWeight.toLocaleString() + ' kg';
    document.getElementById('totalWeight').textContent = totalWeight.toLocaleString() + ' kg';
    
    <?php if ($product['price'] && $product['price'] > 0): ?>
    // 금액 계산 (kg를 톤으로 변환: /1000)
    const piecePrice = Math.round((pieceWeight / 1000) * pricePerTon);
    const totalPrice = Math.round((totalWeight / 1000) * pricePerTon);
    
    // 금액 표시
    document.getElementById('piecePrice').textContent = piecePrice.toLocaleString() + ' 원';
    document.getElementById('totalPrice').textContent = totalPrice.toLocaleString() + ' 원';
    <?php endif; ?>
    
    // 견적문의 링크에 계산된 값 추가
    const quoteLink = document.getElementById('quoteLink');
    const baseUrl = 'quote_write.php?product=<?php echo urlencode($product['product_name']); ?>&product_id=<?php echo $product['id']; ?>';
    quoteLink.href = baseUrl + '&weight=' + totalWeight + '&length=' + length + '&quantity=' + quantity;
}

// 페이지 로드시 초기 계산
window.onload = function() {
    calculateWeight();
};
<?php endif; ?>
</script>

<?php include 'tail.php'; ?>