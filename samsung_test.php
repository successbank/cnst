<?php
$currentPage = 'home';
$pageTitle = '삼성 스타일 테스트';
include 'head.php';
?>

<style>
/* Test specific styles */
.test-section {
    padding: 40px 0;
    background-color: white;
}

.test-box {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.color-box {
    display: inline-block;
    width: 100px;
    height: 50px;
    margin: 5px;
    border-radius: 8px;
    text-align: center;
    line-height: 50px;
    color: white;
    font-size: 12px;
}
</style>

<!-- 스타일 테스트 -->
<section class="test-section">
    <div class="test-box">
        <h2>삼성 서비스센터 스타일 테스트</h2>
        
        <h3>1. 색상 팔레트</h3>
        <div>
            <div class="color-box" style="background: #1428A0;">#1428A0</div>
            <div class="color-box" style="background: #2C5AA0;">#2C5AA0</div>
            <div class="color-box" style="background: #E8F0FE; color: #333;">#E8F0FE</div>
            <div class="color-box" style="background: #F8F9FA; color: #333;">#F8F9FA</div>
            <div class="color-box" style="background: #2C3E50;">#2C3E50</div>
        </div>
        
        <h3 style="margin-top: 40px;">2. 버튼 스타일</h3>
        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
            <button class="btn btn-primary">Primary 버튼</button>
            <button class="btn btn-secondary">Secondary 버튼</button>
            <button class="btn btn-primary btn-large">Large Primary</button>
        </div>
        
        <h3 style="margin-top: 40px;">3. 카드 컴포넌트</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; max-width: 800px;">
            <div class="card" style="padding: 20px;">
                <h4>카드 제목</h4>
                <p>삼성 스타일 카드 디자인입니다.</p>
            </div>
            <div class="card" style="padding: 20px;">
                <h4>카드 제목</h4>
                <p>호버 효과가 적용됩니다.</p>
            </div>
            <div class="card" style="padding: 20px;">
                <h4>카드 제목</h4>
                <p>깔끔한 그림자 효과</p>
            </div>
        </div>
        
        <h3 style="margin-top: 40px;">4. 서비스 아이콘</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; max-width: 600px;">
            <div class="service-card">
                <div class="service-icon">🏗️</div>
                <h4>철강재 유통</h4>
            </div>
            <div class="service-card">
                <div class="service-icon">⚙️</div>
                <h4>철강 가공</h4>
            </div>
            <div class="service-card">
                <div class="service-icon">📋</div>
                <h4>기술 컨설팅</h4>
            </div>
            <div class="service-card">
                <div class="service-icon">🚚</div>
                <h4>물류 서비스</h4>
            </div>
        </div>
    </div>
</section>

<?php include 'tail.php'; ?>