<?php
/**
 * 접속통계 AJAX 데이터 엔드포인트
 * 지원 액션: summary, timeseries, pages, devices, browsers, referrers, business, all
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

try {
    $result = [];

    // site_visits 데이터 존재 여부 확인
    $visitCount = (int)$pdo->query("SELECT COUNT(*) FROM site_visits")->fetchColumn();
    $hasVisitData = $visitCount > 0;

    if ($action === 'all' || $action === 'summary') {
        $result['summary'] = getSummaryData($pdo, $dateFrom, $dateToEnd, $hasVisitData);
    }
    if ($action === 'all' || $action === 'timeseries') {
        $timeType = $_GET['time_type'] ?? 'daily';
        $result['timeseries'] = getTimeseriesData($pdo, $dateFrom, $dateToEnd, $timeType, $hasVisitData);
    }
    if ($action === 'all' || $action === 'pages') {
        $result['pages'] = getPageStats($pdo, $dateFrom, $dateToEnd, $hasVisitData);
    }
    if ($action === 'all' || $action === 'devices') {
        $result['devices'] = getDeviceStats($pdo, $dateFrom, $dateToEnd, $hasVisitData);
    }
    if ($action === 'all' || $action === 'browsers') {
        $result['browsers'] = getBrowserStats($pdo, $dateFrom, $dateToEnd, $hasVisitData);
    }
    if ($action === 'all' || $action === 'referrers') {
        $result['referrers'] = getReferrerStats($pdo, $dateFrom, $dateToEnd, $hasVisitData);
    }
    if ($action === 'all' || $action === 'business') {
        $result['business'] = getBusinessStats($pdo, $dateFrom, $dateToEnd);
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
// 데이터 조회 함수들
// ============================================

function getSummaryData($pdo, $dateFrom, $dateToEnd, $hasVisitData) {
    $today = date('Y-m-d');
    $todayEnd = $today . ' 23:59:59';
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $yesterdayEnd = $yesterday . ' 23:59:59';
    $monthStart = date('Y-m-01');
    $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
    $lastMonthEnd = date('Y-m-t', strtotime('-1 month')) . ' 23:59:59';

    if ($hasVisitData) {
        // 오늘 방문자 (유니크 세션)
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) FROM site_visits WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$today, $todayEnd]);
        $todayVisitors = (int)$stmt->fetchColumn();

        // 어제 방문자
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) FROM site_visits WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$yesterday, $yesterdayEnd]);
        $yesterdayVisitors = (int)$stmt->fetchColumn();

        // 이번달 방문자
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) FROM site_visits WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$monthStart, $todayEnd]);
        $monthVisitors = (int)$stmt->fetchColumn();

        // 지난달 방문자
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT session_id) FROM site_visits WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$lastMonthStart, $lastMonthEnd]);
        $lastMonthVisitors = (int)$stmt->fetchColumn();

        // 오늘 페이지뷰
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM site_visits WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$today, $todayEnd]);
        $todayPageviews = (int)$stmt->fetchColumn();

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

function getTimeseriesData($pdo, $dateFrom, $dateToEnd, $timeType, $hasVisitData) {
    if (!$hasVisitData) {
        return ['has_data' => false, 'message' => '방문자 추적 데이터를 수집 중입니다.', 'data' => []];
    }

    $data = [];
    if ($timeType === 'hourly') {
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

        // 24시간 전부 채우기
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
        $stmt = $pdo->prepare("
            SELECT DATE(created_at) as label,
                   COUNT(DISTINCT session_id) as visitors,
                   COUNT(*) as pageviews
            FROM site_visits
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY label
        ");
        $stmt->execute([$dateFrom, $dateToEnd]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 날짜 범위 전부 채우기
        $dayMap = [];
        foreach ($rows as $r) $dayMap[$r['label']] = $r;
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
        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as label,
                   COUNT(DISTINCT session_id) as visitors,
                   COUNT(*) as pageviews
            FROM site_visits
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY label
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $parts = explode('-', $r['label']);
            $data[] = [
                'label' => (int)$parts[1] . '월',
                'visitors' => (int)$r['visitors'],
                'pageviews' => (int)$r['pageviews'],
            ];
        }
    }

    return ['has_data' => true, 'data' => $data];
}

function getPageStats($pdo, $dateFrom, $dateToEnd, $hasVisitData) {
    if (!$hasVisitData) {
        return ['has_data' => false, 'message' => '방문자 추적 데이터를 수집 중입니다.', 'data' => []];
    }

    $stmt = $pdo->prepare("
        SELECT page_title as title, page_url as page, COUNT(*) as views
        FROM site_visits
        WHERE created_at BETWEEN ? AND ?
        GROUP BY page_title, page_url
        ORDER BY views DESC
        LIMIT 15
    ");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = array_sum(array_column($rows, 'views'));
    foreach ($rows as &$r) {
        $r['views'] = (int)$r['views'];
        $r['percent'] = $total > 0 ? round(($r['views'] / $total) * 100, 1) : 0;
    }

    return ['has_data' => true, 'data' => $rows];
}

function getDeviceStats($pdo, $dateFrom, $dateToEnd, $hasVisitData) {
    if (!$hasVisitData) {
        return ['has_data' => false, 'message' => '방문자 추적 데이터를 수집 중입니다.', 'data' => []];
    }

    $stmt = $pdo->prepare("
        SELECT device_type as device, COUNT(*) as sessions
        FROM site_visits
        WHERE created_at BETWEEN ? AND ?
        GROUP BY device_type
        ORDER BY sessions DESC
    ");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = ['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet'];
    $total = array_sum(array_column($rows, 'sessions'));
    foreach ($rows as &$r) {
        $r['sessions'] = (int)$r['sessions'];
        $r['label'] = $labels[$r['device']] ?? $r['device'];
        $r['percent'] = $total > 0 ? round(($r['sessions'] / $total) * 100, 1) : 0;
    }

    return ['has_data' => true, 'data' => $rows];
}

function getBrowserStats($pdo, $dateFrom, $dateToEnd, $hasVisitData) {
    if (!$hasVisitData) {
        return ['has_data' => false, 'message' => '방문자 추적 데이터를 수집 중입니다.', 'data' => []];
    }

    $stmt = $pdo->prepare("
        SELECT browser, COUNT(*) as sessions
        FROM site_visits
        WHERE created_at BETWEEN ? AND ?
        GROUP BY browser
        ORDER BY sessions DESC
        LIMIT 10
    ");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = array_sum(array_column($rows, 'sessions'));
    foreach ($rows as &$r) {
        $r['sessions'] = (int)$r['sessions'];
        $r['percent'] = $total > 0 ? round(($r['sessions'] / $total) * 100, 1) : 0;
    }

    return ['has_data' => true, 'data' => $rows];
}

function getReferrerStats($pdo, $dateFrom, $dateToEnd, $hasVisitData) {
    if (!$hasVisitData) {
        return ['has_data' => false, 'message' => '방문자 추적 데이터를 수집 중입니다.', 'data' => []];
    }

    $stmt = $pdo->prepare("
        SELECT referrer, COUNT(*) as sessions
        FROM site_visits
        WHERE created_at BETWEEN ? AND ?
        GROUP BY referrer
    ");
    $stmt->execute([$dateFrom, $dateToEnd]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 유입 경로 분류
    $categories = [
        '직접 유입' => 0,
        '네이버' => 0,
        '구글' => 0,
        '다음' => 0,
        '소셜미디어' => 0,
        '기타' => 0,
    ];

    foreach ($rows as $r) {
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

function getBusinessStats($pdo, $dateFrom, $dateToEnd) {
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

    // 인기 제품 TOP 10 (site_visits 기반)
    $topProducts = [];
    $visitCount = (int)$pdo->query("SELECT COUNT(*) FROM site_visits")->fetchColumn();
    if ($visitCount > 0) {
        $stmt = $pdo->prepare("
            SELECT page_url, page_title, COUNT(*) as views
            FROM site_visits
            WHERE page_url LIKE '%product_detail%'
              AND created_at BETWEEN ? AND ?
            GROUP BY page_url, page_title
            ORDER BY views DESC
            LIMIT 10
        ");
        $stmt->execute([$dateFrom, $dateToEnd]);
        $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
