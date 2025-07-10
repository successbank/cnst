<?php
$currentPage = 'sales';
$pageTitle = '판매제품';
include 'head.php';

// 카테고리 정의
$categories = [
    'rebar' => '철근(특판)',
    'h-beam' => 'H형강(H빔)',
    'steel-plate' => '철강(강판)',
    'metal-lath' => '메탈라스(망철판)',
    'light-h-beam' => '경량H형강',
    'i-beam' => 'I형강(빔)',
    'angle' => 'ㄱ형강(앵글)',
    'channel' => 'ㄷ형강(찬넬)',
    'round-bar' => '환봉(원형강)',
    'flat-bar' => '평철',
    'c-beam' => 'C형강',
    'deck-plate' => '테크플레이트',
    'square-pipe' => '사각파이프(각관)',
    'round-pipe' => '원형파이프(강관)',
    'rail' => '레일',
    'sheet-pile' => '강널말뚝(쉬트파일)',
    'stainless' => '스테인레스(STS)'
];

// 제품 데이터 (실제로는 DB에서 가져와야 함)
$products = [
    'rebar' => [
        ['name' => '이형철근 D10', 'spec' => 'D10 × 8M', 'unit' => 'TON', 'description' => '콘크리트 보강용'],
        ['name' => '이형철근 D13', 'spec' => 'D13 × 8M', 'unit' => 'TON', 'description' => '콘크리트 보강용'],
        ['name' => '이형철근 D16', 'spec' => 'D16 × 8M', 'unit' => 'TON', 'description' => '콘크리트 보강용'],
        ['name' => '이형철근 D19', 'spec' => 'D19 × 8M', 'unit' => 'TON', 'description' => '콘크리트 보강용'],
        ['name' => '이형철근 D22', 'spec' => 'D22 × 8M', 'unit' => 'TON', 'description' => '콘크리트 보강용'],
        ['name' => '이형철근 D25', 'spec' => 'D25 × 8M', 'unit' => 'TON', 'description' => '콘크리트 보강용'],
    ],
    'h-beam' => [
        ['name' => 'H형강 100×100', 'spec' => '100×100×6×8', 'unit' => 'TON', 'description' => '경량 구조용'],
        ['name' => 'H형강 150×150', 'spec' => '150×150×7×10', 'unit' => 'TON', 'description' => '중량 구조용'],
        ['name' => 'H형강 200×200', 'spec' => '200×200×8×12', 'unit' => 'TON', 'description' => '중량 구조용'],
        ['name' => 'H형강 250×250', 'spec' => '250×250×9×14', 'unit' => 'TON', 'description' => '중량 구조용'],
        ['name' => 'H형강 300×300', 'spec' => '300×300×10×15', 'unit' => 'TON', 'description' => '중량 구조용'],
    ],
    'steel-plate' => [
        ['name' => '열연강판 3.2t', 'spec' => '3.2t × 4\' × 8\'', 'unit' => '매', 'description' => '일반 구조용'],
        ['name' => '열연강판 4.5t', 'spec' => '4.5t × 4\' × 8\'', 'unit' => '매', 'description' => '일반 구조용'],
        ['name' => '열연강판 6.0t', 'spec' => '6.0t × 4\' × 8\'', 'unit' => '매', 'description' => '일반 구조용'],
        ['name' => '열연강판 9.0t', 'spec' => '9.0t × 4\' × 8\'', 'unit' => '매', 'description' => '일반 구조용'],
        ['name' => '열연강판 12t', 'spec' => '12t × 4\' × 8\'', 'unit' => '매', 'description' => '일반 구조용'],
    ],
    'angle' => [
        ['name' => '앵글 40×40×3', 'spec' => '40×40×3t', 'unit' => 'M', 'description' => '경량 프레임용'],
        ['name' => '앵글 50×50×5', 'spec' => '50×50×5t', 'unit' => 'M', 'description' => '일반 프레임용'],
        ['name' => '앵글 65×65×6', 'spec' => '65×65×6t', 'unit' => 'M', 'description' => '일반 프레임용'],
        ['name' => '앵글 75×75×6', 'spec' => '75×75×6t', 'unit' => 'M', 'description' => '중량 프레임용'],
        ['name' => '앵글 100×100×10', 'spec' => '100×100×10t', 'unit' => 'M', 'description' => '중량 프레임용'],
    ],
    'channel' => [
        ['name' => '찬넬 75×40×5', 'spec' => '75×40×5t', 'unit' => 'M', 'description' => '경량 구조용'],
        ['name' => '찬넬 100×50×5', 'spec' => '100×50×5t', 'unit' => 'M', 'description' => '일반 구조용'],
        ['name' => '찬넬 125×65×6', 'spec' => '125×65×6t', 'unit' => 'M', 'description' => '일반 구조용'],
        ['name' => '찬넬 150×75×6.5', 'spec' => '150×75×6.5t', 'unit' => 'M', 'description' => '중량 구조용'],
        ['name' => '찬넬 200×80×7.5', 'spec' => '200×80×7.5t', 'unit' => 'M', 'description' => '중량 구조용'],
    ],
    'square-pipe' => [
        ['name' => '각관 50×50×2.3', 'spec' => '50×50×2.3t', 'unit' => 'M', 'description' => '일반 구조용'],
        ['name' => '각관 100×100×3.2', 'spec' => '100×100×3.2t', 'unit' => 'M', 'description' => '일반 구조용'],
        ['name' => '각관 150×150×4.5', 'spec' => '150×150×4.5t', 'unit' => 'M', 'description' => '중량 구조용'],
        ['name' => '각관 200×200×6.0', 'spec' => '200×200×6.0t', 'unit' => 'M', 'description' => '중량 구조용'],
    ],
    'round-pipe' => [
        ['name' => '원형파이프 Φ48.6', 'spec' => 'Φ48.6×2.8t', 'unit' => 'M', 'description' => '일반 배관용'],
        ['name' => '원형파이프 Φ60.5', 'spec' => 'Φ60.5×3.2t', 'unit' => 'M', 'description' => '일반 배관용'],
        ['name' => '원형파이프 Φ89.1', 'spec' => 'Φ89.1×3.2t', 'unit' => 'M', 'description' => '일반 배관용'],
        ['name' => '원형파이프 Φ114.3', 'spec' => 'Φ114.3×3.6t', 'unit' => 'M', 'description' => '구조용 배관'],
    ]
];

// 선택된 카테고리
$selectedCategory = $_GET['category'] ?? '';
?>

<style>
/* Sales page specific styles */
.sales-container {
    display: flex;
    gap: 24px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* 사이드바 카테고리 */
.category-sidebar {
    width: 260px;
    flex-shrink: 0;
}

.category-title {
    font-size: 20px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #E5E5E7;
}

.category-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.category-item {
    display: block;
    padding: 16px 20px;
    color: #333;
    text-decoration: none;
    font-size: 15px;
    border-bottom: 1px solid #F0F0F0;
    transition: all 0.3s ease;
}

.category-item:last-child {
    border-bottom: none;
}

.category-item:hover {
    background: #F8F9FA;
    color: var(--primary-blue);
    padding-left: 24px;
}

.category-item.active {
    background: var(--primary-blue);
    color: white;
    font-weight: 600;
}

/* 메인 콘텐츠 */
.sales-content {
    flex: 1;
    min-width: 0;
}

.content-header {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.content-title {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    margin-bottom: 12px;
}

.content-desc {
    font-size: 16px;
    color: #666;
    line-height: 1.6;
}

/* 제품 리스트 */
.products-section {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.product-table {
    width: 100%;
    border-collapse: collapse;
}

.product-table th {
    background: #F8F9FA;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #333;
    font-size: 14px;
    border-bottom: 2px solid #E5E5E7;
}

.product-table td {
    padding: 16px;
    border-bottom: 1px solid #F0F0F0;
    font-size: 14px;
}

.product-table tr:last-child td {
    border-bottom: none;
}

.product-table tr:hover {
    background: #F8F9FA;
}

.product-name {
    font-weight: 600;
    color: #333;
}

.btn-quote {
    display: inline-block;
    padding: 8px 16px;
    background: var(--primary-blue);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-quote:hover {
    background: #0F1F7A;
    transform: translateY(-1px);
}

/* 전체 제품 보기 */
.all-products {
    text-align: center;
    padding: 60px 20px;
}

.all-products h3 {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
}

.all-products p {
    font-size: 16px;
    color: #666;
    margin-bottom: 32px;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    max-width: 1000px;
    margin: 0 auto;
}

.category-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.category-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.category-name {
    font-size: 16px;
    font-weight: 600;
}

/* 견적 안내 */
.quote-info {
    background: #E8F0FE;
    padding: 24px;
    border-radius: 12px;
    margin-top: 24px;
    text-align: center;
}

.quote-info h4 {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 8px;
}

.quote-info p {
    font-size: 14px;
    color: #666;
    margin-bottom: 16px;
}

.btn-quote-large {
    display: inline-block;
    padding: 12px 32px;
    background: var(--primary-blue);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-quote-large:hover {
    background: #0F1F7A;
    transform: translateY(-2px);
}

/* 반응형 */
@media (max-width: 1024px) {
    .sales-container {
        flex-direction: column;
    }
    
    .category-sidebar {
        width: 100%;
        margin-bottom: 24px;
    }
    
    .category-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 8px;
        padding: 12px;
    }
    
    .category-item {
        text-align: center;
        border: 1px solid #E5E5E7;
        border-radius: 8px;
    }
}

@media (max-width: 768px) {
    .product-table {
        font-size: 12px;
    }
    
    .product-table th,
    .product-table td {
        padding: 8px;
    }
    
    .btn-quote {
        padding: 6px 12px;
        font-size: 12px;
    }
}
</style>

<div class="sales-container">
    <!-- 카테고리 사이드바 -->
    <aside class="category-sidebar">
        <h3 class="category-title">제품 카테고리</h3>
        <nav class="category-list">
            <a href="sales.php" class="category-item <?php echo !$selectedCategory ? 'active' : ''; ?>">전체 제품</a>
            <?php foreach($categories as $key => $name): ?>
                <a href="?category=<?php echo $key; ?>" 
                   class="category-item <?php echo $selectedCategory === $key ? 'active' : ''; ?>">
                    <?php echo $name; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    
    <!-- 메인 콘텐츠 -->
    <main class="sales-content">
        <?php if($selectedCategory && isset($categories[$selectedCategory])): ?>
            <!-- 선택된 카테고리 표시 -->
            <div class="content-header">
                <h2 class="content-title"><?php echo $categories[$selectedCategory]; ?></h2>
                <p class="content-desc">
                    충남스틸에서 공급하는 <?php echo $categories[$selectedCategory]; ?> 제품입니다.
                    모든 제품은 품질 검증을 거친 정품만을 취급합니다.
                </p>
            </div>
            
            <?php if(isset($products[$selectedCategory])): ?>
                <div class="products-section">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th width="30%">제품명</th>
                                <th width="25%">규격</th>
                                <th width="10%">단위</th>
                                <th width="20%">용도</th>
                                <th width="15%">견적문의</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products[$selectedCategory] as $product): ?>
                                <tr>
                                    <td class="product-name"><?php echo $product['name']; ?></td>
                                    <td><?php echo $product['spec']; ?></td>
                                    <td><?php echo $product['unit']; ?></td>
                                    <td><?php echo $product['description']; ?></td>
                                    <td>
                                        <a href="quote.php?product=<?php echo urlencode($product['name']); ?>" 
                                           class="btn-quote">견적요청</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="quote-info">
                        <h4>대량 구매 및 특별 견적 문의</h4>
                        <p>위 목록에 없는 제품이나 대량 구매를 원하시는 경우, 별도 문의 주시면 맞춤 견적을 제공해 드립니다.</p>
                        <a href="quote.php" class="btn-quote-large">통합 견적 문의</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="products-section">
                    <p style="text-align: center; color: #666; padding: 40px;">
                        해당 카테고리의 제품 정보를 준비 중입니다.
                    </p>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- 전체 제품 보기 -->
            <div class="content-header">
                <h2 class="content-title">전체 제품</h2>
                <p class="content-desc">
                    충남스틸에서 취급하는 모든 철강 제품을 한눈에 보실 수 있습니다.
                    원하시는 카테고리를 선택해 주세요.
                </p>
            </div>
            
            <div class="products-section all-products">
                <h3>제품 카테고리를 선택해 주세요</h3>
                <p>각 카테고리별로 상세한 제품 정보와 규격을 확인하실 수 있습니다.</p>
                
                <div class="category-grid">
                    <?php 
                    $icons = [
                        'rebar' => '🔩', 'h-beam' => '🏗️', 'steel-plate' => '📐', 
                        'metal-lath' => '🔲', 'light-h-beam' => '🏢', 'i-beam' => '📍',
                        'angle' => '📏', 'channel' => '🔨', 'round-bar' => '⭕',
                        'flat-bar' => '➖', 'c-beam' => '🔧', 'deck-plate' => '🏗️',
                        'square-pipe' => '⬜', 'round-pipe' => '⚪', 'rail' => '🚂',
                        'sheet-pile' => '🔱', 'stainless' => '✨'
                    ];
                    
                    foreach($categories as $key => $name): 
                    ?>
                        <a href="?category=<?php echo $key; ?>" class="category-card">
                            <div class="category-icon"><?php echo $icons[$key] ?? '📦'; ?></div>
                            <div class="category-name"><?php echo $name; ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include 'tail.php'; ?>