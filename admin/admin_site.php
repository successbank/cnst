<?php
$pageTitle = '사이트 관리';

// 추가 스타일 정의
$additionalStyles = '
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

.settings-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.settings-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #333;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="tel"],
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="tel"]:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #1A237E;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.form-group small {
    display: block;
    margin-top: 4px;
    color: #666;
    font-size: 12px;
}

.submit-btn {
    padding: 10px 24px;
    background: #1A237E;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.submit-btn:hover {
    background: #283593;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
}

.stat-icon {
    font-size: 32px;
    margin-bottom: 12px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: #666;
}
';

// 추가 스크립트
$additionalScripts = '
// 폼 유효성 검사
document.querySelectorAll("form").forEach(form => {
    form.addEventListener("submit", function(e) {
        const emailInputs = form.querySelectorAll(\'input[type="email"]\');
        emailInputs.forEach(input => {
            if (input.value && !isValidEmail(input.value)) {
                e.preventDefault();
                alert("올바른 이메일 주소를 입력해주세요.");
                input.focus();
            }
        });
    });
});

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
';

require_once 'admin_head.php';
require_once '../includes/settings.php';

// 설정 저장 처리
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setting_type = $_POST['setting_type'] ?? '';
    
    try {
        switch($setting_type) {
            case 'company':
                // 회사 정보 저장
                $settings = [
                    'company_name' => $_POST['company_name'] ?? '',
                    'company_ceo' => $_POST['company_ceo'] ?? '',
                    'company_business_number' => $_POST['company_business_number'] ?? '',
                    'company_online_sales_number' => $_POST['company_online_sales_number'] ?? '',
                    'company_address' => $_POST['company_address'] ?? '',
                    'company_tel' => $_POST['company_tel'] ?? '',
                    'company_fax' => $_POST['company_fax'] ?? '',
                    'company_email' => $_POST['company_email'] ?? ''
                ];
                
                if(saveSettings($settings, 'company')) {
                    $success_msg = "회사 정보가 저장되었습니다.";
                } else {
                    $error = "저장 중 오류가 발생했습니다.";
                }
                break;
                
            case 'seo':
                // SEO 설정 저장
                $settings = [
                    'site_title' => $_POST['site_title'] ?? '',
                    'site_description' => $_POST['site_description'] ?? '',
                    'company_intro' => $_POST['company_intro'] ?? '',
                    'site_keywords' => $_POST['site_keywords'] ?? ''
                ];
                
                if(saveSettings($settings, 'seo')) {
                    $success_msg = "SEO 설정이 저장되었습니다.";
                } else {
                    $error = "저장 중 오류가 발생했습니다.";
                }
                break;
                
            case 'contact':
                // 문의 설정 저장 (수신 이메일 다중 지원: 쉼표/줄바꿈 구분)
                require_once '../includes/EmailService.php';

                // 줄바꿈을 쉼표로 통일한 뒤 개별 검증
                $rawEmails = str_replace(["\r\n", "\r", "\n"], ',', $_POST['contact_email'] ?? '');
                $check = EmailService::validateEmails($rawEmails);

                if (empty($check['valid'])) {
                    $error = "문의 수신 이메일을 1개 이상 올바르게 입력해주세요.";
                    break;
                }
                if (!empty($check['invalid'])) {
                    $error = "잘못된 이메일 형식이 있습니다: " . implode(', ', $check['invalid']);
                    break;
                }

                // 정규화하여 쉼표+공백 구분으로 저장 (EmailService.send가 그대로 분해)
                $settings = [
                    'contact_email' => implode(', ', $check['valid']),
                    'contact_phone' => $_POST['contact_phone'] ?? ''
                ];

                if(saveSettings($settings, 'contact')) {
                    $success_msg = count($check['valid']) . "개의 수신 이메일을 포함한 문의 설정이 저장되었습니다.";
                } else {
                    $error = "저장 중 오류가 발생했습니다.";
                }
                break;
        }
    } catch(Exception $e) {
        $error = "저장 중 오류가 발생했습니다.";
    }
}

// 현재 설정 값 불러오기
$companySettings = getSettingsByGroup('company');
$seoSettings = getSettingsByGroup('seo');
$contactSettings = getSettingsByGroup('contact');

// 통계 데이터 조회 (예시)
try {
    // 전체 회원 수
    $stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE is_admin = 0");
    $total_members = $stmt->fetchColumn();
    
    // 오늘 방문자 수 (실제로는 방문자 추적 테이블이 필요)
    $today_visitors = rand(50, 200); // 예시 데이터
    
    // 전체 게시물 수
    $stmt = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM board_notice) + 
        (SELECT COUNT(*) FROM board_quote) + 
        (SELECT COUNT(*) FROM board_news) + 
        (SELECT COUNT(*) FROM board_consignment) as total");
    $total_posts = $stmt->fetchColumn();
    
    // 이번 달 문의
    $stmt = $pdo->query("SELECT COUNT(*) FROM board_quote WHERE MONTH(created_at) = MONTH(CURRENT_DATE())");
    $month_inquiries = $stmt->fetchColumn();
    
} catch(PDOException $e) {
    $total_members = 0;
    $today_visitors = 0;
    $total_posts = 0;
    $month_inquiries = 0;
}
?>

<div class="page-header">
    <h1>사이트 관리</h1>
    <p>웹사이트의 기본 설정과 정보를 관리합니다.</p>
</div>

<?php if(isset($success_msg)): ?>
    <div class="msg success"><?php echo $success_msg; ?></div>
<?php endif; ?>

<?php if(isset($error)): ?>
    <div class="msg error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- 사이트 통계 -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?php echo number_format($total_members); ?></div>
        <div class="stat-label">전체 회원</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-value"><?php echo number_format($today_visitors); ?></div>
        <div class="stat-label">오늘 방문자</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-value"><?php echo number_format($total_posts); ?></div>
        <div class="stat-label">전체 게시물</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon">💬</div>
        <div class="stat-value"><?php echo number_format($month_inquiries); ?></div>
        <div class="stat-label">이번 달 문의</div>
    </div>
</div>

<!-- 설정 섹션 -->
<div class="settings-grid">
    <!-- 회사 정보 설정 -->
    <div class="settings-card">
        <h2 class="settings-title">회사 정보</h2>
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <input type="hidden" name="setting_type" value="company">
            
            <div class="form-group">
                <label for="company_name">회사명</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($companySettings['company_name'] ?? '충남스틸'); ?>" required>
                <small>홈페이지 하단(footer) 회사명 및 저작권 표기에 사용됩니다.</small>
            </div>

            <div class="form-group">
                <label for="company_ceo">대표자명</label>
                <input type="text" id="company_ceo" name="company_ceo" value="<?php echo htmlspecialchars($companySettings['company_ceo'] ?? '김영건'); ?>">
            </div>

            <div class="form-group">
                <label for="company_business_number">사업자등록번호</label>
                <input type="text" id="company_business_number" name="company_business_number" value="<?php echo htmlspecialchars($companySettings['company_business_number'] ?? '137-81-79472'); ?>">
            </div>

            <div class="form-group">
                <label for="company_online_sales_number">통신판매업 신고번호</label>
                <input type="text" id="company_online_sales_number" name="company_online_sales_number" value="<?php echo htmlspecialchars($companySettings['company_online_sales_number'] ?? '서구2007-139호'); ?>">
            </div>

            <div class="form-group">
                <label for="company_address">주소</label>
                <input type="text" id="company_address" name="company_address" 
                       value="<?php echo htmlspecialchars($companySettings['company_address'] ?? '충청남도 아산시 둔포면 아산밸리로 63'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="company_tel">대표전화</label>
                <input type="tel" id="company_tel" name="company_tel" value="<?php echo htmlspecialchars($companySettings['company_tel'] ?? '032-564-1616'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="company_fax">팩스</label>
                <input type="tel" id="company_fax" name="company_fax" value="<?php echo htmlspecialchars($companySettings['company_fax'] ?? '032-564-0090'); ?>">
            </div>
            
            <div class="form-group">
                <label for="company_email">대표 이메일</label>
                <input type="email" id="company_email" name="company_email" value="<?php echo htmlspecialchars($companySettings['company_email'] ?? 'info@chungnamsteel.com'); ?>">
            </div>
            
            <button type="submit" class="submit-btn">저장</button>
        </form>
    </div>
    
    <!-- SEO 설정 -->
    <div class="settings-card">
        <h2 class="settings-title">SEO 설정</h2>
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <input type="hidden" name="setting_type" value="seo">
            
            <div class="form-group">
                <label for="site_title">사이트 제목</label>
                <input type="text" id="site_title" name="site_title" 
                       value="<?php echo htmlspecialchars($seoSettings['site_title'] ?? '충남스틸 - 철강 전문 유통업체'); ?>" required>
                <small>검색 결과에 표시되는 제목입니다.</small>
            </div>
            
            <div class="form-group">
                <label for="site_description">사이트 설명</label>
                <textarea id="site_description" name="site_description" required><?php echo htmlspecialchars($seoSettings['site_description'] ?? '충남스틸은 철강 제품의 판매 및 가공을 전문으로 하는 철강 유통업체입니다. 고품질의 철강 제품과 신속한 납품으로 고객 만족을 추구합니다.'); ?></textarea>
                <small>검색 결과에 표시되는 설명입니다.</small>
            </div>

            <div class="form-group">
                <label for="company_intro">홈페이지 하단(footer) 회사 소개 문구</label>
                <textarea id="company_intro" name="company_intro"><?php echo htmlspecialchars($seoSettings['company_intro'] ?? '충남스틸은 1995년 설립 이래 인천광역시를 중심으로 우수한 품질의 철강 제품을 공급하고 있습니다.'); ?></textarea>
                <small>홈페이지 첫 화면 하단(footer) 회사 소개 영역에 표시됩니다. (검색용 사이트 설명과 별개)</small>
            </div>

            <div class="form-group">
                <label for="site_keywords">키워드</label>
                <input type="text" id="site_keywords" name="site_keywords" 
                       value="<?php echo htmlspecialchars($seoSettings['site_keywords'] ?? '철강, 철강유통, 충남스틸, 아산철강, 철강가공'); ?>">
                <small>쉼표로 구분하여 입력하세요.</small>
            </div>
            
            <button type="submit" class="submit-btn">저장</button>
        </form>
    </div>
    
    <!-- 문의 설정 -->
    <div class="settings-card">
        <h2 class="settings-title">문의 설정</h2>
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <input type="hidden" name="setting_type" value="contact">
            
            <div class="form-group">
                <label for="contact_email">문의 수신 이메일 (다중 가능)</label>
                <textarea id="contact_email" name="contact_email" rows="3" required
                          placeholder="여러 개는 쉼표(,) 또는 줄바꿈으로 구분&#10;예) sales@chungnamsteel.com, manager@chungnamsteel.com"><?php echo htmlspecialchars($contactSettings['contact_email'] ?? 'sales@chungnamsteel.com'); ?></textarea>
                <small>견적문의가 접수되면 이 주소(들)로 문의 내용과 첨부파일이 발송됩니다. 여러 개는 쉼표 또는 줄바꿈으로 구분하세요.</small>
            </div>
            
            <div class="form-group">
                <label for="contact_phone">문의 전화번호</label>
                <input type="tel" id="contact_phone" name="contact_phone" 
                       value="<?php echo htmlspecialchars($contactSettings['contact_phone'] ?? '032-564-1616'); ?>" required>
                <small>고객이 연락할 수 있는 대표 번호입니다.</small>
            </div>
            
            <button type="submit" class="submit-btn">저장</button>
        </form>
    </div>
</div>

<?php require_once 'admin_tail.php'; ?>