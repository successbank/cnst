<?php
$pageTitle = '접속통계';

$additionalStyles = '
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

.stat-up { color: #2E7D32; }
.stat-down { color: #C62828; }

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

.chart-control:hover { background: #F5F5F7; }

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

.data-table tr:hover { background: #F8F9FA; }

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

.filter-form button, .filter-form .filter-btn {
    padding: 10px 20px;
    background: #1428A0;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.filter-form button:hover, .filter-form .filter-btn:hover {
    background: #0F1F70;
}

.quick-filter {
    padding: 8px 14px;
    border: 1px solid #E5E5E7;
    background: white;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
    color: #555;
}

.quick-filter:hover { background: #F5F5F7; }
.quick-filter.active {
    background: #1428A0;
    color: white;
    border-color: #1428A0;
}

/* 비즈니스 현황 */
.biz-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.biz-card {
    background: #F8F9FA;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.biz-card .biz-icon {
    font-size: 28px;
    margin-bottom: 8px;
}

.biz-card .biz-value {
    font-size: 28px;
    font-weight: 700;
    color: #1428A0;
}

.biz-card .biz-label {
    font-size: 13px;
    color: #888;
    margin-top: 4px;
}

.biz-card .biz-sub {
    font-size: 11px;
    color: #aaa;
    margin-top: 4px;
}

/* 스켈레톤 로딩 */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: 6px;
}

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.skeleton-text { height: 20px; margin: 8px 0; }
.skeleton-lg { height: 40px; margin: 12px auto; width: 120px; }

.no-data-msg {
    text-align: center;
    padding: 60px 20px;
    color: #999;
    font-size: 15px;
}

.no-data-msg i {
    font-size: 48px;
    color: #ddd;
    display: block;
    margin-bottom: 16px;
}

.badge-answered {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.badge-yes { background: #E8F5E9; color: #2E7D32; }
.badge-no { background: #FFF3E0; color: #E65100; }
';

require_once 'admin_head.php';

// 날짜 필터 기본값
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
?>

<div class="page-header">
    <h1>접속통계</h1>
    <p>웹사이트 방문자 통계 및 비즈니스 현황을 확인합니다.</p>
</div>

<!-- 날짜 필터 -->
<div class="filter-section">
    <div class="filter-form">
        <input type="date" id="dateFrom" value="<?php echo htmlspecialchars($dateFrom); ?>">
        <span>~</span>
        <input type="date" id="dateTo" value="<?php echo htmlspecialchars($dateTo); ?>">
        <button type="button" onclick="loadAllStats()">조회</button>
        <div style="border-left: 1px solid #E5E5E7; height: 30px; margin: 0 8px;"></div>
        <button type="button" class="quick-filter" onclick="setQuickFilter(0)">오늘</button>
        <button type="button" class="quick-filter" onclick="setQuickFilter(7)">7일</button>
        <button type="button" class="quick-filter active" onclick="setQuickFilter(30)">30일</button>
        <button type="button" class="quick-filter" onclick="setQuickFilter(90)">90일</button>
    </div>
</div>

<!-- 요약 카드 -->
<div class="stats-container" id="summaryCards">
    <div class="stat-card">
        <div class="stat-label">오늘 방문자</div>
        <div class="stat-value" id="todayVisitors"><div class="skeleton skeleton-lg"></div></div>
        <div class="stat-change" id="todayChange"></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">이번 달 방문자</div>
        <div class="stat-value" id="monthVisitors"><div class="skeleton skeleton-lg"></div></div>
        <div class="stat-change" id="monthChange"></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">견적문의</div>
        <div class="stat-value" id="quoteCount"><div class="skeleton skeleton-lg"></div></div>
        <div class="stat-change" id="quoteSub">조회기간 내</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">페이지뷰 (오늘)</div>
        <div class="stat-value" id="todayPageviews"><div class="skeleton skeleton-lg"></div></div>
        <div class="stat-change" id="pageviewsSub"></div>
    </div>
</div>

<!-- 비즈니스 현황 -->
<div class="chart-box">
    <div class="chart-header">
        <h2 class="chart-title">비즈니스 현황</h2>
    </div>
    <div class="biz-grid" id="bizGrid">
        <div class="biz-card">
            <div class="biz-icon"><i class="fas fa-file-alt"></i></div>
            <div class="biz-value" id="bizQuote">-</div>
            <div class="biz-label">견적문의</div>
            <div class="biz-sub" id="bizQuoteSub"></div>
        </div>
        <div class="biz-card">
            <div class="biz-icon"><i class="fas fa-calculator"></i></div>
            <div class="biz-value" id="bizProductQuote">-</div>
            <div class="biz-label">제품견적서</div>
            <div class="biz-sub" id="bizProductQuoteSub"></div>
        </div>
        <div class="biz-card">
            <div class="biz-icon"><i class="fas fa-handshake"></i></div>
            <div class="biz-value" id="bizConsignment">-</div>
            <div class="biz-label">중계판매</div>
            <div class="biz-sub" id="bizConsignmentSub"></div>
        </div>
        <div class="biz-card">
            <div class="biz-icon"><i class="fas fa-users"></i></div>
            <div class="biz-value" id="bizMembers">-</div>
            <div class="biz-label">전체회원</div>
            <div class="biz-sub" id="bizMembersSub"></div>
        </div>
        <div class="biz-card">
            <div class="biz-icon"><i class="fas fa-sign-in-alt"></i></div>
            <div class="biz-value" id="bizLogin">-</div>
            <div class="biz-label">로그인</div>
            <div class="biz-sub" id="bizLoginSub"></div>
        </div>
        <div class="biz-card">
            <div class="biz-icon"><i class="fas fa-envelope"></i></div>
            <div class="biz-value" id="bizEmail">-</div>
            <div class="biz-label">이메일 발송</div>
            <div class="biz-sub">조회기간 내</div>
        </div>
    </div>

    <!-- 최근 견적문의 -->
    <div class="table-header" style="margin-top: 24px;">
        <h3 class="table-title" style="font-size: 16px;">최근 견적문의</h3>
    </div>
    <table class="data-table" id="recentQuotesTable">
        <thead>
            <tr>
                <th>제목</th>
                <th>작성자</th>
                <th>회사</th>
                <th>작성일</th>
                <th>답변</th>
            </tr>
        </thead>
        <tbody id="recentQuotesBody">
            <tr><td colspan="5" class="no-data-msg">로딩 중...</td></tr>
        </tbody>
    </table>
</div>

<!-- 시계열 차트 -->
<div class="chart-box">
    <div class="chart-header">
        <h2 class="chart-title">방문자 추이</h2>
        <div class="chart-controls">
            <button class="chart-control active" onclick="switchTimeType('hourly', this)">시간별</button>
            <button class="chart-control" onclick="switchTimeType('daily', this)">일별</button>
            <button class="chart-control" onclick="switchTimeType('monthly', this)">월별</button>
        </div>
    </div>
    <div id="timeseriesNoData" style="display: none;"></div>
    <div class="chart-container">
        <canvas id="visitorChart"></canvas>
    </div>
</div>

<!-- 페이지별 방문 통계 -->
<div class="data-table-box">
    <div class="table-header">
        <h2 class="table-title">페이지별 방문 통계</h2>
    </div>
    <div id="pageStatsNoData" style="display: none;"></div>
    <table class="data-table" id="pageStatsTable">
        <thead>
            <tr>
                <th>순위</th>
                <th>페이지</th>
                <th>페이지명</th>
                <th>방문수</th>
                <th>비율</th>
            </tr>
        </thead>
        <tbody id="pageStatsBody">
            <tr><td colspan="5" class="no-data-msg">로딩 중...</td></tr>
        </tbody>
    </table>
</div>

<!-- 기기별 / 브라우저별 통계 -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="data-table-box">
        <div class="table-header">
            <h2 class="table-title">기기별 접속 통계</h2>
        </div>
        <div id="deviceNoData" style="display: none;"></div>
        <table class="data-table" id="deviceTable">
            <thead>
                <tr><th>기기</th><th>세션수</th><th>비율</th></tr>
            </thead>
            <tbody id="deviceBody">
                <tr><td colspan="3" class="no-data-msg">로딩 중...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="data-table-box">
        <div class="table-header">
            <h2 class="table-title">브라우저별 통계</h2>
        </div>
        <div id="browserNoData" style="display: none;"></div>
        <table class="data-table" id="browserTable">
            <thead>
                <tr><th>브라우저</th><th>세션수</th><th>비율</th></tr>
            </thead>
            <tbody id="browserBody">
                <tr><td colspan="3" class="no-data-msg">로딩 중...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 유입 경로 통계 -->
<div class="data-table-box">
    <div class="table-header">
        <h2 class="table-title">유입 경로 분석</h2>
    </div>
    <div id="referrerNoData" style="display: none;"></div>
    <div class="chart-container" style="height: 300px;">
        <canvas id="referrerChart"></canvas>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 차트 인스턴스
let visitorChart = null;
let referrerChart = null;
let currentTimeType = 'hourly';

// 초기 로드
document.addEventListener('DOMContentLoaded', function() {
    loadAllStats();
});

// 빠른 필터
function setQuickFilter(days) {
    const today = new Date();
    const from = new Date();

    document.querySelectorAll('.quick-filter').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');

    if (days === 0) {
        from.setTime(today.getTime());
    } else {
        from.setDate(today.getDate() - days);
    }

    document.getElementById('dateFrom').value = formatDate(from);
    document.getElementById('dateTo').value = formatDate(today);
    loadAllStats();
}

function formatDate(d) {
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

// 전체 데이터 로드
function loadAllStats() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;

    fetch(`ajax/statistics_data.php?action=all&date_from=${dateFrom}&date_to=${dateTo}&time_type=${currentTimeType}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.error || '데이터 로드 실패');
                return;
            }
            renderSummaryCards(data.summary);
            renderBusinessStats(data.business);
            renderTimeseriesChart(data.timeseries);
            renderPageStats(data.pages);
            renderDeviceStats(data.devices);
            renderBrowserStats(data.browsers);
            renderReferrerChart(data.referrers);
        })
        .catch(err => {
            console.error('Statistics load error:', err);
        });
}

// 요약 카드 렌더링
function renderSummaryCards(s) {
    document.getElementById('todayVisitors').textContent = s.today_visitors.toLocaleString();
    document.getElementById('monthVisitors').textContent = s.month_visitors.toLocaleString();
    document.getElementById('quoteCount').textContent = s.quote_count.toLocaleString();
    document.getElementById('todayPageviews').textContent = s.today_pageviews.toLocaleString();

    const todayEl = document.getElementById('todayChange');
    if (s.today_change !== 0) {
        const icon = s.today_change >= 0 ? '▲' : '▼';
        const cls = s.today_change >= 0 ? 'stat-up' : 'stat-down';
        todayEl.className = 'stat-change ' + cls;
        todayEl.textContent = `${icon} ${Math.abs(s.today_change)}% (어제 대비)`;
    } else {
        todayEl.className = 'stat-change';
        todayEl.textContent = s.today_visitors === 0 ? '수집 중' : '어제와 동일';
    }

    const monthEl = document.getElementById('monthChange');
    if (s.month_change !== 0) {
        const icon = s.month_change >= 0 ? '▲' : '▼';
        const cls = s.month_change >= 0 ? 'stat-up' : 'stat-down';
        monthEl.className = 'stat-change ' + cls;
        monthEl.textContent = `${icon} ${Math.abs(s.month_change)}% (지난달 대비)`;
    } else {
        monthEl.className = 'stat-change';
        monthEl.textContent = s.month_visitors === 0 ? '수집 중' : '지난달과 동일';
    }
}

// 비즈니스 현황 렌더링
function renderBusinessStats(b) {
    document.getElementById('bizQuote').textContent = b.quote.total.toLocaleString();
    document.getElementById('bizQuoteSub').textContent = `오늘 ${b.quote.today}건 / 이번달 ${b.quote.month}건`;

    document.getElementById('bizProductQuote').textContent = b.product_quote.total.toLocaleString();
    document.getElementById('bizProductQuoteSub').textContent = `오늘 ${b.product_quote.today}건`;

    document.getElementById('bizConsignment').textContent = b.consignment.total.toLocaleString();
    document.getElementById('bizConsignmentSub').textContent = `이번달 ${b.consignment.month}건`;

    document.getElementById('bizMembers').textContent = b.members.total.toLocaleString();
    document.getElementById('bizMembersSub').textContent = `신규 ${b.members.new_period}명 (기간) / ${b.members.new_month}명 (월)`;

    document.getElementById('bizLogin').textContent = b.login.total.toLocaleString();
    document.getElementById('bizLoginSub').textContent = `오늘 ${b.login.today}회`;

    document.getElementById('bizEmail').textContent = b.email.total.toLocaleString();

    // 최근 견적문의
    const tbody = document.getElementById('recentQuotesBody');
    if (b.recent_quotes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="no-data-msg">견적문의가 없습니다.</td></tr>';
        return;
    }
    tbody.innerHTML = b.recent_quotes.map(q => {
        const answered = q.is_answered == 1
            ? '<span class="badge-answered badge-yes">답변완료</span>'
            : '<span class="badge-answered badge-no">미답변</span>';
        const date = q.created_at ? q.created_at.substring(0, 10) : '-';
        return `<tr>
            <td><a href="admin_quote_view.php?id=${q.id}" style="color:#1428A0;">${escHtml(q.title)}</a></td>
            <td>${escHtml(q.writer || '-')}</td>
            <td>${escHtml(q.company || '-')}</td>
            <td>${date}</td>
            <td>${answered}</td>
        </tr>`;
    }).join('');
}

// 시계열 차트
function switchTimeType(type, btn) {
    document.querySelectorAll('.chart-controls .chart-control').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    currentTimeType = type;

    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;

    fetch(`ajax/statistics_data.php?action=timeseries&date_from=${dateFrom}&date_to=${dateTo}&time_type=${type}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) renderTimeseriesChart(data.timeseries);
        });
}

function renderTimeseriesChart(ts) {
    const noDataEl = document.getElementById('timeseriesNoData');
    const canvas = document.getElementById('visitorChart');

    if (!ts.has_data) {
        noDataEl.innerHTML = `<div class="no-data-msg"><i class="fas fa-chart-line"></i>${ts.message}</div>`;
        noDataEl.style.display = 'block';
        canvas.style.display = 'none';
        if (visitorChart) { visitorChart.destroy(); visitorChart = null; }
        return;
    }

    noDataEl.style.display = 'none';
    canvas.style.display = 'block';

    const labels = ts.data.map(d => d.label);
    const visitors = ts.data.map(d => d.visitors);
    const pageviews = ts.data.map(d => d.pageviews);

    if (visitorChart) visitorChart.destroy();

    visitorChart = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '방문자수',
                    data: visitors,
                    borderColor: '#1428A0',
                    backgroundColor: 'rgba(20, 40, 160, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: '페이지뷰',
                    data: pageviews,
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
                legend: { position: 'bottom' },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [2, 2] } },
                x: { grid: { display: false } }
            }
        }
    });
}

// 페이지별 통계
function renderPageStats(ps) {
    const noDataEl = document.getElementById('pageStatsNoData');
    const table = document.getElementById('pageStatsTable');
    const tbody = document.getElementById('pageStatsBody');

    if (!ps.has_data) {
        noDataEl.innerHTML = `<div class="no-data-msg"><i class="fas fa-file"></i>${ps.message}</div>`;
        noDataEl.style.display = 'block';
        table.style.display = 'none';
        return;
    }

    noDataEl.style.display = 'none';
    table.style.display = 'table';

    tbody.innerHTML = ps.data.map((p, i) => `<tr>
        <td>${i + 1}</td>
        <td>${escHtml(p.page)}</td>
        <td>${escHtml(p.title)}</td>
        <td>${p.views.toLocaleString()}</td>
        <td>
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="progress-bar" style="width:100px;">
                    <div class="progress-fill" style="width:${p.percent}%;"></div>
                </div>
                <span>${p.percent}%</span>
            </div>
        </td>
    </tr>`).join('');
}

// 기기별 통계
function renderDeviceStats(ds) {
    const noDataEl = document.getElementById('deviceNoData');
    const table = document.getElementById('deviceTable');
    const tbody = document.getElementById('deviceBody');
    const icons = { 'Desktop': '<i class="fas fa-desktop"></i>', 'Mobile': '<i class="fas fa-mobile-alt"></i>', 'Tablet': '<i class="fas fa-tablet-alt"></i>' };

    if (!ds.has_data) {
        noDataEl.innerHTML = `<div class="no-data-msg"><i class="fas fa-laptop"></i>${ds.message}</div>`;
        noDataEl.style.display = 'block';
        table.style.display = 'none';
        return;
    }

    noDataEl.style.display = 'none';
    table.style.display = 'table';

    tbody.innerHTML = ds.data.map(d => `<tr>
        <td>${icons[d.label] || ''} ${escHtml(d.label)}</td>
        <td>${d.sessions.toLocaleString()}</td>
        <td>
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="progress-bar" style="width:80px;">
                    <div class="progress-fill" style="width:${d.percent}%;"></div>
                </div>
                <span>${d.percent}%</span>
            </div>
        </td>
    </tr>`).join('');
}

// 브라우저별 통계
function renderBrowserStats(bs) {
    const noDataEl = document.getElementById('browserNoData');
    const table = document.getElementById('browserTable');
    const tbody = document.getElementById('browserBody');

    if (!bs.has_data) {
        noDataEl.innerHTML = `<div class="no-data-msg"><i class="fas fa-globe"></i>${bs.message}</div>`;
        noDataEl.style.display = 'block';
        table.style.display = 'none';
        return;
    }

    noDataEl.style.display = 'none';
    table.style.display = 'table';

    tbody.innerHTML = bs.data.map(b => `<tr>
        <td>${escHtml(b.browser)}</td>
        <td>${b.sessions.toLocaleString()}</td>
        <td>
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="progress-bar" style="width:80px;">
                    <div class="progress-fill" style="width:${b.percent}%;"></div>
                </div>
                <span>${b.percent}%</span>
            </div>
        </td>
    </tr>`).join('');
}

// 유입 경로 차트
function renderReferrerChart(rs) {
    const noDataEl = document.getElementById('referrerNoData');
    const canvas = document.getElementById('referrerChart');

    if (!rs.has_data) {
        noDataEl.innerHTML = `<div class="no-data-msg"><i class="fas fa-link"></i>${rs.message}</div>`;
        noDataEl.style.display = 'block';
        canvas.style.display = 'none';
        if (referrerChart) { referrerChart.destroy(); referrerChart = null; }
        return;
    }

    noDataEl.style.display = 'none';
    canvas.style.display = 'block';

    const colors = ['#1428A0', '#FF6900', '#767676', '#00B8D4', '#FFD600', '#9C27B0'];

    if (referrerChart) referrerChart.destroy();

    referrerChart = new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: rs.data.map(r => r.source),
            datasets: [{
                data: rs.data.map(r => r.sessions),
                backgroundColor: colors.slice(0, rs.data.length),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { padding: 20, usePointStyle: true, font: { size: 14 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const d = rs.data[ctx.dataIndex];
                            return `${d.source}: ${d.sessions.toLocaleString()} (${d.percent}%)`;
                        }
                    }
                }
            }
        }
    });
}

// HTML 이스케이프
function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>

<?php require_once 'admin_tail.php'; ?>
