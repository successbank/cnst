<?php
// 세션 시작 및 회원 체크 (HTML 출력 전에 실행)
if(!isset($_SESSION)) {
    session_start();
}
require_once 'member_check.php';
require_once 'db.php';
if(file_exists('includes/settings.php')) {
    require_once 'includes/settings.php';
}

// 방문자 추적
if (file_exists(__DIR__ . '/includes/visitor_tracker.php')) {
    require_once __DIR__ . '/includes/visitor_tracker.php';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php 
    // SEO 메타 태그 출력
    if(function_exists('printSeoMeta')) {
        printSeoMeta(isset($pageTitle) ? $pageTitle : null);
    } else {
        // 기본값 사용
        echo '<title>' . (isset($pageTitle) ? $pageTitle . ' | ' : '') . '충남스틸</title>';
        echo '<meta name="description" content="충남스틸 - 믿을 수 있는 철강 파트너, 다양한 철강 제품과 서비스를 제공합니다.">';
        echo '<meta name="keywords" content="충남스틸, 철강, 스틸, 철강재, 건축자재, H형강, 철근, 강관">';
    }
    ?>
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/samsung-style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if(isset($additionalCSS)): ?>
        <?php foreach($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="samsung-header">
            <div class="header-container">
                <!-- Logo -->
                <div class="logo">
                    <a href="index.php">
                        <span class="logo-text-main">충남스틸</span>
                    </a>
                </div>
                
                <!-- Navigation -->
                <nav class="main-nav">
                    <ul>
                        <li><a href="products_new.php" class="<?php echo $currentPage == 'products' ? 'active' : ''; ?>">판매제품</a></li>
                        <li><a href="about.php" class="<?php echo $currentPage == 'about' ? 'active' : ''; ?>">회사소개</a></li>
                        <li><a href="https://cnst.co.kr/ebook/mobile/index.html#p=1" target="_blank" ; ?>단중표</a></li>
                        <li><a href="consignment.php" class="<?php echo $currentPage == 'consignment' ? 'active' : ''; ?>">중계판매</a></li>
                        <li><a href="quote.php" class="<?php echo $currentPage == 'quote' ? 'active' : ''; ?>">견적문의</a></li>
                        <li><a href="notice.php" class="<?php echo $currentPage == 'notice' ? 'active' : ''; ?>">공지사항</a></li>
                        <li><a href="news.php" class="<?php echo $currentPage == 'news' ? 'active' : ''; ?>">철강뉴스</a></li>
                    </ul>
                </nav>
                
                <!-- Header Icons -->
                <div class="header-icons">
                    <?php if(isLoggedIn()): ?>
                        <div class="user-menu">
                            <button class="icon-btn user-btn" onclick="toggleUserMenu()">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="currentColor"/>
                                </svg>
                            </button>
                            <div class="user-dropdown" id="userDropdown">
                                <a href="mypage.php">마이페이지</a>
                                <a href="logout.php">로그아웃</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="icon-btn" title="로그인">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="currentColor"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                    <button class="icon-btn cart-btn" title="장바구니">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M7 18C5.9 18 5.01 18.9 5.01 20C5.01 21.1 5.9 22 7 22C8.1 22 9 21.1 9 20C9 18.9 8.1 18 7 18ZM1 2V4H3L6.6 11.59L5.25 14.04C5.09 14.32 5 14.65 5 15C5 16.1 5.9 17 7 17H19V15H7.42C7.28 15 7.17 14.89 7.17 14.75L7.2 14.63L8.1 13H15.55C16.3 13 16.96 12.59 17.3 11.97L20.88 5.48C20.96 5.34 21 5.17 21 5C21 4.45 20.55 4 20 4H5.21L4.27 2H1ZM17 18C15.9 18 15.01 18.9 15.01 20C15.01 21.1 15.9 22 17 22C18.1 22 19 21.1 19 20C19 18.9 18.1 18 17 18Z" fill="currentColor"/>
                        </svg>
                        <span class="cart-count">0</span>
                    </button>
                    <button class="icon-btn search-btn" id="search-icon-btn" title="검색">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M15.5 14H14.71L14.43 13.73C15.41 12.59 16 11.11 16 9.5C16 5.91 13.09 3 9.5 3C5.91 3 3 5.91 3 9.5C3 13.09 5.91 16 9.5 16C11.11 16 12.59 15.41 13.73 14.43L14 14.71V15.5L19 20.49L20.49 19L15.5 14ZM9.5 14C7.01 14 5 11.99 5 9.5C5 7.01 7.01 5 9.5 5C11.99 5 14 7.01 14 9.5C14 11.99 11.99 14 9.5 14Z" fill="currentColor"/>
                        </svg>
                    </button>
                    <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
            <div id="global-search-form" class="global-search-form">
                <form action="search_results.php" method="get">
                    <input type="text" name="q" placeholder="통합 검색..." required>
                    <button type="submit">검색</button>
                </form>
            </div>
        </header>
        
        <!-- Mobile Menu Overlay -->
        <div class="mobile-menu-overlay" onclick="toggleMobileMenu()"></div>
        
        <!-- Main Content -->
        <main class="main-content">

<style>
.user-menu {
    position: relative;
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 8px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 150px;
    display: none;
    z-index: 1000;
}

.user-dropdown.active {
    display: block;
}

.user-dropdown a {
    display: block;
    padding: 12px 16px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
}

.user-dropdown a:hover {
    background: #F5F5F7;
    color: var(--primary-blue);
}

.user-dropdown a:first-child {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.user-dropdown a:last-child {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
}

/* Mobile menu overlay */
.mobile-menu-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
}

.mobile-menu-overlay.active {
    display: block;
}

/* Hamburger button animation */
.mobile-menu-btn {
    position: relative;
    width: 24px;
    height: 20px;
}

.mobile-menu-btn span {
    position: absolute;
    display: block;
    width: 100%;
    height: 2px;
    background: #333;
    transition: all 0.3s ease;
    left: 0;
}

.mobile-menu-btn span:nth-child(1) {
    top: 0;
}

.mobile-menu-btn span:nth-child(2) {
    top: 50%;
    transform: translateY(-50%);
}

.mobile-menu-btn span:nth-child(3) {
    bottom: 0;
}

.mobile-menu-btn.active span:nth-child(1) {
    transform: rotate(45deg);
    top: 50%;
    transform-origin: center;
}

.mobile-menu-btn.active span:nth-child(2) {
    opacity: 0;
}

.mobile-menu-btn.active span:nth-child(3) {
    transform: rotate(-45deg);
    bottom: 50%;
    transform-origin: center;
}

/* Prevent body scroll when menu is open */
body.menu-open {
    overflow: hidden;
}

/* Cart count badge */
.cart-btn {
    position: relative;
}

.cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ff0000;
    color: white;
    font-size: 12px;
    font-weight: bold;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
}

/* Adjust z-index for mobile menu */
@media (max-width: 1050px) {
    .main-nav {
        z-index: 999;
    }
    
    .main-nav.active {
        overflow-y: auto;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function toggleUserMenu() {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) {
            dropdown.classList.toggle('active');
        }
    }

    function toggleMobileMenu() {
        const nav = document.querySelector('.main-nav');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        const overlay = document.querySelector('.mobile-menu-overlay');
        const body = document.body;

        if (nav && menuBtn && overlay) {
            nav.classList.toggle('active');
            menuBtn.classList.toggle('active');
            overlay.classList.toggle('active');
            body.classList.toggle('menu-open');
        }
    }

    // Attach to window object if needed for inline onclick, or add event listeners here
    window.toggleUserMenu = toggleUserMenu;
    window.toggleMobileMenu = toggleMobileMenu;

    const searchIconBtn = document.getElementById('search-icon-btn');
    const globalSearchForm = document.getElementById('global-search-form');

    if(searchIconBtn) {
        searchIconBtn.addEventListener('click', function(event) {
            event.stopPropagation();
            globalSearchForm.classList.toggle('active');
            if (globalSearchForm.classList.contains('active')) {
                globalSearchForm.querySelector('input').focus();
            }
        });
    }

    document.addEventListener('click', function(event) {
        if (globalSearchForm && !globalSearchForm.contains(event.target) && !searchIconBtn.contains(event.target)) {
            globalSearchForm.classList.remove('active');
        }
        
        const userDropdown = document.getElementById('userDropdown');
        const userBtn = document.querySelector('.user-btn');
        if (userDropdown && userBtn && !userBtn.contains(event.target) && !userDropdown.contains(event.target)) {
            userDropdown.classList.remove('active');
        }
    });
    
    const mobileNavLinks = document.querySelectorAll('.main-nav a');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1050) {
                toggleMobileMenu(); // Use the function to ensure everything is toggled
            }
        });
    });
    
    // Also close menu when overlay is clicked
    const overlay = document.querySelector('.mobile-menu-overlay');
    if(overlay) {
        overlay.addEventListener('click', toggleMobileMenu);
    }
    
    // Cart button click handler
    const cartBtn = document.querySelector('.cart-btn');
    if(cartBtn) {
        cartBtn.addEventListener('click', function() {
            // 마이페이지의 제품견적서로 이동
            window.location.href = 'my_quote_cart.php';
        });
    }
    
    // Update cart count on page load
    function updateCartCount() {
        const quoteCart = JSON.parse(sessionStorage.getItem('quoteCart') || '[]');
        const cartCount = quoteCart.length; // 아이템(건) 단위로 카운트
        
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = cartCount;
            cartCountElement.style.display = cartCount > 0 ? 'block' : 'none';
        }
    }
    
    // Call updateCartCount on page load
    updateCartCount();
});
</script>
