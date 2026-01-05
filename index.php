<?php
$currentPage = '';
$pageTitle = '홈';
include 'head.php';
require_once 'db.php';

// 활성화된 배너 가져오기
$stmt = $pdo->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY display_order ASC, id DESC");
$banners = $stmt->fetchAll();
?>

<!-- 메인 비주얼 캐러셀 -->
<?php if (!empty($banners)): ?>
<section class="banner-carousel">
    <div class="carousel-container">
        <div class="carousel-track" id="carouselTrack">
            <?php foreach ($banners as $index => $banner): ?>
            <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                <?php if ($banner['link_url']): ?>
                <a href="<?php echo escape($banner['link_url']); ?>" target="<?php echo escape($banner['link_target']); ?>">
                <?php endif; ?>
                    <div class="slide-image">
                        <?php if ($banner['image_path']): ?>
                        <img src="uploads/banners/<?php echo escape($banner['image_path']); ?>" 
                             alt="<?php echo escape($banner['title']); ?>">
                        <?php else: ?>
                        <div class="slide-placeholder"></div>
                        <?php endif; ?>
                    </div>
                    <div class="slide-content">
                        <div class="section-container">
                            <h2><?php echo escape($banner['title']); ?></h2>
                            <?php if ($banner['subtitle']): ?>
                            <p><?php echo nl2br(escape($banner['subtitle'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php if ($banner['link_url']): ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($banners) > 1): ?>
        <!-- 캐러셀 컨트롤 -->
        <button class="carousel-control carousel-prev" onclick="changeSlide(-1)" aria-label="이전 배너">
            <i class="fas fa-angle-left"></i>
        </button>
        <button class="carousel-control carousel-next" onclick="changeSlide(1)" aria-label="다음 배너">
            <i class="fas fa-angle-right"></i>
        </button>
        
        <!-- 캐러셀 인디케이터 -->
        <div class="carousel-indicators">
            <?php foreach ($banners as $index => $banner): ?>
            <button class="carousel-indicator <?php echo $index === 0 ? 'active' : ''; ?>" 
                    onclick="goToSlide(<?php echo $index; ?>)"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.banner-carousel {
    position: relative;
    overflow: hidden;
    background: #f5f5f5;
}

.carousel-container {
    position: relative;
    width: 100%;
    height: 600px;
}

.carousel-track {
    position: relative;
    height: 100%;
}

.carousel-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
}

.carousel-slide.active {
    opacity: 1;
}

.slide-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.slide-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.slide-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.slide-content {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    text-align: center;
    color: white;
    z-index: 2;
}

.slide-content h2 {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.slide-content p {
    font-size: 20px;
    line-height: 1.6;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.carousel-control {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.9);
    color: #333;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.3s;
    z-index: 3;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.carousel-control:hover {
    background: rgba(255,255,255,1);
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.carousel-control:active {
    transform: translateY(-50%) scale(0.95);
}

.carousel-prev {
    left: 20px;
}

.carousel-next {
    right: 20px;
}

.carousel-indicators {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 3;
}

.carousel-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s;
}

.carousel-indicator.active {
    background: white;
    width: 30px;
    border-radius: 6px;
}

@media (max-width: 768px) {
    .carousel-container {
        height: 400px;
    }
    
    .slide-content h2 {
        font-size: 32px;
    }
    
    .slide-content p {
        font-size: 16px;
    }
    
    .carousel-control {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }
    
    .carousel-prev {
        left: 10px;
    }
    
    .carousel-next {
        right: 10px;
    }
}
</style>

<script>
let currentSlide = 0;
const slides = document.querySelectorAll('.carousel-slide');
const indicators = document.querySelectorAll('.carousel-indicator');
const totalSlides = slides.length;

// 자동 슬라이드
let slideInterval;

function startAutoSlide() {
    if (totalSlides > 1) {
        slideInterval = setInterval(() => {
            changeSlide(1);
        }, 5000);
    }
}

function stopAutoSlide() {
    clearInterval(slideInterval);
}

function updateSlide() {
    slides.forEach((slide, index) => {
        slide.classList.toggle('active', index === currentSlide);
    });
    
    indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentSlide);
    });
}

function changeSlide(direction) {
    currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
    updateSlide();
    
    // 자동 슬라이드 재시작
    stopAutoSlide();
    startAutoSlide();
}

function goToSlide(index) {
    currentSlide = index;
    updateSlide();
    
    // 자동 슬라이드 재시작
    stopAutoSlide();
    startAutoSlide();
}

// 페이지 로드 시 자동 슬라이드 시작
startAutoSlide();
</script>

<?php else: ?>
<!-- 배너가 없을 때 기본 히어로 섹션 -->
<section class="hero-section">
    <div class="section-container">
        <div class="hero-content">
            <h2>믿을 수 있는 철강 파트너<br>충남스틸</h2>
            <p>최고 품질의 철강 제품과 전문적인 서비스로<br>고객의 성공을 함께 만들어갑니다.</p>
            <div class="hero-buttons">
                <a href="quote.php" class="btn btn-primary btn-large">견적문의</a>
                <a href="about.php" class="btn btn-secondary btn-large">회사소개</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 주요 제품 -->
<?php 
// 활성화된 제품 아이콘 가져오기
try {
    $stmt = $pdo->query("SELECT * FROM product_icons WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
    $icons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $icons = [];
}

// 기본 SVG 아이콘 맵핑
$defaultIcons = [
    'rebar' => '<path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>',
    'hbeam' => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>',
    'plate' => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>',
    'metalath' => '<path d="M2 20h20v-4H2v4zm2-3h2v2H4v-2zM2 4v4h20V4H2zm4 3H4V5h2v2zm-4 7h20v-4H2v4zm2-3h2v2H4v-2z"/>',
    'light-hbeam' => '<path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>',
    'ibeam' => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 14H7v-2h10v2zm0-4H7v-2h10v2zm0-4H7V7h10v2z"/>',
    'angle' => '<path d="M12 2l-5.5 9h11z M12 22l5.5-9h-11z"/>',
    'channel' => '<path d="M21 18H2v2h19c1.1 0 2-.9 2-2s-.9-2-2-2zm0-14H2v2h19c1.1 0 2-.9 2-2s-.9-2-2-2zm0 7H2v2h19c1.1 0 2-.9 2-2s-.9-2-2-2z"/>',
    'round-bar' => '<circle cx="12" cy="12" r="10"/>',
    'flat-bar' => '<rect x="2" y="10" width="20" height="4"/>',
    'c-channel' => '<path d="M6 2v20h12V2H6zm2 18V4h8v16H8z"/>',
    'tech-plate' => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/><circle cx="18" cy="6" r="2" fill="#1A237E"/>',
    'square-pipe' => '<rect x="4" y="4" width="16" height="16" fill="none" stroke="white" stroke-width="2"/><rect x="8" y="8" width="8" height="8" fill="#1A237E"/>',
    'round-pipe' => '<circle cx="12" cy="12" r="10" fill="none" stroke="white" stroke-width="2"/><circle cx="12" cy="12" r="6" fill="#1A237E"/>',
    'rail' => '<path d="M2 12h20M2 8h20M2 16h20"/><rect x="6" y="6" width="12" height="12" fill="none" stroke="white" stroke-width="1"/>',
    'sheet-pile' => '<path d="M3 3v18l3-3 3 3 3-3 3 3 3-3 3 3V3l-3 3-3-3-3 3-3-3-3 3z"/>',
    'stainless' => '<circle cx="12" cy="12" r="3"/><path d="M12 1l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 1z"/>'
];
?>
<section class="section">
    <div class="section-container">
        <div class="section-header">
            <h3>주요 제품</h3>
            <p>충남스틸이 공급하는 고품질 철강 제품입니다</p>
        </div>
        <!-- 통합된 제품 영역 -->
        <div class="product-nav-unified" id="productNavUnified">
            <?php if (!empty($icons)): ?>
            <!-- 첫 번째 줄 (8개) -->
            <div class="product-row product-row-main">
                <?php 
                $firstRowIcons = array_slice($icons, 0, 8);
                foreach ($firstRowIcons as $icon): 
                ?>
                <a href="<?php echo htmlspecialchars($icon['icon_url'] ?? '#'); ?>" 
                   target="<?php echo htmlspecialchars($icon['url_target'] ?? '_self'); ?>" 
                   class="product-nav-item">
                    <div class="product-nav-icon">
                        <?php if ($icon['icon_image']): ?>
                            <img src="<?php echo htmlspecialchars($icon['icon_image']); ?>" alt="">
                        <?php elseif (isset($defaultIcons[$icon['category_code']])): ?>
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="white">
                                <?php echo $defaultIcons[$icon['category_code']]; ?>
                            </svg>
                        <?php else: ?>
                            <span style="font-size: 24px;">📦</span>
                        <?php endif; ?>
                    </div>
                    <span><?php echo htmlspecialchars($icon['icon_name']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($icons) > 8): ?>
            <!-- 더보기 버튼 (중앙에 위치) -->
            <div class="product-expand-container" id="productExpandContainer">
                <button class="product-expand-btn" onclick="toggleProductExpansion()">
                    <svg class="expand-arrow" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 10l5 5 5-5z"/>
                    </svg>
                </button>
            </div>
            
            <!-- 확장 제품 영역 (두 번째 줄) -->
            <div class="product-rows-expanded" id="productRowsExpanded">
                <!-- 두 번째 줄 (8개) -->
                <div class="product-row">
                    <?php 
                    $secondRowIcons = array_slice($icons, 8, 8);
                    foreach ($secondRowIcons as $icon): 
                    ?>
                    <a href="<?php echo htmlspecialchars($icon['icon_url'] ?? '#'); ?>" 
                       target="<?php echo htmlspecialchars($icon['url_target'] ?? '_self'); ?>" 
                       class="product-nav-item">
                        <div class="product-nav-icon">
                            <?php if ($icon['icon_image']): ?>
                                <img src="<?php echo htmlspecialchars($icon['icon_image']); ?>" alt="">
                            <?php elseif (isset($defaultIcons[$icon['category_code']])): ?>
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="white">
                                    <?php echo $defaultIcons[$icon['category_code']]; ?>
                                </svg>
                            <?php else: ?>
                                <span style="font-size: 24px;">📦</span>
                            <?php endif; ?>
                        </div>
                        <span><?php echo htmlspecialchars($icon['icon_name']); ?></span>
                    </a>
                    <?php endforeach; ?>

                    <?php
                    // 추가 아이콘이 있으면 표시 (16개 이상)
                    if (count($icons) > 16):
                        $additionalIcons = array_slice($icons, 16);
                        foreach ($additionalIcons as $icon):
                    ?>
                    <a href="<?php echo htmlspecialchars($icon['icon_url'] ?? '#'); ?>"
                       target="<?php echo htmlspecialchars($icon['url_target'] ?? '_self'); ?>"
                       class="product-nav-item">
                        <div class="product-nav-icon">
                            <?php if ($icon['icon_image']): ?>
                                <img src="<?php echo htmlspecialchars($icon['icon_image']); ?>" alt="">
                            <?php elseif (isset($defaultIcons[$icon['category_code']])): ?>
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="white">
                                    <?php echo $defaultIcons[$icon['category_code']]; ?>
                                </svg>
                            <?php else: ?>
                                <span style="font-size: 24px;">📦</span>
                            <?php endif; ?>
                        </div>
                        <span><?php echo htmlspecialchars($icon['icon_name']); ?></span>
                    </a>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </div>
                
                <!-- 접기 버튼 -->
                <div class="product-collapse-container">
                    <button class="product-collapse-btn" onclick="toggleProductExpansion()">
                        <svg class="collapse-arrow" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17 14l-5-5-5 5z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <!-- 아이콘이 없을 때 기본 표시 -->
            <div style="text-align: center; padding: 60px 0; color: #666;">
                <p>표시할 제품 아이콘이 없습니다.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>



<!-- 추천 제품 (메인페이지 노출 제품) -->
<?php
// 메인페이지 노출 제품 가져오기
$stmt = $pdo->query("
    SELECT p.*, pc.category_name,
    CONCAT(p.product_name, IF(p.specification IS NOT NULL AND p.specification != '', CONCAT(' ', p.specification), IF(p.specifications IS NOT NULL AND p.specifications != '', CONCAT(' ', p.specifications), ''))) AS display_name
    FROM products p
    JOIN product_categories pc ON p.category_code = pc.category_code
    WHERE p.show_on_homepage = 1 AND p.is_active = 1
    ORDER BY p.homepage_display_order ASC, p.id DESC
    LIMIT 12
");
$featured_products = $stmt->fetchAll();

if (!empty($featured_products)):
?>
<section class="section featured-products-section">
    <div class="section-container">
        <div class="section-header">
            <h3>추천 제품</h3>
            <p>충남스틸이 추천하는 인기 제품입니다</p>
        </div>
        
        <div class="featured-products-container">
            <div class="featured-products-slider" id="featuredProductsSlider">
                <?php foreach ($featured_products as $index => $product): ?>
                <div class="featured-product-card">
                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="product-card-link">
                        <div class="product-card-image">
                            <?php if ($product['main_image']): ?>
                                <img src="<?php echo htmlspecialchars($product['main_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <span>이미지 준비중</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-card-info">
                            <h4><?php echo htmlspecialchars($product['display_name'] ?? $product['product_name']); ?></h4>
                            <?php if ($product['specifications']): ?>
                                <p class="product-spec"><?php echo htmlspecialchars($product['specifications']); ?></p>
                            <?php endif; ?>
                            <div class="product-category">
                                <span class="category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            </div>
                            <?php if ($product['price'] && $product['price'] > 0): ?>
                                <div class="product-price">
                                    <span class="price-label">기준단가</span>
                                    <span class="price-value"><?php echo number_format($product['price']); ?>원</span>
                                </div>
                                <?php if (isset($product['min_price']) && isset($product['max_price']) && $product['min_price'] && $product['max_price']): ?>
                                <div class="price-range">
                                    <?php echo number_format($product['min_price']); ?>원 ~ <?php echo number_format($product['max_price']); ?>원
                                </div>
                                <?php else: ?>
                                <div class="price-range">
                                    <?php echo number_format($product['price'] * 0.9); ?>원 ~ <?php echo number_format($product['price'] * 1.1); ?>원
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 슬라이드 네비게이션 -->
            <button class="featured-nav-btn featured-prev" onclick="slideFeaturedProducts(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="featured-nav-btn featured-next" onclick="slideFeaturedProducts(1)">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <!-- 점 표시기 -->
            <div class="featured-dots" id="featuredDots"></div>
        </div>
    </div>
</section>

<style>
/* 추천 제품 섹션 스타일 */
.featured-products-section {
    background-color: #f8f9fa;
    padding: 60px 0;
}

.featured-products-container {
    position: relative;
    overflow: hidden;
}

.featured-products-slider {
    display: flex;
    transition: transform 0.5s ease;
    gap: 20px;
}

.featured-product-card {
    flex: 0 0 calc(25% - 15px);
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
}

.featured-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.product-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.product-card-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    background: #f5f5f5;
}

.product-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    color: #6c757d;
    font-size: 14px;
}

.product-card-info {
    padding: 20px;
}

.product-card-info h4 {
    font-size: 18px;
    margin: 0 0 10px 0;
    color: #333;
    font-weight: 600;
}

.product-spec {
    font-size: 14px;
    color: #666;
    margin: 0 0 10px 0;
}

.product-category {
    margin-bottom: 15px;
}

.category-badge {
    display: inline-block;
    padding: 4px 12px;
    background: #e3f2fd;
    color: #1976d2;
    font-size: 12px;
    border-radius: 4px;
    font-weight: 500;
}

.product-price {
    margin-bottom: 5px;
}

.price-label {
    font-size: 12px;
    color: #666;
    margin-right: 5px;
}

.price-value {
    font-size: 20px;
    font-weight: 700;
    color: #1A237E;
}

.price-range {
    font-size: 14px;
    color: #666;
}

/* 네비게이션 버튼 */
.featured-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s ease;
}

.featured-nav-btn:hover {
    background: white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.featured-prev {
    left: 20px;
}

.featured-next {
    right: 20px;
}

/* 점 표시기 */
.featured-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 30px;
}

.featured-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ccc;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.featured-dot.active {
    background: #1A237E;
    width: 24px;
    border-radius: 4px;
}

/* 반응형 디자인 */
@media (max-width: 1200px) {
    .featured-product-card {
        flex: 0 0 calc(33.333% - 14px);
    }
}

@media (max-width: 768px) {
    .featured-product-card {
        flex: 0 0 calc(50% - 10px);
    }
    
    .featured-nav-btn {
        width: 35px;
        height: 35px;
        font-size: 14px;
    }
    
    .featured-prev {
        left: 10px;
    }
    
    .featured-next {
        right: 10px;
    }
}

@media (max-width: 480px) {
    .featured-product-card {
        flex: 0 0 100%;
    }
}
</style>

<script>
// 추천 제품 슬라이드 기능
let featuredCurrentSlide = 0;
let featuredProductsPerView = 4;
let featuredTotalSlides = 0;

function initFeaturedProducts() {
    const slider = document.getElementById('featuredProductsSlider');
    const products = slider.querySelectorAll('.featured-product-card');
    const dotsContainer = document.getElementById('featuredDots');
    
    // 반응형에 따른 표시 개수 설정
    if (window.innerWidth <= 480) {
        featuredProductsPerView = 1;
    } else if (window.innerWidth <= 768) {
        featuredProductsPerView = 2;
    } else if (window.innerWidth <= 1200) {
        featuredProductsPerView = 3;
    } else {
        featuredProductsPerView = 4;
    }
    
    featuredTotalSlides = Math.ceil(products.length / featuredProductsPerView);
    
    // 점 표시기 생성
    dotsContainer.innerHTML = '';
    for (let i = 0; i < featuredTotalSlides; i++) {
        const dot = document.createElement('button');
        dot.className = 'featured-dot' + (i === 0 ? ' active' : '');
        dot.onclick = () => goToFeaturedSlide(i);
        dotsContainer.appendChild(dot);
    }
    
    updateFeaturedSlider();
}

function slideFeaturedProducts(direction) {
    featuredCurrentSlide += direction;
    
    if (featuredCurrentSlide < 0) {
        featuredCurrentSlide = featuredTotalSlides - 1;
    } else if (featuredCurrentSlide >= featuredTotalSlides) {
        featuredCurrentSlide = 0;
    }
    
    updateFeaturedSlider();
}

function goToFeaturedSlide(index) {
    featuredCurrentSlide = index;
    updateFeaturedSlider();
}

function updateFeaturedSlider() {
    const slider = document.getElementById('featuredProductsSlider');
    const slideWidth = 100 / featuredProductsPerView;
    const offset = -featuredCurrentSlide * 100;
    
    slider.style.transform = `translateX(${offset}%)`;
    
    // 점 표시기 업데이트
    const dots = document.querySelectorAll('.featured-dot');
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === featuredCurrentSlide);
    });
}

// 자동 슬라이드
let featuredAutoSlideInterval;

function startFeaturedAutoSlide() {
    featuredAutoSlideInterval = setInterval(() => {
        slideFeaturedProducts(1);
    }, 5000);
}

function stopFeaturedAutoSlide() {
    clearInterval(featuredAutoSlideInterval);
}

// 초기화 및 이벤트 리스너
document.addEventListener('DOMContentLoaded', () => {
    initFeaturedProducts();
    startFeaturedAutoSlide();
    
    // 마우스 호버 시 자동 슬라이드 중지
    const container = document.querySelector('.featured-products-container');
    container.addEventListener('mouseenter', stopFeaturedAutoSlide);
    container.addEventListener('mouseleave', startFeaturedAutoSlide);
});

// 윈도우 리사이즈 시 재초기화
window.addEventListener('resize', () => {
    initFeaturedProducts();
});
</script>
<?php endif; ?>

<!-- 주요 서비스 -->
<section class="section" style="background-color: #F8F9FA;">
    <div class="section-container">
        <div class="section-header">
            <h3>주요 서비스</h3>
            <p>충남스틸이 제공하는 전문 서비스입니다</p>
        </div>
        <div class="service-grid">
            <div class="service-card" style="background-color: #E8F5E9;">
                <h4>철강재 유통</h4>
                <p>H형강, 철근, 강관 등<br>다양한 철강 제품 공급</p>
                <div class="service-icon-bottom">🏗️</div>
            </div>
            <div class="service-card" style="background-color: #FFF3E0;">
                <h4>철강 가공</h4>
                <p>절단, 절곡, 용접 등<br>맞춤형 가공 서비스</p>
                <div class="service-icon-bottom">⚙️</div>
            </div>
            <div class="service-card" style="background-color: #F3E5F5;">
                <h4>기술 컨설팅</h4>
                <p>제품 선정부터 시공까지<br>전문 기술 지원</p>
                <div class="service-icon-bottom">📋</div>
            </div>
            <div class="service-card" style="background-color: #FCE4EC;">
                <h4>물류 서비스</h4>
                <p>신속하고 안전한<br>제품 운송 서비스</p>
                <div class="service-icon-bottom">🚚</div>
            </div>
        </div>
    </div>
</section>

<!-- 전화 문의 배너 -->
<section class="phone-banner-section">
    <div class="section-container">
        <div class="phone-banner">
            <div class="banner-content">
                <h3>궁금한 점이 있으신가요? 지금 바로 연락주세요!</h3>
                <div class="phone-contact-area">
                    <span class="contact-label">문의 :</span>
                    <a href="tel:01098200495" class="phone-button">010-9820-0495</a>
                    <span class="contact-info">(영업부 김영건 실장)</span>
                </div>
                <p class="banner-description">전화번호를 터치하시면 바로 통화연결됩니다</p>
            </div>
            <div class="banner-icon">📞</div>
        </div>
    </div>
</section>

<style>
.phone-banner-section {
    background: linear-gradient(135deg, #1428A0 0%, #1e3c72 100%);
    padding: 40px 0;
}

.phone-banner {
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}

.phone-banner::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(20, 40, 160, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.banner-content {
    flex: 1;
    z-index: 1;
}

.banner-content h3 {
    font-size: 28px;
    font-weight: 700;
    color: #1A237E;
    margin-bottom: 20px;
    line-height: 1.4;
}

.phone-contact-area {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.contact-label {
    font-size: 18px;
    color: #333;
    font-weight: 500;
}

.phone-button {
    background: linear-gradient(135deg, #1A237E 0%, #303F9F 100%);
    color: white;
    padding: 12px 24px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 700;
    font-size: 18px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
    position: relative;
    z-index: 10;
}

.phone-button:hover {
    background: linear-gradient(135deg, #0D47A1 0%, #1976D2 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(26, 35, 126, 0.4);
    color: white;
}

.phone-button:active {
    transform: translateY(0px);
}

.contact-info {
    font-size: 16px;
    color: #666;
}

.banner-description {
    font-size: 14px;
    color: #666;
    margin: 0;
    font-style: italic;
}

.banner-icon {
    font-size: 60px;
    animation: ring 2s ease-in-out infinite;
    z-index: 1;
}

@keyframes ring {
    0%, 100% { transform: rotate(0deg); }
    10%, 30% { transform: rotate(-15deg); }
    20%, 40% { transform: rotate(15deg); }
}

/* 모바일 반응형 - 30.png 디자인 */
@media (max-width: 768px) {
    .phone-banner-section {
        padding: 30px 0;
        background: linear-gradient(135deg, #2E3B8C 0%, #1A237E 100%);
    }
    
    .phone-banner {
        background: white;
        padding: 40px 30px;
        flex-direction: column;
        text-align: center;
        border-radius: 20px;
        margin: 0 20px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .banner-content h3 {
        font-size: 24px;
        margin-bottom: 30px;
        color: #2E3B8C;
        line-height: 1.3;
    }
    
    .phone-contact-area {
        flex-direction: column;
        gap: 16px;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .contact-label {
        font-size: 18px;
        color: #333;
    }
    
    .phone-button {
        font-size: 20px;
        padding: 16px 32px;
        border-radius: 30px;
        background: linear-gradient(135deg, #2E3B8C 0%, #1A237E 100%);
        box-shadow: 0 6px 20px rgba(46, 59, 140, 0.4);
        min-width: 200px;
        text-align: center;
    }
    
    .phone-button:hover {
        background: linear-gradient(135deg, #1A237E 0%, #0D47A1 100%);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(46, 59, 140, 0.5);
    }
    
    .contact-info {
        font-size: 16px;
        color: #666;
        text-align: center;
    }
    
    .banner-description {
        font-size: 14px;
        margin-top: 16px;
        color: #666;
    }
    
    .banner-icon {
        font-size: 60px;
        margin-top: 20px;
        color: #666;
    }
}
</style>

<!-- 최신 공지사항 -->
<section class="section" style="background-color: white;">
    <div class="section-container">
        <div class="section-header">
            <h3>공지사항</h3>
            <a href="notice.php" class="more-link">더보기 →</a>
        </div>
        <div class="notice-list card">
        <?php
        // 최신 공지사항 5개 가져오기
        try {
            $sql = "SELECT id, title, created_at, is_important 
                    FROM board_notice 
                    ORDER BY is_important DESC, created_at DESC 
                    LIMIT 5";
            $stmt = $pdo->query($sql);
            $notices = $stmt->fetchAll();
            
            foreach ($notices as $notice):
        ?>
        <div class="notice-item">
            <a href="#" onclick="openNoticeModal(<?php echo $notice['id']; ?>); return false;">
                <?php if ($notice['is_important']): ?>
                    <span class="badge-important">[중요]</span>
                <?php endif; ?>
                <?php echo escape($notice['title']); ?>
            </a>
            <span class="date"><?php echo formatDate($notice['created_at']); ?></span>
        </div>
        <?php 
            endforeach;
        } catch (Exception $e) {
            echo '<p style="text-align: center; color: #666;">공지사항을 불러올 수 없습니다.</p>';
        }
        ?>
        </div>
    </div>
</section>

<!-- 최신 철강뉴스 -->
<section class="section">
    <div class="section-container">
        <div class="section-header">
            <h3>철강뉴스</h3>
            <div class="news-controls">
                <button class="news-nav-btn" id="newsPrevBtn" onclick="slideNews(-1)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                    </svg>
                </button>
                <button class="news-nav-btn" id="newsNextBtn" onclick="slideNews(1)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                    </svg>
                </button>
                <a href="news.php" class="more-link">더보기 →</a>
            </div>
        </div>
        <div class="news-slider-container">
            <div class="news-slider" id="newsSlider">
            <?php
            // 최신 뉴스 9개 가져오기 (슬라이드용)
            try {
                $sql = "SELECT id, title, content, source, created_at 
                        FROM board_news 
                        ORDER BY created_at DESC 
                        LIMIT 9";
                $stmt = $pdo->query($sql);
                $newsList = $stmt->fetchAll();
                
                foreach ($newsList as $index => $news):
            ?>
            <div class="news-card card">
                <h4><a href="board_view.php?type=news&id=<?php echo $news['id']; ?>"><?php echo escape($news['title']); ?></a></h4>
                <p><?php echo escape(mb_substr(strip_tags($news['content']), 0, 100)) . '...'; ?></p>
                <div class="news-meta">
                    <span class="source"><?php echo escape($news['source'] ?: '충남스틸'); ?></span>
                    <span class="date"><?php echo formatDate($news['created_at']); ?></span>
                </div>
            </div>
            <?php 
                endforeach;
            } catch (Exception $e) {
                echo '<div class="news-card card" style="grid-column: 1/-1;"><p style="text-align: center; color: #666;">뉴스를 불러올 수 없습니다.</p></div>';
            }
            ?>
            </div>
        </div>
        <div class="news-dots" id="newsDots"></div>
    </div>
</section>


<style>
/* Page specific styles */
/* 통합된 제품 영역 스타일 */
.product-nav-unified {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    padding: 20px;
    overflow: hidden;
}

.product-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.product-row:last-child {
    margin-bottom: 0;
}

.product-row-main {
    margin-bottom: 0;
}

.product-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #333;
    padding: 10px;
    min-width: 80px;
    transition: all 0.3s ease;
}

.product-nav-item:hover {
    transform: translateY(-3px);
}

.product-nav-icon {
    width: 60px;
    height: 60px;
    background: #1A237E;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    transition: all 0.3s ease;
    overflow: hidden;
}

.product-nav-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.product-nav-item:hover .product-nav-icon {
    background: #0D47A1;
    transform: scale(1.05);
}

.product-nav-item span {
    font-size: 14px;
    font-weight: 500;
    color: #333;
    text-align: center;
}

@media (max-width: 768px) {
    .product-nav {
        justify-content: flex-start;
        padding: 15px;
    }
    
    .product-nav-item {
        min-width: 70px;
    }
}

/* 제품 확장 버튼 스타일 */
.product-expand-container {
    text-align: center;
    margin: 20px 0 10px 0;
}

.product-expand-btn {
    background: transparent;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 8px;
    transition: all 0.3s ease;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.product-expand-btn:hover {
    background: #f5f5f5;
    color: #1A237E;
}

.expand-arrow {
    transition: transform 0.3s ease;
}

.product-expand-btn.expanded .expand-arrow {
    transform: rotate(180deg);
}

/* 확장 영역 스타일 */
.product-rows-expanded {
    max-height: 0;
    overflow: hidden;
    opacity: 0;
    transition: all 0.5s ease;
    margin-top: 0;
}

.product-rows-expanded.show {
    max-height: 400px;
    opacity: 1;
    margin-top: 10px;
}

/* 접기 버튼 컨테이너 */
.product-collapse-container {
    text-align: center;
    margin: 20px 0 10px 0;
}

.product-collapse-btn {
    background: transparent;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 8px;
    transition: all 0.3s ease;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.product-collapse-btn:hover {
    background: #f5f5f5;
    color: #1A237E;
}

@media (max-width: 768px) {
    .product-nav-unified {
        padding: 15px;
    }
    
    .product-row {
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .product-expand-btn, .product-collapse-btn {
        width: 36px;
        height: 36px;
    }
    
    .product-nav-icon {
        width: 50px;
        height: 50px;
    }
    
    .product-nav-item span {
        font-size: 12px;
    }
}

.service-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.service-card {
    padding: 40px 24px 80px;
    text-align: center;
    border-radius: 20px;
    position: relative;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    border: none;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.service-card h4 {
    font-size: 20px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
}

.service-card p {
    font-size: 15px;
    color: #666;
    line-height: 1.6;
}

.service-icon-bottom {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .service-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.notice-list {
    padding: 0;
    background: white;
}

.notice-item {
    padding: 16px 20px;
    border-bottom: 1px solid #E5E5E7;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background-color 0.2s ease;
}

.notice-item:last-child {
    border-bottom: none;
}

.notice-item:hover {
    background-color: #F8F9FA;
}

.notice-item a {
    color: #333;
    text-decoration: none;
    flex: 1;
    font-weight: 500;
}

.notice-item a:hover {
    color: var(--primary-blue);
}

.badge-important {
    color: #FF6900;
    font-weight: 700;
    margin-right: 8px;
}

.date {
    color: #666;
    font-size: 14px;
}

.more-link {
    color: var(--primary-blue);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.more-link:hover {
    text-decoration: underline;
}

/* 뉴스 슬라이더 스타일 */
.news-controls {
    display: flex;
    align-items: center;
    gap: 12px;
}

.news-nav-btn {
    background: #1976D2;
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(25, 118, 210, 0.3);
}

.news-nav-btn:hover {
    background: #1565C0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(25, 118, 210, 0.4);
}

.news-nav-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.news-slider-container {
    overflow: hidden;
    border-radius: 12px;
    margin-bottom: 20px;
}

.news-slider {
    display: flex;
    transition: transform 0.5s ease;
    gap: 20px;
}

.news-card {
    padding: 24px;
    width: calc(33.333% - 14px);
    min-width: calc(33.333% - 14px);
    max-width: calc(33.333% - 14px);
    flex-shrink: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.news-card h4 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 12px;
}

.news-card h4 a {
    color: #333;
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.news-card h4 a:hover {
    color: var(--primary-blue);
}

.news-card p {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 16px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.news-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #999;
    padding-top: 12px;
    border-top: 1px solid #E5E5E7;
}

.news-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
}

.news-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #ddd;
    cursor: pointer;
    transition: all 0.3s ease;
}

.news-dot.active {
    background: #1976D2;
    transform: scale(1.2);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.section-header h3 {
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

@media (max-width: 768px) {
    .news-card {
        width: calc(100% - 20px);
        min-width: calc(100% - 20px);
        max-width: calc(100% - 20px);
    }
    
    .news-controls {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .news-nav-btn {
        width: 36px;
        height: 36px;
    }
}

/* 공지사항 모달 스타일 */
.notice-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s ease;
}

.notice-modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease;
}

.notice-modal-header {
    background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
    color: white;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notice-modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.notice-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.3s ease;
}

.notice-modal-close:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.notice-modal-body {
    padding: 30px;
    max-height: 60vh;
    overflow-y: auto;
}

.notice-modal-title {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
    line-height: 1.4;
}

.notice-modal-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #E5E5E7;
    font-size: 14px;
    color: #666;
}

.notice-modal-content-text {
    line-height: 1.8;
    color: #333;
    font-size: 16px;
}

.notice-modal-content-text img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 16px 0;
}

.notice-modal-loading {
    text-align: center;
    padding: 60px 30px;
    color: #666;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 768px) {
    .notice-modal-content {
        width: 95%;
        margin: 10% auto;
        max-height: 85vh;
    }
    
    .notice-modal-header {
        padding: 16px 20px;
    }
    
    .notice-modal-header h3 {
        font-size: 18px;
    }
    
    .notice-modal-body {
        padding: 20px;
    }
    
    .notice-modal-title {
        font-size: 20px;
    }
    
    .notice-modal-meta {
        flex-direction: column;
        gap: 8px;
    }
}

</style>

<!-- 공지사항 모달 -->
<div id="noticeModal" class="notice-modal">
    <div class="notice-modal-content">
        <div class="notice-modal-header">
            <h3>공지사항</h3>
            <button class="notice-modal-close" onclick="closeNoticeModal()">&times;</button>
        </div>
        <div class="notice-modal-body">
            <div id="noticeModalContent" class="notice-modal-loading">
                <div style="display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #1976D2; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p style="margin-top: 16px;">로딩 중...</p>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
function openNoticeModal(noticeId) {
    const modal = document.getElementById('noticeModal');
    const content = document.getElementById('noticeModalContent');
    
    // 모달 표시
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // 로딩 상태로 초기화
    content.innerHTML = `
        <div class="notice-modal-loading">
            <div style="display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #1976D2; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 16px;">로딩 중...</p>
        </div>
    `;
    
    // AJAX로 공지사항 내용 가져오기
    fetch(`ajax/get_notice.php?id=${noticeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notice = data.notice;
                const importantBadge = notice.is_important == 1 ? '<span style="color: #FF6900; font-weight: 700; margin-right: 8px;">[중요]</span>' : '';
                
                content.innerHTML = `
                    <div class="notice-modal-title">
                        ${importantBadge}${notice.title}
                    </div>
                    <div class="notice-modal-meta">
                        <span>작성자: ${notice.writer || '관리자'}</span>
                        <span>작성일: ${notice.created_at}</span>
                        <span>조회수: ${notice.view_count || 0}</span>
                    </div>
                    <div class="notice-modal-content-text">
                        ${notice.content}
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <p>${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #666;">
                    <p>공지사항을 불러오는 중 오류가 발생했습니다.</p>
                </div>
            `;
        });
}

function closeNoticeModal() {
    const modal = document.getElementById('noticeModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// 모달 외부 클릭시 닫기
document.getElementById('noticeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeNoticeModal();
    }
});

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeNoticeModal();
    }
});

// 뉴스 슬라이더 기능
let currentNewsSlide = 0;
let newsPerView = window.innerWidth <= 768 ? 1 : 3;
let totalNewsSlides = 0;

function initNewsSlider() {
    const newsCards = document.querySelectorAll('.news-card');
    newsPerView = window.innerWidth <= 768 ? 1 : 3;
    totalNewsSlides = Math.ceil(newsCards.length / newsPerView);
    
    // 도트 생성
    const dotsContainer = document.getElementById('newsDots');
    dotsContainer.innerHTML = '';
    
    for (let i = 0; i < totalNewsSlides; i++) {
        const dot = document.createElement('div');
        dot.className = `news-dot ${i === 0 ? 'active' : ''}`;
        dot.onclick = () => goToNewsSlide(i);
        dotsContainer.appendChild(dot);
    }
    
    updateNewsButtons();
}

function slideNews(direction) {
    const slider = document.getElementById('newsSlider');
    const cardWidth = slider.children[0].offsetWidth + 20; // 카드 너비 + gap
    
    currentNewsSlide += direction;
    
    // 경계 처리
    if (currentNewsSlide < 0) {
        currentNewsSlide = totalNewsSlides - 1;
    } else if (currentNewsSlide >= totalNewsSlides) {
        currentNewsSlide = 0;
    }
    
    const translateX = -(currentNewsSlide * cardWidth * newsPerView);
    slider.style.transform = `translateX(${translateX}px)`;
    
    updateNewsButtons();
    updateNewsDots();
}

function goToNewsSlide(slideIndex) {
    const slider = document.getElementById('newsSlider');
    const cardWidth = slider.children[0].offsetWidth + 20;
    
    currentNewsSlide = slideIndex;
    const translateX = -(currentNewsSlide * cardWidth * newsPerView);
    slider.style.transform = `translateX(${translateX}px)`;
    
    updateNewsButtons();
    updateNewsDots();
}

function updateNewsButtons() {
    const prevBtn = document.getElementById('newsPrevBtn');
    const nextBtn = document.getElementById('newsNextBtn');
    
    // 무한 슬라이드이므로 버튼은 항상 활성화
    prevBtn.disabled = false;
    nextBtn.disabled = false;
}

function updateNewsDots() {
    const dots = document.querySelectorAll('.news-dot');
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentNewsSlide);
    });
}

// 자동 슬라이드 (선택사항)
function startNewsAutoSlide() {
    setInterval(() => {
        slideNews(1);
    }, 5000); // 5초마다 자동 슬라이드
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initNewsSlider();
    // 자동 슬라이드 시작 (필요에 따라 주석 해제)
    // startNewsAutoSlide();
});

// 창 크기 변경 시 슬라이더 재초기화
window.addEventListener('resize', function() {
    setTimeout(() => {
        initNewsSlider();
        goToNewsSlide(0); // 첫 번째 슬라이드로 리셋
    }, 250);
});

// 제품 확장 기능
function toggleProductExpansion() {
    const expandedRows = document.getElementById('productRowsExpanded');
    const expandContainer = document.getElementById('productExpandContainer');
    
    if (expandedRows.classList.contains('show')) {
        // 접기
        expandedRows.classList.remove('show');
        expandContainer.style.display = 'block';
    } else {
        // 펼치기
        expandedRows.classList.add('show');
        expandContainer.style.display = 'none';
    }
}
</script>

<?php include 'tail.php'; ?>