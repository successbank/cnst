<?php
$currentPage = 'history';
$pageTitle = '연혁';
require_once 'includes/sub_layout.php';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('연혁', 'history');

// 사이드바
companySidebar('history');

// 연혁 데이터 (최신순)
$historyData = [
    2025 => [
        ['month' => '07', 'content' => '중소벤처기업부장관 표창 (예정)', 'highlight' => true],
        ['month' => '07', 'content' => '서구청 우수기업인상 (예정)', 'highlight' => true],
        ['month' => '03', 'content' => '서인천세무서장 위촉장'],
        ['month' => '02', 'content' => '고용노동청 표창장'],
    ],
    2024 => [
        ['month' => '12', 'content' => '중소기업중앙회 표창장'],
        ['month' => '10', 'content' => 'KB BEST 기업 선정', 'highlight' => true],
        ['month' => '01', 'content' => '서부경찰서장 위촉장'],
    ],
    2023 => [
        ['month' => '11', 'content' => '인천광역시장 표창장'],
    ],
    2022 => [
        ['month' => '10', 'content' => '중소기업중앙회장 표창장'],
    ],
    2020 => [
        ['month' => '12', 'content' => '산업통상자원부장관 표창'],
        ['month' => '06', 'content' => '용산구청장 표창'],
    ],
    2019 => [
        ['month' => '11', 'content' => 'KB국민은행 감사장'],
        ['month' => '10', 'content' => '중소기업중앙회장 표창'],
    ],
    2017 => [
        ['month' => '12', 'content' => '서구청장 표창'],
        ['month' => '03', 'content' => '서인천세무서장 상장'],
    ],
    2015 => [
        ['month' => '10', 'content' => 'ISO 9001 인증 취득', 'highlight' => true],
        ['month' => '07', 'content' => '벤처기업 인증 취득', 'highlight' => true],
        ['month' => '05', 'content' => 'KB 베스트기업 선정'],
    ],
    2014 => [
        ['month' => '09', 'content' => '신사옥 이전 완료', 'highlight' => true],
    ],
    2013 => [
        ['month' => '06', 'content' => '신사옥 준공', 'highlight' => true],
        ['month' => '03', 'content' => '자본금 증자 9.5억원'],
    ],
    2012 => [
        ['month' => '11', 'content' => '서구청장 표창'],
    ],
    2011 => [
        ['month' => '12', 'content' => '연매출 380억원 달성'],
        ['month' => '06', 'content' => 'KB 우등기업 선정'],
    ],
    2010 => [
        ['month' => '12', 'content' => '연매출 270억원 달성'],
        ['month' => '05', 'content' => 'KB 우수업체 선정'],
    ],
    2009 => [
        ['month' => '12', 'content' => '연매출 230억원 달성'],
        ['month' => '08', 'content' => '에스크로 서비스 도입'],
        ['month' => '02', 'content' => '자본금 증자'],
    ],
    2008 => [
        ['month' => '12', 'content' => '연매출 190억원 달성'],
        ['month' => '06', 'content' => '현대제철 대리점 계약'],
        ['month' => '01', 'content' => '자본금 증자'],
    ],
    2007 => [
        ['month' => '09', 'content' => '법인 설립 (충남스틸 주식회사)', 'highlight' => true],
        ['month' => '07', 'content' => '동국제강 대리점 계약', 'highlight' => true],
        ['month' => '03', 'content' => '온라인 쇼핑몰 구축'],
    ],
];
?>

<main class="sub-content">
    <div class="content-header">
        <h2>연혁</h2>
        <p>충남스틸의 성장과 발전의 역사를 소개합니다.</p>
    </div>

    <div class="content-body">

<style>
/* ===== History Page Styles ===== */
:root {
    --history-primary: #1565C0;
    --history-primary-dark: #0D47A1;
    --history-primary-light: #42A5F5;
    --history-gradient: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%);
    --history-text-dark: #1a1a2e;
    --history-text-gray: #5a5a6e;
    --history-bg-light: #f8fafc;
}

/* 통계 카드 섹션 */
.history-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 50px;
}

.history-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    border: 1px solid #E8EDF5;
    transition: all 0.3s ease;
}

.history-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(21, 101, 192, 0.15);
    border-color: var(--history-primary-light);
}

.history-stat-number {
    font-size: 36px;
    font-weight: 800;
    color: var(--history-primary);
    line-height: 1;
    margin-bottom: 8px;
}

.history-stat-label {
    font-size: 14px;
    color: var(--history-text-gray);
    font-weight: 500;
}

/* 타임라인 컨테이너 */
.history-timeline {
    position: relative;
    padding-left: 40px;
}

.history-timeline::before {
    content: '';
    position: absolute;
    left: 12px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, var(--history-primary), var(--history-primary-light));
    border-radius: 2px;
}

/* 연도 그룹 */
.history-year-group {
    position: relative;
    margin-bottom: 40px;
}

.history-year-group:last-child {
    margin-bottom: 0;
}

/* 연도 마커 */
.history-year-marker {
    position: absolute;
    left: -40px;
    width: 26px;
    height: 26px;
    background: var(--history-gradient);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    box-shadow: 0 4px 12px rgba(21, 101, 192, 0.3);
}

.history-year-marker::after {
    content: '';
    width: 10px;
    height: 10px;
    background: #fff;
    border-radius: 50%;
}

/* 연도 헤더 */
.history-year-header {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
}

.history-year-badge {
    background: var(--history-gradient);
    color: #fff;
    font-size: 18px;
    font-weight: 700;
    padding: 8px 20px;
    border-radius: 25px;
    display: inline-block;
}

/* 이벤트 목록 */
.history-events {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.history-event {
    background: #fff;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid #E8EDF5;
    transition: all 0.3s ease;
}

.history-event:hover {
    background: var(--history-bg-light);
    border-color: var(--history-primary-light);
    transform: translateX(8px);
}

.history-event.highlight {
    background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
    border-color: var(--history-primary-light);
}

.history-event.highlight:hover {
    background: linear-gradient(135deg, #BBDEFB 0%, #90CAF9 100%);
}

.history-event-month {
    background: var(--history-primary);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 6px;
    min-width: 45px;
    text-align: center;
}

.history-event.highlight .history-event-month {
    background: var(--history-primary-dark);
}

.history-event-content {
    font-size: 15px;
    color: var(--history-text-dark);
    font-weight: 500;
    flex: 1;
}

.history-event.highlight .history-event-content {
    color: var(--history-primary-dark);
    font-weight: 600;
}

.history-event-badge {
    background: #FF6B35;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 12px;
}

/* 시작 마커 */
.history-start-marker {
    position: relative;
    margin-top: 30px;
    padding-left: 0;
}

.history-start-content {
    background: var(--history-gradient);
    color: #fff;
    border-radius: 16px;
    padding: 24px 30px;
    text-align: center;
    position: relative;
}

.history-start-content::before {
    content: '';
    position: absolute;
    left: -28px;
    top: 50%;
    transform: translateY(-50%);
    width: 30px;
    height: 30px;
    background: var(--history-gradient);
    border-radius: 50%;
    border: 4px solid #fff;
    box-shadow: 0 4px 12px rgba(21, 101, 192, 0.3);
}

.history-start-year {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 8px;
}

.history-start-text {
    font-size: 16px;
    opacity: 0.9;
}

/* 반응형 */
@media (max-width: 768px) {
    .history-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .history-stat-number {
        font-size: 28px;
    }

    .history-timeline {
        padding-left: 30px;
    }

    .history-timeline::before {
        left: 8px;
    }

    .history-year-marker {
        left: -30px;
        width: 20px;
        height: 20px;
    }

    .history-year-marker::after {
        width: 8px;
        height: 8px;
    }

    .history-event {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .history-start-content::before {
        left: -22px;
        width: 24px;
        height: 24px;
    }
}

@media (max-width: 480px) {
    .history-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- 통계 카드 -->
<div class="history-stats">
    <div class="history-stat-card">
        <div class="history-stat-number">18+</div>
        <div class="history-stat-label">Years of History</div>
    </div>
    <div class="history-stat-card">
        <div class="history-stat-number">30+</div>
        <div class="history-stat-label">Awards & Certifications</div>
    </div>
    <div class="history-stat-card">
        <div class="history-stat-number">9.5억</div>
        <div class="history-stat-label">Capital</div>
    </div>
    <div class="history-stat-card">
        <div class="history-stat-number">380억+</div>
        <div class="history-stat-label">Annual Sales Record</div>
    </div>
</div>

<!-- 타임라인 -->
<div class="history-timeline">
    <?php foreach ($historyData as $year => $events): ?>
    <div class="history-year-group">
        <div class="history-year-marker"></div>
        <div class="history-year-header">
            <span class="history-year-badge"><?php echo $year; ?></span>
        </div>
        <div class="history-events">
            <?php foreach ($events as $event): ?>
            <div class="history-event <?php echo isset($event['highlight']) && $event['highlight'] ? 'highlight' : ''; ?>">
                <span class="history-event-month"><?php echo $event['month']; ?>월</span>
                <span class="history-event-content"><?php echo htmlspecialchars($event['content']); ?></span>
                <?php if (isset($event['highlight']) && $event['highlight'] && $year == 2007): ?>
                <span class="history-event-badge">창립</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- 시작점 마커 -->
    <div class="history-start-marker">
        <div class="history-start-content">
            <div class="history-start-year">2007</div>
            <div class="history-start-text">충남스틸 주식회사 창립</div>
        </div>
    </div>
</div>

    </div>
</main>

<?php
endSubPage();
include 'tail.php';
?>
