<?php
session_start();
require_once 'db.php';

// 이미 로그인한 경우 메인으로 리다이렉트
if(isset($_SESSION['member_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $address_detail = trim($_POST['address_detail'] ?? '');
    
    // 유효성 검사
    if(strlen($user_id) < 4) {
        $error = '아이디는 4자 이상이어야 합니다.';
    } elseif(strlen($password) < 6) {
        $error = '비밀번호는 6자 이상이어야 합니다.';
    } elseif($password !== $password_confirm) {
        $error = '비밀번호가 일치하지 않습니다.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '올바른 이메일 주소를 입력해주세요.';
    } else {
        try {
            // 아이디 중복 체크
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE user_id = ?");
            $stmt->execute([$user_id]);
            if($stmt->fetchColumn() > 0) {
                $error = '이미 사용중인 아이디입니다.';
            } else {
                // 이메일 중복 체크
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ?");
                $stmt->execute([$email]);
                if($stmt->fetchColumn() > 0) {
                    $error = '이미 사용중인 이메일입니다.';
                } else {
                    // 회원가입 처리
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO members (user_id, password, name, email, phone, company, position, zipcode, address, address_detail) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$user_id, $hashed_password, $name, $email, $phone, $company, $position, $zipcode, $address, $address_detail]);
                    
                    // 회원가입 성공 시 자동 로그인 처리
                    $_SESSION['member_id'] = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['member_name'] = $name;
                    $_SESSION['member_email'] = $email;
                    
                    // 메인 페이지로 즉시 이동
                    header('Location: index.php');
                    exit;
                }
            }
        } catch(PDOException $e) {
            $error = '회원가입 중 오류가 발생했습니다.';
        }
    }
}

$currentPage = 'register';
$pageTitle = '회원가입';
include 'head.php';
?>

<style>
.register-section {
    padding: 60px 0;
    background: #F8F9FA;
    min-height: 80vh;
}

.register-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 20px;
}

.register-box {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.register-title {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    text-align: center;
    margin-bottom: 32px;
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

.form-group label span {
    color: #F44336;
}

.form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary-blue);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.address-group {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
}

.zipcode-input {
    width: 150px !important;
}

.btn-find-zipcode {
    padding: 12px 20px;
    background: #666;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    white-space: nowrap;
}

.btn-find-zipcode:hover {
    background: #555;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}

.alert.error {
    background: #FFEBEE;
    color: #C62828;
}

.alert.success {
    background: #E8F5E9;
    color: #2E7D32;
}

.submit-btn {
    width: 100%;
    padding: 14px;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.submit-btn:hover {
    background: #0F1F7A;
    transform: translateY(-1px);
}

.login-link {
    text-align: center;
    margin-top: 24px;
    font-size: 14px;
    color: #666;
}

.login-link a {
    color: var(--primary-blue);
    text-decoration: none;
    font-weight: 600;
}

.login-link a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .register-box {
        padding: 24px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="register-section">
    <div class="register-container">
        <div class="register-box">
            <h2 class="register-title">회원가입</h2>
            
            <?php if($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="user_id">아이디 <span>*</span></label>
                    <input type="text" id="user_id" name="user_id" required 
                           minlength="4" maxlength="20"
                           pattern="[a-zA-Z0-9]+"
                           title="영문자와 숫자만 사용 가능합니다"
                           value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">비밀번호 <span>*</span></label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">비밀번호 확인 <span>*</span></label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name">이름 <span>*</span></label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">이메일 <span>*</span></label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">휴대폰 번호</label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="010-0000-0000"
                           pattern="[0-9]{3}-[0-9]{4}-[0-9]{4}"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company">회사명</label>
                        <input type="text" id="company" name="company" 
                               value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">직급/부서</label>
                        <input type="text" id="position" name="position" 
                               value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">주소</label>
                    <div class="address-group">
                        <input type="text" id="zipcode" name="zipcode" class="zipcode-input" 
                               placeholder="우편번호" readonly
                               value="<?php echo htmlspecialchars($_POST['zipcode'] ?? ''); ?>">
                        <button type="button" class="btn-find-zipcode" onclick="findZipcode()">우편번호 찾기</button>
                    </div>
                    <input type="text" id="address" name="address" placeholder="기본주소" readonly
                           value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <input type="text" id="address_detail" name="address_detail" placeholder="상세주소"
                           value="<?php echo htmlspecialchars($_POST['address_detail'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="submit-btn">회원가입</button>
            </form>
            
            <div class="login-link">
                이미 회원이신가요? <a href="login.php">로그인</a>
            </div>
        </div>
    </div>
</section>

<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
function findZipcode() {
    new daum.Postcode({
        oncomplete: function(data) {
            document.getElementById('zipcode').value = data.zonecode;
            document.getElementById('address').value = data.roadAddress;
            document.getElementById('address_detail').focus();
        }
    }).open();
}

// 비밀번호 확인
document.getElementById('password_confirm').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    
    if(password !== confirm) {
        this.setCustomValidity('비밀번호가 일치하지 않습니다.');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include 'tail.php'; ?>