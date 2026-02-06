<?php
/**
 * 방문자 추적 스크립트
 * register_shutdown_function을 사용하여 페이지 응답 후 비동기적으로 INSERT 실행
 * admin 페이지, AJAX 요청은 자동 제외
 */

// 이미 로드된 경우 중복 실행 방지
if (defined('VISITOR_TRACKER_LOADED')) return;
define('VISITOR_TRACKER_LOADED', true);

// admin 페이지 제외
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($requestUri, '/admin/') !== false) return;

// AJAX 요청 제외
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') return;
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false && $_SERVER['REQUEST_METHOD'] === 'POST') return;

// 정적 파일 요청 제외
$ext = pathinfo(parse_url($requestUri, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);
if (in_array(strtolower($ext), ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'map'])) return;

// 페이지 응답 전송 후 실행
register_shutdown_function(function() {
    try {
        require_once __DIR__ . '/../db.php';
        $pdo = getDB();

        if (!session_id()) {
            session_start();
        }
        $sessionId = session_id();

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $pageUrl = parse_url($requestUri, PHP_URL_PATH) ?: '/';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $memberId = $_SESSION['member_id'] ?? null;

        // URL → 페이지명 매핑
        $titleMap = [
            '/' => '메인',
            '/index.php' => '메인',
            '/products_new.php' => '판매제품',
            '/about.php' => '회사소개',
            '/consignment.php' => '중계판매',
            '/quote.php' => '견적문의',
            '/notice.php' => '공지사항',
            '/news.php' => '철강뉴스',
            '/login.php' => '로그인',
            '/register.php' => '회원가입',
            '/mypage.php' => '마이페이지',
            '/my_quote_cart.php' => '제품견적서',
            '/product_detail.php' => '제품상세',
            '/search_results.php' => '검색결과',
            '/find_password.php' => '비밀번호찾기',
            '/location.php' => '오시는길',
        ];
        $pageTitle = $titleMap[$pageUrl] ?? basename($pageUrl, '.php');

        // User-Agent 파싱: device_type
        $ua = strtolower($userAgent);
        $deviceType = 'desktop';
        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
            $deviceType = 'tablet';
        } elseif (preg_match('/mobile|android.*mobile|iphone|ipod|opera mini|opera mobi|blackberry|windows phone/i', $ua)) {
            $deviceType = 'mobile';
        }

        // User-Agent 파싱: browser
        $browser = 'Other';
        if (preg_match('/edg(e|a)?\/[\d]/i', $userAgent)) $browser = 'Edge';
        elseif (preg_match('/samsungbrowser/i', $userAgent)) $browser = 'Samsung Browser';
        elseif (preg_match('/opr\/|opera/i', $userAgent)) $browser = 'Opera';
        elseif (preg_match('/whale/i', $userAgent)) $browser = 'Whale';
        elseif (preg_match('/chrome\/[\d]/i', $userAgent) && !preg_match('/edg/i', $userAgent)) $browser = 'Chrome';
        elseif (preg_match('/firefox\/[\d]/i', $userAgent)) $browser = 'Firefox';
        elseif (preg_match('/safari\/[\d]/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) $browser = 'Safari';
        elseif (preg_match('/trident|msie/i', $userAgent)) $browser = 'IE';

        // User-Agent 파싱: OS
        $os = 'Other';
        if (preg_match('/windows nt/i', $userAgent)) $os = 'Windows';
        elseif (preg_match('/macintosh|mac os x/i', $userAgent)) $os = 'macOS';
        elseif (preg_match('/android/i', $userAgent)) $os = 'Android';
        elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) $os = 'iOS';
        elseif (preg_match('/linux/i', $userAgent)) $os = 'Linux';

        // 신규 방문자 판별 (세션 내 첫 방문)
        $isNewVisitor = 0;
        if (!isset($_SESSION['visitor_tracked'])) {
            $_SESSION['visitor_tracked'] = true;
            $isNewVisitor = 1;
        }

        $stmt = $pdo->prepare("INSERT INTO site_visits
            (session_id, page_url, page_title, ip_address, user_agent, referrer, device_type, browser, os, member_id, is_new_visitor)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $sessionId,
            substr($pageUrl, 0, 500),
            substr($pageTitle, 0, 200),
            $ipAddress,
            substr($userAgent, 0, 500),
            substr($referrer, 0, 500),
            $deviceType,
            $browser,
            $os,
            $memberId,
            $isNewVisitor
        ]);
    } catch (Exception $e) {
        error_log("Visitor tracker error: " . $e->getMessage());
    }
});
