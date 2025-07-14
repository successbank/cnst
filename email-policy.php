<?php
$pageTitle = "이메일무단수집거부";
$currentPage = "email-policy";
$bodyClass = "legal-page";

// 페이지별 추가 CSS
$additionalCSS = [
    'css/steel.css'
];

include 'head.php';
?>

<main class="main-content">
    <div class="legal-container">
        <h1 class="legal-title">이메일무단수집거부</h1>
        
        <div class="legal-content email-policy">
            <section class="legal-section">
                <div class="warning-box">
                    <h2>이메일 주소 무단수집 거부</h2>
                    <p>본 웹사이트에 게시된 이메일 주소가 전자우편 수집 프로그램이나 그 밖의 기술적 장치를 이용하여 무단으로 수집되는 것을 거부하며, 이를 위반시 정보통신망법에 의해 형사 처벌됨을 유념하시기 바랍니다.</p>
                </div>
            </section>

            <section class="legal-section">
                <h2>관련 법령</h2>
                
                <h3>정보통신망 이용촉진 및 정보보호 등에 관한 법률</h3>
                
                <div class="law-article">
                    <h4>제50조의2(전자우편주소의 무단 수집행위 등 금지)</h4>
                    <ol>
                        <li>누구든지 인터넷 홈페이지 운영자 또는 관리자의 사전 동의 없이 인터넷 홈페이지에서 자동으로 전자우편주소를 수집하는 프로그램이나 그 밖의 기술적 장치를 이용하여 전자우편주소를 수집하여서는 아니 된다.</li>
                        <li>누구든지 제1항을 위반하여 수집된 전자우편주소를 판매·유통하여서는 아니 된다.</li>
                        <li>누구든지 제1항과 제2항에 따라 수집·판매 및 유통이 금지된 전자우편주소임을 알면서 이를 정보전송에 이용하여서는 아니 된다.</li>
                    </ol>
                </div>

                <div class="law-article">
                    <h4>제74조(벌칙)</h4>
                    <p>다음 각 호의 어느 하나에 해당하는 자는 1년 이하의 징역 또는 1천만원 이하의 벌금에 처한다.</p>
                    <ol start="4">
                        <li>제50조의2를 위반하여 전자우편주소를 수집·판매·유통하거나 정보전송에 이용한 자</li>
                    </ol>
                </div>
            </section>

            <section class="legal-section">
                <h2>수집거부 안내</h2>
                <p>주식회사 충남스틸은 회원가입 시 수집하는 개인정보 및 게시판 등을 통해 수집되는 이메일 주소가 전자우편 수집 프로그램이나 그 밖의 기술적 장치를 이용하여 무단으로 수집되는 것을 거부합니다.</p>
                
                <p>이를 위반 시 정보통신망법에 의해 형사처벌(1년 이하의 징역 또는 1천만원 이하의 벌금)됨을 유념하시기 바랍니다.</p>
            </section>

            <section class="legal-section">
                <h2>게시일</h2>
                <p>위와 같이 이메일 주소 무단수집을 거부합니다.</p>
                <p class="date-posted">게시일: 2025년 1월 1일</p>
            </section>

            <section class="legal-section report-section">
                <h2>신고 및 문의</h2>
                <p>불법 스팸메일 및 이메일 주소 무단수집에 대한 신고나 문의사항이 있으시면 아래로 연락해 주시기 바랍니다.</p>
                
                <div class="contact-info">
                    <h3>충남스틸 개인정보보호 담당부서</h3>
                    <ul>
                        <li><strong>전화:</strong> 032-564-1616</li>
                        <li><strong>팩스:</strong> 032-564-0090</li>
                        <li><strong>이메일:</strong> cn1616@naver.com</li>
                        <li><strong>주소:</strong> 22605 인천 서구 봉수대로 1626 충남스틸 (금곡동)</li>
                    </ul>
                </div>

                <div class="contact-info">
                    <h3>한국인터넷진흥원 불법스팸대응센터</h3>
                    <ul>
                        <li><strong>전화:</strong> (국번없이) 118</li>
                        <li><strong>홈페이지:</strong> <a href="https://www.kisa.or.kr/spam" target="_blank" rel="noopener noreferrer">www.kisa.or.kr/spam</a></li>
                    </ul>
                </div>
            </section>
        </div>
    </div>
</main>

<style>
.legal-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
}

.legal-title {
    font-size: 32px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 40px;
    text-align: center;
}

.legal-content {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.legal-section {
    margin-bottom: 40px;
}

.legal-section:last-child {
    margin-bottom: 0;
}

.legal-section h2 {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 20px;
}

.legal-section h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 20px 0 15px;
}

.legal-section h4 {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 15px 0 10px;
}

.legal-section p {
    line-height: 1.8;
    color: var(--text-primary);
    margin-bottom: 15px;
}

.legal-section ol,
.legal-section ul {
    margin: 15px 0;
    padding-left: 30px;
}

.legal-section li {
    margin-bottom: 10px;
    line-height: 1.8;
}

.warning-box {
    background-color: #FFF3CD;
    border: 1px solid #FFE8A1;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
}

.warning-box h2 {
    color: #856404;
    margin-bottom: 15px;
}

.warning-box p {
    color: #856404;
    font-size: 16px;
    line-height: 1.8;
    margin: 0;
}

.law-article {
    background-color: var(--bg-light);
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.law-article h4 {
    color: var(--primary-color);
}

.date-posted {
    font-weight: 600;
    color: var(--primary-color);
}

.report-section {
    background-color: #E8F4FD;
    padding: 30px;
    border-radius: 8px;
}

.contact-info {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    border: 1px solid var(--border-color);
}

.contact-info h3 {
    margin-bottom: 15px;
    color: var(--primary-color);
}

.contact-info ul {
    list-style: none;
    padding: 0;
}

.contact-info li {
    margin-bottom: 8px;
}

.contact-info a {
    color: var(--primary-color);
    text-decoration: none;
}

.contact-info a:hover {
    text-decoration: underline;
}

.legal-footer {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.legal-footer p {
    margin: 5px 0;
    color: var(--primary-color);
}

@media (max-width: 768px) {
    .legal-content {
        padding: 20px;
    }
    
    .legal-title {
        font-size: 24px;
    }
    
    .legal-section h2 {
        font-size: 20px;
    }
    
    .warning-box {
        padding: 20px;
    }
}
</style>

<?php include 'tail.php'; ?>