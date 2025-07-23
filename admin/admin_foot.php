    </main>
    <!-- admin-content 종료 -->

<script>
// 모바일 메뉴 토글
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const adminNav = document.querySelector('.admin-nav');
    const overlay = document.querySelector('.mobile-overlay');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            adminNav.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            adminNav.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });
    }
    
    // 드롭다운 메뉴
    const dropdownToggles = document.querySelectorAll('.nav-dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = this.parentElement;
            dropdown.classList.toggle('active');
        });
    });
});
</script>

</body>
</html>