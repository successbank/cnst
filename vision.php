<?php
$currentPage = 'vision';
$pageTitle = '경영이념/사훈';
require_once 'includes/sub_layout.php';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('경영이념/사훈', 'vision');

// 사이드바
companySidebar('vision');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>경영이념/사훈</h2>
        <p>충남스틸의 경영이념과 사훈입니다.</p>
    </div>
    
    <div class="content-body">

<style>
.vision-section { margin-bottom: 50px; }
.vision-section:last-child { margin-bottom: 0; }

.vision-section-header { text-align: center; margin-bottom: 30px; }
.vision-section-tag {
    display: inline-block;
    background: #E8F0FE;
    color: #1428A0;
    font-size: 13px;
    font-weight: 600;
    padding: 6px 16px;
    border-radius: 20px;
    margin-bottom: 12px;
}
.vision-section-header h3 {
    font-size: 28px;
    font-weight: 700;
    color: #1D1D1F;
}

.vision-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}
.vision-card {
    text-align: center;
    padding: 36px 24px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #E5E5E7;
    transition: all 0.3s ease;
}
.vision-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    border-color: #E8F0FE;
}
.vision-card-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #1428A0, #1565C0);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
}
.vision-card h4 {
    font-size: 20px;
    font-weight: 700;
    color: #1565C0;
    margin-bottom: 8px;
}
.vision-card .vision-card-hanja {
    display: block;
    font-size: 14px;
    color: #999;
    margin-bottom: 12px;
}
.vision-card p {
    font-size: 15px;
    color: #666;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .vision-grid { grid-template-columns: 1fr; gap: 16px; }
    .vision-card { padding: 28px 20px; }
    .vision-section-header h3 { font-size: 24px; }
}
</style>

<!-- 경영이념 섹션 -->
<section class="vision-section">
    <div class="vision-section-header">
        <span class="vision-section-tag">Management Philosophy</span>
        <h3>경영이념</h3>
    </div>
    <div class="vision-grid">
        <div class="vision-card">
            <div class="vision-card-icon"><i class="fas fa-award"></i></div>
            <h4>최고의 제품</h4>
            <p>품질에 타협하지 않는 최고의 철강 제품을 공급합니다.</p>
        </div>
        <div class="vision-card">
            <div class="vision-card-icon"><i class="fas fa-headset"></i></div>
            <h4>최고의 서비스</h4>
            <p>고객 만족을 최우선으로 신속하고 정확한 서비스를 제공합니다.</p>
        </div>
        <div class="vision-card">
            <div class="vision-card-icon"><i class="fas fa-hand-holding-usd"></i></div>
            <h4>거품없는 가격</h4>
            <p>합리적이고 투명한 가격으로 고객에게 신뢰를 드립니다.</p>
        </div>
    </div>
</section>

<!-- 사훈 섹션 -->
<section class="vision-section">
    <div class="vision-section-header">
        <span class="vision-section-tag">Company Motto</span>
        <h3>사훈</h3>
    </div>
    <div class="vision-grid">
        <div class="vision-card">
            <div class="vision-card-icon"><i class="fas fa-running"></i></div>
            <h4>근면</h4>
            <span class="vision-card-hanja">勤勉</span>
            <p>부지런히 일하며 끊임없이 노력합니다.</p>
        </div>
        <div class="vision-card">
            <div class="vision-card-icon"><i class="fas fa-handshake"></i></div>
            <h4>성실</h4>
            <span class="vision-card-hanja">誠實</span>
            <p>정직하고 진실된 마음으로 임합니다.</p>
        </div>
        <div class="vision-card">
            <div class="vision-card-icon"><i class="fas fa-shield-alt"></i></div>
            <h4>신뢰</h4>
            <span class="vision-card-hanja">信賴</span>
            <p>믿음을 바탕으로 굳건한 관계를 쌓아갑니다.</p>
        </div>
    </div>
</section>

    </div>
</main>

<?php 
endSubPage();
include 'tail.php'; 
?>
