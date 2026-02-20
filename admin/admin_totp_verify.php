<?php
session_start();
require_once '../db.php';
require_once '../includes/input_validator.php';
require_once '../includes/csrf.php';
require_once '../includes/totp.php';

// totp_pending 상태가 아니면 로그인 페이지로
if (empty($_SESSION['totp_pending']) || $_SESSION['totp_pending'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// 5분 타임아웃 (TOTP 입력 화면 대기 시간)
if (time() - ($_SESSION['totp_pending_time'] ?? 0) > 300) {
    unset($_SESSION['totp_pending'], $_SESSION['totp_admin_id'], $_SESSION['totp_admin_username'], $_SESSION['totp_pending_time']);
    header('Location: admin_login.php?msg=timeout');
    exit;
}

$error_msg = '';
$useBackup = isset($_GET['backup']) || isset($_POST['use_backup']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 검증
    if (!verifyCsrfToken(false)) {
        $error_msg = "잘못된 요청입니다. 페이지를 새로고침 해주세요.";
    }
    // Rate limiting: 1분 내 5회 초과 시 차단
    elseif (!checkRateLimit('totp_verify', 5, 60)) {
        $error_msg = "인증 시도가 너무 많습니다. 1분 후 다시 시도해주세요.";
    } else {
        $adminId = $_SESSION['totp_admin_id'];
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT totp_secret, totp_backup_codes FROM admin_users WHERE id = ?");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if (!$admin) {
            unset($_SESSION['totp_pending'], $_SESSION['totp_admin_id'], $_SESSION['totp_admin_username'], $_SESSION['totp_pending_time']);
            header('Location: admin_login.php');
            exit;
        }

        $verified = false;

        if (!empty($_POST['use_backup']) && !empty($_POST['backup_code'])) {
            // 복구 코드 검증
            $backupCode = trim($_POST['backup_code']);
            $hashedCodes = json_decode($admin['totp_backup_codes'], true) ?: [];

            if (verifyBackupCode($backupCode, $hashedCodes)) {
                // 사용된 코드 제거 후 DB 업데이트
                $updateStmt = $pdo->prepare("UPDATE admin_users SET totp_backup_codes = ? WHERE id = ?");
                $updateStmt->execute([json_encode($hashedCodes), $adminId]);
                $verified = true;
            }
        } else {
            // TOTP 코드 검증
            $totpCode = trim($_POST['totp_code'] ?? '');
            if (verifyTotpCode($admin['totp_secret'], $totpCode)) {
                $verified = true;
            }
        }

        if ($verified) {
            // 2FA 검증 성공 → 관리자 세션 설정
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $_SESSION['totp_admin_username'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['admin_login_time'] = time();
            $_SESSION['totp_verified'] = true;

            // pending 세션 정리
            unset($_SESSION['totp_pending'], $_SESSION['totp_admin_id'], $_SESSION['totp_admin_username'], $_SESSION['totp_pending_time']);

            header('Location: admin_index.php');
            exit;
        } else {
            $error_msg = !empty($_POST['use_backup'])
                ? "복구 코드가 올바르지 않습니다."
                : "인증 코드가 올바르지 않습니다.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2단계 인증 | 충남스틸 관리자</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #1A237E 0%, #283593 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verify-container {
            background: white;
            padding: 60px;
            border-radius: 24px;
            box-shadow: 0 15px 60px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
        }
        .verify-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .verify-header h1 {
            font-size: 32px;
            color: #1A237E;
            margin-bottom: 12px;
        }
        .verify-header p {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
        }
        .shield-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }
        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 16px;
        }
        .form-group input {
            width: 100%;
            padding: 16px 20px;
            border: 3px solid #E5E5E7;
            border-radius: 12px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 700;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #1A237E;
        }
        .form-group input.backup-input {
            font-size: 18px;
            letter-spacing: 3px;
        }
        .verify-btn {
            width: 100%;
            padding: 18px;
            background: #1A237E;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .verify-btn:hover { background: #283593; }
        .error-msg {
            background: #FFEBEE;
            color: #C62828;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 15px;
        }
        .toggle-link {
            text-align: center;
            margin-top: 20px;
        }
        .toggle-link a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        .toggle-link a:hover { color: #1A237E; }
        .back-link {
            text-align: center;
            margin-top: 12px;
        }
        .back-link a {
            color: #999;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover { color: #1A237E; }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <span class="shield-icon">&#128274;</span>
            <h1>2단계 인증</h1>
            <?php if ($useBackup): ?>
                <p>2FA 복구 코드 8자리를 입력하세요</p>
            <?php else: ?>
                <p>인증 앱의 6자리 코드를 입력하세요</p>
            <?php endif; ?>
        </div>

        <?php if ($error_msg): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($useBackup): ?>
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="use_backup" value="1">
                <div class="form-group">
                    <label for="backup_code">복구 코드</label>
                    <input type="text" id="backup_code" name="backup_code" class="backup-input"
                           maxlength="8" autocomplete="off" autofocus required
                           placeholder="XXXXXXXX"
                           style="text-transform: uppercase;">
                </div>
                <button type="submit" class="verify-btn">복구 코드로 인증</button>
            </form>
            <div class="toggle-link">
                <a href="admin_totp_verify.php">&#8592; 인증 코드 입력으로 돌아가기</a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="totp_code">인증 코드</label>
                    <input type="text" id="totp_code" name="totp_code"
                           maxlength="6" pattern="\d{6}" inputmode="numeric"
                           autocomplete="one-time-code" autofocus required
                           placeholder="000000">
                </div>
                <button type="submit" class="verify-btn">인증 확인</button>
            </form>
            <div class="toggle-link">
                <a href="admin_totp_verify.php?backup=1">복구 코드 사용 &#8594;</a>
            </div>
        <?php endif; ?>

        <div class="back-link">
            <a href="admin_login.php">&#8592; 로그인으로 돌아가기</a>
        </div>
    </div>
</body>
</html>
