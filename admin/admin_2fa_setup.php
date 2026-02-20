<?php
$pageTitle = '2FA 설정';
require_once 'admin_head.php';
require_once '../includes/totp.php';

$pdo = getDB();
$adminUsername = $_SESSION['admin_id'];

// 현재 관리자 2FA 상태 조회
$stmt = $pdo->prepare("SELECT id, totp_enabled, totp_secret FROM admin_users WHERE username = ?");
$stmt->execute([$adminUsername]);
$admin = $stmt->fetch();

$message = '';
$messageType = '';
$showBackupCodes = [];
$setupStep = ''; // 'show_qr', 'show_backup'

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'enable_start') {
        // 2FA 활성화 시작 → 시크릿 생성 + QR 표시
        $secret = generateTotpSecret();
        $_SESSION['totp_setup_secret'] = $secret;
        $setupStep = 'show_qr';
    }
    elseif ($action === 'enable_verify') {
        // QR 스캔 후 코드 입력 → 검증 + DB 저장
        $code = trim($_POST['totp_code'] ?? '');
        $secret = $_SESSION['totp_setup_secret'] ?? '';

        if (empty($secret)) {
            $message = '설정 세션이 만료되었습니다. 다시 시도해주세요.';
            $messageType = 'error';
        } elseif (verifyTotpCode($secret, $code)) {
            // 복구 코드 생성
            $backupResult = generateBackupCodes(10);

            // DB 업데이트
            $updateStmt = $pdo->prepare("UPDATE admin_users SET totp_secret = ?, totp_enabled = 1, totp_backup_codes = ? WHERE id = ?");
            $updateStmt->execute([$secret, json_encode($backupResult['hashed']), $admin['id']]);

            unset($_SESSION['totp_setup_secret']);

            $message = '2FA가 성공적으로 활성화되었습니다. 아래 복구 코드를 안전한 곳에 저장하세요.';
            $messageType = 'success';
            $showBackupCodes = $backupResult['plain'];
            $setupStep = 'show_backup';

            // 상태 갱신
            $admin['totp_enabled'] = 1;
        } else {
            $message = '인증 코드가 올바르지 않습니다. 다시 시도해주세요.';
            $messageType = 'error';
            $setupStep = 'show_qr';
        }
    }
    elseif ($action === 'disable') {
        // 2FA 비활성화 → 현재 TOTP 코드 필수
        $code = trim($_POST['totp_code'] ?? '');
        if (verifyTotpCode($admin['totp_secret'], $code)) {
            $updateStmt = $pdo->prepare("UPDATE admin_users SET totp_secret = NULL, totp_enabled = 0, totp_backup_codes = NULL WHERE id = ?");
            $updateStmt->execute([$admin['id']]);

            $message = '2FA가 비활성화되었습니다.';
            $messageType = 'success';
            $admin['totp_enabled'] = 0;
            $admin['totp_secret'] = null;
        } else {
            $message = '인증 코드가 올바르지 않습니다.';
            $messageType = 'error';
        }
    }
    elseif ($action === 'regenerate_backup') {
        // 복구 코드 재생성 → 현재 TOTP 코드 필수
        $code = trim($_POST['totp_code'] ?? '');
        if (verifyTotpCode($admin['totp_secret'], $code)) {
            $backupResult = generateBackupCodes(10);
            $updateStmt = $pdo->prepare("UPDATE admin_users SET totp_backup_codes = ? WHERE id = ?");
            $updateStmt->execute([json_encode($backupResult['hashed']), $admin['id']]);

            $message = '복구 코드가 재생성되었습니다. 안전한 곳에 저장하세요.';
            $messageType = 'success';
            $showBackupCodes = $backupResult['plain'];
            $setupStep = 'show_backup';
        } else {
            $message = '인증 코드가 올바르지 않습니다.';
            $messageType = 'error';
        }
    }
}
?>

<div class="page-header">
    <h1>2단계 인증(2FA) 설정</h1>
    <p>TOTP 기반 2단계 인증을 설정하여 관리자 계정을 보호합니다.</p>
</div>

<?php if ($message): ?>
    <div class="msg <?php echo $messageType; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 32px; margin-bottom: 24px;">

<?php if (!empty($showBackupCodes)): ?>
    <!-- 복구 코드 표시 -->
    <div style="margin-bottom: 24px;">
        <h3 style="color: #C62828; margin-bottom: 12px;">&#9888; 복구 코드 (한 번만 표시됩니다)</h3>
        <p style="color: #666; margin-bottom: 16px; font-size: 14px;">
            이 코드들은 인증 앱에 접근할 수 없을 때 사용합니다. 각 코드는 1회만 사용 가능합니다.<br>
            안전한 곳에 저장해두세요 (예: 비밀번호 관리자, 인쇄).
        </p>
        <div style="background: #F5F5F7; border: 2px dashed #E5E5E7; border-radius: 12px; padding: 24px; text-align: center;">
            <div id="backup-codes" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; max-width: 400px; margin: 0 auto;">
                <?php foreach ($showBackupCodes as $code): ?>
                    <code style="font-size: 18px; font-weight: 700; letter-spacing: 2px; padding: 8px; background: white; border-radius: 6px; border: 1px solid #E5E5E7;"><?php echo htmlspecialchars($code); ?></code>
                <?php endforeach; ?>
            </div>
            <button onclick="copyBackupCodes()" style="margin-top: 16px; padding: 10px 20px; background: #1A237E; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px;">
                &#128203; 클립보드에 복사
            </button>
        </div>
    </div>
    <script>
    function copyBackupCodes() {
        var codes = [];
        document.querySelectorAll('#backup-codes code').forEach(function(el) {
            codes.push(el.textContent.trim());
        });
        var text = '충남스틸 관리자 2FA 복구 코드\n' + new Date().toLocaleDateString() + '\n\n' + codes.join('\n');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() { alert('복구 코드가 클립보드에 복사되었습니다.'); });
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            alert('복구 코드가 클립보드에 복사되었습니다.');
        }
    }
    </script>

<?php elseif ($setupStep === 'show_qr'): ?>
    <!-- QR 코드 + 인증 코드 입력 -->
    <?php
    $secret = $_SESSION['totp_setup_secret'] ?? '';
    $totpUri = getTotpUri($secret, $adminUsername);
    ?>
    <h3 style="margin-bottom: 16px;">1단계: 인증 앱에서 QR 코드 스캔</h3>
    <p style="color: #666; margin-bottom: 16px; font-size: 14px;">Google Authenticator, Microsoft Authenticator, Authy 등의 앱을 사용하세요.</p>
    <div style="text-align: center; margin-bottom: 24px;">
        <div id="qrcode" style="display: inline-block; padding: 16px; background: white; border: 2px solid #E5E5E7; border-radius: 12px;"></div>
    </div>
    <div style="background: #F5F5F7; border-radius: 8px; padding: 16px; margin-bottom: 24px; text-align: center;">
        <p style="color: #666; font-size: 13px; margin-bottom: 8px;">QR 코드를 스캔할 수 없는 경우, 아래 시크릿을 수동 입력하세요:</p>
        <code style="font-size: 16px; font-weight: 700; letter-spacing: 3px; word-break: break-all; user-select: all;"><?php echo htmlspecialchars($secret); ?></code>
    </div>

    <h3 style="margin-bottom: 12px;">2단계: 인증 코드 입력</h3>
    <p style="color: #666; margin-bottom: 16px; font-size: 14px;">앱에 표시된 6자리 코드를 입력하여 설정을 완료하세요.</p>
    <form method="POST" style="max-width: 400px;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="enable_verify">
        <div style="display: flex; gap: 12px; align-items: end;">
            <input type="text" name="totp_code" maxlength="6" pattern="\d{6}" inputmode="numeric"
                   autocomplete="one-time-code" required autofocus
                   placeholder="000000"
                   style="flex: 1; padding: 14px; border: 2px solid #E5E5E7; border-radius: 10px; font-size: 24px; text-align: center; letter-spacing: 6px; font-weight: 700;">
            <button type="submit" style="padding: 14px 24px; background: #1A237E; color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; white-space: nowrap;">확인</button>
        </div>
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    new QRCode(document.getElementById("qrcode"), {
        text: <?php echo json_encode($totpUri); ?>,
        width: 200,
        height: 200,
        colorDark: "#1A237E",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });
    </script>

<?php elseif (!empty($admin['totp_enabled'])): ?>
    <!-- 2FA 활성화 상태 -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
        <span style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #E8F5E9; color: #2E7D32; border-radius: 20px; font-weight: 600; font-size: 14px;">
            &#9989; 2FA 활성화됨
        </span>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- 2FA 비활성화 -->
        <div style="border: 1px solid #E5E5E7; border-radius: 12px; padding: 24px;">
            <h3 style="margin-bottom: 8px; color: #C62828;">2FA 비활성화</h3>
            <p style="color: #666; font-size: 14px; margin-bottom: 16px;">비활성화하려면 현재 인증 코드를 입력하세요.</p>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="disable">
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="totp_code" maxlength="6" pattern="\d{6}" inputmode="numeric"
                           required placeholder="000000"
                           style="flex: 1; padding: 10px; border: 2px solid #E5E5E7; border-radius: 8px; font-size: 18px; text-align: center; letter-spacing: 4px; font-weight: 700;">
                    <button type="submit" style="padding: 10px 16px; background: #C62828; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; white-space: nowrap;"
                            onclick="return confirm('정말 2FA를 비활성화하시겠습니까?');">비활성화</button>
                </div>
            </form>
        </div>

        <!-- 복구 코드 재생성 -->
        <div style="border: 1px solid #E5E5E7; border-radius: 12px; padding: 24px;">
            <h3 style="margin-bottom: 8px;">복구 코드 재생성</h3>
            <p style="color: #666; font-size: 14px; margin-bottom: 16px;">기존 복구 코드가 모두 무효화되고 새로운 10개가 생성됩니다.</p>
            <form method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="regenerate_backup">
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="totp_code" maxlength="6" pattern="\d{6}" inputmode="numeric"
                           required placeholder="000000"
                           style="flex: 1; padding: 10px; border: 2px solid #E5E5E7; border-radius: 8px; font-size: 18px; text-align: center; letter-spacing: 4px; font-weight: 700;">
                    <button type="submit" style="padding: 10px 16px; background: #FB8C00; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; white-space: nowrap;">재생성</button>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- 2FA 비활성화 상태 -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
        <span style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #FFF3E0; color: #E65100; border-radius: 20px; font-weight: 600; font-size: 14px;">
            &#9888; 2FA 비활성화됨
        </span>
    </div>
    <p style="color: #666; margin-bottom: 24px; line-height: 1.6;">
        2단계 인증(TOTP)을 활성화하면 비밀번호 유출 시에도 관리자 계정을 보호할 수 있습니다.<br>
        Google Authenticator, Microsoft Authenticator, Authy 등의 앱이 필요합니다.
    </p>
    <form method="POST">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="enable_start">
        <button type="submit" style="padding: 14px 32px; background: #1A237E; color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer;">
            2FA 활성화 시작
        </button>
    </form>
<?php endif; ?>

</div>

</main>
</body>
</html>
