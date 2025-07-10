<?php
$currentPage = 'location';
$pageTitle = '오시는길';
require_once 'includes/sub_layout.php';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('오시는길', 'location');

// 사이드바
companySidebar('location');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>오시는길</h2>
        <p>주식회사 충남스틸 본사 위치 및 연락처 안내</p>
    </div>
    
    <div class="content-body">

<style>
/* About page specific styles applied to location.php */
.about-intro {
    background: white;
    padding: 60px 0;
    text-align: center;
}

.about-intro h3 {
    font-size: 36px;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 24px;
}

.about-intro p {
    font-size: 18px;
    line-height: 1.8;
    color: #666;
    max-width: 800px;
    margin: 0 auto;
}

.about-section {
    background-color: #F8F9FA;
}

.company-values, .location-section-item {
    padding: 60px 0;
    background: white;
}

.values-grid, .info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 40px;
}

.value-item, .info-item {
    text-align: center;
    padding: 40px 20px;
    background: #F8F9FA;
    border-radius: 16px;
    transition: all 0.3s ease;
}

.value-item:hover, .info-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.value-item h4, .info-item h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 16px;
}

.value-item p, .info-item p {
    font-size: 15px;
    color: #666;
    line-height: 1.6;
}

.map-container {
    padding: 60px 0;
    background: #F8F9FA;
}

#map {
    width: 100%;
    height: 450px;
    background: #E5E5E7;
    border-radius: 16px;
}

/* Section headers */
.company-values h3,
.location-section-item h3 {
    font-size: 32px;
    font-weight: 700;
    text-align: center;
    color: #333;
    margin-bottom: 16px;
}

/* Container */
.section-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .values-grid, .info-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}
</style>

<!-- 본사 정보 -->
<section class="location-section-item">
    <div class="section-container">
        <h3>본사 위치</h3>
        <div class="info-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="info-item">
                <h4>주소</h4>
                <p>인천 서구 봉수대로 1626<br>(금곡동)</p>
            </div>
            <div class="info-item">
                <h4>대표전화</h4>
                <p>032-564-1616</p>
            </div>
            <div class="info-item">
                <h4>팩스</h4>
                <p>032-564-0090</p>
            </div>
            <div class="info-item">
                <h4>이메일</h4>
                <p>cn1616@naver.com</p>
            </div>
        </div>
    </div>
</section>

<!-- 지도 -->
<section class="location-section-item" style="background-color: #F8F9FA;">
    <div class="section-container">
        <h3>찾아오시는 길</h3>
        <div id="map">
            <!-- 지도 API 연동 필요 -->
        </div>
    </div>
</section>

    </div>
</main>

<?php 
endSubPage();
include 'tail.php'; 
?>
