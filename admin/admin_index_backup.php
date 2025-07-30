<?php
$pageTitle = '관리자 대시보드';

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

.recent-section {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.recent-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
}

.recent-list {
    list-style: none;
}

.recent-item {
    padding: 12px 0;
    border-bottom: 1px solid #E5E5E7;
}

.recent-item:last-child {
    border-bottom: none;
}

.recent-item a {
    color: #333;
    text-decoration: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.recent-item a:hover {
    color: #1A237E;
}

.recent-date {
    font-size: 12px;
    color: #999;
}

.quick-actions {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.quick-actions-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
}

.quick-action-btn {
    padding: 16px;
    background: #F5F5F7;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    text-align: center;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    background: #1A237E;
    color: white;
    border-color: #1A237E;
}
';

require_once 'admin_head.php';

// 대시보드 통계 데이터 조회
// 기본값 설정
$noticeStats = ['total' => 0];
$quoteStats = ['total' => 0, 'pending' => 0];
$newsStats = ['total' => 0];
$consignmentStats = ['total' => 0, 'active' => 0];
$memberStats = ['total' => 0];
$kakaoStats = ['total' => 0, 'sent' => 0];
$recentQuotes = [];
$recentNotices = [];

try {
    // 공지사항
    $noticeStmt = $pdo->query("SELECT COUNT(*) as total FROM board_notice");
    $noticeStats = $noticeStmt->fetch();
    
    // 견적문의
    $quoteStmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_answered = 0 OR is_answered IS NULL THEN 1 ELSE 0 END) as pending FROM board_quote");
    $quoteStats = $quoteStmt->fetch();
    
    // 철강뉴스
    $newsStmt = $pdo->query("SELECT COUNT(*) as total FROM board_news");
    $newsStats = $newsStmt->fetch();
    
    // 위탁판매
    $consignmentStmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM board_consignment");
    $consignmentStats = $consignmentStmt->fetch();
    
    // 회원
    $memberStmt = $pdo->query("SELECT COUNT(*) as total FROM members WHERE is_admin = 0");
    $memberStats = $memberStmt->fetch();
    
    // 카카오톡 알림 (오늘)
    $kakaoStmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent FROM kakao_notifications WHERE DATE(created_at) = CURDATE()");
    $kakaoStats = $kakaoStmt->fetch();
    
    // 최근 견적문의
    $recentQuotes = $pdo->query("SELECT id, title, writer, created_at FROM board_quote ORDER BY created_at DESC LIMIT 5")->fetchAll();
    
    // 최근 공지사항
    $recentNotices = $pdo->query("SELECT id, title, created_at FROM board_notice ORDER BY created_at DESC LIMIT 5")->fetchAll();
    
} catch (PDOException $e) {
    // 에러 처리 및 디버깅
    error_log("Dashboard Error: " . $e->getMessage());
    echo "<!-- Debug Error: " . htmlspecialchars($e->getMessage()) . " -->";
} catch (Exception $e) {
    // 일반 에러 처리
    error_log("Dashboard General Error: " . $e->getMessage());
    echo "<!-- Debug General Error: " . htmlspecialchars($e->getMessage()) . " -->";
}
?>

<div class="page-header">
    <h1>관리자 대시보드</h1>
    <p>충남스틸 웹사이트 관리 시스템에 오신 것을 환영합니다.</p>
</div>

<!-- 통계 카드 -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon icon-notices">📢</div>
        <div class="stat-value"><?php echo $noticeStats['total']; ?></div>
        <div class="stat-label">공지사항</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-quotes">📋</div>
        <div class="stat-value"><?php echo $quoteStats['total']; ?></div>
        <div class="stat-label">견적문의</div>
        <div class="stat-meta">대기중: <?php echo $quoteStats['pending'] ?? 0; ?>건</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-news">📰</div>
        <div class="stat-value"><?php echo $newsStats['total']; ?></div>
        <div class="stat-label">철강뉴스</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-consignment">📦</div>
        <div class="stat-value"><?php echo $consignmentStats['total']; ?></div>
        <div class="stat-label">위탁판매</div>
        <div class="stat-meta">진행중: <?php echo $consignmentStats['active'] ?? 0; ?>건</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-members">👥</div>
        <div class="stat-value"><?php echo $memberStats['total']; ?></div>
        <div class="stat-label">회원</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon icon-kakao">💬</div>
        <div class="stat-value"><?php echo $kakaoStats['sent'] ?? 0; ?></div>
        <div class="stat-label">오늘 카카오톡 발송</div>
        <div class="stat-meta">전체: <?php echo $kakaoStats['total'] ?? 0; ?>건</div>
    </div>
</div>

<!-- 빠른 작업 -->
<div class="quick-actions">
    <h3 class="quick-actions-title">빠른 작업</h3>
    <div class="quick-actions-grid">
        <a href="admin_notices.php?action=write" class="quick-action-btn">공지사항 작성</a>
        <a href="admin_news.php?action=write" class="quick-action-btn">철강뉴스 작성</a>
        <a href="admin_quotes.php" class="quick-action-btn">견적문의 확인</a>
        <a href="admin_consignment.php" class="quick-action-btn">위탁판매 관리</a>
        <a href="admin_members.php" class="quick-action-btn">회원 관리</a>
        <a href="admin_kakao.php" class="quick-action-btn">카카오톡 현황</a>
    </div>
</div>

<!-- 최근 항목 -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="recent-section">
        <h3 class="recent-title">최근 견적문의</h3>
        <ul class="recent-list">
            <?php if (empty($recentQuotes)): ?>
                <li class="recent-item" style="text-align: center; color: #999;">
                    등록된 견적문의가 없습니다.
                </li>
            <?php else: ?>
                <?php foreach ($recentQuotes as $quote): ?>
                <li class="recent-item">
                    <a href="admin_quote_view.php?id=<?php echo $quote['id']; ?>">
                        <span><?php echo htmlspecialchars($quote['title']); ?></span>
                        <span class="recent-date"><?php echo date('m/d', strtotime($quote['created_at'])); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
    
    <div class="recent-section">
        <h3 class="recent-title">최근 공지사항</h3>
        <ul class="recent-list">
            <?php if (empty($recentNotices)): ?>
                <li class="recent-item" style="text-align: center; color: #999;">
                    등록된 공지사항이 없습니다.
                </li>
            <?php else: ?>
                <?php foreach ($recentNotices as $notice): ?>
                <li class="recent-item">
                    <a href="admin_notice_edit.php?id=<?php echo $notice['id']; ?>">
                        <span><?php echo htmlspecialchars($notice['title']); ?></span>
                        <span class="recent-date"><?php echo date('m/d', strtotime($notice['created_at'])); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php require_once 'admin_tail.php'; ?>