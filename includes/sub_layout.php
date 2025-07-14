<?php
// 서브페이지 공통 레이아웃 함수
function startSubPage($title, $currentMenu = '') {
    ?>
    <style>
    /* 서브페이지 공통 스타일 */
    .sub-container {
        display: flex;
        gap: 24px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
        min-height: calc(100vh - 200px);
    }

    /* 사이드바 */
    .sub-sidebar {
        width: 240px;
        flex-shrink: 0;
    }

    .sidebar-title {
        background: #1A237E;
        color: white;
        padding: 16px 20px;
        font-size: 18px;
        font-weight: 700;
        border-radius: 8px 8px 0 0;
    }

    .sidebar-menu {
        background: white;
        border: 1px solid #E5E5E7;
        border-top: none;
        border-radius: 0 0 8px 8px;
        overflow: hidden;
    }

    .sidebar-menu a {
        display: block;
        padding: 14px 20px;
        color: #666;
        text-decoration: none;
        font-size: 15px;
        border-bottom: 1px solid #F0F0F0;
        transition: all 0.3s ease;
    }

    .sidebar-menu a:last-child {
        border-bottom: none;
    }

    .sidebar-menu a:hover {
        background: #F8F9FA;
        color: #1A237E;
        padding-left: 24px;
    }

    .sidebar-menu a.active {
        background: #F8F9FA;
        color: #1A237E;
        font-weight: 600;
        border-left: 3px solid #1A237E;
    }

    /* 메인 콘텐츠 */
    .sub-content {
        flex: 1;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .content-header {
        background: #F8F9FA;
        padding: 32px;
        border-bottom: 1px solid #E5E5E7;
    }

    .content-header h2 {
        font-size: 24px;
        font-weight: 700;
        color: #333;
        margin-bottom: 8px;
    }

    .content-header p {
        font-size: 15px;
        color: #666;
    }

    .content-body {
        padding: 40px;
    }

    /* 정보 섹션 */
    .info-section {
        margin-bottom: 40px;
    }

    .info-section:last-child {
        margin-bottom: 0;
    }

    .info-section h3 {
        font-size: 18px;
        font-weight: 700;
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #E5E5E7;
    }

    .info-row {
        display: flex;
        padding: 16px 0;
        border-bottom: 1px solid #F0F0F0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        width: 140px;
        font-size: 14px;
        color: #666;
        font-weight: 500;
    }

    .info-value {
        flex: 1;
        font-size: 14px;
        color: #333;
    }

    /* 버튼 */
    .btn-group {
        display: flex;
        gap: 12px;
        margin-top: 32px;
        padding-top: 32px;
        border-top: 1px solid #E5E5E7;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }

    .btn-primary {
        background: #1A237E;
        color: white;
    }

    .btn-primary:hover {
        background: #283593;
    }

    .btn-secondary {
        background: white;
        color: #666;
        border: 2px solid #E5E5E7;
    }

    .btn-secondary:hover {
        background: #F8F9FA;
        border-color: #D0D0D2;
    }

    /* 폼 스타일 */
    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #E5E5E7;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #1A237E;
        box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
    }

    /* 반응형 */
    @media (max-width: 768px) {
        .sub-container {
            flex-direction: column;
        }
        
        .sub-sidebar {
            width: 100%;
        }
        
        .content-body {
            padding: 20px;
        }
        
        .info-row {
            flex-direction: column;
            gap: 8px;
        }
        
        .info-label {
            width: 100%;
            font-weight: 600;
        }
    }
    </style>
    
    <div class="sub-container">
    <?php
}

function endSubPage() {
    ?>
    </div>
    <?php
}

// 마이페이지 사이드바
function myPageSidebar($current = '') {
    ?>
    <aside class="sub-sidebar">
        <h3 class="sidebar-title">마이페이지</h3>
        <nav class="sidebar-menu">
            <a href="mypage.php" class="<?php echo $current == 'info' ? 'active' : ''; ?>">회원정보</a>
            <a href="edit_profile.php" class="<?php echo $current == 'edit' ? 'active' : ''; ?>">정보수정</a>
            <a href="my_quote_cart.php" class="<?php echo $current == 'quote_cart' ? 'active' : ''; ?>">제품견적서</a>
            <a href="my_inquiries.php" class="<?php echo $current == 'inquiries' ? 'active' : ''; ?>">문의내역</a>
            <a href="logout.php">로그아웃</a>
        </nav>
    </aside>
    <?php
}

// 고객센터 사이드바
function customerSidebar($current = '') {
    ?>
    <aside class="sub-sidebar">
        <h3 class="sidebar-title">고객센터</h3>
        <nav class="sidebar-menu">
            <a href="notice.php" class="<?php echo $current == 'notice' ? 'active' : ''; ?>">공지사항</a>
            <a href="news.php" class="<?php echo $current == 'news' ? 'active' : ''; ?>">철강뉴스</a>
            <a href="quote.php" class="<?php echo $current == 'quote' ? 'active' : ''; ?>">견적문의</a>
            <a href="consignment.php" class="<?php echo $current == 'consignment' ? 'active' : ''; ?>">중계판매</a>
            <a href="location.php" class="<?php echo $current == 'location' ? 'active' : ''; ?>">오시는길</a>
        </nav>
    </aside>
    <?php
}

// 제품 사이드바
function productSidebar($current = '') {
    ?>
    <aside class="sub-sidebar">
        <h3 class="sidebar-title">제품정보</h3>
        <nav class="sidebar-menu">
            <a href="sales.php" class="<?php echo $current == 'sales' ? 'active' : ''; ?>">판매제품</a>
            <a href="products.php" class="<?php echo $current == 'products' ? 'active' : ''; ?>">제품소개</a>
            <a href="consignment.php" class="<?php echo $current == 'consignment' ? 'active' : ''; ?>">중계판매</a>
            <a href="quote.php" class="<?php echo $current == 'quote' ? 'active' : ''; ?>">견적문의</a>
        </nav>
    </aside>
    <?php
}

// 문의내역 서브메뉴
function inquirySubmenu($current = 'all') {
    ?>
    <div class="inquiry-submenu">
        <a href="my_inquiries.php" class="menu-item <?php echo $current == 'all' ? 'active' : ''; ?>">
            전체 문의내역
            <span class="submenu-desc">견적문의 내역 + 중계판매 신청 내역</span>
        </a>
        <span class="menu-divider">|</span>
        <a href="javascript:void(0)" onclick="toggleQuoteForm()" class="menu-action">견적문의 하기</a>
        <span class="menu-divider">|</span>
        <a href="javascript:void(0)" onclick="toggleConsignmentForm()" class="menu-action">중계판매 신청</a>
    </div>
    <?php
}

// 회사 사이드바
function companySidebar($current = '') {
    ?>
    <aside class="sub-sidebar">
        <h3 class="sidebar-title">회사소개</h3>
        <nav class="sidebar-menu">
            <a href="about.php" class="<?php echo $current == 'about' ? 'active' : ''; ?>">회사개요</a>
            <a href="vision.php" class="<?php echo $current == 'vision' ? 'active' : ''; ?>">경영이념</a>
            <a href="location.php" class="<?php echo $current == 'location' ? 'active' : ''; ?>">오시는길</a>
        </nav>
    </aside>
    <?php
}
?>