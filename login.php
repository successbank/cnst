<?php
session_start();
require_once 'db.php';

// 이미 로그인한 경우 메인으로 리다이렉트
if(isset($_SESSION['member_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? 'index.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if($user_id && $password) {
        try {
            $stmt = $pdo->prepare("SELECT id, user_id, password, name, email, is_active FROM members WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $member = $stmt->fetch();
            
            if($member && password_verify($password, $member['password'])) {
                if($member['is_active'] == 1) {
                    // 기존 세션 완전히 초기화 (관리자 세션 포함)
                    session_destroy();
                    session_start();
                    
                    // 로그인 성공 - 회원 세션만 설정
                    $_SESSION['member_id'] = $member['id'];
                    $_SESSION['user_id'] = $member['user_id'];
                    $_SESSION['member_name'] = $member['name'];
                    $_SESSION['member_email'] = $member['email'];
                    
                    // 마지막 로그인 시간 업데이트
                    $stmt = $pdo->prepare("UPDATE members SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$member['id']]);
                    
                    // 리다이렉트
                    header('Location: ' . urldecode($redirect));
                    exit;
                } else {
                    $error = '정지된 계정입니다. 관리자에게 문의하세요.';
                }
            } else {
                $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
            }
        } catch(PDOException $e) {
            $error = '로그인 중 오류가 발생했습니다.';
        }
    } else {
        $error = '아이디와 비밀번호를 입력해주세요.';
    }
}

$currentPage = 'login';
$pageTitle = '로그인';
include 'head.php';
?>

<style>
.login-section {
    padding: 80px 0;
    background: #F8F9FA;
    min-height: 80vh;
    display: flex;
    align-items: center;
}

.login-container {
    max-width: 400px;
    margin: 0 auto;
    padding: 0 20px;
    width: 100%;
}

.login-box {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.login-title {
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

.remember-forgot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    font-size: 14px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
}

.forgot-link {
    color: #666;
    text-decoration: none;
}

.forgot-link:hover {
    color: var(--primary-blue);
    text-decoration: underline;
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

.divider {
    text-align: center;
    margin: 24px 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #E5E5E7;
}

.divider span {
    background: white;
    padding: 0 16px;
    position: relative;
    color: #999;
    font-size: 14px;
}

.register-btn {
    width: 100%;
    padding: 14px;
    background: white;
    color: var(--primary-blue);
    border: 2px solid var(--primary-blue);
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: block;
    text-align: center;
}

.register-btn:hover {
    background: var(--primary-blue);
    color: white;
}

@media (max-width: 768px) {
    .login-box {
        padding: 24px;
    }
}
</style>

<section class="login-section">
    <div class="login-container">
        <div class="login-box">
            <h2 class="login-title">로그인</h2>
            
            <?php if($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                
                <div class="form-group">
                    <label for="user_id">아이디</label>
                    <input type="text" id="user_id" name="user_id" required autofocus
                           value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">로그인 상태 유지</label>
                    </div>
                    <a href="find_password.php" class="forgot-link">비밀번호 찾기</a>
                </div>
                
                <button type="submit" class="submit-btn">로그인</button>
            </form>
            
            <div class="divider">
                <span>또는</span>
            </div>
            
            <a href="register.php" class="register-btn">회원가입</a>
        </div>
    </div>
</section>

<?php include 'tail.php'; ?>