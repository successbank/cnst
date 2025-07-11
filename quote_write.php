<?php
require_once 'member_check.php';
require_once 'db.php';
require_once 'includes/sub_layout.php';

// 로그인 체크
checkLogin();

$user_id = $_SESSION['user_id'] ?? '';
$member_id = $_SESSION['member_id'] ?? '';

// URL 파라미터로 제품 정보 받기
$product_name = $_GET['product'] ?? '';
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$calculated_weight = $_GET['weight'] ?? '';
$calculated_length = $_GET['length'] ?? '';
$calculated_quantity = $_GET['quantity'] ?? '';

// 회원 정보 가져오기
$member_info = [];
if ($member_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $member_info = $stmt->fetch() ?: [];
    } catch(PDOException $e) {
        // 에러 무시
    }
}

$error = '';
$success = '';

// POST 처리 - 견적문의 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product = trim($_POST['product'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $specifications = trim($_POST['specifications'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $urgency = $_POST['urgency'] ?? 'normal';
    $additional_notes = trim($_POST['additional_notes'] ?? '');
    
    // 유효성 검사
    if (!$product || !$company || !$author || !$phone) {
        $error = '필수 항목을 모두 입력해주세요.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '올바른 이메일 주소를 입력해주세요.';
    } else {
        try {
            // 제목 생성
            $title = $product . ' 견적문의';
            
            // 내용 생성
            $content = "제품명: {$product}\n";
            $content .= "수량: {$quantity}\n";
            $content .= "규격: {$specifications}\n";
            $content .= "긴급도: {$urgency}\n";
            $content .= "추가사항: {$additional_notes}";
            
            // board_quote 테이블에 저장
            $stmt = $pdo->prepare("
                INSERT INTO board_quote (
                    title, content, writer, password, company, 
                    phone, email, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            // 임시 비밀번호 생성 (나중에 수정/삭제시 필요)
            $temp_password = password_hash($user_id . time(), PASSWORD_DEFAULT);
            
            $stmt->execute([
                $title, $content, $user_id, $temp_password, $company,
                $phone, $email
            ]);
            
            $success = '견적문의가 성공적으로 등록되었습니다. 빠른 시일 내에 답변드리겠습니다.';
            
            // 폼 데이터 초기화
            $product = $quantity = $specifications = $company = $author = '';
            $phone = $email = $additional_notes = '';
            $urgency = 'normal';
            
        } catch(PDOException $e) {
            $error = '견적문의 등록 중 오류가 발생했습니다. 다시 시도해주세요.';
        }
    }
}

$currentPage = 'mypage';
$pageTitle = '견적문의 작성';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('견적문의 작성', 'inquiries');

// 사이드바
myPageSidebar('inquiries');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>견적문의 작성</h2>
        <p>정확한 견적을 위해 상세한 정보를 입력해주세요.</p>
    </div>
    
    <div class="content-body">
        <!-- 문의 서브메뉴 -->
        <?php inquirySubmenu(''); ?>
        
        <?php if($error): ?>
            <div class="alert error">
                <strong>오류:</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert success">
                <strong>완료:</strong> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="quote-form">
            <!-- 제품 정보 -->
            <div class="form-section">
                <h3>제품 정보</h3>
                
                <div class="form-group">
                    <label for="product">제품명 <span class="required">*</span></label>
                    <input type="text" id="product" name="product" 
                           value="<?php echo htmlspecialchars($_POST['product'] ?? $product_name); ?>"
                           placeholder="예: H형강, 철근, 강판 등" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">수량</label>
                        <input type="text" id="quantity" name="quantity" 
                               value="<?php echo htmlspecialchars($_POST['quantity'] ?? ($calculated_quantity ? $calculated_quantity . '본' : '')); ?>"
                               placeholder="예: 10톤, 100개 등">
                    </div>
                    <div class="form-group">
                        <label for="urgency">납기</label>
                        <select id="urgency" name="urgency">
                            <option value="normal" <?php echo ($_POST['urgency'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>일반</option>
                            <option value="urgent" <?php echo ($_POST['urgency'] ?? '') === 'urgent' ? 'selected' : ''; ?>>긴급</option>
                            <option value="flexible" <?php echo ($_POST['urgency'] ?? '') === 'flexible' ? 'selected' : ''; ?>>협의</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="specifications">규격 및 사양</label>
                    <textarea id="specifications" name="specifications" rows="4" 
                              placeholder="예: H-300x300x10x15, KS D 3503, 길이 12m 등 상세 규격을 입력해주세요"><?php echo htmlspecialchars($_POST['specifications'] ?? ($calculated_length ? "길이: {$calculated_length}m\n" : '') . ($calculated_weight ? "예상 총중량: {$calculated_weight}kg" : '')); ?></textarea>
                </div>
            </div>
            
            <!-- 연락처 정보 -->
            <div class="form-section">
                <h3>연락처 정보</h3>
                
                <div class="form-group">
                    <label for="company">회사명 <span class="required">*</span></label>
                    <input type="text" id="company" name="company" 
                           value="<?php echo htmlspecialchars($_POST['company'] ?? $member_info['company'] ?? ''); ?>"
                           placeholder="회사명을 입력해주세요" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="author">담당자명 <span class="required">*</span></label>
                        <input type="text" id="author" name="author" 
                               value="<?php echo htmlspecialchars($_POST['author'] ?? $member_info['name'] ?? ''); ?>"
                               placeholder="담당자 성함" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">연락처 <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? $member_info['phone'] ?? ''); ?>"
                               placeholder="010-0000-0000" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">이메일 <span class="required">*</span></label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? $member_info['email'] ?? ''); ?>"
                           placeholder="example@company.com" required>
                </div>
            </div>
            
            <!-- 추가 요청사항 -->
            <div class="form-section">
                <h3>추가 요청사항</h3>
                
                <div class="form-group">
                    <label for="additional_notes">기타 요청사항</label>
                    <textarea id="additional_notes" name="additional_notes" rows="5" 
                              placeholder="추가적으로 전달하고 싶은 내용이나 특별한 요구사항이 있으시면 입력해주세요"><?php echo htmlspecialchars($_POST['additional_notes'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">견적문의 등록</button>
                <a href="quote.php" class="btn btn-secondary">목록으로</a>
            </div>
        </form>
    </div>
</main>

<style>
.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
}

.alert.error {
    background: #FFEBEE;
    color: #C62828;
    border: 1px solid #FFCDD2;
}

.alert.success {
    background: #E8F5E9;
    color: #2E7D32;
    border: 1px solid #C8E6C9;
}

.quote-form {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.form-section {
    margin-bottom: 40px;
}

.form-section:last-of-type {
    margin-bottom: 32px;
}

.form-section h3 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 24px;
    padding-bottom: 12px;
    border-bottom: 2px solid #E5E5E7;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.required {
    color: #E53935;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #1A237E;
    box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-actions {
    display: flex;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid #E5E5E7;
}

.btn {
    padding: 14px 28px;
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
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
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

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .quote-form {
        padding: 20px;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php 
endSubPage();
include 'tail.php'; 
?>