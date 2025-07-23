<?php
require_once 'db.php';
$currentPage = 'products';
$pageTitle = '제품소개';
$additionalCSS = [];
require_once 'head.php';

// 파라미터 처리
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$view_type = isset($_GET['view']) ? $_GET['view'] : 'tile'; // tile 또는 list
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20; // 페이지당 제품 수
if (!in_array($per_page, [10, 20, 30, 50])) {
    $per_page = 20; // 기본값
}
$offset = ($page - 1) * $per_page;

// 카테고리 클릭 수 증가 (전체가 아닌 경우에만)
if ($category_filter !== 'all' && empty($search) && $page == 1) {
    try {
        $update_click = $pdo->prepare("UPDATE product_categories SET click_count = click_count + 1 WHERE category_code = ?");
        $update_click->execute([$category_filter]);
    } catch (Exception $e) {
        // 오류 무시 (click_count 컬럼이 없을 수도 있음)
    }
}

// 카테고리 목록 가져오기
$stmt = $pdo->query("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY display_order");
$categories = $stmt->fetchAll();

// 현재 선택된 카테고리 정보 가져오기
$categoryInfo = null;
if ($category_filter !== 'all') {
    foreach ($categories as $cat) {
        if ($cat['category_code'] === $category_filter) {
            $categoryInfo = [
                'code' => $cat['category_code'],
                'name' => $cat['category_name']
            ];
            break;
        }
    }
}

// 전체 제품 수 계산 (페이지네이션용)
$where_clause = "p.is_active = 1";
$params = [];

if ($category_filter !== 'all') {
    $where_clause .= " AND p.category_code = ?";
    $params[] = $category_filter;
}

if ($search !== '') {
    $where_clause .= " AND (p.product_name LIKE ? OR p.specifications LIKE ? OR p.description LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// 전체 제품 수 조회
$count_sql = "SELECT COUNT(*) FROM products p WHERE {$where_clause}";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// 제품 목록 가져오기
$sql = "SELECT p.*, pc.category_name 
        FROM products p 
        JOIN product_categories pc ON p.category_code = pc.category_code 
        WHERE {$where_clause}
        ORDER BY p.id DESC
        LIMIT {$per_page} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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
    gap: 0;
    background: white;
    border-radius: 28px;
    padding: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.view-btn {
    padding: 10px 20px;
    background: transparent;
    border: none;
    border-radius: 24px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    font-size: 14px;
}

.view-btn:hover {
    color: var(--primary-blue);
}

.view-btn.active {
    background: var(--primary-blue);
    color: white;
    box-shadow: 0 2px 8px rgba(20, 40, 160, 0.2);
}

.view-btn svg {
    width: 16px;
    height: 16px;
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

/* 검색 폼 스타일 */
.products-search {
    max-width: 1200px;
    margin: 0 auto 30px;
    padding: 0 20px;
}

.search-form {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 16px;
}

.search-input {
    flex: 1;
    padding: 12px 20px;
    border: 2px solid #E5E5E7;
    border-radius: 28px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(20, 40, 160, 0.1);
}

.search-btn {
    padding: 12px 32px;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 28px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: #0F1F7A;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
}

.clear-search {
    padding: 12px 24px;
    background: #F8F9FA;
    color: #666;
    text-decoration: none;
    border-radius: 28px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.clear-search:hover {
    background: #E5E5E7;
    color: #333;
}

.search-result {
    text-align: center;
    color: #666;
    font-size: 16px;
}

.search-result strong {
    color: var(--primary-blue);
}

/* 페이지네이션 스타일 */
.pagination-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    margin-top: 40px;
    padding: 20px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}

.page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    background: white;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    color: #333;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.page-link:hover {
    background: #F8F9FA;
    border-color: var(--primary-blue);
    color: var(--primary-blue);
}

.page-link.active {
    background: var(--primary-blue);
    border-color: var(--primary-blue);
    color: white;
}

/* 페이지당 제품 수 선택 */
.per-page-selector {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #666;
}

.per-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 10px;
    background: white;
    border: 1px solid #E5E5E7;
    border-radius: 6px;
    color: #333;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.per-page-link:hover {
    background: #F8F9FA;
    border-color: var(--primary-blue);
    color: var(--primary-blue);
}

.per-page-link.active {
    background: var(--primary-blue);
    border-color: var(--primary-blue);
    color: white;
}

/* 모바일 반응형 */
@media (max-width: 768px) {
    .search-form {
        flex-wrap: wrap;
    }
    
    .search-input {
        width: 100%;
    }
    
    .search-btn,
    .clear-search {
        flex: 1;
    }
    
    .pagination {
        flex-wrap: wrap;
        gap: 4px;
    }
    
    .page-link {
        min-width: 36px;
        height: 36px;
        font-size: 14px;
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
            <a href="?category=<?php echo $category_filter; ?>&view=tile&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>" class="view-btn <?php echo $view_type === 'tile' ? 'active' : ''; ?>">
                <svg viewBox="0 0 16 16" fill="currentColor">
                    <rect x="1" y="1" width="6" height="6" rx="1"/>
                    <rect x="9" y="1" width="6" height="6" rx="1"/>
                    <rect x="1" y="9" width="6" height="6" rx="1"/>
                    <rect x="9" y="9" width="6" height="6" rx="1"/>
                </svg>
                타일뷰
            </a>
            <a href="?category=<?php echo $category_filter; ?>&view=list&search=<?php echo urlencode($search); ?>&per_page=<?php echo $per_page; ?>" class="view-btn <?php echo $view_type === 'list' ? 'active' : ''; ?>">
                <svg viewBox="0 0 16 16" fill="currentColor">
                    <rect x="1" y="2" width="14" height="2" rx="1"/>
                    <rect x="1" y="7" width="14" height="2" rx="1"/>
                    <rect x="1" y="12" width="14" height="2" rx="1"/>
                </svg>
                리스트뷰
            </a>
        </div>
    </div>

    <!-- 검색 폼 -->
    <div class="products-search">
        <form method="get" action="" class="search-form">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view_type); ?>">
            <input type="hidden" name="per_page" value="<?php echo htmlspecialchars($per_page); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="제품명, 규격, 설명으로 검색" class="search-input">
            <button type="submit" class="search-btn">검색</button>
            <?php if ($search): ?>
                <a href="?category=<?php echo $category_filter; ?>&view=<?php echo $view_type; ?>&per_page=<?php echo $per_page; ?>" class="clear-search">검색 초기화</a>
            <?php endif; ?>
        </form>
        <?php if ($search): ?>
            <p class="search-result">
                "<strong><?php echo htmlspecialchars($search); ?></strong>" 검색 결과: <?php echo $total_products; ?>개
            </p>
        <?php endif; ?>
    </div>

    <div class="products-categories">
        <a href="?category=all&view=<?php echo $view_type; ?>&search=<?php echo urlencode($search); ?>" class="category-btn <?php echo $category_filter === 'all' ? 'active' : ''; ?>">전체</a>
        <?php foreach ($categories as $category): ?>
            <a href="?category=<?php echo $category['category_code']; ?>&view=<?php echo $view_type; ?>&search=<?php echo urlencode($search); ?>" 
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
                        <?php if ($categoryInfo && $categoryInfo['code'] === 'rebar' && preg_match('/단위중량:\s*([\d.]+)kg\/m/', $product['specifications'], $matches)): ?>
                            <p class="unit-weight" style="color: #F57C00; font-weight: 600; font-size: 14px;">
                                단위중량: <?php echo $matches[1]; ?>kg/m
                            </p>
                        <?php endif; ?>
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
                        <?php if ($categoryInfo && $categoryInfo['code'] === 'rebar' && preg_match('/단위중량:\s*([\d.]+)kg\/m/', $product['specifications'], $matches)): ?>
                            <p class="unit-weight" style="color: #F57C00; font-weight: 600; font-size: 14px; margin: 5px 0;">
                                단위중량: <?php echo $matches[1]; ?>kg/m
                            </p>
                        <?php endif; ?>
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
    
    <!-- 페이지네이션 -->
    <?php if ($total_pages > 1 || $total_products > 0): ?>
    <div class="pagination-wrapper">
        <div class="pagination">
            <?php if ($total_pages > 1): ?>
            <?php
            $query_params = [
                'category' => $category_filter,
                'view' => $view_type,
                'search' => $search,
                'per_page' => $per_page
            ];
            
            // 첫 페이지
            if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => 1])); ?>" class="page-link">처음</a>
                <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $page - 1])); ?>" class="page-link">이전</a>
            <?php endif; ?>
            
            <?php
            // 페이지 번호 표시
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $start_page + 4);
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $i])); ?>" 
                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php // 마지막 페이지
            if ($page < $total_pages): ?>
                <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $page + 1])); ?>" class="page-link">다음</a>
                <a href="?<?php echo http_build_query(array_merge($query_params, ['page' => $total_pages])); ?>" class="page-link">마지막</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- 페이지당 제품 수 선택 -->
        <div class="per-page-selector">
            <span>페이지당</span>
            <?php
            $per_page_options = [10, 20, 30, 50];
            foreach ($per_page_options as $option):
                $query_params_per_page = [
                    'category' => $category_filter,
                    'view' => $view_type,
                    'search' => $search,
                    'per_page' => $option,
                    'page' => 1 // 페이지당 수 변경시 첫 페이지로
                ];
            ?>
                <a href="?<?php echo http_build_query($query_params_per_page); ?>" 
                   class="per-page-link <?php echo $per_page == $option ? 'active' : ''; ?>">
                    <?php echo $option; ?>
                </a>
            <?php endforeach; ?>
            <span>개</span>
        </div>
    </div>
    <?php endif; ?>
</section>

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
// 현재 선택된 제품 정보 저장
let selectedProduct = null;

// 견적문의 버튼 클릭 이벤트 처리
document.addEventListener('DOMContentLoaded', function() {
    // 모든 견적문의 버튼에 이벤트 리스너 추가
    const quoteButtons = document.querySelectorAll('.product-btn');
    quoteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // 제품 정보 가져오기
            const productCard = this.closest('.product-card') || this.closest('.product-list-item');
            const productLink = productCard.querySelector('a') || productCard;
            const productId = productLink.href.match(/id=(\d+)/)[1];
            const productName = productCard.querySelector('h3').textContent;
            const productSpecs = productCard.querySelector('.specs, .product-list-specs').textContent.replace('규격: ', '');
            
            // 선택된 제품 정보 저장
            selectedProduct = {
                id: productId,
                name: productName,
                specifications: productSpecs
            };
            
            // 견적 카트에 추가
            addToQuoteCart(selectedProduct);
            
            // 모달에 제품명 표시
            document.getElementById('modalProductName').textContent = productName;
            
            // 모달 표시
            document.getElementById('quoteModal').style.display = 'block';
        });
    });
    
    // 링크 클릭 방지 (견적문의 버튼이 링크 안에 있을 때)
    const productLinks = document.querySelectorAll('.product-card, .product-list-item');
    productLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (e.target.classList.contains('product-btn') || e.target.closest('.product-btn')) {
                e.preventDefault();
            }
        });
    });
});

// 견적 카트에 제품 추가
function addToQuoteCart(product) {
    // 세션 스토리지에서 기존 카트 가져오기
    let quoteCart = JSON.parse(sessionStorage.getItem('quoteCart') || '[]');
    
    // 중복 확인
    const existingIndex = quoteCart.findIndex(item => item.id === product.id);
    if (existingIndex === -1) {
        // 새 제품 추가
        quoteCart.push({
            id: product.id,
            name: product.name,
            specifications: product.specifications,
            quantity: 1,
            addedAt: new Date().toISOString()
        });
    } else {
        // 이미 있는 제품은 수량 증가
        quoteCart[existingIndex].quantity += 1;
    }
    
    // 세션 스토리지에 저장
    sessionStorage.setItem('quoteCart', JSON.stringify(quoteCart));
    
    // 카트 카운트 업데이트
    updateCartCount();
}

// 카트 카운트 업데이트
function updateCartCount() {
    const quoteCart = JSON.parse(sessionStorage.getItem('quoteCart') || '[]');
    const cartCount = quoteCart.reduce((sum, item) => sum + item.quantity, 0);
    
    // 상단 카트 아이콘의 카운트 업데이트
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
updateCartCount();
</script>

<?php include 'tail.php'; ?>