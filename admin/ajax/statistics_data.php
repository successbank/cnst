<?php
/**
 * 접속통계 AJAX 데이터 엔드포인트
 * 지원 액션: summary, timeseries, pages, devices, browsers, referrers, business, all
 *
 * 하이브리드 쿼리: 7일 이전은 집계 테이블, 이후는 원본 테이블에서 조회
 */
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$action = $_GET['action'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// 날짜 형식 검증
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-d', strtotime('-30 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) $dateTo = date('Y-m-d');

// dateTo에 시간 끝까지 포함
$dateToEnd = $dateTo . ' 23:59:59';

// 집계/원본 경계일: 이 날짜 미만은 집계 테이블, 이상은 원본
$boundary = date('Y-m-d', strtotime('-7 days'));

try {
    $result = [];

    // site_visits 데이터 존재 여부 확인 (원본 + 집계)
    $visitCount = (int)$pdo->query("SELECT COUNT(*) FROM site_visits")->fetchColumn();
    $aggCount = (int)$pdo->query("SELECT COUNT(*) FROM site_visits_daily")->fetchColumn();
    $hasVisitData = ($visitCount > 0 || $aggCount > 0);

    if ($action === 'all' || $action === 'summary') {
        $result['summary'] = getSummaryData($pdo, $dateFrom, $dateToEnd, $hasVisitData, $boundary);
    }
    if ($action === 'all' || $action === 'timeseries') {
        $timeType = $_GET['time_type'] ?? 'daily';
        $result['timeseries'] = getTimeseriesData($pdo, $dateFrom, $dateToEnd, $timeType, $hasVisitData, $boundary);
    }
    if ($action === 'all' || $action === 'pages') {
        $result['pages'] = getPageStats($pdo, $dateFrom, $dateToEnd, $hasVisitData, $boundary);
    }
    if ($action === 'all' || $action === 'devices') {
        $result['devices'] = getDeviceStats($pdo, $dateFrom, $dateToEnd, $hasVisitData, $boundary);
    }
    if ($action === 'all' || $action === 'browsers') {
        $result['browsers'] = getBrowserStats($pdo, $dateFrom, $dateToEnd, $hasVisitData, $boundary);
    }
    if ($action === 'all' || $action === 'referrers') {
        $result['referrers'] = getReferrerStats($pdo, $dateFrom, $dateToEnd, $hasVisitData, $boundary);
    }
    if ($action === 'all' || $action === 'business') {
        $result['business'] = getBusinessStats($pdo, $dateFrom, $dateToEnd, $boundary);
    }

    $result['has_visit_data'] = $hasVisitData;
    $result['date_from'] = $dateFrom;
    $result['date_to'] = $dateTo;
    $result['success'] = true;

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Statistics data error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => '데이터 조회 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
}

// ============================================
// 데이터 조회 함수들 (하이브리드: 집계 + 원본)
// ============================================

/**
 * 날짜 범위에서 집계/원본 사용 구간 반환
 * @return array ['agg_from', 'agg_to', 'raw_from', 'raw_to', 'use_agg', 'use_raw']
 */
function getDateRanges($dateFrom, $dateTo, $boundary) {
    $from = substr($dateFrom, 0, 10);
    $to = substr($dateTo, 0, 10);

    $useAgg = ($from < $boundary);
    $useRaw = ($to >= $boundary);

    return [
        'agg_from' => $from,
        'agg_to' => min($to, date('Y-m-d', strtotime($boundary . ' -1 day'))),
        'raw_from' => max($from, $boundary),
        'raw_to' => $to,
        'use_agg' => $useAgg,
        'use_raw' => $useRaw,
    ];
}

function getSummaryData($pdo, $dateFrom, $dateToEnd, $hasVisitData, $boundary) {
    $today = date('Y-m-d');
    $todayEnd = $today . ' 23:59:59';
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $yesterdayEnd = $yesterday . ' 23:59:59';
    $monthStart = date('Y-m-01');
    $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
    $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));

    if ($hasVisitData) {
        // 오늘/어제: 항상 원본 (최근 7일 이내)
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) FROM site_visits WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$today, $todayEnd]);
        $todayVisitors = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) FROM site_visits WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$yesterday, $yesterdayEnd]);
        $yesterdayVisitors = (int)$stmt->fetchColumn();

        // 오늘 페이지뷰
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM site_visits WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$today, $todayEnd]);
        $todayPageviews = (int)$stmt->fetchColumn();

        // 이번달 방문자: 집계(경계 이전) + 원본(경계 이후) 합산
        $monthVisitors = getHybridVisitors($pdo, $monthStart, $today, $boundary);

        // 지난달 방문자: 대부분 집계 테이블
        $lastMonthVisitors = getHybridVisitors($pdo, $lastMonthStart, $lastMonthEnd, $boundary);

        $todayChange = $yesterdayVisitors > 0 ? round((($todayVisitors - $yesterdayVisitors) / $yesterdayVisitors) * 100, 1) : 0;
        $monthChange = $lastMonthVisitors > 0 ? round((($monthVisitors - $lastMonthVisitors) / $lastMonthVisitors) * 100, 1) : 0;
    } else {
        $todayVisitors = 0;
        $yesterdayVisitors = 0;
        $monthVisitors = 0;
        $lastMonthVisitors = 0;
        $todayPageviews = 0;
        $todayChange = 0;
        $monthChange = 0;
    }

    // 견적문의 (board_quote) - 항상 실제 데이터
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_quote WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $quoteCount = (int)$stmt->fetchColumn();

    return [
        'today_visitors' => $todayVisitors,
        'yesterday_visitors' => $yesterdayVisitors,
        'today_change' => $todayChange,
        'month_visitors' => $monthVisitors,
        'month_change' => $monthChange,
        'quote_count' => $quoteCount,
        'today_pageviews' => $todayPageviews,
    ];
}

/**
 * 하이브리드 방문자 수 조회 (집계 + 원본)
 */
function getHybridVisitors($pdo, $from, $to, $boundary) {
    $total = 0;

    // 집계 구간 (경계일 이전)
    if ($from < $boundary) {
        $aggTo = min($to, date('Y-m-d', strtotime($boundary . ' -1 day')));
        if ($from <= $aggTo) {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(unique_visitors), 0) FROM site_visits_daily WHERE visit_date BETWEEN ? AND ?");
            $stmt->execute([$from, $aggTo]);
            $total += (int)$stmt->fetchColumn();
        }
    }

    // 원본 구간 (경계일 이후)
    if ($to >= $boundary) {
        $rawFrom = max($from, $boundary);
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) FROM site_visits WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$rawFrom, $to . ' 23:59:59']);
        $total += (int)$stmt->fetchColumn();
    }

    return $total;
}

function getTimeseriesData($pdo, $dateFrom, $dateToEnd, $timeType, $hasVisitData, $boundary) {
    if (!$hasVisitData) {
        return ['has_data' => false, 'message' => '방문자 추적 데이터를 수집 중입니다.', 'data' => []];
    }

    $data = [];
    if ($timeType === 'hourly') {
        // 시간별: 오늘만 → 원본 (변경 없음)
        $stmt = $pdo->prepare("
            SELECT HOUR(created_at) as label,
                   COUNT(DISTINCT session_id) as visitors,
                   COUNT(*) as pageviews
            FROM site_visits
            WHERE DATE(created_at) = CURDATE()
            GROUP BY HOUR(created_at)
            ORDER BY label
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hourMap = [];
        foreach ($rows as $r) $hourMap[(int)$r['label']] = $r;
        for ($h = 0; $h < 24; $h++) {
            $data[] = [
                'label' => $h . '시',
                'visitors' => (int)($hourMap[$h]['visitors'] ?? 0),
                'pageviews' => (int)($hourMap[$h]['pageviews'] ?? 0),
            ];
        }
    } elseif ($timeType === 'daily') {
        // 일별: 경계 이전은 집계, 이후는 원본
        $dayMap = [];
        $ranges = getDateRanges($dateFrom, substr($dateToEnd, 0, 10), $boundary);

        // 집계 테이블에서 경계 이전 데이터
        if ($ranges['use_agg']) {
            $stmt = $pdo->prepare("
                SELECT visit_date as label, unique_visitors as visitors, pageviews
                FROM site_visits_daily
                WHERE visit_date BETWEEN ? AND ?
                ORDER BY visit_date
            ");
            $stmt->execute([$ranges['agg_from'], $ranges['agg_to']]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $dayMap[$r['label']] = $r;
            }
        }

        // 원본 테이블에서 경계 이후 데이터
        if ($ranges['use_raw']) {
            $stmt = $pdo->prepare("
                SELECT DATE(created_at) as label,
                       COUNT(DISTINCT session_id) as visitors,
                       COUNT(*) as pageviews
                FROM site_visits
                WHERE created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY label
            ");
            $stmt->execute([$ranges['raw_from'], $ranges['raw_to'] . ' 23:59:59']);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $dayMap[$r['label']] = $r;
            }
        }

        // 날짜 범위 전부 채우기
        $current = new DateTime($dateFrom);
        $end = new DateTime(substr($dateToEnd, 0, 10));
        $end->modify('+1 day');
        while ($current < $end) {
            $d = $current->format('Y-m-d');
            $data[] = [
                'label' => $current->format('n/j'),
                'visitors' => (int)($dayMap[$d]['visitors'] ?? 0),
                'pageviews' => (int)($dayMap[$d]['pageviews'] ?? 0),
            ];
            $current->modify('+1 day');
        }
    } elseif ($timeType === 'monthly') {
        // 월별: 집계 + 원본 합산
        $monthMap = [];

        // 집계 테이블에서 월별 합산 (경계 이전)
        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(visit_date, '%Y-%m') as label,
                   SUM(unique_visitors) as visitors,
                   SUM(pageviews) as pageviews
            FROM site_visits_daily
            WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              AND visit_date < ?
            GROUP BY DATE_FORMAT(visit_date, '%Y-%m')
            ORDER BY label
        ");
        $stmt->execute([$boundary]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $monthMap[$r['label']] = [
                'visitors' => (int)$r['visitors'],
                'pageviews' => (int)$r['pageviews'],
            ];
        }

        // 원본 테이블에서 월별 합산 (경계 이후)
        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as label,
                   COUNT(DISTINCT session_id) as visitors,
                   COUNT(*) as pageviews
            FROM site_visits
            WHERE created_at >= ?
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY label
        ");
        $stmt->execute([$boundary]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $m = $r['label'];
            if (isset($monthMap[$m])) {
                $monthMap[$m]['visitors'] += (int)$r['visitors'];
                $monthMap[$m]['pageviews'] += (int)$r['pageviews'];
            } else {
                $monthMap[$m] = [
                    'visitors' => (int)$r['visitors'],
                    'pageviews' => (int)$r['pageviews'],
                ];
            }
        }

        ksort($monthMap);
        foreach ($monthMap as $label => $vals) {
            $parts = explode('-', $label);
            $data[] = [
                'label' => (int)$parts[1] . '월',
                'visitors' => $vals['visitors'],
                'pageviews' => $vals['pageviews'],
            ];
        }
    }

    return ['has_data' => true, 'data' => $data];
}

function getPageStats($pdo, $dateFrom, $dateToEnd, $hasVisitData, $boundary) {
    if (!$hasVisitData) {
        return ['has_data' => false, 'message' => '방문자 추적 데이터를 수집 중입니다.', 'data' => []];
    }

    $ranges = getDateRanges($dateFrom, substr($dateToEnd, 0, 10), $boundary);
    $pageMap = [];

    // 집계 테이블
    if ($ranges['use_agg']) {
        $stmt = $pdo->prepare("
            SELECT page_title as title, page_url as page, SUM(pageviews) as views
            FROM site_visits_daily_pages
            WHERE visit_date BETWEEN ? AND ?
            GROUP BY page_title, page_url
        ");
        $stmt->execute([$ranges['agg_from'], $ranges['agg_to']]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $key = $r['page'];
            $pageMap[$key] = ['title' => $r['title'], 'page' => $r['page'], 'views' => (int)$r['views']];
        }
    }

    // 원본 테이블
    if ($ranges['use_raw']) {
        $stmt = $pdo->prepare("
            SELECT page_title as title, page_url as page, COUNT(*) as views
            FROM site_visits
            WHERE created_at BETWEEN ? AND ?
            GROUP BY page_title, page_url
        ");
        $stmt->execute([$ranges['raw_from'], $ranges['raw_to'] . ' 23:59:59']);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $key = $r['page'];
            if (isset($pageMap[$key])) {
                $pageMap[$key]['views'] += (int)$r['views'];
            } else {
                $pageMap[$key] = ['title' => $r['title'], 'page' => $r['page'], 'views' => (int)$r['views']];
            }
        }
    }

    // 정렬 후 상위 15개
    usort($pageMap, fn($a, $b) => $b['views'] - $a['views']);
    $rows = array_slice($pageMap, 0, 15);

    $total = array_sum(array_column($rows, 'views'));
    foreach ($rows as &$r) {
        $r['percent'] = $total > 0 ? round(($r['views'] / $total) * 100, 1) : 0;
    }

    return ['has_data' => true, 'data' => array_values($rows)];
}

function getDeviceStats($pdo, $dateFrom, $dateToEnd, $hasVisitData, $boundary) {
    if (!$hasVisitData) {
        return ['has_data' => false, 'message' => '방문자 추적 데이터를 수집 중입니다.', 'data' => []];
    }

    $ranges = getDateRanges($dateFrom, substr($dateToEnd, 0, 10), $boundary);
    $deviceMap = [];

    // 집계 테이블
    if ($ranges['use_agg']) {
        $stmt = $pdo->prepare("
            SELECT dimension_value as device, SUM(sessions) as sessions
            FROM site_visits_daily_dimensions
            WHERE visit_date BETWEEN ? AND ?
              AND dimension_type = 'device'
            GROUP BY dimension_value
        ");
        $stmt->execute([$ranges['agg_from'], $ranges['agg_to']]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $deviceMap[$r['device']] = (int)$r['sessions'];
        }
    }

    // 원본 테이블
    if ($ranges['use_raw']) {
        $stmt = $pdo->prepare("
            SELECT device_type as device, COUNT(*) as sessions
            FROM site_visits
            WHERE created_at BETWEEN ? AND ?
            GROUP BY device_type
        ");
        $stmt->execute([$ranges['raw_from'], $ranges['raw_to'] . ' 23:59:59']);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $d = $r['device'];
            $deviceMap[$d] = ($deviceMap[$d] ?? 0) + (int)$r['sessions'];
        }
    }

    $labels = ['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet'];
    $total = array_sum($deviceMap);
    $rows = [];
    arsort($deviceMap);
    foreach ($deviceMap as $device => $sessions) {
        $rows[] = [
            'device' => $device,
            'sessions' => $sessions,
            'label' => $labels[$device] ?? $device,
            'percent' => $total > 0 ? round(($sessions / $total) * 100, 1) : 0,
        ];
    }

    return ['has_data' => true, 'data' => $rows];
}

function getBrowserStats($pdo, $dateFrom, $dateToEnd, $hasVisitData, $boundary) {
    if (!$hasVisitData) {
        return ['has_data' => false, 'message' => '방문자 추적 데이터를 수집 중입니다.', 'data' => []];
    }

    $ranges = getDateRanges($dateFrom, substr($dateToEnd, 0, 10), $boundary);
    $browserMap = [];

    // 집계 테이블
    if ($ranges['use_agg']) {
        $stmt = $pdo->prepare("
            SELECT dimension_value as browser, SUM(sessions) as sessions
            FROM site_visits_daily_dimensions
            WHERE visit_date BETWEEN ? AND ?
              AND dimension_type = 'browser'
            GROUP BY dimension_value
        ");
        $stmt->execute([$ranges['agg_from'], $ranges['agg_to']]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $browserMap[$r['browser']] = (int)$r['sessions'];
        }
    }

    // 원본 테이블
    if ($ranges['use_raw']) {
        $stmt = $pdo->prepare("
            SELECT browser, COUNT(*) as sessions
            FROM site_visits
            WHERE created_at BETWEEN ? AND ?
            GROUP BY browser
        ");
        $stmt->execute([$ranges['raw_from'], $ranges['raw_to'] . ' 23:59:59']);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $b = $r['browser'];
            $browserMap[$b] = ($browserMap[$b] ?? 0) + (int)$r['sessions'];
        }
    }

    arsort($browserMap);
    $browserMap = array_slice($browserMap, 0, 10, true);
    $total = array_sum($browserMap);
    $rows = [];
    foreach ($browserMap as $browser => $sessions) {
        $rows[] = [
            'browser' => $browser,
            'sessions' => $sessions,
            'percent' => $total > 0 ? round(($sessions / $total) * 100, 1) : 0,
        ];
    }

    return ['has_data' => true, 'data' => $rows];
}

function getReferrerStats($pdo, $dateFrom, $dateToEnd, $hasVisitData, $boundary) {
    if (!$hasVisitData) {
        return ['has_data' => false, 'message' => '방문자 추적 데이터를 수집 중입니다.', 'data' => []];
    }

    $ranges = getDateRanges($dateFrom, substr($dateToEnd, 0, 10), $boundary);
    $categories = [
        '직접 유입' => 0,
        '네이버' => 0,
        '구글' => 0,
        '다음' => 0,
        '소셜미디어' => 0,
        '기타' => 0,
    ];

    // 집계 테이블 (이미 카테고리별 집계됨)
    if ($ranges['use_agg']) {
        $stmt = $pdo->prepare("
            SELECT dimension_value as source, SUM(sessions) as sessions
            FROM site_visits_daily_dimensions
            WHERE visit_date BETWEEN ? AND ?
              AND dimension_type = 'referrer'
            GROUP BY dimension_value
        ");
        $stmt->execute([$ranges['agg_from'], $ranges['agg_to']]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if (isset($categories[$r['source']])) {
                $categories[$r['source']] += (int)$r['sessions'];
            } else {
                $categories['기타'] += (int)$r['sessions'];
            }
        }
    }

    // 원본 테이블 (raw referrer → 카테고리 분류)
    if ($ranges['use_raw']) {
        $stmt = $pdo->prepare("
            SELECT referrer, COUNT(*) as sessions
            FROM site_visits
            WHERE created_at BETWEEN ? AND ?
            GROUP BY referrer
        ");
        $stmt->execute([$ranges['raw_from'], $ranges['raw_to'] . ' 23:59:59']);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ref = strtolower($r['referrer'] ?? '');
            $count = (int)$r['sessions'];

            if (empty($ref) || $ref === '-') {
                $categories['직접 유입'] += $count;
            } elseif (strpos($ref, 'naver.com') !== false || strpos($ref, 'search.naver') !== false) {
                $categories['네이버'] += $count;
            } elseif (strpos($ref, 'google.') !== false) {
                $categories['구글'] += $count;
            } elseif (strpos($ref, 'daum.net') !== false || strpos($ref, 'search.daum') !== false) {
                $categories['다음'] += $count;
            } elseif (preg_match('/facebook|instagram|twitter|x\.com|youtube|tiktok|kakao/i', $ref)) {
                $categories['소셜미디어'] += $count;
            } else {
                $categories['기타'] += $count;
            }
        }
    }

    $total = array_sum($categories);
    $data = [];
    foreach ($categories as $source => $sessions) {
        if ($sessions > 0) {
            $data[] = [
                'source' => $source,
                'sessions' => $sessions,
                'percent' => $total > 0 ? round(($sessions / $total) * 100, 1) : 0,
            ];
        }
    }
    usort($data, fn($a, $b) => $b['sessions'] - $a['sessions']);

    return ['has_data' => true, 'data' => $data];
}

function getBusinessStats($pdo, $dateFrom, $dateToEnd, $boundary) {
    $today = date('Y-m-d');
    $todayEnd = $today . ' 23:59:59';
    $monthStart = date('Y-m-01');

    // 견적문의 (board_quote)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_quote WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $quoteTotal = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_quote WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$today, $todayEnd]);
    $quoteToday = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_quote WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $todayEnd]);
    $quoteMonth = (int)$stmt->fetchColumn();

    // 제품견적서 (product_quotes)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_quotes WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $productQuoteTotal = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_quotes WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$today, $todayEnd]);
    $productQuoteToday = (int)$stmt->fetchColumn();

    // 중계판매 (board_consignment)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_consignment WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $consignmentTotal = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_consignment WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $todayEnd]);
    $consignmentMonth = (int)$stmt->fetchColumn();

    // 신규 회원
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $newMembersTotal = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $todayEnd]);
    $newMembersMonth = (int)$stmt->fetchColumn();

    // 전체 회원수
    $totalMembers = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE is_active = 1")->fetchColumn();

    // 로그인 통계
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM member_login_logs WHERE login_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $loginTotal = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM member_login_logs WHERE login_date BETWEEN ? AND ?");
    $stmt->execute([$today, $todayEnd]);
    $loginToday = (int)$stmt->fetchColumn();

    // 이메일 발송
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM email_logs WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $emailTotal = (int)$stmt->fetchColumn();

    // 인기 제품 TOP 10 (하이브리드: 집계 + 원본)
    $ranges = getDateRanges($dateFrom, substr($dateToEnd, 0, 10), $boundary);
    $productMap = [];

    if ($ranges['use_agg']) {
        $stmt = $pdo->prepare("
            SELECT page_url, page_title, SUM(pageviews) as views
            FROM site_visits_daily_pages
            WHERE page_url LIKE '%product_detail%'
              AND visit_date BETWEEN ? AND ?
            GROUP BY page_url, page_title
        ");
        $stmt->execute([$ranges['agg_from'], $ranges['agg_to']]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $productMap[$r['page_url']] = $r;
        }
    }

    if ($ranges['use_raw']) {
        $stmt = $pdo->prepare("
            SELECT page_url, page_title, COUNT(*) as views
            FROM site_visits
            WHERE page_url LIKE '%product_detail%'
              AND created_at BETWEEN ? AND ?
            GROUP BY page_url, page_title
        ");
        $stmt->execute([$ranges['raw_from'], $ranges['raw_to'] . ' 23:59:59']);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $key = $r['page_url'];
            if (isset($productMap[$key])) {
                $productMap[$key]['views'] = (int)$productMap[$key]['views'] + (int)$r['views'];
            } else {
                $productMap[$key] = $r;
            }
        }
    }

    usort($productMap, fn($a, $b) => (int)$b['views'] - (int)$a['views']);
    $topProducts = array_slice($productMap, 0, 10);

    // 최근 견적문의 5건
    $recentQuotes = $pdo->query("
        SELECT id, title, writer, company, created_at, is_answered
        FROM board_quote
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    return [
        'quote' => ['total' => $quoteTotal, 'today' => $quoteToday, 'month' => $quoteMonth],
        'product_quote' => ['total' => $productQuoteTotal, 'today' => $productQuoteToday],
        'consignment' => ['total' => $consignmentTotal, 'month' => $consignmentMonth],
        'members' => ['total' => $totalMembers, 'new_period' => $newMembersTotal, 'new_month' => $newMembersMonth],
        'login' => ['total' => $loginTotal, 'today' => $loginToday],
        'email' => ['total' => $emailTotal],
        'top_products' => $topProducts,
        'recent_quotes' => $recentQuotes,
    ];
}
