<?php
session_start();
require_once 'db.php';
require_once 'includes/csrf.php';
require_once 'includes/EmailService.php';

$error = '';
$success = '';
$step = 1;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 검증
    if (!verifyCsrfToken(false)) {
        $error = '잘못된 요청입니다. 페이지를 새로고침 해주세요.';
    } elseif(isset($_POST['find'])) {
        $user_id = trim($_POST['user_id'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // 타이밍 공격 방지: 항상 동일한 처리 시간
        $startTime = microtime(true);

        try {
            $stmt = $pdo->prepare("SELECT id, name, email FROM members WHERE user_id = ? AND email = ?");
            $stmt->execute([$user_id, $email]);
            $member = $stmt->fetch();

            if($member) {
                // 임시 비밀번호 생성
                $temp_password = bin2hex(random_bytes(6));
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);

                // 비밀번호 업데이트
                $stmt = $pdo->prepare("UPDATE members SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $member['id']]);

                // 이메일로 임시 비밀번호 전송
                $emailService = new EmailService($pdo);
                $emailBody = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #1A237E;'>충남스틸 임시 비밀번호 안내</h2>
                    <p>안녕하세요, " . htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') . "님.</p>
                    <p>요청하신 임시 비밀번호입니다:</p>
                    <div style='background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center;'>
                        <p style='margin: 0; font-size: 14px; color: #666;'>아이디</p>
                        <p style='margin: 5px 0 15px; font-size: 18px; font-weight: bold;'>" . htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') . "</p>
                        <p style='margin: 0; font-size: 14px; color: #666;'>임시 비밀번호</p>
                        <p style='margin: 5px 0 0; font-size: 24px; font-weight: bold; color: #1A237E;'>" . htmlspecialchars($temp_password, ENT_QUOTES, 'UTF-8') . "</p>
                    </div>
                    <p style='color: #c62828;'>보안을 위해 로그인 후 반드시 비밀번호를 변경해주세요.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #999;'>본 메일은 충남스틸 비밀번호 찾기 요청에 의해 자동 발송되었습니다.</p>
                </div>";

                $emailResult = $emailService->send($member['email'], '[충남스틸] 임시 비밀번호 안내', $emailBody);

                if (!$emailResult['success']) {
                    error_log("Password reset email failed for user {$user_id}: " . $emailResult['message']);
                }
            } else {
                // 사용자 미존재 시에도 동일한 처리 시간을 위해 dummy hash
                password_hash('dummy_timing_equalization', PASSWORD_DEFAULT);
            }
        } catch(PDOException $e) {
            error_log("find_password error: " . $e->getMessage());
            // 동일 메시지 반환
        }

        // 최소 처리 시간 보장 (타이밍 공격 방지)
        $elapsed = microtime(true) - $startTime;
        if ($elapsed < 0.5) {
            usleep((int)((0.5 - $elapsed) * 1000000));
        }

        // 사용자 존재 여부와 무관하게 동일 메시지
        $success = "입력하신 정보와 일치하는 회원이 있다면 등록된 이메일로 임시 비밀번호가 전송됩니다.<br><br>이메일을 확인해주세요.";
        $step = 2;
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
                    <div class="alert error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="form-group">
                        <label for="user_id">아이디</label>
                        <input type="text" id="user_id" name="user_id" required autofocus
                               value="<?php echo htmlspecialchars($_POST['user_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">이메일</label>
                        <input type="email" id="email" name="email" required
                               value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <button type="submit" name="find" class="submit-btn">비밀번호 찾기</button>
                </form>

                <div class="back-link">
                    <a href="login.php">← 로그인으로 돌아가기</a>
                </div>

            <?php elseif($step === 2): ?>
                <div class="alert success"><?php echo $success; ?></div>
                <a href="login.php" class="login-btn">로그인하기</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'tail.php'; ?>
