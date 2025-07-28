<?php
require_once 'db.php';
$currentPage = 'products';
$pageTitle = '제품소개';
$additionalCSS = [];
require_once 'head.php';

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
    'steel-plate' => '📋',
    'sheet-pile' => '🔨',
    'rail' => '🚂',
    'conduit-pipe' => '🔌',
    'pressure-pipe' => '🔧',
    'temporary-deck' => '🚧',
    'scaffold-pipe' => '🏗️',
    'structural-pipe' => '🏭',
    'steel-pipe-pile' => '🔩',
    'ks-pipe' => '🔧',
    'bs-pipe' => '🔧',
    'rebar' => '🔩'
];

// 카테고리 목록 가져오기 (click_count 포함)
try {
    $stmt = $pdo->query("
        SELECT pc.*, COUNT(p.id) as product_count,
               COALESCE(pc.click_count, 0) as click_count
        FROM product_categories pc
        LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
        WHERE pc.is_active = 1
        GROUP BY pc.id
        ORDER BY pc.display_order ASC, pc.category_name ASC
    ");
} catch (Exception $e) {
    // click_count 컬럼이 없는 경우 대체 쿼리
    $stmt = $pdo->query("
        SELECT pc.*, COUNT(p.id) as product_count,
               0 as click_count
        FROM product_categories pc
        LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
        WHERE pc.is_active = 1
        GROUP BY pc.id
        ORDER BY pc.display_order ASC, pc.category_name ASC
    ");
}
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 클릭 수 기준으로 상위 3개 카테고리 찾기
$categories_by_clicks = $categories;
usort($categories_by_clicks, function($a, $b) {
    return $b['click_count'] - $a['click_count'];
});
$top_3_categories = array_slice(array_column($categories_by_clicks, 'category_code'), 0, 3);
?>

<style>
.products-header {
    text-align: center;
    padding: 60px 20px 40px;
    background: #f8f9fa;
}

.products-header h2 {
    font-size: 36px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
}

.products-header p {
    font-size: 18px;
    color: #666;
}

.categories-section {
    padding: 60px 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

.category-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: block;
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.category-icon {
    font-size: 64px;
    margin-bottom: 16px;
    display: block;
}

.category-name {
    font-size: 20px;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}

.category-count {
    font-size: 16px;
    color: #666;
}

.category-description {
    font-size: 14px;
    color: #999;
    margin-top: 12px;
    line-height: 1.5;
}

/* 대표 카테고리 강조 */
.category-card.featured {
    background: linear-gradient(135deg, #1428A0 0%, #0F1F7A 100%);
    color: white;
}

.category-card.featured .category-name,
.category-card.featured .category-count,
.category-card.featured .category-description {
    color: white;
}

.category-card.featured:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(20, 40, 160, 0.3);
}

/* 빈 카테고리 스타일 */
.category-card.empty {
    opacity: 0.6;
}

.category-card.empty:hover {
    transform: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    cursor: not-allowed;
}

.category-popular {
    font-size: 13px;
    color: #ff6b6b;
    margin-top: 8px;
    font-weight: 600;
}

/* 철근 카테고리 특별 스타일 */
.category-card[href*="rebar_quote"] {
    background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
    color: white;
}

.category-card[href*="rebar_quote"] .category-name,
.category-card[href*="rebar_quote"] .category-count,
.category-card[href*="rebar_quote"] .category-description {
    color: white;
}

.category-card[href*="rebar_quote"]:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(33, 150, 243, 0.3);
}

@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 16px;
    }
    
    .category-card {
        padding: 24px 16px;
    }
    
    .category-icon {
        font-size: 48px;
    }
    
    .category-name {
        font-size: 16px;
    }
    
    .category-count {
        font-size: 14px;
    }
}
</style>

<section class="products-header">
    <h2>판매제품</h2>
    <p>충남스틸이 공급하는 고품질 철강 제품을 카테고리별로 확인하세요</p>
</section>

<section class="categories-section">
    <div class="categories-grid">
        <?php foreach ($categories as $index => $category): ?>
            <?php 
            $isEmpty = $category['product_count'] == 0;
            $isFeatured = in_array($category['category_code'], $top_3_categories);
            ?>
            <?php 
            // 철근 카테고리는 특별한 견적 페이지로 이동
            $categoryLink = $isEmpty ? '#' : 
                ($category['category_code'] == 'rebar' ? 'http://211.248.112.67:1112/products_new.php?category=rebar&view=tile&search=' : 
                'products_new.php?category=' . $category['category_code'] . '&view=tile');
            ?>
            <a href="<?php echo $categoryLink; ?>" 
               class="category-card <?php echo $isEmpty ? 'empty' : ''; ?> <?php echo $isFeatured ? 'featured' : ''; ?>"
               <?php echo $isEmpty ? 'onclick="return false;"' : ''; ?>>
                <span class="category-icon">
                    <?php echo isset($icons[$category['category_code']]) ? $icons[$category['category_code']] : '📦'; ?>
                </span>
                <h3 class="category-name"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                <?php if ($category['category_code'] == 'rebar'): ?>
                    <p class="category-count" style="color: #ff6b6b; font-weight: 600;">📊 견적 계산기</p>
                <?php else: ?>
                    <p class="category-count">제품 <?php echo $category['product_count']; ?>개</p>
                <?php endif; ?>
                <?php if ($isFeatured && $category['click_count'] > 0): ?>
                    <p class="category-popular">🔥 인기 카테고리</p>
                <?php endif; ?>
                <?php if (isset($category['description']) && $category['description']): ?>
                    <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once 'tail.php'; ?>