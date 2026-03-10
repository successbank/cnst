<?php
session_start();
require_once '../db.php';
require_once '../includes/input_validator.php';
require_once '../includes/csrf.php';

// 이미 관리자로 로그인되어 있으면 관리자 페이지로 리다이렉트
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_index.php');
    exit;
}

// 세션 타임아웃 메시지
if(isset($_GET['msg']) && $_GET['msg'] === 'timeout') {
    $error_msg = "세션이 만료되었습니다. 다시 로그인해주세요.";
}

// 로그인 처리
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 검증
    if (!verifyCsrfToken(false)) {
        $error_msg = "잘못된 요청입니다. 페이지를 새로고침 해주세요.";
    }
    // Rate limiting: 5분 내 3회 초과 시 차단
    elseif (!checkRateLimit('admin_login', 3, 300)) {
        $error_msg = "로그인 시도가 너무 많습니다. 5분 후 다시 시도해주세요.";
    } else {
        $admin_id = $_POST['admin_id'] ?? '';
        $admin_pw = $_POST['admin_pw'] ?? '';

        // DB 기반 관리자 인증
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id, username, password_hash, totp_enabled, totp_secret, totp_backup_codes FROM admin_users WHERE username = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($admin_pw, $admin['password_hash'])) {
                // 세션 고정 공격 방지
                session_regenerate_id(true);

                // [보안] 관리자 로그인 성공 로그 기록
                try {
                    $log_stmt = $pdo->prepare("INSERT INTO admin_login_logs (admin_username, login_ip, user_agent, login_status) VALUES (?, ?, ?, 'success')");
                    $log_stmt->execute([$admin_id, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
                } catch(Exception $e) { error_log("Admin login log error: " . $e->getMessage()); }

                // 2FA 활성화 여부 확인
                if (!empty($admin['totp_enabled'])) {
                    // TOTP 활성화 → 2단계 인증 필요
                    $_SESSION['totp_pending'] = true;
                    $_SESSION['totp_admin_id'] = $admin['id'];
                    $_SESSION['totp_admin_username'] = $admin['username'];
                    $_SESSION['totp_pending_time'] = time();
                    header('Location: admin_totp_verify.php');
                    exit;
                }

                // 2FA 비활성화 → 기존 로그인 흐름
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['username'];
                $_SESSION['user_role'] = 'admin';
                $_SESSION['admin_login_time'] = time();

                header('Location: admin_index.php');
                exit;
            } else {
                $error_msg = "아이디 또는 비밀번호가 올바르지 않습니다.";
                // [보안] 관리자 로그인 실패 로그 기록
                try {
                    $log_stmt = $pdo->prepare("INSERT INTO admin_login_logs (admin_username, login_ip, user_agent, login_status) VALUES (?, ?, ?, 'failed')");
                    $log_stmt->execute([$admin_id, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
                } catch(Exception $e) { error_log("Admin login log error: " . $e->getMessage()); }
            }
        } catch (PDOException $e) {
            error_log("관리자 로그인 DB 오류: " . $e->getMessage());
            $error_msg = "시스템 오류가 발생했습니다. 잠시 후 다시 시도해주세요.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인 | 충남스틸</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #1A237E 0%, #283593 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* CNST.CO.KR 배경 텍스트 애니메이션 */
        .bg-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 240px;
            font-weight: 900;
            color: rgba(255, 255, 255, 0.20);
            letter-spacing: 0.05em;
            white-space: nowrap;
            z-index: 0;
            pointer-events: none;
            animation: text-pop-up-top 3s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite alternate;
        }
        
        /* 반응형 텍스트 크기 조정 */
        @media (max-width: 1600px) {
            .bg-text {
                font-size: 200px;
            }
        }
        
        @media (max-width: 1200px) {
            .bg-text {
                font-size: 160px;
            }
        }
        
        @media (max-width: 992px) {
            .bg-text {
                font-size: 120px;
            }
        }
        
        @media (max-width: 768px) {
            .bg-text {
                font-size: 80px;
            }
        }
        
        @media (max-width: 576px) {
            .bg-text {
                font-size: 60px;
                letter-spacing: 0.02em;
            }
        }
        
        .bg-text span {
            display: inline-block;
            animation: text-pop-up-letter 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) both;
        }
        
        .bg-text span:nth-child(1) { animation-delay: 0.1s; }
        .bg-text span:nth-child(2) { animation-delay: 0.2s; }
        .bg-text span:nth-child(3) { animation-delay: 0.3s; }
        .bg-text span:nth-child(4) { animation-delay: 0.4s; }
        .bg-text span:nth-child(5) { animation-delay: 0.5s; }
        .bg-text span:nth-child(6) { animation-delay: 0.6s; }
        .bg-text span:nth-child(7) { animation-delay: 0.7s; }
        .bg-text span:nth-child(8) { animation-delay: 0.8s; }
        .bg-text span:nth-child(9) { animation-delay: 0.9s; }
        .bg-text span:nth-child(10) { animation-delay: 1.0s; }
        
        @keyframes text-pop-up-letter {
            0% {
                transform: translateY(100px) scale(0);
                opacity: 0;
            }
            100% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
        
        @keyframes text-pop-up-top {
            0% {
                transform: translate(-50%, -50%) scale(1);
                filter: blur(0);
            }
            50% {
                transform: translate(-50%, -50%) scale(1.05);
                filter: blur(0.5px);
            }
            100% {
                transform: translate(-50%, -50%) scale(1);
                filter: blur(0);
            }
        }
        
        /* 추가 배경 효과 */
        .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(100, 150, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(100, 150, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.02) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
            animation: overlay-pulse 6s infinite ease-in-out;
        }
        
        @keyframes overlay-pulse {
            0%, 100% {
                opacity: 0.5;
            }
            50% {
                opacity: 1;
            }
        }
        
        .login-wrapper {
            position: relative;
            z-index: 1;
        }
        
        .login-container {
            background: white;
            padding: 60px;
            border-radius: 24px;
            box-shadow: 0 15px 60px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 600px;
            position: relative;
            z-index: 1;
            transform: scale(1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .login-header h1 {
            font-size: 42px;
            color: #1A237E;
            margin-bottom: 12px;
        }
        
        .login-header p {
            color: #666;
            font-size: 21px;
        }
        
        .form-group {
            margin-bottom: 30px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 12px;
            color: #333;
            font-weight: 500;
            font-size: 21px;
        }
        
        .form-group input {
            width: 100%;
            padding: 18px 24px;
            border: 3px solid #E5E5E7;
            border-radius: 12px;
            font-size: 24px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1A237E;
        }
        
        .login-btn {
            width: 100%;
            padding: 21px;
            background: #1A237E;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 24px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            background: #283593;
            transform: translateY(-1px);
        }
        
        .error-msg {
            background: #FFEBEE;
            color: #C62828;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 21px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 21px;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #1A237E;
        }
    </style>
</head>
<body>
    <div class="bg-text">
        <span>C</span><span>N</span><span>S</span><span>T</span><span>.</span><span>C</span><span>O</span><span>.</span><span>K</span><span>R</span>
    </div>
    <div class="bg-overlay"></div>
    <div class="login-wrapper">
        <div class="login-container">
        <div class="login-header">
            <h1>충남스틸 관리자</h1>
            <p>관리자 로그인이 필요합니다</p>
        </div>
        
        <?php if(isset($error_msg)): ?>
            <div class="error-msg"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="admin_id">관리자 ID</label>
                <input type="text" id="admin_id" name="admin_id" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="admin_pw">비밀번호</label>
                <input type="password" id="admin_pw" name="admin_pw" required>
            </div>
            
            <button type="submit" class="login-btn">로그인</button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">← 메인 페이지로 돌아가기</a>
        </div>
    </div>
    </div>
</body>
</html>