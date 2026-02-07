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
        
        /* 모바일 햄버거 메뉴 버튼 */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
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
        
        /* 드롭다운 메뉴 스타일 */
        .nav-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .nav-dropdown-toggle {
            padding: 16px 24px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .nav-dropdown-toggle:hover {
            color: #1A237E;
            background: #F5F5F7;
        }
        
        .nav-dropdown-toggle.active {
            color: #1A237E;
            border-bottom-color: #1A237E;
        }
        
        .nav-dropdown-toggle::after {
            content: '▼';
            font-size: 10px;
            transition: transform 0.3s ease;
        }
        
        .nav-dropdown.open .nav-dropdown-toggle::after {
            transform: rotate(180deg);
        }
        
        .nav-dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 0 0 8px 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .nav-dropdown.open .nav-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .nav-dropdown-item {
            display: block;
            padding: 12px 20px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .nav-dropdown-item:hover {
            background: #F5F5F7;
            color: #1A237E;
        }
        
        .nav-dropdown-item.active {
            background: #E8EAF6;
            color: #1A237E;
            font-weight: 600;
        }
        
        .nav-dropdown-item:last-child {
            border-radius: 0 0 8px 8px;
        }
        
        /* 모바일 네비게이션 스타일 */
        @media (max-width: 768px) {
            .admin-nav {
                position: fixed;
                left: -280px;
                top: 0;
                width: 280px;
                height: 100vh;
                background: white;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
                transition: left 0.3s ease;
                z-index: 1000;
                overflow-y: auto;
            }
            
            .admin-nav.mobile-open {
                left: 0;
            }
            
            .nav-content {
                padding: 0;
            }
            
            .nav-menu {
                flex-direction: column;
                gap: 0;
                padding-top: 60px;
            }
            
            .nav-item,
            .nav-dropdown-toggle {
                width: 100%;
                padding: 16px 20px;
                border-bottom: none;
                border-left: 3px solid transparent;
            }
            
            .nav-item.active,
            .nav-dropdown-toggle.active {
                border-left-color: #1A237E;
                border-bottom-color: transparent;
            }
            
            .nav-dropdown-menu {
                position: static;
                box-shadow: none;
                border-radius: 0;
                margin-left: 20px;
                background: #f8f9fa;
                transform: none;
                opacity: 1;
                visibility: visible;
                display: none;
            }
            
            .nav-dropdown.open .nav-dropdown-menu {
                display: block;
            }
            
            /* 모바일 오버레이 */
            .mobile-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            
            .mobile-overlay.active {
                display: block;
            }
            
            /* 헤더 반응형 */
            .header-title {
                font-size: 20px;
            }
            
            .header-info {
                gap: 10px;
            }
            
            .admin-info {
                display: none;
            }
            
            /* 콘텐츠 반응형 */
            .admin-content {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .page-header {
                padding: 16px;
            }
            
            .page-header h1 {
                font-size: 20px;
            }
            
            /* 테이블 반응형 */
            .data-table-wrapper {
                margin: 0 -15px;
            }
            
            .data-table th,
            .data-table td {
                padding: 12px 8px;
                font-size: 13px;
            }
            
            /* 필터 반응형 */
            .filter-form {
                flex-direction: column;
                gap: 8px;
            }
            
            .filter-form input[type="text"],
            .filter-form input[type="date"],
            .filter-form select,
            .filter-form button {
                width: 100%;
            }
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
        
        .action-links a,
        .action-links button {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            border: none;
            cursor: pointer;
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
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
            <h1 class="header-title">충남스틸 관리자</h1>
            <div class="header-info">
                <span class="admin-info">관리자: <?php echo $_SESSION['admin_id']; ?></span>
                <a href="admin_logout.php" class="logout-btn">로그아웃</a>
            </div>
        </div>
    </header>
    
    <!-- 모바일 오버레이 -->
    <div class="mobile-overlay" onclick="closeMobileMenu()"></div>
    
    <nav class="admin-nav">
        <div class="nav-content">
            <div class="nav-menu">
                <a href="admin_index.php" class="nav-item <?php echo $currentFile === 'admin_index.php' ? 'active' : ''; ?>">대시보드</a>
                
                <!-- 게시판 관리 드롭다운 -->
                <div class="nav-dropdown" id="boardDropdown">
                    <a href="#" class="nav-dropdown-toggle <?php echo in_array($currentFile, ['admin_notices.php', 'admin_notice_write.php', 'admin_news.php', 'admin_news_write.php']) ? 'active' : ''; ?>" onclick="toggleDropdown('boardDropdown'); return false;">
                        게시판 관리
                    </a>
                    <div class="nav-dropdown-menu">
                        <a href="admin_notices.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_notices.php' || $currentFile === 'admin_notice_write.php' ? 'active' : ''; ?>">공지사항 관리</a>
                        <a href="admin_news.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_news.php' || $currentFile === 'admin_news_write.php' ? 'active' : ''; ?>">철강뉴스 관리</a>
                    </div>
                </div>
                
                <!-- 영업 관리 드롭다운 -->
                <div class="nav-dropdown" id="salesDropdown">
                    <a href="#" class="nav-dropdown-toggle <?php echo in_array($currentFile, ['admin_quotes.php', 'admin_quote_view.php', 'admin_consignment.php', 'admin_products_integrated.php', 'admin_products_edit.php', 'admin_unit_weights_edit.php', 'admin_product_quotes.php', 'admin_product_quote_view.php', 'admin_product_categories.php', 'admin_product_categories_tree_v2.php']) ? 'active' : ''; ?>" onclick="toggleDropdown('salesDropdown'); return false;">
                        영업 관리
                    </a>
                    <div class="nav-dropdown-menu">
                        <a href="admin_quotes.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_quotes.php' || $currentFile === 'admin_quote_view.php' ? 'active' : ''; ?>">견적문의 관리</a>
                        <a href="admin_consignment.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_consignment.php' ? 'active' : ''; ?>">위탁판매 관리</a>
                        <a href="admin_products_integrated.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_products_integrated.php' || $currentFile === 'admin_products_edit.php' || $currentFile === 'admin_unit_weights_edit.php' ? 'active' : ''; ?>">제품 관리</a>
                        <a href="admin_bulk_image_apply.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_bulk_image_apply.php' ? 'active' : ''; ?>">제품 이미지 일괄 등록</a>
                        <a href="admin_product_quotes.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_product_quotes.php' || $currentFile === 'admin_product_quote_view.php' ? 'active' : ''; ?>">제품견적서</a>
                        <a href="admin_product_categories_tree_v2.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_product_categories_tree_v2.php' || $currentFile === 'admin_product_categories.php' ? 'active' : ''; ?>">카테고리 관리</a>
                    </div>
                </div>
                
                <!-- 고객 관리 드롭다운 -->
                <div class="nav-dropdown" id="customerDropdown">
                    <a href="#" class="nav-dropdown-toggle <?php echo in_array($currentFile, ['admin_members.php', 'admin_members_detail.php']) || strpos($currentFile, 'admin_kakao') === 0 ? 'active' : ''; ?>" onclick="toggleDropdown('customerDropdown'); return false;">
                        고객 관리
                    </a>
                    <div class="nav-dropdown-menu">
                        <a href="admin_members.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_members.php' || $currentFile === 'admin_members_detail.php' ? 'active' : ''; ?>">회원 관리</a>
                    </div>
                </div>
                
                <!-- 시스템 관리 드롭다운 -->
                <div class="nav-dropdown" id="systemDropdown">
                    <a href="#" class="nav-dropdown-toggle <?php echo in_array($currentFile, ['admin_statistics.php', 'admin_site.php', 'admin_banners.php', 'admin_product_icons.php', 'admin_product_icon_edit.php', 'admin_layer_popups.php', 'admin_layer_popup_edit.php', 'admin_backup.php', 'admin_mail_manage.php', 'admin_logs.php']) ? 'active' : ''; ?>" onclick="toggleDropdown('systemDropdown'); return false;">
                        시스템 관리
                    </a>
                    <div class="nav-dropdown-menu">
                        <a href="admin_statistics.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_statistics.php' ? 'active' : ''; ?>">접속통계</a>
                        <a href="admin_site.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_site.php' ? 'active' : ''; ?>">사이트 관리</a>
                        <a href="admin_banners.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_banners.php' ? 'active' : ''; ?>">배너 관리</a>
                        <a href="admin_layer_popups.php" class="nav-dropdown-item <?php echo in_array($currentFile, ['admin_layer_popups.php', 'admin_layer_popup_edit.php']) ? 'active' : ''; ?>">레이어팝업 관리</a>
                        <a href="admin_product_icons.php" class="nav-dropdown-item <?php echo in_array($currentFile, ['admin_product_icons.php', 'admin_product_icon_edit.php']) ? 'active' : ''; ?>">제품 아이콘 관리</a>
                        <a href="admin_backup.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_backup.php' ? 'active' : ''; ?>">백업/복원</a>
                        <a href="admin_mail_manage.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_mail_manage.php' ? 'active' : ''; ?>">메일 관리</a>
                        <a href="admin_logs.php" class="nav-dropdown-item <?php echo $currentFile === 'admin_logs.php' ? 'active' : ''; ?>">로그 관리</a>
                    </div>
                </div>
                <a href="../index.php" class="nav-item" target="_blank">사이트 보기</a>
            </div>
        </div>
    </nav>
    
    <script>
    // 드롭다운 토글 함수
    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const isOpen = dropdown.classList.contains('open');
        
        // 모든 드롭다운 닫기
        document.querySelectorAll('.nav-dropdown').forEach(d => {
            d.classList.remove('open');
        });
        
        // 클릭한 드롭다운 토글
        if (!isOpen) {
            dropdown.classList.add('open');
        }
    }
    
    // 외부 클릭시 드롭다운 닫기
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown').forEach(d => {
                d.classList.remove('open');
            });
        }
    });
    
    // 현재 페이지에 해당하는 드롭다운 자동 열기 (선택사항)
    window.addEventListener('DOMContentLoaded', function() {
        const activeDropdownToggle = document.querySelector('.nav-dropdown-toggle.active');
        if (activeDropdownToggle) {
            // activeDropdownToggle.closest('.nav-dropdown').classList.add('open');
        }
    });
    
    // 모바일 메뉴 토글
    function toggleMobileMenu() {
        const nav = document.querySelector('.admin-nav');
        const overlay = document.querySelector('.mobile-overlay');
        
        nav.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
        
        // 모바일에서 메뉴 열릴 때 body 스크롤 방지
        if (nav.classList.contains('mobile-open')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
    
    // 모바일 메뉴 닫기
    function closeMobileMenu() {
        const nav = document.querySelector('.admin-nav');
        const overlay = document.querySelector('.mobile-overlay');
        
        nav.classList.remove('mobile-open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // 창 크기 변경시 모바일 메뉴 초기화
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMobileMenu();
        }
    });
    
    // 모바일에서 메뉴 항목 클릭시 처리
    document.addEventListener('DOMContentLoaded', function() {
        // 모바일에서 드롭다운 토글 개선
        const dropdownToggles = document.querySelectorAll('.nav-dropdown-toggle');
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    toggleDropdown(this.closest('.nav-dropdown').id);
                }
            });
        });
    });
    </script>
    
    <main class="admin-content">
