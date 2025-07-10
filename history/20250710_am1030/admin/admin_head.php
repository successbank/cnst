<?php
// 세션 체크 (admin_check.php 포함)
require_once 'admin_check.php';
require_once '../db.php';

// 현재 페이지 확인
$currentFile = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '관리자'; ?> | 충남스틸</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #F5F5F7;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-y: scroll; /* 항상 세로 스크롤바 표시 */
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
            width: 100%;
            flex: 1;
        }
        
        /* 공통 스타일 */
        .page-header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }
        
        .page-header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: #666;
            font-size: 14px;
        }
        
        .data-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .data-table-wrapper {
            overflow-x: auto;
        }
        
        .data-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: #F5F5F7;
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: #666;
            white-space: nowrap;
        }
        
        .data-table td {
            padding: 16px 12px;
            border-top: 1px solid #E5E5E7;
            font-size: 14px;
        }
        
        .data-table tr:hover {
            background: #F8F9FA;
        }
        
        .msg {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .msg.success {
            background: #E8F5E9;
            color: #2E7D32;
        }
        
        .msg.error {
            background: #FFEBEE;
            color: #C62828;
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
        
        .filter-form input[type="text"],
        .filter-form input[type="date"],
        .filter-form select {
            padding: 10px 16px;
            border: 1px solid #E5E5E7;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .filter-form button {
            padding: 10px 20px;
            background: #1A237E;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-form button:hover {
            background: #283593;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }
        
        .page-link {
            padding: 8px 12px;
            background: white;
            border: 1px solid #E5E5E7;
            color: #666;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .page-link:hover,
        .page-link.active {
            background: #1A237E;
            color: white;
            border-color: #1A237E;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .action-links {
            display: flex;
            gap: 4px;
            justify-content: center;
        }
        
        .action-links a {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .btn-view {
            background: #E8EAF6;
            color: #3F51B5;
        }
        
        .btn-view:hover {
            background: #C5CAE9;
        }
        
        .btn-edit {
            background: #E0F2F1;
            color: #00897B;
        }
        
        .btn-edit:hover {
            background: #B2DFDB;
        }
        
        .btn-delete {
            background: #FFEBEE;
            color: #E53935;
        }
        
        .btn-delete:hover {
            background: #FFCDD2;
        }
        
        .btn-toggle {
            background: #FFF3E0;
            color: #FB8C00;
        }
        
        .btn-toggle:hover {
            background: #FFE0B2;
        }
        
        /* 추가 페이지별 스타일 */
        <?php if (isset($additionalStyles)): ?>
        <?php echo $additionalStyles; ?>
        <?php endif; ?>
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
                <a href="admin_index.php" class="nav-item <?php echo $currentFile === 'admin_index.php' ? 'active' : ''; ?>">대시보드</a>
                <a href="admin_notices.php" class="nav-item <?php echo $currentFile === 'admin_notices.php' || $currentFile === 'admin_notice_write.php' ? 'active' : ''; ?>">공지사항 관리</a>
                <a href="admin_quotes.php" class="nav-item <?php echo $currentFile === 'admin_quotes.php' || $currentFile === 'admin_quote_view.php' ? 'active' : ''; ?>">견적문의 관리</a>
                <a href="admin_news.php" class="nav-item <?php echo $currentFile === 'admin_news.php' || $currentFile === 'admin_news_write.php' ? 'active' : ''; ?>">철강뉴스 관리</a>
                <a href="admin_consignment.php" class="nav-item <?php echo $currentFile === 'admin_consignment.php' ? 'active' : ''; ?>">위탁판매 관리</a>
                <a href="admin_members.php" class="nav-item <?php echo $currentFile === 'admin_members.php' ? 'active' : ''; ?>">회원 관리</a>
                <a href="admin_kakao.php" class="nav-item <?php echo strpos($currentFile, 'admin_kakao') === 0 ? 'active' : ''; ?>">카카오톡</a>
                <a href="admin_statistics.php" class="nav-item <?php echo $currentFile === 'admin_statistics.php' ? 'active' : ''; ?>">접속통계</a>
                <a href="admin_site.php" class="nav-item <?php echo $currentFile === 'admin_site.php' ? 'active' : ''; ?>">사이트 관리</a>
                <a href="../index.php" class="nav-item" target="_blank">사이트 보기</a>
            </div>
        </div>
    </nav>
    
    <main class="admin-content">