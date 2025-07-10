<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';
$step = 1;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['find'])) {
        // Step 1: 아이디와 이메일로 회원 찾기
        $user_id = trim($_POST['user_id'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        try {
            $stmt = $pdo->prepare("SELECT id, name FROM members WHERE user_id = ? AND email = ?");
            $stmt->execute([$user_id, $email]);
            $member = $stmt->fetch();
            
            if($member) {
                // 임시 비밀번호 생성
                $temp_password = substr(md5(uniqid()), 0, 8);
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                
                // 비밀번호 업데이트
                $stmt = $pdo->prepare("UPDATE members SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $member['id']]);
                
                $success = "임시 비밀번호가 생성되었습니다.<br><br>";
                $success .= "아이디: {$user_id}<br>";
                $success .= "임시 비밀번호: <strong>{$temp_password}</strong><br><br>";
                $success .= "로그인 후 반드시 비밀번호를 변경해주세요.";
                
                $step = 2;
            } else {
                $error = '일치하는 회원 정보를 찾을 수 없습니다.';
            }
        } catch(PDOException $e) {
            $error = '오류가 발생했습니다. 다시 시도해주세요.';
        }
    }
}

$currentPage = 'find_password';
$pageTitle = '비밀번호 찾기';
include 'head.php';
?>

<style>
.find-section {
    padding: 80px 0;
    background: #F8F9FA;
    min-height: 80vh;
    display: flex;
    align-items: center;
}

.find-container {
    max-width: 400px;
    margin: 0 auto;
    padding: 0 20px;
    width: 100%;
}

.find-box {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.find-title {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    text-align: center;
    margin-bottom: 12px;
}

.find-desc {
    text-align: center;
    color: #666;
    font-size: 14px;
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

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert.error {
    background: #FFEBEE;
    color: #C62828;
    text-align: center;
}

.alert.success {
    background: #E8F5E9;
    color: #2E7D32;
    line-height: 1.6;
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

.back-link {
    text-align: center;
    margin-top: 24px;
    font-size: 14px;
}

.back-link a {
    color: #666;
    text-decoration: none;
}

.back-link a:hover {
    color: var(--primary-blue);
    text-decoration: underline;
}

.login-btn {
    display: block;
    width: 100%;
    padding: 14px;
    background: var(--primary-blue);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    transition: all 0.3s ease;
}

.login-btn:hover {
    background: #0F1F7A;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .find-box {
        padding: 24px;
    }
}
</style>

<section class="find-section">
    <div class="find-container">
        <div class="find-box">
            <h2 class="find-title">비밀번호 찾기</h2>
            
            <?php if($step === 1): ?>
                <p class="find-desc">가입시 등록한 아이디와 이메일을 입력해주세요.</p>
                
                <?php if($error): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="user_id">아이디</label>
                        <input type="text" id="user_id" name="user_id" required autofocus
                               value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">이메일</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" name="find" class="submit-btn">비밀번호 찾기</button>
                </form>
                
                <div class="back-link">
                    <a href="login.php">← 로그인으로 돌아가기</a>
                </div>
                
            <?php elseif($step === 2): ?>
                <?php if($success): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                    <a href="login.php" class="login-btn">로그인하기</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'tail.php'; ?>