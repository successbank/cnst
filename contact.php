<?php
$currentPage = 'contact';
$pageTitle = '연락처';
require_once 'includes/sub_layout.php';
include 'head.php';

// 폼 제출 처리
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8') : '';
    $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL) : '';
    $subject = isset($_POST['subject']) ? htmlspecialchars(trim($_POST['subject']), ENT_QUOTES, 'UTF-8') : '';
    $messageText = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message']), ENT_QUOTES, 'UTF-8') : '';

    if ($name && $email && $messageText) {
        $message = '<div class="alert-success">문의가 접수되었습니다. 빠른 시일 내에 답변드리겠습니다.</div>';
    } else {
        $message = '<div class="alert-error">필수 항목을 모두 입력해주세요.</div>';
    }
}

// 서브페이지 레이아웃 시작
startSubPage('연락처', 'contact');

// 사이드바
companySidebar('contact');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>연락처</h2>
        <p>충남스틸에 문의하세요</p>
    </div>

    <div class="content-body">

<style>
.contact-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    margin-bottom: 48px;
}

.contact-info-card {
    text-align: center;
    padding: 32px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border: 1px solid #E5E5E7;
    transition: all 0.3s ease;
}

.contact-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.contact-info-card .card-icon {
    font-size: 32px;
    margin-bottom: 16px;
}

.contact-info-card h4 {
    font-size: 18px;
    font-weight: 700;
    color: #1565C0;
    margin-bottom: 12px;
}

.contact-info-card p {
    font-size: 15px;
    color: #333;
    line-height: 1.6;
}

.contact-info-card a {
    color: #1565C0;
    text-decoration: none;
}

.contact-info-card a:hover {
    text-decoration: underline;
}

.contact-form-section {
    border-top: 2px solid #E5E5E7;
    padding-top: 40px;
}

.contact-form-section h3 {
    font-size: 20px;
    font-weight: 700;
    color: #333;
    margin-bottom: 24px;
}

.contact-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.alert-success {
    background: #E8F5E9;
    color: #2E7D32;
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 15px;
    border: 1px solid #C8E6C9;
}

.alert-error {
    background: #FFEBEE;
    color: #C62828;
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 15px;
    border: 1px solid #FFCDD2;
}

/* 반응형 - 태블릿 */
@media (max-width: 1062px) {
    .contact-info-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
}

/* 반응형 - 모바일 */
@media (max-width: 768px) {
    .contact-info-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .contact-form .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .contact-info-card {
        padding: 24px 16px;
    }
}
</style>

<?php echo $message; ?>

<!-- 연락처 정보 카드 -->
<div class="contact-info-grid">
    <div class="contact-info-card">
        <div class="card-icon">📍</div>
        <h4>주소</h4>
        <p>인천 서구 봉수대로 1626<br>(금곡동)</p>
    </div>
    <div class="contact-info-card">
        <div class="card-icon">📞</div>
        <h4>대표전화 / 팩스</h4>
        <p><a href="tel:032-564-1616">032-564-1616</a><br>팩스: 032-564-0090</p>
    </div>
    <div class="contact-info-card">
        <div class="card-icon">✉️</div>
        <h4>이메일</h4>
        <p><a href="mailto:cn1616@naver.com">cn1616@naver.com</a></p>
    </div>
    <div class="contact-info-card">
        <div class="card-icon">🕐</div>
        <h4>영업시간</h4>
        <p>평일: 09:00 - 18:00<br>토요일: 09:00 - 13:00</p>
    </div>
</div>

<!-- 문의 폼 -->
<div class="contact-form-section">
    <h3>온라인 문의</h3>
    <form method="POST" action="contact.php" class="contact-form">
        <div class="form-row">
            <div class="form-group">
                <label for="name">이름 <span style="color:#E53935;">*</span></label>
                <input type="text" id="name" name="name" placeholder="이름을 입력하세요" required>
            </div>
            <div class="form-group">
                <label for="email">이메일 <span style="color:#E53935;">*</span></label>
                <input type="email" id="email" name="email" placeholder="이메일을 입력하세요" required>
            </div>
        </div>
        <div class="form-group">
            <label for="subject">제목</label>
            <input type="text" id="subject" name="subject" placeholder="문의 제목을 입력하세요">
        </div>
        <div class="form-group">
            <label for="message">문의내용 <span style="color:#E53935;">*</span></label>
            <textarea id="message" name="message" rows="6" placeholder="문의 내용을 입력하세요" required></textarea>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">문의하기</button>
            <button type="reset" class="btn btn-secondary">초기화</button>
        </div>
    </form>
</div>

    </div>
</main>

<?php
endSubPage();
include 'tail.php';
?>
