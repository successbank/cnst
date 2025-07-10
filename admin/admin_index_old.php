<?php
require_once 'admin_check.php';
require_once '../db.php';

// 통계 데이터 가져오기
$stats = [
    'total_notices' => 0,
    'total_quotes' => 0,
    'today_quotes' => 0,
    'total_news' => 0
];

try {
    // 공지사항 수
    $stmt = $pdo->query("SELECT COUNT(*) FROM notices");
    $stats['total_notices'] = $stmt->fetchColumn();
    
    // 전체 견적문의 수
    $stmt = $pdo->query("SELECT COUNT(*) FROM quotes");
    $stats['total_quotes'] = $stmt->fetchColumn();
    
    // 오늘 견적문의 수
    $stmt = $pdo->query("SELECT COUNT(*) FROM quotes WHERE DATE(created_at) = CURDATE()");
    $stats['today_quotes'] = $stmt->fetchColumn();
    
    // 철강뉴스 수
    $stmt = $pdo->query("SELECT COUNT(*) FROM news");
    $stats['total_news'] = $stmt->fetchColumn();
} catch(PDOException $e) {
    // 에러 무시 (테이블이 없을 수 있음)
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 대시보드 | 충남스틸</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #F5F5F7;
        }
        
        .admin-header {
            background: #1A237E;
            color: white;
            padding: 16px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            font-size: 24px;
            font-weight: 700;
        }
        
        .header-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .admin-info {
            font-size: 14px;
        }
        
        .logout-btn {
            padding: 8px 16px;
            background: white;
            color: #1A237E;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #F5F5F7;
        }
        
        .admin-nav {
            background: white;
            border-bottom: 1px solid #E5E5E7;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav-menu {
            display: flex;
            gap: 0;
        }
        
        .nav-item {
            padding: 16px 24px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .nav-item:hover {
            color: #1A237E;
            background: #F5F5F7;
        }
        
        .nav-item.active {
            color: #1A237E;
            border-bottom-color: #1A237E;
        }
        
        .admin-content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
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
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: #1A237E;
        }
        
        .stat-card .desc {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }
        
        .quick-links {
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .quick-links h2 {
            font-size: 20px;
            margin-bottom: 24px;
            color: #333;
        }
        
        .link-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .link-item {
            display: flex;
            align-items: center;
            padding: 16px;
            background: #F5F5F7;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .link-item:hover {
            background: #1A237E;
            color: white;
            transform: translateX(4px);
        }
        
        .link-item::before {
            content: '→';
            margin-right: 12px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="header-content">
            <h1 class="header-title">충남스틸 관리자</h1>
            <div class="header-info">
                <span class="admin-info">관리자: <?php echo $_SESSION['admin_id']; ?></span>
                <a href="admin_logout.php" class="logout-btn">로그아웃</a>
            </div>
        </div>
    </header>
    
    <nav class="admin-nav">
        <div class="nav-content">
            <div class="nav-menu">
                <a href="admin_index.php" class="nav-item active">대시보드</a>
                <a href="admin_notices.php" class="nav-item">공지사항 관리</a>
                <a href="admin_quotes.php" class="nav-item">견적문의 관리</a>
                <a href="admin_news.php" class="nav-item">철강뉴스 관리</a>
                <a href="admin_products.php" class="nav-item">제품 관리</a>
                <a href="../index.php" class="nav-item" target="_blank">사이트 보기</a>
            </div>
        </div>
    </nav>
    
    <main class="admin-content">
        <div class="dashboard-grid">
            <div class="stat-card">
                <h3>공지사항</h3>
                <div class="number"><?php echo number_format($stats['total_notices']); ?></div>
                <div class="desc">전체 공지사항 수</div>
            </div>
            
            <div class="stat-card">
                <h3>견적문의</h3>
                <div class="number"><?php echo number_format($stats['total_quotes']); ?></div>
                <div class="desc">전체 견적문의 수</div>
            </div>
            
            <div class="stat-card">
                <h3>오늘의 견적문의</h3>
                <div class="number"><?php echo number_format($stats['today_quotes']); ?></div>
                <div class="desc">오늘 접수된 견적</div>
            </div>
            
            <div class="stat-card">
                <h3>철강뉴스</h3>
                <div class="number"><?php echo number_format($stats['total_news']); ?></div>
                <div class="desc">전체 뉴스 수</div>
            </div>
        </div>
        
        <div class="quick-links">
            <h2>빠른 메뉴</h2>
            <div class="link-grid">
                <a href="admin_notices.php?action=write" class="link-item">공지사항 작성</a>
                <a href="admin_quotes.php" class="link-item">견적문의 확인</a>
                <a href="admin_news.php?action=write" class="link-item">철강뉴스 작성</a>
                <a href="admin_products.php" class="link-item">제품 관리</a>
                <a href="admin_settings.php" class="link-item">사이트 설정</a>
                <a href="admin_backup.php" class="link-item">데이터 백업</a>
            </div>
        </div>
    </main>
</body>
</html>