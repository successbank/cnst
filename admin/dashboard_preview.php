<?php
// 임시 대시보드 미리보기 (로그인 불필요)
require_once dirname(__DIR__) . '/db.php';
$pdo = getDB();

// 추가 스타일
$additionalStyles = '
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: #666;
}

.stat-meta {
    font-size: 12px;
    color: #999;
    margin-top: 8px;
}

.icon-notices { background: #E8EAF6; color: #3F51B5; }
.icon-quotes { background: #E8F5E9; color: #2E7D32; }
.icon-news { background: #FFF3E0; color: #F57C00; }
.icon-consignment { background: #F3E5F5; color: #7B1FA2; }
.icon-members { background: #E0F2F1; color: #00897B; }
.icon-kakao { background: #FFF9C4; color: #F57F17; }
';

// 데이터베이스 통계 조회
$noticeCount = 0;
$quoteCount = 0;
$quotePending = 0;
$newsCount = 0;
$consignmentCount = 0;
$consignmentActive = 0;
$memberCount = 0;
$kakaoSent = 0;
$kakaoTotal = 0;

try {
    // 공지사항
    $stmt = $pdo->query("SELECT COUNT(*) FROM board_notice");
    $noticeCount = $stmt->fetchColumn();
    
    // 견적문의
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_answered = 0 OR is_answered IS NULL THEN 1 ELSE 0 END) as pending FROM board_quote");
    $result = $stmt->fetch();
    $quoteCount = $result['total'] ?? 0;
    $quotePending = $result['pending'] ?? 0;
    
    // 철강뉴스
    $stmt = $pdo->query("SELECT COUNT(*) FROM board_news");
    $newsCount = $stmt->fetchColumn();
    
    // 위탁판매
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM board_consignment");
    $consignmentCount = $stmt->fetchColumn();
    
    // 회원
    $stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE is_admin = 0");
    $memberCount = $stmt->fetchColumn();
    
    // 카카오톡 알림 (오늘)
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent FROM kakao_notifications WHERE DATE(created_at) = CURDATE()");
    $result = $stmt->fetch();
    $kakaoTotal = $result['total'] ?? 0;
    $kakaoSent = $result['sent'] ?? 0;
    
} catch (Exception $e) {
    echo "<!-- 에러: " . $e->getMessage() . " -->\n";
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 대시보드 미리보기</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f7;
            margin: 0;
            padding: 20px;
        }
        <?php echo $additionalStyles; ?>
    </style>
</head>
<body>

<h1>관리자 대시보드 (미리보기)</h1>
<p>충남스틸 웹사이트 관리 시스템 - 데이터 확인용</p>

<!-- 통계 카드 -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon icon-notices">📢</div>
        <div class="stat-value"><?php echo number_format($noticeCount); ?></div>
        <div class="stat-label">공지사항</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-quotes">📋</div>
        <div class="stat-value"><?php echo number_format($quoteCount); ?></div>
        <div class="stat-label">견적문의</div>
        <div class="stat-meta">대기중: <?php echo number_format($quotePending); ?>건</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-news">📰</div>
        <div class="stat-value"><?php echo number_format($newsCount); ?></div>
        <div class="stat-label">철강뉴스</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-consignment">📦</div>
        <div class="stat-value"><?php echo number_format($consignmentCount); ?></div>
        <div class="stat-label">위탁판매</div>
        <div class="stat-meta">진행중: <?php echo number_format($consignmentActive); ?>건</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-members">👥</div>
        <div class="stat-value"><?php echo number_format($memberCount); ?></div>
        <div class="stat-label">회원</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-kakao">💬</div>
        <div class="stat-value"><?php echo number_format($kakaoSent); ?></div>
        <div class="stat-label">오늘 카카오톡 발송</div>
        <div class="stat-meta">전체: <?php echo number_format($kakaoTotal); ?>건</div>
    </div>
</div>

<p style="margin-top: 40px; color: #666;">
    이 페이지는 대시보드 데이터가 정상적으로 표시되는지 확인하기 위한 미리보기 페이지입니다.<br>
    실제 관리자 대시보드는 <a href="/admin/admin_index.php">여기</a>에서 로그인 후 접속하실 수 있습니다.
</p>

</body>
</html>