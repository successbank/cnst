<?php
$currentPage = 'location';
$pageTitle = '오시는길';
require_once 'includes/sub_layout.php';
require_once 'includes/kakao_config.php'; // 카카오맵 API 키 설정
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
    border-radius: 16px;
    border: 2px solid #E5E5E7;
    overflow: hidden;
    position: relative;
}

/* 지도 로딩 전 플레이스홀더 */
#map.loading {
    background: linear-gradient(135deg, #E5E5E7 0%, #F0F0F0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

#map.loading::before {
    content: "🗺️ 지도를 불러오는 중...";
    font-size: 18px;
    color: #666;
}

/* 카카오맵 커스텀 인포윈도우 스타일 */
.kakao-infowindow {
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    min-width: 280px;
    font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif;
}

.kakao-infowindow .company-name {
    font-size: 18px;
    font-weight: 700;
    color: #1565C0;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.kakao-infowindow .company-name::before {
    content: "🏭";
    font-size: 20px;
}

.kakao-infowindow .info-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 8px;
    font-size: 14px;
    color: #333;
}

.kakao-infowindow .info-row:last-child {
    margin-bottom: 0;
}

.kakao-infowindow .info-icon {
    flex-shrink: 0;
    width: 20px;
    text-align: center;
}

.kakao-infowindow .info-text {
    line-height: 1.5;
}

.kakao-infowindow .btn-directions {
    display: block;
    margin-top: 15px;
    padding: 10px 16px;
    background: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    text-align: center;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.kakao-infowindow .btn-directions:hover {
    background: linear-gradient(135deg, #0D47A1 0%, #0A3D91 100%);
    transform: translateY(-1px);
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
        <div id="map" class="loading"></div>

        <!-- 지도 하단 버튼 영역 -->
        <div class="map-actions" style="margin-top: 20px; text-align: center; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <a href="https://map.kakao.com/link/to/충남스틸,37.5434,126.6626" target="_blank" class="chat-button" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                🚗 길찾기
            </a>
            <a href="https://map.kakao.com/link/map/충남스틸,37.5434,126.6626" target="_blank" class="chat-button" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: linear-gradient(135deg, #FEE500 0%, #F5D800 100%); color: #3C1E1E;">
                🗺️ 카카오맵에서 보기
            </a>
            <button class="chat-button" onclick="openChat()" style="display: inline-flex; align-items: center; gap: 8px;">
                💬 문의하기
            </button>
        </div>
    </div>
</section>

    </div>
</main>

<!-- 카카오맵 JavaScript SDK (autoload=false로 로드 타이밍 제어) -->
<script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=<?php echo defined('KAKAO_MAP_API_KEY') ? KAKAO_MAP_API_KEY : 'YOUR_KAKAO_MAP_API_KEY'; ?>&libraries=services&autoload=false"></script>

<script>
// ============================================================
// 충남스틸 오시는길 - 카카오맵 구현
// ============================================================

// 회사 정보 설정
const COMPANY_INFO = {
    name: '충남스틸 주식회사',
    address: '인천 서구 봉수대로 1626 (금곡동)',
    phone: '032-564-0090',
    email: 'cn1616@naver.com',
    // 기본 좌표 (주소 검색 실패 시 사용)
    defaultLat: 37.5434,
    defaultLng: 126.6626
};

// 지도 초기화
function initKakaoMap() {
    const mapContainer = document.getElementById('map');

    // 카카오맵 SDK 로드 확인
    if (typeof kakao === 'undefined' || typeof kakao.maps === 'undefined') {
        console.error('카카오맵 SDK가 로드되지 않았습니다. 도메인 등록을 확인하세요.');
        console.log('현재 도메인:', window.location.hostname);
        console.log('카카오 개발자 콘솔에서 http://' + window.location.hostname + ' 도메인을 등록해주세요.');
        mapContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;flex-direction:column;gap:10px;background:#f5f5f5;"><span style="font-size:48px;">🗺️</span><p style="color:#666;text-align:center;">지도를 불러올 수 없습니다.<br><small>카카오 개발자 콘솔에서 도메인 등록이 필요합니다.</small></p><a href="https://map.kakao.com/link/map/충남스틸,37.5434,126.6626" target="_blank" style="margin-top:10px;padding:10px 20px;background:#1565C0;color:white;text-decoration:none;border-radius:8px;font-size:14px;">카카오맵에서 보기</a></div>';
        mapContainer.classList.remove('loading');
        return;
    }

    // 초기 지도 옵션 (기본 좌표 사용)
    const mapOption = {
        center: new kakao.maps.LatLng(COMPANY_INFO.defaultLat, COMPANY_INFO.defaultLng),
        level: 3 // 확대 레벨 (숫자가 작을수록 확대)
    };

    // 지도 생성
    const map = new kakao.maps.Map(mapContainer, mapOption);
    mapContainer.classList.remove('loading');

    // 지도 컨트롤 추가
    const zoomControl = new kakao.maps.ZoomControl();
    map.addControl(zoomControl, kakao.maps.ControlPosition.RIGHT);

    const mapTypeControl = new kakao.maps.MapTypeControl();
    map.addControl(mapTypeControl, kakao.maps.ControlPosition.TOPRIGHT);

    // Geocoder를 사용하여 주소로 좌표 검색
    const geocoder = new kakao.maps.services.Geocoder();

    geocoder.addressSearch(COMPANY_INFO.address, function(result, status) {
        let coords;

        if (status === kakao.maps.services.Status.OK) {
            // 주소 검색 성공
            coords = new kakao.maps.LatLng(result[0].y, result[0].x);
            console.log('주소 검색 성공:', result[0].y, result[0].x);
        } else {
            // 주소 검색 실패 시 기본 좌표 사용
            coords = new kakao.maps.LatLng(COMPANY_INFO.defaultLat, COMPANY_INFO.defaultLng);
            console.log('주소 검색 실패, 기본 좌표 사용');
        }

        // 마커 이미지 설정 (커스텀 마커)
        const markerImage = new kakao.maps.MarkerImage(
            'https://t1.daumcdn.net/localimg/localimages/07/mapapidoc/marker_red.png',
            new kakao.maps.Size(64, 69),
            { offset: new kakao.maps.Point(27, 69) }
        );

        // 마커 생성
        const marker = new kakao.maps.Marker({
            map: map,
            position: coords,
            image: markerImage,
            title: COMPANY_INFO.name
        });

        // 인포윈도우 내용
        const infoContent = `
            <div class="kakao-infowindow">
                <div class="company-name">${COMPANY_INFO.name}</div>
                <div class="info-row">
                    <span class="info-icon">📍</span>
                    <span class="info-text">${COMPANY_INFO.address}</span>
                </div>
                <div class="info-row">
                    <span class="info-icon">📞</span>
                    <span class="info-text"><a href="tel:${COMPANY_INFO.phone}" style="color:#1565C0;text-decoration:none;">${COMPANY_INFO.phone}</a></span>
                </div>
                <div class="info-row">
                    <span class="info-icon">✉️</span>
                    <span class="info-text"><a href="mailto:${COMPANY_INFO.email}" style="color:#1565C0;text-decoration:none;">${COMPANY_INFO.email}</a></span>
                </div>
                <a href="https://map.kakao.com/link/to/${encodeURIComponent(COMPANY_INFO.name)},${coords.getLat()},${coords.getLng()}" target="_blank" class="btn-directions">
                    🚗 길찾기
                </a>
            </div>
        `;

        // 인포윈도우 생성
        const infowindow = new kakao.maps.InfoWindow({
            content: infoContent,
            removable: true
        });

        // 마커 클릭 시 인포윈도우 표시
        kakao.maps.event.addListener(marker, 'click', function() {
            infowindow.open(map, marker);
        });

        // 지도 중심을 마커 위치로 이동
        map.setCenter(coords);

        // 초기 로드 시 인포윈도우 자동 표시
        setTimeout(function() {
            infowindow.open(map, marker);
        }, 500);
    });

    // 반응형 지도 크기 조정
    window.addEventListener('resize', function() {
        map.relayout();
    });
}

// 채팅 기능
function openChat() {
    alert('문의 기능은 준비 중입니다.\n\n아래 연락처로 문의해 주세요:\n📞 032-564-0090\n✉️ cn1616@naver.com');
}

// 페이지 로드 완료 후 지도 초기화
function loadKakaoMap() {
    // kakao 객체 존재 확인
    if (typeof kakao !== 'undefined' && typeof kakao.maps !== 'undefined') {
        // autoload=false 사용 시 kakao.maps.load() 호출 필요
        kakao.maps.load(function() {
            console.log('카카오맵 SDK 로드 완료');
            initKakaoMap();
        });
    } else {
        console.error('카카오맵 SDK를 찾을 수 없습니다.');
        console.log('API 키 또는 도메인 등록을 확인하세요.');
        // SDK 로드 실패 시 폴백 UI 표시
        initKakaoMap();
    }
}

// DOM 로드 완료 후 실행
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadKakaoMap);
} else {
    // 약간의 지연 후 실행 (SDK 스크립트 로드 대기)
    setTimeout(loadKakaoMap, 100);
}
</script>

<?php 
endSubPage();
include 'tail.php'; 
?>
