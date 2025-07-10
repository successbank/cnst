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
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border: 1px solid #E5E5E7;
    transition: all 0.3s ease;
}

.value-item:hover, .info-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.value-item h4, .info-item h4 {
    font-size: 20px;
    font-weight: 700;
    color: #1565C0;
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
    background: linear-gradient(135deg, #E5E5E7 0%, #F0F0F0 100%);
    border-radius: 16px;
    border: 2px solid #E5E5E7;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 16px;
    position: relative;
}

#map::before {
    content: "🗺️";
    font-size: 48px;
    margin-bottom: 10px;
}

#map::after {
    content: "지도가 로드됩니다";
    position: absolute;
    bottom: 30%;
    left: 50%;
    transform: translateX(-50%);
    font-size: 14px;
    color: #999;
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

/* 모바일 최적화 - 31.png, 32.png 디자인 (1062px까지 적용) */
@media (max-width: 1062px) {
    .values-grid, .info-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-top: 30px;
    }
    
    .content-header {
        padding: 30px 20px;
        text-align: center;
    }
    
    .content-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: #333;
        margin-bottom: 12px;
    }
    
    .content-header p {
        font-size: 16px;
        color: #666;
        line-height: 1.5;
    }
    
    .location-section-item {
        padding: 40px 0;
    }
    
    .location-section-item h3 {
        font-size: 24px;
        margin-bottom: 25px;
        text-align: center;
    }
    
    .info-item {
        padding: 25px 15px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 1px solid #E5E5E7;
    }
    
    .info-item h4 {
        font-size: 16px;
        font-weight: 700;
        color: #1565C0;
        margin-bottom: 12px;
        text-align: center;
    }
    
    .info-item p {
        font-size: 14px;
        color: #333;
        line-height: 1.4;
        text-align: center;
        word-break: keep-all;
    }
    
    .section-container {
        padding: 0 20px;
    }
    
    #map {
        height: 300px;
        margin-top: 20px;
    }
    
    
    /* 팩스 삭제 후 3개 항목 레이아웃 조정 */
    .info-grid[style*="repeat(3, 1fr)"] {
        display: grid;
        grid-template-columns: repeat(2, 1fr) !important;
        grid-template-rows: auto auto;
        gap: 15px;
    }
    
    /* 첫 번째 줄: 주소, 대표전화 */
    .info-item:nth-child(1), .info-item:nth-child(2) {
        /* 첫 번째 줄 */
    }
    
    /* 이메일은 두 번째 줄 전체 너비 */
    .info-item:nth-child(3) {
        grid-column: 1 / -1;
        max-width: none;
        margin: 0;
    }
    
    /* 각 카드의 호버 효과 개선 */
    .info-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    /* 배경색 구분 */
    .location-section-item:nth-child(odd) {
        background-color: white;
    }
    
    .location-section-item:nth-child(even) {
        background-color: #F8F9FA;
    }
    
    /* 지도 섹션 개선 */
    .location-section-item:last-child {
        background-color: #F8F9FA !important;
    }
    
    .location-section-item:last-child h3 {
        margin-bottom: 20px;
    }
    
    /* 32.png 참고 - 채팅 버튼 스타일 */
    .chat-button {
        background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 25px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
        min-width: 120px;
    }
    
    .chat-button:hover {
        background: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(25, 118, 210, 0.4);
    }
    
    .chat-button:active {
        transform: translateY(0);
    }
    
    .map-actions {
        margin-top: 20px;
        text-align: center;
    }
}

/* 태블릿 크기 (769px ~ 1062px)에서 추가 최적화 */
@media (min-width: 769px) and (max-width: 1062px) {
    .info-item {
        padding: 30px 20px;
    }
    
    .info-item h4 {
        font-size: 18px;
        color: #1565C0;
    }
    
    .info-item p {
        font-size: 15px;
    }
    
    .chat-button {
        padding: 14px 28px;
        font-size: 17px;
    }
}
</style>

<!-- 본사 정보 -->
<section class="location-section-item">
    <div class="section-container">
        <h3>본사 위치</h3>
        <div class="info-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="info-item">
                <h4>주소</h4>
                <p>인천 서구 봉수대로 1626<br>(금곡동)</p>
            </div>
            <div class="info-item">
                <h4>대표전화</h4>
                <p>032-564-1616</p>
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
        
        <!-- 32.png 참고 - 추가 액션 버튼 -->
        <div class="map-actions" style="margin-top: 20px; text-align: center;">
            <button class="chat-button" onclick="openChat()">
                💬 채팅
            </button>
        </div>
    </div>
</section>

    </div>
</main>

<script>
function openChat() {
    // 32.png 참고 - 채팅 기능 (예시)
    alert('채팅 기능은 준비 중입니다.\n\n대신 다음 연락처로 문의해 주세요:\n📞 032-564-1616\n📧 cn1616@naver.com');
}
</script>

<?php 
endSubPage();
include 'tail.php'; 
?>
