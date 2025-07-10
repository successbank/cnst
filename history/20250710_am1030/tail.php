        </main>
        
        <footer>
            <div class="footer-content">
                <div class="footer-grid">
                    <div class="footer-section footer-company">
                        <h3>주식회사 충남스틸</h3>
                        <p>충남스틸은 1995년 설립 이래 인천광역시를 중심으로 우수한 품질의 철강 제품을 공급하고 있습니다.</p>
                        <div class="company-info">
                            <p><strong>대표:</strong> 김영건</p>
                            <p><strong>사업자등록번호:</strong> 137-81-79472</p>
                            <p><strong>통신판매업:</strong> 서구2007-139호</p>
                        </div>
                    </div>
                    <div class="footer-section">
                        <h3>고객센터</h3>
                        <ul>
                            <li class="footer-label">대표전화</li>
                            <li class="footer-value">032-564-1616</li>
                            <li class="footer-label">팩스</li>
                            <li class="footer-value">032-564-0090</li>
                            <li class="footer-label">이메일</li>
                            <li class="footer-value">cn1616@naver.com</li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h3>영업시간</h3>
                        <ul>
                            <li class="footer-label">평일</li>
                            <li class="footer-value">09:00 - 18:00</li>
                            <li class="footer-label">토요일</li>
                            <li class="footer-value">09:00 - 13:00</li>
                            <li class="footer-label">일요일/공휴일</li>
                            <li class="footer-value">휴무</li>
                        </ul>
                    </div>
                    <div class="footer-section">
                        <h3>바로가기</h3>
                        <ul class="footer-links">
                            <li><a href="about.php">회사소개</a></li>
                            <li><a href="quote.php">견적문의</a></li>
                            <li><a href="notice.php">공지사항</a></li>
                            <li><a href="location.php">오시는길</a></li>
                            <li><a href="news.php">철강뉴스</a></li>
                        </ul>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>© <?php echo date('Y'); ?> 충남스틸. All Rights Reserved.</p>
                    <p>주소: 22605 인천 서구 봉수대로 1626 충남스틸 (금곡동)</p>
                </div>
            </div>
        </footer>
    </div>
    
    <script>
    // Mobile menu functionality
    function toggleMobileMenu() {
        const nav = document.getElementById('mainNav');
        nav.classList.toggle('active');
        
        const menuBtn = document.querySelector('.mobile-menu-btn');
        menuBtn.classList.toggle('active');
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const nav = document.getElementById('mainNav');
        const menuBtn = document.querySelector('.mobile-menu-btn');
        
        if (nav && menuBtn && !nav.contains(event.target) && !menuBtn.contains(event.target)) {
            nav.classList.remove('active');
            menuBtn.classList.remove('active');
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId !== '#' && document.querySelector(targetId)) {
                e.preventDefault();
                document.querySelector(targetId).scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    </script>
    
    <!-- 채팅 버튼 -->
    <div id="chat-button" class="chat-button">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
        </svg>
        <span>채팅</span>
    </div>

    <!-- 채팅 창 -->
    <div id="chat-window" class="chat-window">
        <div class="chat-header">
            <div class="chat-header-text">
                <h4>24/7/365</h4>
                <p>언제든지 문의하세요</p>
            </div>
            <button id="chat-minimize" class="chat-minimize">−</button>
        </div>
        <div class="chat-body">
            <form id="chat-form" class="chat-form">
                <div class="form-group">
                    <label for="chat-name">이름</label>
                    <input type="text" id="chat-name" name="name" placeholder="홍길동" required>
                </div>
                <div class="form-group">
                    <label for="chat-email">이메일</label>
                    <input type="email" id="chat-email" name="email" placeholder="example@email.com" required>
                </div>
                <div class="form-group">
                    <label for="chat-department">부서 선택</label>
                    <select id="chat-department" name="department" required>
                        <option value="">-</option>
                        <option value="sales">영업부</option>
                        <option value="technical">기술지원</option>
                        <option value="customer">고객서비스</option>
                        <option value="other">기타</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="chat-phone">전화번호(선택 사항)</label>
                    <input type="tel" id="chat-phone" name="phone" placeholder="01012345678">
                </div>
                <div class="form-group">
                    <label for="chat-message">메시지(선택 사항)</label>
                    <textarea id="chat-message" name="message" rows="3" placeholder="문의 내용을 입력해주세요"></textarea>
                </div>
                <button type="submit" class="chat-submit">채팅 시작</button>
            </form>
        </div>
        <div class="chat-footer">
            <span>Powered by zendesk</span>
        </div>
    </div>
    
    <style>
    /* 채팅 버튼 스타일 */
    .chat-button {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #1428A0;
        color: white;
        border-radius: 30px;
        padding: 12px 24px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
        z-index: 1000;
        transition: all 0.3s ease;
        font-weight: 600;
    }

    .chat-button:hover {
        transform: translateY(-2px);
        background: #0C1F7F;
        box-shadow: 0 6px 16px rgba(20, 40, 160, 0.4);
    }

    .chat-button svg {
        width: 24px;
        height: 24px;
    }

    /* 채팅 창 스타일 */
    .chat-window {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 380px;
        max-width: 90vw;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        z-index: 1001;
        display: none;
        flex-direction: column;
        max-height: 600px;
    }

    .chat-window.active {
        display: flex;
    }

    .chat-header {
        background: #1428A0;
        padding: 20px;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-header-text h4 {
        font-size: 18px;
        font-weight: 700;
        color: white;
        margin: 0;
    }

    .chat-header-text p {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.9);
        margin: 4px 0 0 0;
    }

    .chat-minimize {
        background: none;
        border: none;
        font-size: 24px;
        color: white;
        cursor: pointer;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: background 0.2s ease;
    }

    .chat-minimize:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .chat-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }

    .chat-form .form-group {
        margin-bottom: 16px;
    }

    .chat-form label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 6px;
    }

    .chat-form input,
    .chat-form select,
    .chat-form textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #E0E0E0;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s ease;
    }

    .chat-form input:focus,
    .chat-form select:focus,
    .chat-form textarea:focus {
        outline: none;
        border-color: #1428A0;
    }

    .chat-form textarea {
        resize: vertical;
        min-height: 80px;
    }

    .chat-submit {
        width: 100%;
        padding: 12px;
        background: #1428A0;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s ease;
        margin-top: 20px;
    }

    .chat-submit:hover {
        background: #0C1F7F;
    }

    .chat-footer {
        padding: 12px;
        text-align: center;
        border-top: 1px solid #E0E0E0;
        color: #999;
        font-size: 12px;
    }

    @media (max-width: 480px) {
        .chat-button {
            bottom: 20px;
            right: 20px;
        }
        
        .chat-window {
            width: calc(100vw - 40px);
            right: 20px;
            bottom: 20px;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatButton = document.getElementById('chat-button');
        const chatWindow = document.getElementById('chat-window');
        const chatMinimize = document.getElementById('chat-minimize');
        const chatForm = document.getElementById('chat-form');
        
        // 채팅 버튼 클릭 시 창 열기
        chatButton.addEventListener('click', function() {
            chatWindow.classList.add('active');
            chatButton.style.display = 'none';
        });
        
        // 최소화 버튼 클릭 시 창 닫기
        chatMinimize.addEventListener('click', function() {
            chatWindow.classList.remove('active');
            chatButton.style.display = 'flex';
        });
        
        // 폼 제출 처리
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // 폼 데이터 수집
            const formData = {
                name: document.getElementById('chat-name').value,
                email: document.getElementById('chat-email').value,
                department: document.getElementById('chat-department').value,
                phone: document.getElementById('chat-phone').value,
                message: document.getElementById('chat-message').value
            };
            
            // 여기에 실제 채팅 시작 로직 구현
            // 예: Zendesk API 호출 또는 자체 채팅 서버로 데이터 전송
            console.log('채팅 시작:', formData);
            
            // 임시로 알림 표시
            alert('채팅 요청이 접수되었습니다. 곧 상담원이 연결됩니다.');
            
            // 폼 초기화
            chatForm.reset();
            
            // 채팅 창 닫기
            chatWindow.classList.remove('active');
            chatButton.style.display = 'flex';
        });
    });
    </script>
    
    <?php if(isset($additionalJS)): ?>
        <?php foreach($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>