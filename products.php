<?php
$currentPage = 'products';
$pageTitle = '제품소개';
include 'head.php';
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
}

.category-btn:hover,
.category-btn.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

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

/* Responsive */
@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .products-categories {
        padding: 0 20px;
    }
}
</style>

<section class="products-header">
    <h2>제품소개</h2>
    <p>충남스틸이 공급하는 고품질 철강 제품을 소개합니다</p>
</section>

<section class="products-section">
    <div class="products-categories">
        <button class="category-btn active" onclick="filterProducts('all')">전체</button>
        <button class="category-btn" onclick="filterProducts('rebar')">철근</button>
        <button class="category-btn" onclick="filterProducts('h-beam')">H형강</button>
        <button class="category-btn" onclick="filterProducts('steel-plate')">철강(강판)</button>
        <button class="category-btn" onclick="filterProducts('metal-lath')">메탈라스</button>
        <button class="category-btn" onclick="filterProducts('light-h-beam')">경량H형강</button>
        <button class="category-btn" onclick="filterProducts('i-beam')">I형강</button>
        <button class="category-btn" onclick="filterProducts('angle')">ㄱ형강(앵글)</button>
        <button class="category-btn" onclick="filterProducts('channel')">ㄷ형강(찬넬)</button>
        <button class="category-btn" onclick="filterProducts('round-bar')">환봉</button>
        <button class="category-btn" onclick="filterProducts('flat-bar')">평철</button>
        <button class="category-btn" onclick="filterProducts('c-beam')">C형강</button>
        <button class="category-btn" onclick="filterProducts('deck-plate')">테크플레이트</button>
        <button class="category-btn" onclick="filterProducts('square-pipe')">사각파이프</button>
        <button class="category-btn" onclick="filterProducts('round-pipe')">원형파이프</button>
        <button class="category-btn" onclick="filterProducts('rail')">레일</button>
        <button class="category-btn" onclick="filterProducts('sheet-pile')">강널말뚝</button>
        <button class="category-btn" onclick="filterProducts('stainless')">스테인레스</button>
    </div>

    <div class="products-grid">
        <!-- 철근(특판) -->
        <div class="product-card" data-category="rebar">
            <div class="product-image">🔩</div>
            <div class="product-info">
                <h3>철근(특판)</h3>
                <p class="specs">D10 ~ D51</p>
                <p class="description">콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.</p>
                <a href="board_write.php?type=quote&product=철근(특판)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- H형강(H빔) -->
        <div class="product-card" data-category="h-beam">
            <div class="product-image">🏗️</div>
            <div class="product-info">
                <h3>H형강(H빔)</h3>
                <p class="specs">100×100 ~ 900×300</p>
                <p class="description">건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.</p>
                <a href="board_write.php?type=quote&product=H형강(H빔)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 철강(강판) -->
        <div class="product-card" data-category="steel-plate">
            <div class="product-image">📐</div>
            <div class="product-info">
                <h3>철강(강판)</h3>
                <p class="specs">1.6t ~ 100t</p>
                <p class="description">일반 구조용 및 용접 구조용으로 사용되는 열간 압연 강판입니다.</p>
                <a href="board_write.php?type=quote&product=철강(강판)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 메탈라스(망철판) -->
        <div class="product-card" data-category="metal-lath">
            <div class="product-image">🔲</div>
            <div class="product-info">
                <h3>메탈라스(망철판)</h3>
                <p class="specs">0.5t ~ 3.2t</p>
                <p class="description">미장 및 흡음재용으로 사용되는 다이아몬드 형태의 망철판입니다.</p>
                <a href="board_write.php?type=quote&product=메탈라스(망철판)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 경량H형강 -->
        <div class="product-card" data-category="light-h-beam">
            <div class="product-image">🏢</div>
            <div class="product-info">
                <h3>경량H형강</h3>
                <p class="specs">100×50 ~ 300×150</p>
                <p class="description">중소형 건축물에 적합한 경제적인 경량 H형강입니다.</p>
                <a href="board_write.php?type=quote&product=경량H형강" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- I형강(빔) -->
        <div class="product-card" data-category="i-beam">
            <div class="product-image">📍</div>
            <div class="product-info">
                <h3>I형강(빔)</h3>
                <p class="specs">100×75 ~ 600×190</p>
                <p class="description">보 구조물에 사용되는 I자형 단면의 형강입니다.</p>
                <a href="board_write.php?type=quote&product=I형강(빔)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- ㄱ형강(앵글) -->
        <div class="product-card" data-category="angle">
            <div class="product-image">📏</div>
            <div class="product-info">
                <h3>ㄱ형강(앵글)</h3>
                <p class="specs">30×30 ~ 250×250</p>
                <p class="description">트러스 구조 및 프레임 제작에 사용되는 L자형 형강입니다.</p>
                <a href="board_write.php?type=quote&product=ㄱ형강(앵글)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- ㄷ형강(찬넬) -->
        <div class="product-card" data-category="channel">
            <div class="product-image">🔨</div>
            <div class="product-info">
                <h3>ㄷ형강(찬넬)</h3>
                <p class="specs">50×25 ~ 380×100</p>
                <p class="description">프레임 및 지지 구조물에 사용되는 C자형 단면의 형강입니다.</p>
                <a href="board_write.php?type=quote&product=ㄷ형강(찬넬)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 환봉(원형강) -->
        <div class="product-card" data-category="round-bar">
            <div class="product-image">⭕</div>
            <div class="product-info">
                <h3>환봉(원형강)</h3>
                <p class="specs">Φ6 ~ Φ300</p>
                <p class="description">기계 부품 및 축 제작에 사용되는 원형 단면 강재입니다.</p>
                <a href="board_write.php?type=quote&product=환봉(원형강)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 평철 -->
        <div class="product-card" data-category="flat-bar">
            <div class="product-image">➖</div>
            <div class="product-info">
                <h3>평철</h3>
                <p class="specs">3×16 ~ 50×300</p>
                <p class="description">각종 프레임 및 브라켓 제작에 사용되는 평평한 강재입니다.</p>
                <a href="board_write.php?type=quote&product=평철" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- C형강 -->
        <div class="product-card" data-category="c-beam">
            <div class="product-image">🔧</div>
            <div class="product-info">
                <h3>C형강</h3>
                <p class="specs">60×30 ~ 300×80</p>
                <p class="description">경량 구조물 및 천장 프레임에 사용되는 C형 단면 강재입니다.</p>
                <a href="board_write.php?type=quote&product=C형강" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 테크플레이트 -->
        <div class="product-card" data-category="deck-plate">
            <div class="product-image">🏗️</div>
            <div class="product-info">
                <h3>테크플레이트</h3>
                <p class="specs">0.8t ~ 1.6t</p>
                <p class="description">콘크리트 슬래브용 거푸집 겸용 구조재입니다.</p>
                <a href="board_write.php?type=quote&product=테크플레이트" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 사각파이프(각관) -->
        <div class="product-card" data-category="square-pipe">
            <div class="product-image">⬜</div>
            <div class="product-info">
                <h3>사각파이프(각관)</h3>
                <p class="specs">50×50 ~ 400×400</p>
                <p class="description">건축 구조물 및 각종 프레임 제작에 사용되는 사각 단면 강관입니다.</p>
                <a href="board_write.php?type=quote&product=사각파이프(각관)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 원형파이프(강관) -->
        <div class="product-card" data-category="round-pipe">
            <div class="product-image">⚪</div>
            <div class="product-info">
                <h3>원형파이프(강관)</h3>
                <p class="specs">Φ21.5 ~ Φ508</p>
                <p class="description">배관 및 구조용으로 다양하게 활용되는 원형 단면 강관입니다.</p>
                <a href="board_write.php?type=quote&product=원형파이프(강관)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 레일 -->
        <div class="product-card" data-category="rail">
            <div class="product-image">🚂</div>
            <div class="product-info">
                <h3>레일</h3>
                <p class="specs">15kg ~ 60kg</p>
                <p class="description">크레인 및 이송 설비용 레일입니다.</p>
                <a href="board_write.php?type=quote&product=레일" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 강널말뚝(쉬트파일) -->
        <div class="product-card" data-category="sheet-pile">
            <div class="product-image">🔱</div>
            <div class="product-info">
                <h3>강널말뚝(쉬트파일)</h3>
                <p class="specs">SP-II ~ SP-IV</p>
                <p class="description">가설 흙막이 및 항만 구조물에 사용되는 강재 말뚝입니다.</p>
                <a href="board_write.php?type=quote&product=강널말뚝(쉬트파일)" class="product-btn">견적문의</a>
            </div>
        </div>

        <!-- 스테인레스(STS) -->
        <div class="product-card" data-category="stainless">
            <div class="product-image">✨</div>
            <div class="product-info">
                <h3>스테인레스(STS)</h3>
                <p class="specs">STS304, STS316</p>
                <p class="description">내식성이 우수한 스테인레스 강재 제품입니다.</p>
                <a href="board_write.php?type=quote&product=스테인레스(STS)" class="product-btn">견적문의</a>
            </div>
        </div>
    </div>
</section>

<script>
function filterProducts(category) {
    const buttons = document.querySelectorAll('.category-btn');
    const products = document.querySelectorAll('.product-card');
    
    // 버튼 활성화 상태 변경
    buttons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.onclick.toString().includes(`'${category}'`)) {
            btn.classList.add('active');
        }
    });
    
    // 제품 필터링
    products.forEach(product => {
        if (category === 'all' || product.dataset.category === category) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}
</script>

<?php include 'tail.php'; ?>