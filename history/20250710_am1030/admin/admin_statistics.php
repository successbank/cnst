<?php
$pageTitle = '접속통계';

// 추가 스타일 정의
$additionalStyles = '
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
}

.stat-value {
    font-size: 36px;
    font-weight: 700;
    margin: 12px 0;
    color: #1428A0;
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
}

.stat-change {
    font-size: 12px;
    margin-top: 8px;
}

.stat-up {
    color: #2E7D32;
}

.stat-down {
    color: #C62828;
}

.chart-container {
    position: relative;
    height: 400px;
    margin-top: 20px;
}

.chart-box {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.chart-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.chart-controls {
    display: flex;
    gap: 12px;
}

.chart-control {
    padding: 8px 16px;
    border: 1px solid #E5E5E7;
    background: white;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.chart-control:hover {
    background: #F5F5F7;
}

.chart-control.active {
    background: #1428A0;
    color: white;
    border-color: #1428A0;
}

.data-table-box {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.table-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #F5F5F7;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    color: #666;
    border-bottom: 2px solid #E5E5E7;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #E5E5E7;
    font-size: 14px;
}

.data-table tr:hover {
    background: #F8F9FA;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #E5E5E7;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #1428A0;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.device-icon {
    width: 20px;
    height: 20px;
    display: inline-block;
    margin-right: 8px;
    vertical-align: middle;
}

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.filter-form {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-form input[type="date"] {
    padding: 10px 16px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
}

.filter-form button {
    padding: 10px 20px;
    background: #1428A0;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-form button:hover {
    background: #0F1F70;
}
';

require_once 'admin_head.php';

// 날짜 필터 처리
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// 샘플 데이터 생성 (실제로는 접속 로그 테이블에서 가져와야 함)
// 오늘 접속자 수
$todayVisitors = rand(150, 300);
$yesterdayVisitors = rand(140, 290);
$todayChange = round((($todayVisitors - $yesterdayVisitors) / $yesterdayVisitors) * 100, 1);

// 이번 달 접속자 수
$monthVisitors = rand(4000, 6000);
$lastMonthVisitors = rand(3800, 5800);
$monthChange = round((($monthVisitors - $lastMonthVisitors) / $lastMonthVisitors) * 100, 1);

// 평균 체류 시간 (초)
$avgDuration = rand(180, 420);
$avgDurationMin = floor($avgDuration / 60);
$avgDurationSec = $avgDuration % 60;

// 페이지뷰
$todayPageviews = rand(500, 1000);
$bounceRate = rand(30, 50);

// 시간대별 접속 데이터 (24시간)
$hourlyData = [];
for ($i = 0; $i < 24; $i++) {
    $hourlyData[] = [
        'hour' => $i,
        'visitors' => rand(5, 50),
        'pageviews' => rand(20, 200)
    ];
}

// 일별 접속 데이터 (최근 30일)
$dailyData = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $dailyData[] = [
        'date' => $date,
        'visitors' => rand(100, 300),
        'pageviews' => rand(400, 1200),
        'new_visitors' => rand(30, 100)
    ];
}

// 페이지별 방문 수
$pageStats = [
    ['page' => '/', 'title' => '메인 페이지', 'views' => rand(800, 1500), 'percent' => 0],
    ['page' => '/products.php', 'title' => '제품소개', 'views' => rand(400, 800), 'percent' => 0],
    ['page' => '/quote.php', 'title' => '견적문의', 'views' => rand(300, 600), 'percent' => 0],
    ['page' => '/news.php', 'title' => '철강뉴스', 'views' => rand(200, 400), 'percent' => 0],
    ['page' => '/location.php', 'title' => '오시는길', 'views' => rand(100, 300), 'percent' => 0],
    ['page' => '/consignment.php', 'title' => '중계판매', 'views' => rand(150, 350), 'percent' => 0],
    ['page' => '/mypage.php', 'title' => '마이페이지', 'views' => rand(80, 200), 'percent' => 0],
];

// 페이지 통계 백분율 계산
$totalPageViews = array_sum(array_column($pageStats, 'views'));
foreach ($pageStats as &$page) {
    $page['percent'] = round(($page['views'] / $totalPageViews) * 100, 1);
}
usort($pageStats, function($a, $b) {
    return $b['views'] - $a['views'];
});

// 기기별 접속 통계
$deviceStats = [
    ['device' => 'Desktop', 'icon' => '🖥️', 'sessions' => rand(1500, 2500), 'percent' => 0],
    ['device' => 'Mobile', 'icon' => '📱', 'sessions' => rand(2000, 3500), 'percent' => 0],
    ['device' => 'Tablet', 'icon' => '📋', 'sessions' => rand(200, 500), 'percent' => 0],
];

// 기기별 백분율 계산
$totalDevices = array_sum(array_column($deviceStats, 'sessions'));
foreach ($deviceStats as &$device) {
    $device['percent'] = round(($device['sessions'] / $totalDevices) * 100, 1);
}

// 브라우저별 통계
$browserStats = [
    ['browser' => 'Chrome', 'sessions' => rand(2000, 3000), 'percent' => 0],
    ['browser' => 'Safari', 'sessions' => rand(800, 1500), 'percent' => 0],
    ['browser' => 'Edge', 'sessions' => rand(400, 800), 'percent' => 0],
    ['browser' => 'Firefox', 'sessions' => rand(300, 600), 'percent' => 0],
    ['browser' => 'Samsung Browser', 'sessions' => rand(500, 1000), 'percent' => 0],
];

// 브라우저별 백분율 계산
$totalBrowsers = array_sum(array_column($browserStats, 'sessions'));
foreach ($browserStats as &$browser) {
    $browser['percent'] = round(($browser['sessions'] / $totalBrowsers) * 100, 1);
}
usort($browserStats, function($a, $b) {
    return $b['sessions'] - $a['sessions'];
});

// 유입 경로 통계
$referrerStats = [
    ['source' => '직접 유입', 'sessions' => rand(1500, 2500), 'percent' => 0],
    ['source' => '네이버 검색', 'sessions' => rand(1000, 2000), 'percent' => 0],
    ['source' => '구글 검색', 'sessions' => rand(500, 1000), 'percent' => 0],
    ['source' => '다음 검색', 'sessions' => rand(200, 500), 'percent' => 0],
    ['source' => '소셜 미디어', 'sessions' => rand(100, 300), 'percent' => 0],
];

// 유입 경로 백분율 계산
$totalReferrers = array_sum(array_column($referrerStats, 'sessions'));
foreach ($referrerStats as &$referrer) {
    $referrer['percent'] = round(($referrer['sessions'] / $totalReferrers) * 100, 1);
}
?>

<div class="page-header">
    <h1>접속통계</h1>
    <p>웹사이트 방문자 통계 및 분석 정보를 확인합니다.</p>
</div>

<!-- 날짜 필터 -->
<div class="filter-section">
    <form method="GET" action="" class="filter-form">
        <input type="date" name="date_from" value="<?php echo $dateFrom; ?>">
        <span>~</span>
        <input type="date" name="date_to" value="<?php echo $dateTo; ?>">
        <button type="submit">조회</button>
        <a href="admin_statistics.php" style="padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">초기화</a>
    </form>
</div>

<!-- 주요 통계 카드 -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-label">오늘 방문자</div>
        <div class="stat-value"><?php echo number_format($todayVisitors); ?></div>
        <div class="stat-change <?php echo $todayChange >= 0 ? 'stat-up' : 'stat-down'; ?>">
            <?php echo $todayChange >= 0 ? '▲' : '▼'; ?> <?php echo abs($todayChange); ?>% (어제 대비)
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">이번 달 방문자</div>
        <div class="stat-value"><?php echo number_format($monthVisitors); ?></div>
        <div class="stat-change <?php echo $monthChange >= 0 ? 'stat-up' : 'stat-down'; ?>">
            <?php echo $monthChange >= 0 ? '▲' : '▼'; ?> <?php echo abs($monthChange); ?>% (지난달 대비)
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">평균 체류시간</div>
        <div class="stat-value"><?php echo $avgDurationMin; ?>분 <?php echo $avgDurationSec; ?>초</div>
        <div class="stat-change">페이지당 평균</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">페이지뷰</div>
        <div class="stat-value"><?php echo number_format($todayPageviews); ?></div>
        <div class="stat-change">이탈률 <?php echo $bounceRate; ?>%</div>
    </div>
</div>

<!-- 시간대별 접속 차트 -->
<div class="chart-box">
    <div class="chart-header">
        <h2 class="chart-title">시간대별 접속 현황</h2>
        <div class="chart-controls">
            <button class="chart-control active" onclick="switchChart('hourly')">시간별</button>
            <button class="chart-control" onclick="switchChart('daily')">일별</button>
            <button class="chart-control" onclick="switchChart('monthly')">월별</button>
        </div>
    </div>
    <div class="chart-container">
        <canvas id="visitorChart"></canvas>
    </div>
</div>

<!-- 페이지별 방문 통계 -->
<div class="data-table-box">
    <div class="table-header">
        <h2 class="table-title">페이지별 방문 통계</h2>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>순위</th>
                <th>페이지</th>
                <th>페이지명</th>
                <th>방문수</th>
                <th>비율</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pageStats as $index => $page): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($page['page']); ?></td>
                <td><?php echo htmlspecialchars($page['title']); ?></td>
                <td><?php echo number_format($page['views']); ?></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="progress-bar" style="width: 100px;">
                            <div class="progress-fill" style="width: <?php echo $page['percent']; ?>%;"></div>
                        </div>
                        <span><?php echo $page['percent']; ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 기기별 / 브라우저별 통계 -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- 기기별 접속 통계 -->
    <div class="data-table-box">
        <div class="table-header">
            <h2 class="table-title">기기별 접속 통계</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>기기</th>
                    <th>세션수</th>
                    <th>비율</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deviceStats as $device): ?>
                <tr>
                    <td>
                        <span class="device-icon"><?php echo $device['icon']; ?></span>
                        <?php echo htmlspecialchars($device['device']); ?>
                    </td>
                    <td><?php echo number_format($device['sessions']); ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="progress-bar" style="width: 80px;">
                                <div class="progress-fill" style="width: <?php echo $device['percent']; ?>%;"></div>
                            </div>
                            <span><?php echo $device['percent']; ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- 브라우저별 통계 -->
    <div class="data-table-box">
        <div class="table-header">
            <h2 class="table-title">브라우저별 통계</h2>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>브라우저</th>
                    <th>세션수</th>
                    <th>비율</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($browserStats as $browser): ?>
                <tr>
                    <td><?php echo htmlspecialchars($browser['browser']); ?></td>
                    <td><?php echo number_format($browser['sessions']); ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="progress-bar" style="width: 80px;">
                                <div class="progress-fill" style="width: <?php echo $browser['percent']; ?>%;"></div>
                            </div>
                            <span><?php echo $browser['percent']; ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 유입 경로 통계 -->
<div class="data-table-box">
    <div class="table-header">
        <h2 class="table-title">유입 경로 분석</h2>
    </div>
    <div class="chart-container" style="height: 300px;">
        <canvas id="referrerChart"></canvas>
    </div>
</div>

<!-- 요일별 접속 통계 -->
<div class="chart-box">
    <div class="chart-header">
        <h2 class="chart-title">요일별 접속 패턴</h2>
    </div>
    <div class="chart-container">
        <canvas id="dayOfWeekChart"></canvas>
    </div>
</div>

<!-- 시간대별 상세 통계 -->
<div class="data-table-box">
    <div class="table-header">
        <h2 class="table-title">시간대별 상세 접속 통계</h2>
    </div>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- 히트맵 -->
        <div>
            <canvas id="heatmapChart" style="width: 100%; height: 400px;"></canvas>
        </div>
        <!-- 시간대별 표 -->
        <div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>시간대</th>
                        <th>평균 접속수</th>
                        <th>피크 비율</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // 시간대별 통계 데이터
                    $timeStats = [];
                    $maxVisitors = 0;
                    for ($hour = 0; $hour < 24; $hour++) {
                        $avgVisitors = rand(20, 200);
                        if ($hour >= 9 && $hour <= 11) $avgVisitors = rand(150, 250);
                        if ($hour >= 14 && $hour <= 16) $avgVisitors = rand(180, 280);
                        if ($hour >= 20 && $hour <= 22) $avgVisitors = rand(160, 260);
                        
                        $timeStats[] = [
                            'hour' => $hour,
                            'visitors' => $avgVisitors
                        ];
                        $maxVisitors = max($maxVisitors, $avgVisitors);
                    }
                    
                    foreach ($timeStats as $stat):
                        $peakRatio = round(($stat['visitors'] / $maxVisitors) * 100, 1);
                    ?>
                    <tr>
                        <td><?php echo sprintf('%02d:00-%02d:00', $stat['hour'], ($stat['hour'] + 1) % 24); ?></td>
                        <td><?php echo number_format($stat['visitors']); ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="progress-bar" style="width: 60px;">
                                    <div class="progress-fill" style="width: <?php echo $peakRatio; ?>%; background: <?php echo $peakRatio > 80 ? '#FF6900' : '#1428A0'; ?>;"></div>
                                </div>
                                <span><?php echo $peakRatio; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 지역별 접속 통계 -->
<div class="data-table-box">
    <div class="table-header">
        <h2 class="table-title">지역별 접속 통계</h2>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- 지역별 테이블 -->
        <div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>지역</th>
                        <th>접속수</th>
                        <th>비율</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // 대한민국 주요 지역별 접속 데이터 (샘플)
                    $regionStats = [
                        ['region' => '서울', 'sessions' => rand(2000, 3000), 'percent' => 0],
                        ['region' => '경기', 'sessions' => rand(1500, 2500), 'percent' => 0],
                        ['region' => '인천', 'sessions' => rand(500, 800), 'percent' => 0],
                        ['region' => '부산', 'sessions' => rand(600, 900), 'percent' => 0],
                        ['region' => '대구', 'sessions' => rand(400, 700), 'percent' => 0],
                        ['region' => '대전', 'sessions' => rand(350, 600), 'percent' => 0],
                        ['region' => '광주', 'sessions' => rand(300, 500), 'percent' => 0],
                        ['region' => '울산', 'sessions' => rand(250, 400), 'percent' => 0],
                        ['region' => '세종', 'sessions' => rand(100, 200), 'percent' => 0],
                        ['region' => '충남', 'sessions' => rand(800, 1200), 'percent' => 0],
                        ['region' => '충북', 'sessions' => rand(300, 500), 'percent' => 0],
                        ['region' => '전남', 'sessions' => rand(250, 450), 'percent' => 0],
                        ['region' => '전북', 'sessions' => rand(300, 500), 'percent' => 0],
                        ['region' => '경남', 'sessions' => rand(400, 700), 'percent' => 0],
                        ['region' => '경북', 'sessions' => rand(350, 600), 'percent' => 0],
                        ['region' => '강원', 'sessions' => rand(200, 400), 'percent' => 0],
                        ['region' => '제주', 'sessions' => rand(150, 300), 'percent' => 0],
                    ];
                    
                    // 지역별 백분율 계산
                    $totalRegions = array_sum(array_column($regionStats, 'sessions'));
                    foreach ($regionStats as &$region) {
                        $region['percent'] = round(($region['sessions'] / $totalRegions) * 100, 1);
                    }
                    usort($regionStats, function($a, $b) {
                        return $b['sessions'] - $a['sessions'];
                    });
                    
                    // 상위 10개 지역만 표시
                    $topRegions = array_slice($regionStats, 0, 10);
                    foreach ($topRegions as $region): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($region['region']); ?></td>
                        <td><?php echo number_format($region['sessions']); ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="progress-bar" style="width: 80px;">
                                    <div class="progress-fill" style="width: <?php echo $region['percent']; ?>%;"></div>
                                </div>
                                <span><?php echo $region['percent']; ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 지역별 지도 차트 -->
        <div>
            <div class="chart-container" style="height: 400px;">
                <canvas id="regionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 도시별 상세 통계 -->
<div class="data-table-box" style="margin-top: 24px;">
    <div class="table-header">
        <h2 class="table-title">주요 도시별 접속 통계</h2>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>순위</th>
                <th>도시</th>
                <th>지역</th>
                <th>접속수</th>
                <th>페이지뷰</th>
                <th>평균 체류시간</th>
                <th>비율</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // 주요 도시별 상세 데이터 (샘플)
            $cityStats = [
                ['city' => '서울특별시', 'region' => '서울', 'sessions' => rand(2000, 3000), 'pageviews' => rand(8000, 12000), 'avg_duration' => rand(180, 420)],
                ['city' => '성남시', 'region' => '경기', 'sessions' => rand(400, 600), 'pageviews' => rand(1600, 2400), 'avg_duration' => rand(150, 380)],
                ['city' => '수원시', 'region' => '경기', 'sessions' => rand(350, 550), 'pageviews' => rand(1400, 2200), 'avg_duration' => rand(160, 390)],
                ['city' => '부산광역시', 'region' => '부산', 'sessions' => rand(600, 900), 'pageviews' => rand(2400, 3600), 'avg_duration' => rand(170, 400)],
                ['city' => '인천광역시', 'region' => '인천', 'sessions' => rand(500, 800), 'pageviews' => rand(2000, 3200), 'avg_duration' => rand(160, 380)],
                ['city' => '대구광역시', 'region' => '대구', 'sessions' => rand(400, 700), 'pageviews' => rand(1600, 2800), 'avg_duration' => rand(155, 370)],
                ['city' => '대전광역시', 'region' => '대전', 'sessions' => rand(350, 600), 'pageviews' => rand(1400, 2400), 'avg_duration' => rand(165, 385)],
                ['city' => '광주광역시', 'region' => '광주', 'sessions' => rand(300, 500), 'pageviews' => rand(1200, 2000), 'avg_duration' => rand(150, 360)],
                ['city' => '울산광역시', 'region' => '울산', 'sessions' => rand(250, 400), 'pageviews' => rand(1000, 1600), 'avg_duration' => rand(145, 350)],
                ['city' => '천안시', 'region' => '충남', 'sessions' => rand(300, 500), 'pageviews' => rand(1200, 2000), 'avg_duration' => rand(170, 410)],
                ['city' => '아산시', 'region' => '충남', 'sessions' => rand(250, 450), 'pageviews' => rand(1000, 1800), 'avg_duration' => rand(160, 390)],
                ['city' => '고양시', 'region' => '경기', 'sessions' => rand(300, 500), 'pageviews' => rand(1200, 2000), 'avg_duration' => rand(155, 375)],
                ['city' => '용인시', 'region' => '경기', 'sessions' => rand(280, 480), 'pageviews' => rand(1120, 1920), 'avg_duration' => rand(150, 370)],
                ['city' => '창원시', 'region' => '경남', 'sessions' => rand(200, 400), 'pageviews' => rand(800, 1600), 'avg_duration' => rand(140, 340)],
                ['city' => '청주시', 'region' => '충북', 'sessions' => rand(200, 350), 'pageviews' => rand(800, 1400), 'avg_duration' => rand(145, 355)],
            ];
            
            // 도시별 백분율 계산
            $totalCities = array_sum(array_column($cityStats, 'sessions'));
            foreach ($cityStats as &$city) {
                $city['percent'] = round(($city['sessions'] / $totalCities) * 100, 1);
            }
            usort($cityStats, function($a, $b) {
                return $b['sessions'] - $a['sessions'];
            });
            
            foreach ($cityStats as $index => $city): 
                $avgMin = floor($city['avg_duration'] / 60);
                $avgSec = $city['avg_duration'] % 60;
            ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($city['city']); ?></td>
                <td><?php echo htmlspecialchars($city['region']); ?></td>
                <td><?php echo number_format($city['sessions']); ?></td>
                <td><?php echo number_format($city['pageviews']); ?></td>
                <td><?php echo $avgMin; ?>분 <?php echo $avgSec; ?>초</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="progress-bar" style="width: 80px;">
                            <div class="progress-fill" style="width: <?php echo $city['percent']; ?>%;"></div>
                        </div>
                        <span><?php echo $city['percent']; ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 차트 데이터
const hourlyData = <?php echo json_encode($hourlyData); ?>;
const dailyData = <?php echo json_encode($dailyData); ?>;

// 시간대별 접속 차트
const ctx = document.getElementById('visitorChart').getContext('2d');
let visitorChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: hourlyData.map(item => item.hour + '시'),
        datasets: [
            {
                label: '방문자수',
                data: hourlyData.map(item => item.visitors),
                borderColor: '#1428A0',
                backgroundColor: 'rgba(20, 40, 160, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: '페이지뷰',
                data: hourlyData.map(item => item.pageviews),
                borderColor: '#FF6900',
                backgroundColor: 'rgba(255, 105, 0, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    borderDash: [2, 2]
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// 차트 전환 함수
function switchChart(type) {
    // 버튼 활성화 상태 변경
    document.querySelectorAll('.chart-control').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // 차트 데이터 변경
    if (type === 'hourly') {
        visitorChart.data.labels = hourlyData.map(item => item.hour + '시');
        visitorChart.data.datasets[0].data = hourlyData.map(item => item.visitors);
        visitorChart.data.datasets[1].data = hourlyData.map(item => item.pageviews);
    } else if (type === 'daily') {
        visitorChart.data.labels = dailyData.map(item => {
            const date = new Date(item.date);
            return (date.getMonth() + 1) + '/' + date.getDate();
        });
        visitorChart.data.datasets[0].data = dailyData.map(item => item.visitors);
        visitorChart.data.datasets[1].data = dailyData.map(item => item.pageviews);
    } else if (type === 'monthly') {
        // 월별 데이터 (샘플)
        const months = ['1월', '2월', '3월', '4월', '5월', '6월', '7월'];
        visitorChart.data.labels = months;
        visitorChart.data.datasets[0].data = months.map(() => Math.floor(Math.random() * 5000) + 3000);
        visitorChart.data.datasets[1].data = months.map(() => Math.floor(Math.random() * 20000) + 10000);
    }
    
    visitorChart.update();
}

// 유입 경로 도넛 차트
const referrerData = <?php echo json_encode($referrerStats); ?>;
const referrerCtx = document.getElementById('referrerChart').getContext('2d');
new Chart(referrerCtx, {
    type: 'doughnut',
    data: {
        labels: referrerData.map(item => item.source),
        datasets: [{
            data: referrerData.map(item => item.sessions),
            backgroundColor: [
                '#1428A0',
                '#FF6900',
                '#767676',
                '#00B8D4',
                '#FFD600'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    font: {
                        size: 14
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const percent = referrerData[context.dataIndex].percent;
                        return `${label}: ${value.toLocaleString()} (${percent}%)`;
                    }
                }
            }
        }
    }
});

// 지역별 막대 차트
const regionData = <?php echo json_encode(array_slice($regionStats, 0, 10)); ?>;
const regionCtx = document.getElementById('regionChart').getContext('2d');
new Chart(regionCtx, {
    type: 'bar',
    data: {
        labels: regionData.map(item => item.region),
        datasets: [{
            label: '접속수',
            data: regionData.map(item => item.sessions),
            backgroundColor: '#1428A0',
            borderRadius: 6,
            barPercentage: 0.8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const value = context.parsed.y || 0;
                        const percent = regionData[context.dataIndex].percent;
                        return `접속수: ${value.toLocaleString()} (${percent}%)`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    borderDash: [2, 2]
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// 요일별 접속 차트
<?php
// 요일별 데이터 생성
$dayOfWeekData = [
    ['day' => '월요일', 'visitors' => rand(800, 1200), 'pageviews' => rand(3200, 4800)],
    ['day' => '화요일', 'visitors' => rand(900, 1300), 'pageviews' => rand(3600, 5200)],
    ['day' => '수요일', 'visitors' => rand(950, 1400), 'pageviews' => rand(3800, 5600)],
    ['day' => '목요일', 'visitors' => rand(900, 1350), 'pageviews' => rand(3600, 5400)],
    ['day' => '금요일', 'visitors' => rand(850, 1250), 'pageviews' => rand(3400, 5000)],
    ['day' => '토요일', 'visitors' => rand(600, 900), 'pageviews' => rand(2400, 3600)],
    ['day' => '일요일', 'visitors' => rand(500, 800), 'pageviews' => rand(2000, 3200)],
];
?>
const dayOfWeekData = <?php echo json_encode($dayOfWeekData); ?>;
const dayOfWeekCtx = document.getElementById('dayOfWeekChart').getContext('2d');
new Chart(dayOfWeekCtx, {
    type: 'line',
    data: {
        labels: dayOfWeekData.map(item => item.day),
        datasets: [
            {
                label: '방문자수',
                data: dayOfWeekData.map(item => item.visitors),
                borderColor: '#1428A0',
                backgroundColor: 'rgba(20, 40, 160, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: '페이지뷰',
                data: dayOfWeekData.map(item => item.pageviews),
                borderColor: '#FF6900',
                backgroundColor: 'rgba(255, 105, 0, 0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                grid: {
                    borderDash: [2, 2]
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// 시간대별 히트맵 (간단한 버전)
const heatmapCtx = document.getElementById('heatmapChart').getContext('2d');
<?php
// 요일별 시간대별 데이터 생성
$heatmapData = [];
$days = ['월', '화', '수', '목', '금', '토', '일'];
for ($d = 0; $d < 7; $d++) {
    for ($h = 0; $h < 24; $h++) {
        $value = rand(10, 100);
        // 평일 업무시간대 높은 트래픽
        if ($d < 5 && (($h >= 9 && $h <= 11) || ($h >= 14 && $h <= 16))) {
            $value = rand(70, 100);
        }
        // 주말 낮은 트래픽
        if ($d >= 5) {
            $value = rand(20, 60);
        }
        $heatmapData[] = [
            'x' => $h,
            'y' => $d,
            'v' => $value
        ];
    }
}
?>
const heatmapData = <?php echo json_encode($heatmapData); ?>;
new Chart(heatmapCtx, {
    type: 'bubble',
    data: {
        datasets: [{
            label: '접속수',
            data: heatmapData.map(item => ({
                x: item.x,
                y: item.y,
                r: Math.sqrt(item.v) * 2
            })),
            backgroundColor: function(context) {
                const value = context.raw.r / 2;
                const alpha = value / 10;
                return `rgba(20, 40, 160, ${alpha})`;
            }
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const days = ['월요일', '화요일', '수요일', '목요일', '금요일', '토요일', '일요일'];
                        const hour = context.parsed.x;
                        const day = days[context.parsed.y];
                        const value = Math.round(Math.pow(context.raw.r / 2, 2));
                        return `${day} ${hour}:00 - 접속수: ${value}`;
                    }
                }
            }
        },
        scales: {
            x: {
                type: 'linear',
                position: 'bottom',
                min: 0,
                max: 23,
                ticks: {
                    stepSize: 1,
                    callback: function(value) {
                        return value + ':00';
                    }
                }
            },
            y: {
                type: 'linear',
                min: -0.5,
                max: 6.5,
                ticks: {
                    stepSize: 1,
                    callback: function(value) {
                        const days = ['월', '화', '수', '목', '금', '토', '일'];
                        return days[value] || '';
                    }
                }
            }
        }
    }
});
</script>

<?php require_once 'admin_tail.php'; ?>