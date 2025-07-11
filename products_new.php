<?php
$currentPage = 'products';
$pageTitle = '제품소개';
include 'head.php';
include 'db.php';

// 카테고리 필터
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$view_type = isset($_GET['view']) ? $_GET['view'] : 'tile'; // tile 또는 list

// 카테고리 목록 가져오기
$stmt = $pdo->query("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY display_order");
$categories = $stmt->fetchAll();

// 제품 목록 가져오기
if ($category_filter === 'all') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE is_active = 1 ORDER BY id DESC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_code = ? AND is_active = 1 ORDER BY id DESC");
    $stmt->execute([$category_filter]);
}
$products = $stmt->fetchAll();
?>

<style>
/* Products page specific styles */
.products-header {
    background: linear-gradient(135deg, #E8F0FE 0%, #F8F9FA 100%);
    padding: 60px 0;
    text-align: center;
}

.products-header h2 {
    font-size: 36px;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 12px;
}

.products-header p {
    font-size: 18px;
    color: #666;
}

.products-section {
    padding: 60px 0;
    background: #F8F9FA;
}

.products-controls {
    max-width: 1200px;
    margin: 0 auto 30px;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.view-toggle {
    display: flex;
    gap: 10px;
}

.view-btn {
    padding: 8px 16px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.view-btn.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

.products-categories {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 40px;
    flex-wrap: wrap;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 20px;
}

.category-btn {
    padding: 10px 20px;
    background: white;
    border: 2px solid #E5E5E7;
    border-radius: 28px;
    font-size: 14px;
    font-weight: 500;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    text-decoration: none;
    display: inline-block;
}

.category-btn:hover,
.category-btn.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

/* 타일 뷰 스타일 */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.product-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    text-decoration: none;
    display: block;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.product-image {
    width: 100%;
    height: 200px;
    background: #F0F0F0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: #999;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    padding: 24px;
}

.product-info h3 {
    font-size: 20px;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}

.product-info .specs {
    font-size: 14px;
    color: #666;
    margin-bottom: 16px;
}

.product-info .description {
    font-size: 14px;
    color: #999;
    line-height: 1.6;
    margin-bottom: 20px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-btn {
    display: inline-block;
    padding: 10px 24px;
    background: var(--primary-blue);
    color: white;
    text-decoration: none;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.product-btn:hover {
    background: #0F1F7A;
}

/* 리스트 뷰 스타일 */
.products-list {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.product-list-item {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    display: flex;
    gap: 24px;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.product-list-item:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.product-list-image {
    width: 150px;
    height: 150px;
    background: #F0F0F0;
    border-radius: 8px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: #999;
    overflow: hidden;
}

.product-list-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-list-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.product-list-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 12px;
}

.product-list-title {
    font-size: 22px;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.product-list-specs {
    font-size: 16px;
    color: #666;
    margin-bottom: 12px;
}

.product-list-description {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 16px;
    flex: 1;
}

.product-list-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.product-stock {
    font-size: 14px;
    color: #28a745;
}

.product-stock.out-of-stock {
    color: #dc3545;
}

/* Responsive */
@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .products-categories {
        padding: 0 20px;
    }
    
    .product-list-item {
        flex-direction: column;
    }
    
    .product-list-image {
        width: 100%;
        height: 200px;
    }
    
    .products-controls {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<section class="products-header">
    <h2>제품소개</h2>
    <p>충남스틸이 공급하는 고품질 철강 제품을 소개합니다</p>
</section>

<section class="products-section">
    <div class="products-controls">
        <div></div>
        <div class="view-toggle">
            <a href="?category=<?php echo $category_filter; ?>&view=tile" class="view-btn <?php echo $view_type === 'tile' ? 'active' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <rect x="1" y="1" width="5" height="5"/>
                    <rect x="9" y="1" width="5" height="5"/>
                    <rect x="1" y="9" width="5" height="5"/>
                    <rect x="9" y="9" width="5" height="5"/>
                </svg>
                타일형
            </a>
            <a href="?category=<?php echo $category_filter; ?>&view=list" class="view-btn <?php echo $view_type === 'list' ? 'active' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <rect x="1" y="2" width="14" height="2"/>
                    <rect x="1" y="7" width="14" height="2"/>
                    <rect x="1" y="12" width="14" height="2"/>
                </svg>
                리스트형
            </a>
        </div>
    </div>

    <div class="products-categories">
        <a href="?view=<?php echo $view_type; ?>" class="category-btn <?php echo $category_filter === 'all' ? 'active' : ''; ?>">전체</a>
        <?php foreach ($categories as $category): ?>
            <a href="?category=<?php echo $category['category_code']; ?>&view=<?php echo $view_type; ?>" 
               class="category-btn <?php echo $category_filter === $category['category_code'] ? 'active' : ''; ?>">
                <?php echo escape($category['category_name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($view_type === 'tile'): ?>
        <!-- 타일 뷰 -->
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <div class="product-image">
                        <?php if ($product['main_image']): ?>
                            <img src="<?php echo escape($product['main_image']); ?>" alt="<?php echo escape($product['product_name']); ?>">
                        <?php else: ?>
                            <?php
                            // 카테고리별 아이콘
                            $icons = [
                                'rebar' => '🔩',
                                'h-beam' => '🏗️',
                                'steel-plate' => '📐',
                                'metal-lath' => '🔲',
                                'light-h-beam' => '🏢',
                                'i-beam' => '📍',
                                'angle' => '📏',
                                'channel' => '🔨',
                                'round-bar' => '⭕',
                                'flat-bar' => '➖',
                                'c-beam' => '🔧',
                                'deck-plate' => '🏗️',
                                'square-pipe' => '⬜',
                                'round-pipe' => '⚪',
                                'rail' => '🚂',
                                'sheet-pile' => '🔱',
                                'stainless' => '✨'
                            ];
                            echo $icons[$product['category_code']] ?? '📦';
                            ?>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3><?php echo escape($product['product_name']); ?></h3>
                        <p class="specs"><?php echo escape($product['specifications']); ?></p>
                        <p class="description"><?php echo escape($product['description']); ?></p>
                        <span class="product-btn">견적문의</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- 리스트 뷰 -->
        <div class="products-list">
            <?php foreach ($products as $product): ?>
                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="product-list-item">
                    <div class="product-list-image">
                        <?php if ($product['main_image']): ?>
                            <img src="<?php echo escape($product['main_image']); ?>" alt="<?php echo escape($product['product_name']); ?>">
                        <?php else: ?>
                            <?php
                            // 카테고리별 아이콘
                            $icons = [
                                'rebar' => '🔩',
                                'h-beam' => '🏗️',
                                'steel-plate' => '📐',
                                'metal-lath' => '🔲',
                                'light-h-beam' => '🏢',
                                'i-beam' => '📍',
                                'angle' => '📏',
                                'channel' => '🔨',
                                'round-bar' => '⭕',
                                'flat-bar' => '➖',
                                'c-beam' => '🔧',
                                'deck-plate' => '🏗️',
                                'square-pipe' => '⬜',
                                'round-pipe' => '⚪',
                                'rail' => '🚂',
                                'sheet-pile' => '🔱',
                                'stainless' => '✨'
                            ];
                            echo $icons[$product['category_code']] ?? '📦';
                            ?>
                        <?php endif; ?>
                    </div>
                    <div class="product-list-content">
                        <div class="product-list-header">
                            <h3 class="product-list-title"><?php echo escape($product['product_name']); ?></h3>
                        </div>
                        <p class="product-list-specs">규격: <?php echo escape($product['specifications']); ?></p>
                        <p class="product-list-description"><?php echo escape($product['description']); ?></p>
                        <div class="product-list-footer">
                            <span class="product-stock <?php echo $product['stock_status'] === 'out_of_stock' ? 'out-of-stock' : ''; ?>">
                                <?php 
                                switch($product['stock_status']) {
                                    case 'in_stock': echo '재고 있음'; break;
                                    case 'out_of_stock': echo '재고 없음'; break;
                                    case 'on_order': echo '주문 가능'; break;
                                }
                                ?>
                            </span>
                            <span class="product-btn">견적문의</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include 'tail.php'; ?>