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
    $stmt = $pdo->prepare("SELECT p.*, pc.category_name FROM products p 
                           JOIN product_categories pc ON p.category_code = pc.category_code 
                           WHERE p.is_active = 1 ORDER BY p.id DESC");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT p.*, pc.category_name FROM products p 
                           JOIN product_categories pc ON p.category_code = pc.category_code 
                           WHERE p.category_code = ? AND p.is_active = 1 ORDER BY p.id DESC");
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
    text-decoration: none;
    color: #333;
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
}

.category-btn:hover,
.category-btn.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

/* Tile View */
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
}

.product-info .category-badge {
    display: inline-block;
    padding: 4px 12px;
    background: #E8F0FE;
    color: var(--primary-blue);
    border-radius: 16px;
    font-size: 12px;
    margin-bottom: 12px;
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

/* List View */
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
    display: flex;
    align-items: center;
    gap: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.product-list-item:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.product-list-image {
    width: 120px;
    height: 120px;
    background: #F0F0F0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: #999;
    flex-shrink: 0;
}

.product-list-info {
    flex: 1;
}

.product-list-info h3 {
    font-size: 20px;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}

.product-list-info .specs {
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
}

.product-list-info .description {
    font-size: 14px;
    color: #999;
    line-height: 1.6;
}

.product-list-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: flex-end;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    max-width: 600px;
    margin: 0 auto;
}

.empty-state-icon {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 24px;
}

.empty-state h3 {
    font-size: 24px;
    color: #333;
    margin-bottom: 12px;
}

.empty-state p {
    font-size: 16px;
    color: #666;
}

/* Responsive */
@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .products-categories {
        padding: 0 20px;
    }
    
    .products-controls {
        flex-direction: column;
        gap: 20px;
        align-items: stretch;
    }
    
    .view-toggle {
        justify-content: center;
    }
    
    .product-list-item {
        flex-direction: column;
        text-align: center;
    }
    
    .product-list-actions {
        align-items: center;
        width: 100%;
    }
    
    .product-btn {
        width: 100%;
        text-align: center;
    }
}
</style>

<section class="products-header">
    <h2>제품소개</h2>
    <p>충남스틸이 공급하는 고품질 철강 제품을 소개합니다</p>
</section>

<section class="products-section">
    <!-- View Toggle -->
    <div class="products-controls">
        <div class="view-toggle">
            <a href="?category=<?php echo $category_filter; ?>&view=tile" 
               class="view-btn <?php echo $view_type === 'tile' ? 'active' : ''; ?>">
                <span>⊞</span> 타일뷰
            </a>
            <a href="?category=<?php echo $category_filter; ?>&view=list" 
               class="view-btn <?php echo $view_type === 'list' ? 'active' : ''; ?>">
                <span>☰</span> 리스트뷰
            </a>
        </div>
    </div>

    <!-- Category Buttons -->
    <div class="products-categories">
        <a href="?view=<?php echo $view_type; ?>" 
           class="category-btn <?php echo $category_filter === 'all' ? 'active' : ''; ?>">전체</a>
        <?php foreach ($categories as $category): ?>
            <?php 
                $product_count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_code = ? AND is_active = 1");
                $product_count->execute([$category['category_code']]);
                $count = $product_count->fetchColumn();
            ?>
            <a href="?category=<?php echo $category['category_code']; ?>&view=<?php echo $view_type; ?>" 
               class="category-btn <?php echo $category_filter === $category['category_code'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($category['category_name']); ?> (<?php echo $count; ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>제품이 없습니다</h3>
            <p>선택하신 카테고리에 등록된 제품이 없습니다.</p>
        </div>
    <?php else: ?>
        <?php if ($view_type === 'tile'): ?>
            <!-- Tile View -->
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php if (isset($product['image_url']) && $product['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                 class="product-image" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="product-image">
                                <?php 
                                // 카테고리별 아이콘
                                $icons = [
                                    'h-beam' => '🏗️',
                                    'light-h-beam' => '🏢',
                                    'i-beam' => '📍',
                                    'angle' => '📏',
                                    'unequal-angle' => '📐',
                                    'channel' => '🔨',
                                    'c-beam' => '🔧',
                                    'round-bar' => '⭕',
                                    'flat-bar' => '➖',
                                    'deck-plate' => '🏗️',
                                    'square-pipe' => '⬜',
                                    'checkered-plate' => '🔲',
                                    'expanded-metal' => '🔷',
                                    'grating' => '⚏',
                                    'color-steel' => '🎨',
                                    'galvanized' => '✨',
                                    'cold-rolled' => '❄️',
                                    'hot-rolled' => '🔥',
                                    'conduit-pipe' => '🔌',
                                    'pressure-pipe' => '🔧',
                                    'temporary-deck' => '🚧',
                                    'scaffold-pipe' => '🏗️',
                                    'structural-pipe' => '🏭',
                                    'steel-pipe-pile' => '🔩',
                                    'ks-pipe' => '🔧',
                                    'bs-pipe' => '🔧',
                                    'steel-plate' => '📋',
                                    'sheet-pile' => '🔨',
                                    'rail' => '🚂'
                                ];
                                echo isset($icons[$product['category_code']]) ? $icons[$product['category_code']] : '📦';
                                ?>
                            </div>
                        <?php endif; ?>
                        <div class="product-info">
                            <div class="category-badge"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="specs"><?php echo htmlspecialchars($product['specifications']); ?></p>
                            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <a href="products_new.php?category=<?php echo $product['category_code']; ?>&view=tile" class="product-btn">제품 보기</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- List View -->
            <div class="products-list">
                <?php foreach ($products as $product): ?>
                    <div class="product-list-item">
                        <?php if (isset($product['image_url']) && $product['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                 class="product-list-image" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="product-list-image">
                                <?php 
                                // 카테고리별 아이콘
                                $icons = [
                                    'h-beam' => '🏗️',
                                    'light-h-beam' => '🏢',
                                    'i-beam' => '📍',
                                    'angle' => '📏',
                                    'unequal-angle' => '📐',
                                    'channel' => '🔨',
                                    'c-beam' => '🔧',
                                    'round-bar' => '⭕',
                                    'flat-bar' => '➖',
                                    'deck-plate' => '🏗️',
                                    'square-pipe' => '⬜',
                                    'checkered-plate' => '🔲',
                                    'expanded-metal' => '🔷',
                                    'grating' => '⚏',
                                    'color-steel' => '🎨',
                                    'galvanized' => '✨',
                                    'cold-rolled' => '❄️',
                                    'hot-rolled' => '🔥',
                                    'conduit-pipe' => '🔌',
                                    'pressure-pipe' => '🔧',
                                    'temporary-deck' => '🚧',
                                    'scaffold-pipe' => '🏗️',
                                    'structural-pipe' => '🏭',
                                    'steel-pipe-pile' => '🔩',
                                    'ks-pipe' => '🔧',
                                    'bs-pipe' => '🔧',
                                    'steel-plate' => '📋',
                                    'sheet-pile' => '🔨',
                                    'rail' => '🚂'
                                ];
                                echo isset($icons[$product['category_code']]) ? $icons[$product['category_code']] : '📦';
                                ?>
                            </div>
                        <?php endif; ?>
                        <div class="product-list-info">
                            <span class="category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="specs">규격: <?php echo htmlspecialchars($product['specifications']); ?></p>
                            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                        </div>
                        <div class="product-list-actions">
                            <a href="products_new.php?category=<?php echo $product['category_code']; ?>&view=tile" class="product-btn">제품 보기</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php include 'tail.php'; ?>