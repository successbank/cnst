<?php
$currentPage = 'vision';
$pageTitle = '경영이념';
require_once 'includes/sub_layout.php';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('경영이념', 'vision');

// 사이드바
companySidebar('vision');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>경영이념</h2>
        <p>충남스틸이 추구하는 핵심 가치입니다.</p>
    </div>
    
    <div class="content-body">

<style>
/* About page specific styles */
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

/* Section headers */
.company-values h3 {
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
    .values-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}
</style>

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

    </div>
</main>

<?php 
endSubPage();
include 'tail.php'; 
?>
