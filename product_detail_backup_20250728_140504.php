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

// 철근 카테고리 확인 (문자열과 숫자 모두 체크)
$is_rebar = ($product['category_code'] === 'rebar' || 
             $product['category_code'] === '114' || 
             $product['category_code'] == 114 ||
             strpos(strtolower($product['category_name']), '철근') !== false);

// 철근 제품인 경우 추가 데이터 가져오기
$rebar_spec = null;
$rebar_materials = [];
$rebar_lengths = [];

if ($is_rebar) {
    // 규격명 추출 (예: D10, D13 등)
    // 제품명에서 추출 시도
    $spec_name = '';
    if (preg_match('/(D\d+)/', $product['product_name'], $matches)) {
        $spec_name = $matches[1];
    }
    // 규격에서도 추출 시도
    if (!$spec_name && preg_match('/(D\d+)/', $product['specifications'], $matches)) {
        $spec_name = $matches[1];
    }
    
    // 철근 규격 정보 가져오기
    if ($spec_name) {
        $stmt = $pdo->prepare("
            SELECT rs.*, 
                   COALESCE(rp.unit_price, 0) as unit_price 
            FROM rebar_specifications rs
            LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id 
                AND rp.is_active = TRUE
                AND rp.effective_date <= CURDATE()
                AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE())
            WHERE rs.spec_name = ? AND rs.is_active = TRUE
        ");
        $stmt->execute([$spec_name]);
        $rebar_spec = $stmt->fetch();
        
        // 길이 정보 가져오기
        if ($rebar_spec) {
            $stmt = $pdo->prepare("
                SELECT rl.*, rs.unit_weight 
                FROM rebar_length_info rl
                JOIN rebar_specifications rs ON rl.spec_id = rs.id
                WHERE rl.spec_id = ?
                ORDER BY rl.length
            ");
            $stmt->execute([$rebar_spec['id']]);
            $rebar_lengths = $stmt->fetchAll();
        }
    }
    
    // 재질 목록 가져오기
    $stmt = $pdo->query("
        SELECT * FROM rebar_materials 
        WHERE is_active = TRUE 
        ORDER BY display_order
    ");
    $rebar_materials = $stmt->fetchAll();
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

// 철근 카테고리일 경우 추가 처리
$is_rebar_category = false;
$rebar_specifications = null;
$rebar_lengths = null;
$rebar_materials = null;
$rebar_prices_by_material = [];

if ($product && ($product['category_code'] === '114' || $product['category_code'] === 'rebar')) {
    $is_rebar_category = true;
    
    // 철근 규격 정보 가져오기
    $spec_name = preg_replace('/[^D0-9]/', '', $product['product_name']);
    $stmt = $pdo->prepare("
        SELECT * FROM rebar_specifications 
        WHERE spec_name = ? AND is_active = TRUE
    ");
    $stmt->execute([$spec_name]);
    $rebar_specifications = $stmt->fetch();
    
    // 철근 길이 정보 가져오기
    if ($rebar_specifications) {
        $stmt = $pdo->prepare("
            SELECT * FROM rebar_length_info 
            WHERE spec_id = ? 
            ORDER BY length
        ");
        $stmt->execute([$rebar_specifications['id']]);
        $rebar_lengths = $stmt->fetchAll();
        
        // 재질 목록 가져오기
        $stmt = $pdo->query("
            SELECT * FROM rebar_materials 
            WHERE is_active = TRUE 
            ORDER BY display_order
        ");
        $rebar_materials = $stmt->fetchAll();
        
        // 재질별 가격 정보 가져오기
        $stmt = $pdo->prepare("
            SELECT 
                rm.id AS material_id,
                rm.material_code,
                rm.material_name,
                rm.additional_price,
                COALESCE(rp.unit_price, 0) AS base_price,
                (COALESCE(rp.unit_price, 0) + COALESCE(rm.additional_price, 0)) AS total_price
            FROM rebar_materials rm
            LEFT JOIN rebar_prices rp ON rp.spec_id = ? 
                AND rp.is_active = TRUE 
                AND rp.effective_date <= CURDATE()
                AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE())
            WHERE rm.is_active = TRUE
            ORDER BY rm.display_order
        ");
        $stmt->execute([$rebar_specifications['id']]);
        while ($row = $stmt->fetch()) {
            $rebar_prices_by_material[$row['material_id']] = $row;
        }
    }
}
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

<<<<<<< HEAD
/* 철근 계산기 스타일 */
.rebar-calculator .material-btn {
    position: relative;
    overflow: hidden;
}

.rebar-calculator .material-btn:hover {
    background: #f0f7ff !important;
    border-color: #1428A0 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.rebar-calculator .material-btn.active {
    background: #1428A0 !important;
    border-color: #1428A0 !important;
    color: white !important;
}

.rebar-calculator .material-btn.active small {
    color: #e3f2fd !important;
}

.rebar-calculator select:focus,
.rebar-calculator input:focus {
    border-color: #1428A0 !important;
    box-shadow: 0 0 0 3px rgba(20, 40, 160, 0.1);
}

.rebar-calculator .form-control {
    font-size: 16px;
}

.rebar-calculator .result-item {
    transition: background 0.3s ease;
}

.rebar-calculator .result-item:hover {
    background: #f8f9fa;
}

/* 계산 버튼 스타일 */
.calc-btn.primary:hover {
    background: #0F1F7A !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
}

.calc-btn.secondary:hover {
    background: #5a6268 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

=======
/* 재질 선택 버튼 스타일 */
.material-btn:hover {
    background: #e9ecef !important;
    border-color: #3498db !important;
}

.material-btn.active {
    background: #3498db !important;
    color: white !important;
    border-color: #3498db !important;
}

.material-btn.active small {
    color: white !important;
}

>>>>>>> 4779a5cf4f27bbf1862cfc06a4f3b51bbbb26bb7
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

            <?php if ($is_rebar_category && $rebar_specifications && !empty($rebar_prices_by_material)): ?>
            <!-- 철근 재질별 가격 표시 -->
            <div class="product-price-section">
                <div class="price-label">재질별 가격</div>
                <div class="material-price-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <?php foreach ($rebar_materials as $material): ?>
                    <?php 
                    ?>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e9ecef;">
                        <div style="font-weight: 600; color: #333; margin-bottom: 5px;"><?php echo htmlspecialchars($material['material_name']); ?></div>
                        <div style="font-size: 20px; font-weight: 700; color: #1428A0;">
                            <?php echo number_format($material['additional_price']); ?>원
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 3px;">원/kg</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="price-info">
                    <span>※ kg당 가격 (부가세 별도)</span>
                    <span>※ 수량에 따라 변동 가능</span>
                </div>
            </div>
            <?php elseif ($product['price'] && $product['price'] > 0): ?>
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

<<<<<<< HEAD
            <!-- 디버깅 정보 (임시) -->
            <?php if ($is_rebar): ?>
            <div style="background: #ffebee; padding: 10px; margin-bottom: 20px; border-radius: 8px; font-size: 12px;">
                <strong>디버깅 정보:</strong><br>
                카테고리 코드: <?php echo $product['category_code']; ?><br>
                카테고리명: <?php echo $product['category_name']; ?><br>
                철근 여부: <?php echo $is_rebar ? '예' : '아니오'; ?><br>
                규격명: <?php echo $spec_name ?: '없음'; ?><br>
                철근 스펙 존재: <?php echo $rebar_spec ? '예' : '아니오'; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($is_rebar && $rebar_spec): ?>
            <!-- 철근 계산기 (rebar_quote.php 방식) -->
            <div class="rebar-calculator" style="background: #f8f9fa; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
                <h3 style="margin-bottom: 25px;">철근 견적 계산</h3>
                
                <!-- 재질 선택 -->
                <div class="material-selection" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">재질 선택</label>
                    <div class="material-buttons" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                        <?php foreach ($rebar_materials as $material): ?>
                        <button type="button" 
                                class="material-btn" 
                                data-material-id="<?php echo $material['id']; ?>"
                                data-material-price="<?php echo $material['additional_price']; ?>"
                                style="padding: 15px 10px; background: white; border: 2px solid #e9ecef; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                            <strong><?php echo escape($material['material_name']); ?></strong>
                            <small style="display: block; margin-top: 5px; color: #666;">+<?php echo number_format($material['additional_price']); ?>원/kg</small>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- 길이 선택 -->
                <div class="length-selection" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">길이 선택</label>
                    <select id="lengthSelect" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                        <option value="">길이를 선택하세요</option>
                        <?php foreach ($rebar_lengths as $length): ?>
                        <option value="<?php echo $length['length']; ?>" 
                                data-pieces-per-ton="<?php echo $length['pieces_per_ton']; ?>"
                                data-weight-per-piece="<?php echo $length['weight_per_piece']; ?>"
                                data-total-weight="<?php echo $length['total_weight'] ?? ''; ?>">
                            <?php echo $length['length']; ?>m (톤당 <?php echo number_format($length['pieces_per_ton'], 0); ?>본)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- 수량 입력 -->
                <div class="quantity-selection" style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">수량 (톤)</label>
                    <input type="number" id="tonQuantity" class="form-control" min="1" value="1" step="0.1"
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
                    <small style="display: block; margin-top: 5px; color: #666;">* 입력한 톤 수 × 톤당 본수로 계산됩니다</small>
                </div>
                
                <!-- 계산 버튼 -->
                <div style="margin-bottom: 20px; display: flex; gap: 10px;">
                    <button type="button" onclick="calculateRebarPrice()" class="calc-btn primary"
                            style="flex: 1; padding: 12px; background: #1428A0; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        계산하기
                    </button>
                    <button type="button" onclick="resetCalculator()" class="calc-btn secondary"
                            style="padding: 12px 24px; background: #6c757d; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
                        초기화
                    </button>
                </div>
                
                <!-- 계산 결과 -->
                <div class="calc-result" style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                    <h4 style="margin-bottom: 15px;">계산 결과</h4>
                    <div class="result-grid" style="display: grid; gap: 10px;">
                        <div class="result-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <span>규격</span>
                            <span id="resultSpec"><?php echo escape($rebar_spec['spec_name']); ?></span>
                        </div>
                        <div class="result-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <span>재질</span>
                            <span id="resultMaterial">-</span>
                        </div>
                        <div class="result-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <span>길이</span>
                            <span id="resultLength">-</span>
                        </div>
                        <div class="result-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <span>톤수</span>
                            <span id="resultTon">-</span>
                        </div>
                        <div class="result-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <span>총 본수</span>
                            <span id="resultQuantity">-</span>
                        </div>
                        <div class="result-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <span>총 중량</span>
                            <span id="resultTotalWeight">-</span>
                        </div>
                        <div class="result-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <span>기준단가</span>
                            <span id="resultBasePrice"><?php echo number_format($rebar_spec['unit_price'] ?: 0); ?>원/kg</span>
                        </div>
                        <div class="result-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <span>재질 추가단가</span>
                            <span id="resultMaterialPrice">-</span>
                        </div>
                        <div class="result-item" style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                            <span>적용 단가</span>
                            <span id="resultFinalPrice">-</span>
                        </div>
                        <div class="result-item total" style="display: flex; justify-content: space-between; padding: 12px 0; border-top: 2px solid #333; margin-top: 10px; font-size: 18px; font-weight: bold;">
                            <span>총 금액</span>
                            <span id="resultTotalPrice" style="color: #1428A0;">-</span>
                        </div>
                    </div>
                    
                    <div style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin-top: 20px; font-size: 14px; color: #1976d2;">
                        <strong>계산식:</strong><br>
                        본당 중량 = 단위중량 × 길이<br>
                        총 중량 = 본당 중량 × 실제 본수<br>
                        적용 단가 = 기준단가 + 재질 추가단가<br>
                        총 금액 = 총 중량 × 적용 단가
                    </div>
                </div>
            </div>
            
            <?php elseif ($unit_weight): ?>
            <!-- 길이/수량 선택 및 중량 계산 (일반 제품) -->
=======
            <?php 
            // 철근 카테고리인지 확인
            $is_rebar = ($product['category_code'] === '114' || $product['category_code'] === 'rebar');
            
            // 철근인 경우 단중 가져오기
            if ($is_rebar) {
                require_once 'includes/rebar_unit_weights.php';
                $rebar_unit_weight = getRebarUnitWeightFromProductName($product['product_name']);
            }
            ?>
            
            <?php 
            // 철근인 경우 규격 ID 가져오기
            $rebar_spec_id = null;
            if ($is_rebar) {
                $spec = extractRebarSpec($product['product_name']);
                if ($spec) {
                    $stmt = $pdo->prepare("SELECT id FROM rebar_specifications WHERE spec_name = ? AND is_active = 1");
                    $stmt->execute([$spec]);
                    $rebar_spec = $stmt->fetch();
                    if ($rebar_spec) {
                        $rebar_spec_id = $rebar_spec['id'];
                    }
                }
                
                // 재질 목록 가져오기
                $stmt = $pdo->query("SELECT * FROM rebar_materials WHERE is_active = 1 ORDER BY display_order");
                $rebar_materials = $stmt->fetchAll();
            }
            ?>
            
            <?php if ($is_rebar_category): ?>
            <!-- 철근 재질 선택 및 견적 계산 -->
            <div class="weight-calculator">
                <h3>견적 계산기</h3>
                <?php 
                // 디버깅 정보 (개발 중에만 표시)
                if (false) { // true로 변경하면 디버깅 정보 표시
                    echo "<pre>";
                    echo "is_rebar_category: " . ($is_rebar_category ? 'true' : 'false') . "\n";
                    echo "product category_code: " . $product['category_code'] . "\n";
                    echo "product name: " . $product['product_name'] . "\n";
                    echo "spec_name: " . $spec_name . "\n";
                    echo "rebar_specifications: " . ($rebar_specifications ? 'found' : 'not found') . "\n";
                    echo "rebar_materials count: " . (is_array($rebar_materials) ? count($rebar_materials) : 0) . "\n";
                    echo "</pre>";
                }
                ?>
                <?php if ($rebar_materials && !empty($rebar_materials)): ?>
                <div class="calculator-form">
                    <!-- 재질 선택 -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #555;">재질 선택</label>
                        <div class="material-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px;">
                            <?php foreach ($rebar_materials as $material): ?>
                            <button type="button" 
                                    class="material-btn" 
                                    data-material-id="<?php echo $material['id']; ?>"
                                    data-material-price="<?php echo $material['additional_price']; ?>"
                                    style="padding: 12px; background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 8px; cursor: pointer; text-align: center; transition: all 0.3s ease; font-size: 14px;">
                                <?php echo htmlspecialchars($material['material_name']); ?>
                                <small style="display: block; font-size: 12px; margin-top: 3px; color: #666;">
                                    <?php echo number_format($material['additional_price']); ?>원/kg
                                </small>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="calc-row">
                        <?php if ($rebar_specifications && $rebar_lengths): ?>
                        <div class="calc-group">
                            <label>길이 선택</label>
                            <select id="lengthSelect" onchange="loadRebarData()" style="font-size: 16px; padding: 12px;">
                                <option value="">길이를 선택하세요</option>
                                <?php foreach ($rebar_lengths as $length): ?>
                                <option value="<?php echo $length['length']; ?>"><?php echo $length['length']; ?>m</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="calc-group" style="<?php echo ($rebar_specifications && $rebar_lengths) ? '' : 'width: 100%;'; ?>">
                            <label>수량 (톤)</label>
                            <input type="number" id="tonQuantity" value="1" min="0.1" step="0.1" onchange="calculatePrice()" oninput="calculatePrice()" style="font-size: 18px; padding: 12px;">
                        </div>
                    </div>
                    
                    <div class="calc-result">
                        <div class="result-item">
                            <span class="label">기준단가:</span>
                            <span class="value" style="font-size: 18px; color: #666;" id="basePriceDisplay"><?php echo number_format($rebar_spec['unit_price'] ?: 0); ?> 원/kg</span>
                        </div>
                        <?php if ($is_rebar): ?>
                        <div class="result-item" id="materialPriceRow" style="display: none;">
                            <span class="label">재질단가:</span>
                            <span class="value" id="materialPrice">-</span>
                        </div>
                        <div class="result-item" id="finalPriceRow" style="display: none;">
                            <span class="label">적용단가:</span>
                            <span class="value" id="finalPrice">-</span>
                        </div>
                        <div class="result-item">
                            <span class="label">단위중량:</span>
                            <span class="value" id="unitWeightDisplay"><?php echo $rebar_unit_weight; ?> kg/m</span>
                        </div>
                        <div class="result-item" id="piecesPerTonRow" style="display: none;">
                            <span class="label">톤당 본수:</span>
                            <span class="value" id="piecesPerTon">-</span>
                        </div>
                        <div class="result-item" id="actualQuantityRow" style="display: none;">
                            <span class="label">실제 본수:</span>
                            <span class="value" id="actualQuantity">-</span>
                        </div>
                        <div class="result-item" id="totalWeightRow" style="display: none;">
                            <span class="label">총 중량:</span>
                            <span class="value" id="totalWeight">-</span>
                        </div>
                        <?php endif; ?>
                        <div class="result-divider"></div>
                        <div class="result-item total-price" style="background: #e3f2fd; padding: 15px; margin-top: 15px;">
                            <span class="label" style="font-size: 20px; color: #1976d2;">예상 금액:</span>
                            <span class="value" id="totalPrice" style="font-size: 28px; color: #1976d2;">-</span>
                        </div>
                        <div class="price-notice-small">
                            <?php if ($is_rebar): ?>
                            * 계산식: 총 중량(kg) × 재질단가(원/kg)
                            <?php else: ?>
                            * 예상 금액 = 톤수 × 기준단가<br>
                            * 실제 가격은 재질, 길이, 수량에 따라 달라질 수 있습니다.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="padding: 30px; text-align: center; color: #666;">
                    <p>재질 정보를 불러올 수 없습니다.</p>
                    <p style="font-size: 14px; margin-top: 10px;">관리자에게 문의해주세요.</p>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif (($unit_weight || ($is_rebar && $rebar_unit_weight)) && $product['price'] && $product['price'] > 0): ?>
            <!-- 기존 길이/수량 선택 및 중량 계산 (철근이 아닌 경우) -->
>>>>>>> 4779a5cf4f27bbf1862cfc06a4f3b51bbbb26bb7
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
                <button type="button" onclick="addToQuoteCart()" class="btn-quote" id="quoteBtn">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"/>
                        <path d="M5 7h10M5 10h10M5 13h6"/>
                    </svg>
                    견적 문의하기
                </button>
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

<?php if ($is_rebar_category && $rebar_specifications): ?>
// 전역 변수
let rebarData = {};
let selectedMaterialId = null;
let selectedMaterialPrice = 0;

// 재질 선택 이벤트
document.querySelectorAll('.material-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // 이전 선택 제거
        document.querySelectorAll('.material-btn').forEach(b => b.classList.remove('active'));
        
        // 현재 선택 추가
        this.classList.add('active');
        selectedMaterialId = this.dataset.materialId;
        selectedMaterialPrice = parseFloat(this.dataset.materialPrice) || 0;
        
        // 재질 정보 표시
        document.getElementById('materialPrice').textContent = selectedMaterialPrice.toLocaleString() + ' 원/kg';
        document.getElementById('materialPriceRow').style.display = 'flex';
        
        calculatePrice();
    });
});

// 길이 옵션 로드
function loadLengthOptions() {
    const specId = <?php echo $rebar_spec_id ?: 'null'; ?>;
    
    if (!specId) {
        console.error('No specification ID available');
        return;
    }
    
    fetch(`/api/get_rebar_lengths.php?spec_id=${specId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const lengthSelect = document.getElementById('lengthSelect');
                lengthSelect.innerHTML = '<option value="">길이를 선택하세요</option>';
                
                data.lengths.forEach(item => {
                    rebarData[item.length] = {
                        pieces_per_ton: parseFloat(item.pieces_per_ton),
                        total_weight: parseFloat(item.total_weight),
                        weight_per_piece: parseFloat(item.weight_per_piece)
                    };
                    
                    const option = document.createElement('option');
                    option.value = item.length;
                    option.textContent = `${item.length}m (톤당 ${item.pieces_per_ton}본)`;
                    lengthSelect.appendChild(option);
                });
            } else {
                console.error('Failed to load lengths:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading lengths:', error);
        });
}

// 길이 선택시 데이터 로드
function loadRebarData() {
    calculatePrice();
}

// 철근 제품 가격 계산 함수
function calculatePrice() {
    const tonQuantity = parseFloat(document.getElementById('tonQuantity').value) || 0;
    const length = parseFloat(document.getElementById('lengthSelect').value);
    
    if (!length || tonQuantity <= 0) {
        document.getElementById('totalPrice').textContent = '-';
        document.getElementById('piecesPerTonRow').style.display = 'none';
        document.getElementById('actualQuantityRow').style.display = 'none';
        document.getElementById('totalWeightRow').style.display = 'none';
        document.getElementById('finalPriceRow').style.display = 'none';
        return;
    }
    
    const data = rebarData[length];
    if (!data) return;
    
    // 실제 본수 계산
    const actualQuantity = Math.round(tonQuantity * data.pieces_per_ton);
    
    // 총 중량 계산 (엑셀의 고정값 사용 - total_weight는 톤당 kg)
    const totalWeight = data.total_weight * tonQuantity;
    
    // 적용 단가 = 재질의 추가단가 (최종 판매가격)
    const finalPrice = selectedMaterialPrice;
    
    // 총 금액 계산
    const totalPrice = Math.round(totalWeight * finalPrice);
    
    // 결과 표시
    document.getElementById('piecesPerTon').textContent = data.pieces_per_ton + ' 본';
    document.getElementById('actualQuantity').textContent = actualQuantity.toLocaleString() + ' 본';
    document.getElementById('totalWeight').textContent = totalWeight.toFixed(2) + ' kg';
    document.getElementById('finalPrice').textContent = finalPrice.toLocaleString() + ' 원/kg';
    document.getElementById('totalPrice').textContent = totalPrice.toLocaleString() + ' 원';
    
    // 정보 행 표시
    document.getElementById('piecesPerTonRow').style.display = 'flex';
    document.getElementById('actualQuantityRow').style.display = 'flex';
    document.getElementById('totalWeightRow').style.display = 'flex';
    document.getElementById('finalPriceRow').style.display = 'flex';
}

// 페이지 로드시 초기화
window.addEventListener('DOMContentLoaded', function() {
    loadLengthOptions();
    
    // SD400을 기본 선택
    const sd400Btn = document.querySelector('.material-btn[data-material-id="2"]');
    if (sd400Btn) {
        sd400Btn.click();
    }
});
<?php elseif ($unit_weight): ?>
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

<!-- 견적문의 모달 -->
<div id="quoteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); max-width: 400px; width: 90%;">
        <h3 style="margin-bottom: 20px; color: #333; font-size: 20px;">견적문의 추가</h3>
        <p style="margin-bottom: 24px; color: #666; line-height: 1.6;">
            <strong id="modalProductName" style="color: #1428A0;"></strong> 제품이 견적서에 담겼습니다.<br>
            제품견적서 페이지로 이동하시겠습니까?
        </p>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button onclick="closeQuoteModal()" style="padding: 10px 24px; background: #f0f0f0; border: none; border-radius: 6px; cursor: pointer; font-size: 16px;">아니오</button>
            <button onclick="goToQuote()" style="padding: 10px 24px; background: #1428A0; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px;">예</button>
        </div>
    </div>
</div>

<script>
// 견적 카트에 제품 추가
function addToQuoteCart() {
    // 제품 정보 가져오기
    const productId = '<?php echo $product['id']; ?>';
    const productName = '<?php echo addslashes($product['product_name']); ?>';
    const productSpecs = '<?php echo addslashes($product['specifications']); ?>';
    const categoryCode = '<?php echo $product['category_code']; ?>';
    
    // 현재 선택된 값들 가져오기 (단중표가 있는 경우)
    let selectedInfo = {
        id: productId,
        name: productName,
        specifications: productSpecs,
        category: categoryCode
    };
    
    <?php if ($is_rebar && $rebar_spec): ?>
    // 철근 제품인 경우
    if (!selectedMaterialId || !document.getElementById('lengthSelect').value || !document.getElementById('tonQuantity').value) {
        alert('재질, 길이, 수량을 모두 선택해주세요.');
        return;
    }
    
    selectedInfo.material = selectedMaterialName;
    selectedInfo.materialId = selectedMaterialId;
    selectedInfo.length = document.getElementById('resultLength').textContent;
    selectedInfo.tonQuantity = document.getElementById('resultTon').textContent;
    selectedInfo.quantity = document.getElementById('resultQuantity').textContent.replace(/[^0-9]/g, '');
    selectedInfo.totalWeight = document.getElementById('resultTotalWeight').textContent;
    selectedInfo.totalPrice = document.getElementById('resultTotalPrice').textContent;
    selectedInfo.unitPrice = document.getElementById('resultFinalPrice').textContent;
    selectedInfo.isRebar = true;
    selectedInfo.specId = <?php echo $rebar_spec['id']; ?>;
    
    <?php elseif ($unit_weight): ?>
    // 단중표가 있는 경우 추가 정보 포함
    const length = document.getElementById('length') ? document.getElementById('length').value : '';
    const quantity = document.getElementById('quantity') ? document.getElementById('quantity').value : 1;
    const totalWeight = document.getElementById('totalWeight') ? document.getElementById('totalWeight').textContent.replace(/[^0-9.]/g, '') : '';
    
    if (length) {
        selectedInfo.length = length + 'm';
        selectedInfo.quantity = quantity;
        selectedInfo.totalWeight = totalWeight + 'kg';
    }
    <?php endif; ?>
    
    // 세션 스토리지에서 기존 카트 가져오기
    let quoteCart = JSON.parse(sessionStorage.getItem('quoteCart') || '[]');
    
    // 중복 확인
    const existingIndex = quoteCart.findIndex(item => item.id === productId);
    if (existingIndex === -1) {
        // 새 제품 추가
        quoteCart.push({
            ...selectedInfo,
            quantity: selectedInfo.quantity || 1,
            addedAt: new Date().toISOString()
        });
    } else {
        // 이미 있는 제품은 수량 증가
        quoteCart[existingIndex].quantity = parseInt(quoteCart[existingIndex].quantity) + parseInt(selectedInfo.quantity || 1);
    }
    
    // 세션 스토리지에 저장
    sessionStorage.setItem('quoteCart', JSON.stringify(quoteCart));
    
    // 카트 카운트 업데이트
    updateCartCount();
    
    // 모달에 제품명 표시
    document.getElementById('modalProductName').textContent = productName;
    
    // 모달 표시
    document.getElementById('quoteModal').style.display = 'block';
}

// 카트 카운트 업데이트
function updateCartCount() {
    const quoteCart = JSON.parse(sessionStorage.getItem('quoteCart') || '[]');
    const cartCount = quoteCart.length; // 아이템(건) 단위로 카운트
    
    // 상단 카트 아이콘의 카운트 업데이트 (head.php에 있다면)
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = cartCount;
        cartCountElement.style.display = cartCount > 0 ? 'block' : 'none';
    }
}

// 모달 닫기
function closeQuoteModal() {
    document.getElementById('quoteModal').style.display = 'none';
}

// 제품견적서 페이지로 이동
function goToQuote() {
    // 마이페이지의 제품견적서로 이동
    window.location.href = 'my_quote_cart.php';
}

// 모달 외부 클릭 시 닫기
document.getElementById('quoteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQuoteModal();
    }
});

// 페이지 로드 시 카트 카운트 업데이트
window.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
});

<?php if ($is_rebar && $rebar_spec): ?>
// 철근 계산 로직 (rebar_quote.php 방식)
let selectedMaterialId = null;
let selectedMaterialPrice = 0;
let selectedMaterialName = '';

// 철근 데이터
const rebarData = {
    spec_id: <?php echo $rebar_spec['id']; ?>,
    spec_name: '<?php echo $rebar_spec['spec_name']; ?>',
    unit_weight: <?php echo $rebar_spec['unit_weight']; ?>,
    base_price: <?php echo $rebar_spec['unit_price'] ?: 0; ?> // 관리자 페이지의 현재단가
};

// 재질 버튼 클릭 이벤트
document.querySelectorAll('.material-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // 이전 선택 제거
        document.querySelectorAll('.material-btn').forEach(b => {
            b.classList.remove('active');
        });
        
        // 현재 선택 추가
        this.classList.add('active');
        
        selectedMaterialId = this.dataset.materialId;
        selectedMaterialPrice = parseFloat(this.dataset.materialPrice);
        selectedMaterialName = this.querySelector('strong').textContent;
        
        document.getElementById('resultMaterial').textContent = selectedMaterialName;
        
        calculateRebarPrice();
    });
});

// 길이 선택 변경 이벤트
document.getElementById('lengthSelect').addEventListener('change', calculateRebarPrice);

// 수량 입력 변경 이벤트
document.getElementById('tonQuantity').addEventListener('input', function() {
    clearTimeout(this.inputTimer);
    this.inputTimer = setTimeout(calculateRebarPrice, 500);
});

// 철근 제품 가격 계산 함수 (rebar_quote.php 계산식 참조)
function calculateRebarPrice() {
    const lengthSelect = document.getElementById('lengthSelect');
    const tonQuantity = parseFloat(document.getElementById('tonQuantity').value) || 0;
    
    if (!selectedMaterialId || !lengthSelect.value || tonQuantity <= 0) {
        return;
    }
    
    const selectedOption = lengthSelect.options[lengthSelect.selectedIndex];
    const length = parseFloat(lengthSelect.value);
    const piecesPerTon = parseFloat(selectedOption.dataset.piecesPerTon);
    const dbTotalWeight = parseFloat(selectedOption.dataset.totalWeight) || 0;
    
    // 1. 실제 본수 계산 = 톤수 × 톤당 본수
    const actualQuantity = Math.round(tonQuantity * piecesPerTon);
    
    // 2. 본당 중량 = 단위중량 × 길이
    const weightPerPiece = rebarData.unit_weight * length;
    
    // 3. 총 중량 = DB에 고정된 중량 사용 (있는 경우), 없으면 계산
    const totalWeight = dbTotalWeight > 0 ? dbTotalWeight * tonQuantity : weightPerPiece * actualQuantity;
    
    // 4. 적용 단가 = 기준단가 + 재질 추가단가
    const finalPrice = rebarData.base_price + selectedMaterialPrice;
    
    // 5. 총 금액 = 총 중량 × 적용 단가
    const totalPrice = Math.round(totalWeight * finalPrice);
    
    // 결과 표시
    document.getElementById('resultLength').textContent = length + 'm';
    document.getElementById('resultTon').textContent = tonQuantity + '톤';
    document.getElementById('resultQuantity').textContent = actualQuantity.toLocaleString() + '본';
    document.getElementById('resultTotalWeight').textContent = totalWeight.toFixed(2) + 'kg';
    document.getElementById('resultMaterialPrice').textContent = '+' + selectedMaterialPrice.toLocaleString() + '원/kg';
    document.getElementById('resultFinalPrice').textContent = finalPrice.toLocaleString() + '원/kg';
    document.getElementById('resultTotalPrice').textContent = totalPrice.toLocaleString() + '원';
}

// 초기화 함수
function resetCalculator() {
    // 재질 선택 초기화
    document.querySelectorAll('.material-btn').forEach(b => {
        b.classList.remove('active');
    });
    selectedMaterialId = null;
    selectedMaterialPrice = 0;
    selectedMaterialName = '';
    
    // 입력 필드 초기화
    document.getElementById('lengthSelect').value = '';
    document.getElementById('tonQuantity').value = '1';
    
    // 결과 표시 초기화
    document.getElementById('resultMaterial').textContent = '-';
    document.getElementById('resultLength').textContent = '-';
    document.getElementById('resultTon').textContent = '-';
    document.getElementById('resultQuantity').textContent = '-';
    document.getElementById('resultTotalWeight').textContent = '-';
    document.getElementById('resultMaterialPrice').textContent = '-';
    document.getElementById('resultFinalPrice').textContent = '-';
    document.getElementById('resultTotalPrice').textContent = '-';
}

// 페이지 로드 시 첫 번째 재질 자동 선택
window.addEventListener('DOMContentLoaded', function() {
    const firstMaterialBtn = document.querySelector('.material-btn');
    if (firstMaterialBtn) {
        firstMaterialBtn.click();
    }
});

// Enter 키 이벤트
document.getElementById('tonQuantity').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        calculateRebarPrice();
    }
});
<?php endif; ?>
</script>

<?php include 'tail.php'; ?>