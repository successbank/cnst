<?php
$currentPage = 'about';
$pageTitle = '회사소개';
require_once 'includes/sub_layout.php';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('회사소개', 'about');

// 사이드바
companySidebar('about');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>회사소개</h2>
        <p>충남스틸은 믿을 수 있는 철강 파트너입니다.</p>
    </div>
    
    <div class="content-body">

<style>
/* About page specific styles */
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

.company-values {
    padding: 60px 0;
    background: white;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 40px;
}

.value-item {
    text-align: center;
    padding: 40px 20px;
    background: #F8F9FA;
    border-radius: 16px;
    transition: all 0.3s ease;
}

.value-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.value-item h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 16px;
}

.value-item p {
    font-size: 15px;
    color: #666;
    line-height: 1.6;
}

.company-history {
    padding: 60px 0;
    background: #F8F9FA;
}

.history-list {
    list-style: none;
    padding: 0;
    margin-top: 40px;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.history-list li {
    display: flex;
    align-items: center;
    padding: 20px;
    background: white;
    margin-bottom: 16px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.history-list li:hover {
    transform: translateX(8px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.year {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-blue);
    min-width: 80px;
    margin-right: 40px;
}

.event {
    font-size: 16px;
    color: #333;
    flex: 1;
}

.business-area {
    padding: 60px 0;
    background: white;
}

.business-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    margin-top: 40px;
}

.business-item {
    background: #F8F9FA;
    border-radius: 16px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
}

.business-item:hover {
    background: var(--primary-blue);
    color: white;
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(20, 40, 160, 0.3);
}

.business-item:hover h4,
.business-item:hover p {
    color: white;
}

.business-item h4 {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 12px;
    transition: color 0.3s ease;
}

.business-item p {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    transition: color 0.3s ease;
}

.certificates {
    padding: 60px 0;
    background: #F8F9FA;
}

.certificates ul {
    list-style: none;
    padding: 0;
    margin-top: 30px;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.certificates li {
    padding: 16px 24px;
    background: white;
    margin-bottom: 12px;
    border-radius: 8px;
    border-left: 4px solid var(--primary-blue);
    font-size: 16px;
    color: #333;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* Section headers */
.company-values h3,
.company-history h3,
.business-area h3,
.certificates h3 {
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
    .values-grid,
    .business-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .history-list li {
        flex-direction: column;
        text-align: center;
    }
    
    .year {
        margin-right: 0;
        margin-bottom: 10px;
    }
}
</style>

<!-- 회사 소개 헤더 -->
<section class="about-intro">
    <div class="section-container">
        <h3>충남스틸을 소개합니다</h3>
        <p>충남스틸은 2009년 설립 이래 충청남도 지역을 중심으로 우수한 품질의 철강 제품을 공급하고 있습니다. 
        고객의 다양한 요구에 맞춘 철강재 가공 및 유통 서비스를 제공하며, 
        건설, 제조, 조선 등 다양한 산업 분야에 필요한 철강 솔루션을 제공하고 있습니다.</p>
    </div>
</section>

<!-- 경영이념 -->
<section class="company-values">
    <div class="section-container">
        <h3>경영이념</h3>
        <div class="values-grid">
            <div class="value-item">
                <h4>품질제일주의</h4>
                <p>최고 품질의 제품만을 고객에게 제공합니다.</p>
            </div>
            <div class="value-item">
                <h4>고객만족경영</h4>
                <p>고객의 요구를 최우선으로 생각하며 만족을 추구합니다.</p>
            </div>
            <div class="value-item">
                <h4>신뢰와 성실</h4>
                <p>투명하고 정직한 경영으로 신뢰를 쌓아갑니다.</p>
            </div>
        </div>
    </div>
</section>

<!-- 사업분야 -->
<section class="business-area">
    <div class="section-container">
        <h3>사업분야</h3>
        <div class="business-grid">
            <div class="business-item">
                <h4>철강재 유통</h4>
                <p>H형강, 철근, 강관, 형강 등 다양한 철강 제품 공급</p>
            </div>
            <div class="business-item">
                <h4>철강 가공</h4>
                <p>절단, 절곡, 용접 등 고객 맞춤형 가공 서비스</p>
            </div>
            <div class="business-item">
                <h4>기술 컨설팅</h4>
                <p>철강 제품 선정 및 시공 관련 전문 기술 지원</p>
            </div>
            <div class="business-item">
                <h4>물류 서비스</h4>
                <p>신속하고 안전한 제품 운송 서비스</p>
            </div>
        </div>
    </div>
</section>

<!-- 인증 및 수상 -->
<section class="certificates">
    <div class="section-container">
        <h3>인증 및 수상</h3>
        <ul>
            <li>ISO 9001:2015 품질경영시스템 인증</li>
            <li>2019년 충청남도 우수기업 선정</li>
            <li>2018년 고용노동부 일자리 창출 우수기업</li>
            <li>한국철강협회 정회원사</li>
        </ul>
    </div>
</section>

    </div>
</main>

<?php 
endSubPage();
include 'tail.php'; 
?>