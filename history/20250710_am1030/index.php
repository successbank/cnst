<?php
$currentPage = '';
$pageTitle = '홈';
include 'head.php';
?>

<!-- 메인 비주얼 -->
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

<!-- 주요 제품 -->
<section class="section">
    <div class="section-container">
        <div class="section-header">
            <h3>주요 제품</h3>
            <p>충남스틸이 공급하는 고품질 철강 제품입니다</p>
        </div>
        <div class="product-nav">
            <a href="products.php#rebar" class="product-nav-item">
                <div class="product-nav-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#1976D2">
                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                    </svg>
                </div>
                <span>철근</span>
            </a>
            <a href="products.php#hbeam" class="product-nav-item">
                <div class="product-nav-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#1976D2">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                </div>
                <span>H형강</span>
            </a>
            <a href="products.php#plate" class="product-nav-item">
                <div class="product-nav-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#1976D2">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                    </svg>
                </div>
                <span>철판</span>
            </a>
            <a href="products.php#metalath" class="product-nav-item">
                <div class="product-nav-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#1976D2">
                        <path d="M2 20h20v-4H2v4zm2-3h2v2H4v-2zM2 4v4h20V4H2zm4 3H4V5h2v2zm-4 7h20v-4H2v4zm2-3h2v2H4v-2z"/>
                    </svg>
                </div>
                <span>메탈라스</span>
            </a>
            <a href="products.php#light-hbeam" class="product-nav-item">
                <div class="product-nav-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#1976D2">
                        <path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                    </svg>
                </div>
                <span>경량H형강</span>
            </a>
            <a href="products.php#ibeam" class="product-nav-item">
                <div class="product-nav-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#1976D2">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <span>I형강</span>
            </a>
            <a href="products.php#angle" class="product-nav-item">
                <div class="product-nav-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#1976D2">
                        <path d="M12 2l-5.5 9h11z M12 22l5.5-9h-11z"/>
                    </svg>
                </div>
                <span>ㄱ형강</span>
            </a>
            <a href="products.php#channel" class="product-nav-item">
                <div class="product-nav-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#1976D2">
                        <path d="M21 18H2v2h19c1.1 0 2-.9 2-2s-.9-2-2-2zm0-14H2v2h19c1.1 0 2-.9 2-2s-.9-2-2-2zm0 7H2v2h19c1.1 0 2-.9 2-2s-.9-2-2-2z"/>
                    </svg>
                </div>
                <span>ㄷ형강</span>
            </a>
            <a href="products.php#round" class="product-nav-item">
                <div class="product-nav-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#1976D2">
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                </div>
                <span>원형</span>
            </a>
            <a href="products.php#square" class="product-nav-item">
                <div class="product-nav-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="#1976D2">
                        <rect x="4" y="4" width="16" height="16"/>
                    </svg>
                </div>
                <span>각관</span>
            </a>
        </div>
    </div>
</section>

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
            <a href="board_view.php?type=notice&id=<?php echo $notice['id']; ?>">
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
            <a href="news.php" class="more-link">더보기 →</a>
        </div>
        <div class="news-grid">
        <?php
        // 최신 뉴스 3개 가져오기
        try {
            $sql = "SELECT id, title, content, source, created_at 
                    FROM board_news 
                    ORDER BY created_at DESC 
                    LIMIT 3";
            $stmt = $pdo->query($sql);
            $newsList = $stmt->fetchAll();
            
            foreach ($newsList as $news):
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
            echo '<p style="text-align: center; color: #666; grid-column: 1/-1;">뉴스를 불러올 수 없습니다.</p>';
        }
        ?>
        </div>
    </div>
</section>


<style>
/* Page specific styles */
.product-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow-x: auto;
    gap: 10px;
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
    background: #E3F2FD;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.product-nav-item:hover .product-nav-icon {
    background: #BBDEFB;
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

.news-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.news-card {
    padding: 24px;
}

.news-card h4 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 12px;
}

.news-card h4 a {
    color: #333;
    text-decoration: none;
}

.news-card h4 a:hover {
    color: var(--primary-blue);
}

.news-card p {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 16px;
}

.news-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #999;
    padding-top: 12px;
    border-top: 1px solid #E5E5E7;
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
    .news-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}

</style>


<?php include 'tail.php'; ?>