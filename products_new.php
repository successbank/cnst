<?php
session_start();
require_once 'db.php';
require_once 'includes/rebar_unit_weights.php';
$currentPage = 'products';
$pageTitle = '제품소개';
$additionalCSS = [];
require_once 'head.php';

// 관리자 권한 체크
$is_admin = isset($_SESSION['admin_id']) && $_SESSION['admin_id'];

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
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
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


/* 리스트뷰 상세내용 스타일 */
.product-detailed-info {
    margin-top: 12px;
}

.key-features-list {
    margin-top: 12px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.key-features-list .feature-item {
    display: block;
    font-size: 14px;
    color: #444;
    margin: 6px 0;
}

.certifications-info {
    margin-top: 10px;
    font-size: 13px;
    color: #666;
}

.cert-label {
    font-weight: 600;
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

/* 이미지 업로드 스타일 */
.image-upload-wrapper {
    position: relative;
}

.image-upload-btn {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: rgba(20, 40, 160, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    z-index: 10;
}

.image-upload-btn:hover {
    background: rgba(20, 40, 160, 1);
    transform: scale(1.1);
}

.image-delete-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    z-index: 10;
}

.image-delete-btn:hover {
    background: rgba(220, 53, 69, 1);
    transform: scale(1.1);
}

.upload-input {
    display: none;
}

.upload-progress {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: #333;
    z-index: 15;
}

/* 리스트뷰 이미지 업로드 스타일 */
.product-list-image .image-upload-btn {
    bottom: 5px;
    right: 5px;
    width: 35px;
    height: 35px;
    font-size: 18px;
}

.product-list-image .image-delete-btn {
    top: 5px;
    right: 5px;
    width: 25px;
    height: 25px;
    font-size: 14px;
}
</style>

<section class="products-header">
    <h2>판매제품</h2>
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

    <?php 
    // 철근 카테고리의 경우 제품명에서 규격을 추출하고 정렬
    if ($category_filter === 'rebar') {
        // 제품에 규격 정보 추가
        foreach ($products as &$product) {
            $spec = extractRebarSpec($product['product_name']);
            $product['rebar_spec'] = $spec;
            $product['unit_weight'] = $spec ? getRebarUnitWeight($spec) : null;
            
            // 규격 숫자 추출 (D10 -> 10)
            $product['spec_number'] = $spec ? intval(substr($spec, 1)) : 999;
        }
        
        // 규격 번호로 정렬
        usort($products, function($a, $b) {
            return $a['spec_number'] - $b['spec_number'];
        });
    }
    ?>
    
    <?php if ($view_type === 'tile'): ?>
        <!-- 타일 뷰 -->
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="product-card">
                    <div class="product-image image-upload-wrapper" data-product-id="<?php echo $product['id']; ?>">
                        <?php if ($product['main_image']): ?>
                            <img src="<?php echo escape($product['main_image']); ?>" alt="<?php echo escape($product['product_name']); ?>">
                            <?php if ($is_admin): ?>
                                <button class="image-delete-btn" onclick="deleteProductImage(event, <?php echo $product['id']; ?>)">×</button>
                            <?php endif; ?>
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
                        <?php if ($is_admin): ?>
                            <button class="image-upload-btn" onclick="triggerImageUpload(event, <?php echo $product['id']; ?>)">+</button>
                            <input type="file" class="upload-input" id="upload-<?php echo $product['id']; ?>" accept="image/*" onchange="uploadProductImage(event, <?php echo $product['id']; ?>)">
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3><?php echo escape($product['product_name']); ?></h3>
                        <p class="specs"><?php echo escape($product['specifications']); ?></p>
                        <?php if ($product['category_code'] === 'rebar'): ?>
                            <?php
                            // 이미 계산된 단중 사용
                            $unitWeight = isset($product['unit_weight']) ? $product['unit_weight'] : getRebarUnitWeightFromProductName($product['product_name']);
                            if ($unitWeight):
                            ?>
                            <p class="unit-weight" style="color: #F57C00; font-weight: 600; font-size: 14px;">
                                단위중량: <?php echo $unitWeight; ?>kg/m
                            </p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <p class="description"><?php echo escape($product['description']); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- 리스트 뷰 -->
        <div class="products-list">
            <?php foreach ($products as $product): ?>
                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="product-list-item">
                    <div class="product-list-image image-upload-wrapper" data-product-id="<?php echo $product['id']; ?>">
                        <?php if ($product['main_image']): ?>
                            <img src="<?php echo escape($product['main_image']); ?>" alt="<?php echo escape($product['product_name']); ?>">
                            <?php if ($is_admin): ?>
                                <button class="image-delete-btn" onclick="deleteProductImage(event, <?php echo $product['id']; ?>)">×</button>
                            <?php endif; ?>
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
                        <?php if ($is_admin): ?>
                            <button class="image-upload-btn" onclick="triggerImageUpload(event, <?php echo $product['id']; ?>)">+</button>
                            <input type="file" class="upload-input" id="upload-list-<?php echo $product['id']; ?>" accept="image/*" onchange="uploadProductImage(event, <?php echo $product['id']; ?>)">
                        <?php endif; ?>
                    </div>
                    <div class="product-list-content">
                        <div class="product-list-header">
                            <h3 class="product-list-title"><?php echo escape($product['product_name']); ?></h3>
                        </div>
                        <p class="product-list-specs">규격: <?php echo escape($product['specifications']); ?></p>
                        <?php if ($product['category_code'] === 'rebar'): ?>
                            <?php
                            // 이미 계산된 단중 사용
                            $unitWeight = isset($product['unit_weight']) ? $product['unit_weight'] : getRebarUnitWeightFromProductName($product['product_name']);
                            if ($unitWeight):
                            ?>
                            <p class="unit-weight" style="color: #F57C00; font-weight: 600; font-size: 14px; margin: 5px 0;">
                                단위중량: <?php echo $unitWeight; ?>kg/m
                            </p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <p class="product-list-description"><?php echo escape($product['description']); ?></p>
                        <?php if (!empty($product['detailed_description']) && (isset($product['show_details']) ? $product['show_details'] : true)): ?>
                        <div class="product-detailed-info">
                            <p class="detailed-description"><?php echo nl2br(escape(mb_substr($product['detailed_description'], 0, 200))) . (mb_strlen($product['detailed_description']) > 200 ? '...' : ''); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($product['key_features']) && (isset($product['show_details']) ? $product['show_details'] : true)): ?>
                        <div class="key-features-list">
                            <?php 
                            $features = array_slice(array_filter(array_map('trim', explode("\n", $product['key_features']))), 0, 3);
                            foreach ($features as $feature): 
                            ?>
                            <span class="feature-item">• <?php echo escape($feature); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($product['certifications']) && (isset($product['show_details']) ? $product['show_details'] : true)): ?>
                        <div class="certifications-info">
                            <span class="cert-label">인증:</span> <?php echo escape($product['certifications']); ?>
                        </div>
                        <?php endif; ?>
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
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php 
    // 철근 카테고리에 제품이 없는 경우 기본 철근 규격 목록 표시
    if ($category_filter === 'rebar' && empty($products)): 
    ?>
    <div class="rebar-specs-container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h3 style="text-align: center; margin-bottom: 30px; color: #2c3e50; font-size: 24px;">철근 규격별 단위중량</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <?php 
            $all_specs = getAllRebarSpecsWithWeight();
            foreach ($all_specs as $spec): 
            ?>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
                <h4 style="color: #1428A0; font-size: 20px; margin-bottom: 10px;"><?php echo $spec['spec_name']; ?></h4>
                <p style="color: #F57C00; font-weight: 600; font-size: 16px;"><?php echo $spec['unit_weight']; ?>kg/m</p>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 30px;">
            <a href="rebar_quote.php" class="btn" style="display: inline-block; padding: 15px 40px; background: #1428A0; color: white; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600;">
                철근 견적 계산하기
            </a>
        </div>
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


<script>
// 페이지 로드 이벤트
document.addEventListener('DOMContentLoaded', function() {
    // 이미지 업로드 기능 초기화 등 다른 기능들을 위한 이벤트 리스너
});

// 이미지 업로드 관련 함수들
function triggerImageUpload(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    
    // 리스트뷰인지 타일뷰인지 확인
    const isListView = event.target.closest('.product-list-item') !== null;
    const inputId = isListView ? `upload-list-${productId}` : `upload-${productId}`;
    document.getElementById(inputId).click();
}

function uploadProductImage(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    
    const file = event.target.files[0];
    if (!file) return;
    
    // 파일 유효성 검사
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('JPG, PNG, GIF, WebP 형식의 이미지만 업로드 가능합니다.');
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        alert('파일 크기는 5MB 이하여야 합니다.');
        return;
    }
    
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('image', file);
    
    // 업로드 중 표시
    const wrapper = document.querySelector(`.image-upload-wrapper[data-product-id="${productId}"]`);
    const progressDiv = document.createElement('div');
    progressDiv.className = 'upload-progress';
    progressDiv.innerHTML = '업로드 중...';
    wrapper.appendChild(progressDiv);
    
    // AJAX 업로드
    fetch('admin/ajax/upload_product_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Invalid JSON response: ' + text);
        }
    })
    .then(data => {
        if (data.success) {
            // 페이지 새로고침하여 이미지 표시
            location.reload();
        } else {
            alert(data.message || '업로드 실패');
            progressDiv.remove();
        }
    })
    .catch(error => {
        alert('업로드 중 오류가 발생했습니다.\n' + error.message);
        progressDiv.remove();
        console.error('Upload error:', error);
    });
}

function deleteProductImage(event, productId) {
    event.preventDefault();
    event.stopPropagation();
    
    if (!confirm('이미지를 삭제하시겠습니까?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('product_id', productId);
    
    fetch('admin/ajax/delete_product_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '삭제 실패');
        }
    })
    .catch(error => {
        alert('삭제 중 오류가 발생했습니다.');
        console.error('Delete error:', error);
    });
}
</script>

<?php include 'tail.php'; ?>