<?php
session_start();
require_once 'db.php';
require_once 'includes/auth_tokens.php';

// 로그인 확인
if(!isset($_SESSION['member_id'])) {
    header('Location: login.php?redirect=settings.php');
    exit;
}

$member_id = $_SESSION['member_id'];
$success = '';
$error = '';

// 현재 설정 가져오기
$prefs = getMemberSessionPreferences($member_id);

// POST 요청 처리
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'update_session_duration':
                $duration = (int)$_POST['session_duration'];
                $remember_me_enabled = isset($_POST['remember_me_enabled']) ? 1 : 0;

                // 유효한 값인지 확인
                $valid_durations = array_keys(getSessionDurationOptions());
                $valid_durations[] = 0; // 기본값 허용

                if(in_array($duration, $valid_durations)) {
                    if(saveMemberSessionPreferences($member_id, $duration, $remember_me_enabled)) {
                        $success = '설정이 저장되었습니다.';
                        $prefs = getMemberSessionPreferences($member_id);

                        // 기능 비활성화 시 기존 토큰 삭제
                        if(!$remember_me_enabled) {
                            deleteAllRememberTokens($member_id);
                            deleteRememberCookie();
                        }
                    } else {
                        $error = '설정 저장 중 오류가 발생했습니다.';
                    }
                } else {
                    $error = '유효하지 않은 설정값입니다.';
                }
                break;

            case 'delete_all_tokens':
                deleteAllRememberTokens($member_id);
                deleteRememberCookie();
                $success = '모든 로그인 유지 정보가 삭제되었습니다.';
                break;
        }
    }
}

$duration_options = getSessionDurationOptions();
$currentPage = 'settings';
$pageTitle = '설정';
include 'head.php';
?>

<style>
.settings-section {
    padding: 60px 0;
    background: #F8F9FA;
    min-height: calc(100vh - 200px);
}

.settings-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.settings-box {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.settings-header {
    padding: 30px;
    border-bottom: 1px solid #E5E5E7;
    background: linear-gradient(135deg, var(--primary-blue) 0%, #0F1F7A 100%);
    color: white;
}

.settings-header h2 {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
}

.settings-header p {
    margin: 8px 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.settings-content {
    padding: 30px;
}

.setting-group {
    padding: 30px 0;
    border-bottom: 1px solid #E5E5E7;
}

.setting-group:last-child {
    border-bottom: none;
}

.setting-group h3 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}

.setting-group .description {
    color: #666;
    font-size: 14px;
    margin-bottom: 20px;
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

.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: white;
}

.form-group select:focus {
    outline: none;
    border-color: var(--primary-blue);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #F8F9FA;
    border-radius: 8px;
    margin-bottom: 20px;
}

.checkbox-group input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.checkbox-group label {
    margin: 0;
    cursor: pointer;
    flex: 1;
}

.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert.success {
    background: #E8F5E9;
    color: #2E7D32;
    border: 1px solid #A5D6A7;
}

.alert.error {
    background: #FFEBEE;
    color: #C62828;
    border: 1px solid #EF9A9A;
}

.alert-icon {
    font-size: 20px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--primary-blue);
    color: white;
}

.btn-primary:hover {
    background: #0F1F7A;
    transform: translateY(-1px);
}

.btn-danger {
    background: #DC3545;
    color: white;
}

.btn-danger:hover {
    background: #C82333;
}

.btn-group {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.info-box {
    background: #E3F2FD;
    border-left: 4px solid var(--primary-blue);
    padding: 16px;
    border-radius: 8px;
    margin-top: 20px;
}

.info-box h4 {
    font-size: 14px;
    font-weight: 700;
    color: var(--primary-blue);
    margin: 0 0 8px;
}

.info-box ul {
    margin: 0;
    padding-left: 20px;
    font-size: 14px;
    color: #555;
}

.info-box ul li {
    margin-bottom: 4px;
}

@media (max-width: 768px) {
    .settings-content {
        padding: 20px;
    }

    .setting-group {
        padding: 20px 0;
    }

    .btn-group {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }
}
</style>

<section class="settings-section">
    <div class="settings-container">
        <div class="settings-box">
            <div class="settings-header">
                <h2>⚙️ 로그인 설정</h2>
                <p>로그인 유지 시간 및 보안 설정을 관리하세요</p>
            </div>

            <div class="settings-content">
                <?php if($success): ?>
                    <div class="alert success">
                        <span class="alert-icon">✓</span>
                        <span><?php echo $success; ?></span>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert error">
                        <span class="alert-icon">⚠</span>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <!-- 로그인 유지 시간 설정 -->
                <div class="setting-group">
                    <h3>🕐 로그인 유지 시간</h3>
                    <p class="description">
                        "로그인 상태 유지" 체크박스를 선택했을 때 로그인이 유지되는 시간을 설정합니다.
                    </p>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_session_duration">

                        <div class="checkbox-group">
                            <input type="checkbox" id="remember_me_enabled" name="remember_me_enabled"
                                   <?php echo $prefs['remember_me_enabled'] ? 'checked' : ''; ?>>
                            <label for="remember_me_enabled">
                                <strong>로그인 유지 기능 활성화</strong>
                                <div style="font-size: 13px; color: #666; margin-top: 4px;">
                                    이 기능을 비활성화하면 "로그인 상태 유지" 옵션이 작동하지 않습니다.
                                </div>
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="session_duration">로그인 유지 시간 선택</label>
                            <select name="session_duration" id="session_duration"
                                    <?php echo !$prefs['remember_me_enabled'] ? 'disabled' : ''; ?>>
                                <option value="0" <?php echo $prefs['session_duration'] == 0 ? 'selected' : ''; ?>>
                                    기본값 (30일)
                                </option>
                                <?php foreach($duration_options as $seconds => $label): ?>
                                    <option value="<?php echo $seconds; ?>"
                                            <?php echo $prefs['session_duration'] == $seconds ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                💾 설정 저장
                            </button>
                        </div>
                    </form>

                    <div class="info-box">
                        <h4>ℹ️ 안내사항</h4>
                        <ul>
                            <li><strong>기본값</strong>: 로그인 유지 체크 시 30일간 로그인 상태가 유지됩니다.</li>
                            <li><strong>시간 선택</strong>: 1시간부터 30일까지 원하는 시간을 선택할 수 있습니다.</li>
                            <li><strong>종일 옵션</strong>: 선택 시 오늘 자정(00:00)까지 로그인 상태가 유지됩니다.</li>
                            <li><strong>보안</strong>: 공용 컴퓨터에서는 짧은 시간을 선택하거나 기능을 비활성화하세요.</li>
                            <li><strong>로그아웃</strong>: 로그아웃하면 모든 로그인 유지 정보가 즉시 삭제됩니다.</li>
                        </ul>
                    </div>
                </div>

                <!-- 보안 관리 -->
                <div class="setting-group">
                    <h3>🔒 보안 관리</h3>
                    <p class="description">
                        모든 기기에서 로그인 유지 정보를 삭제하고 재로그인을 요구합니다.
                    </p>

                    <form method="POST" action="" onsubmit="return confirm('모든 기기에서 로그아웃됩니다. 계속하시겠습니까?');">
                        <input type="hidden" name="action" value="delete_all_tokens">

                        <div class="btn-group">
                            <button type="submit" class="btn btn-danger">
                                🗑️ 모든 로그인 정보 삭제
                            </button>
                        </div>
                    </form>

                    <div class="info-box">
                        <h4>⚠️ 주의사항</h4>
                        <ul>
                            <li>이 작업을 수행하면 모든 기기에서 자동 로그인이 해제됩니다.</li>
                            <li>현재 세션은 유지되지만 브라우저를 닫으면 재로그인이 필요합니다.</li>
                            <li>비밀번호 변경 또는 보안 문제 발생 시 이 기능을 사용하세요.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// 로그인 유지 기능 활성화/비활성화에 따라 시간 선택 활성화
document.getElementById('remember_me_enabled').addEventListener('change', function() {
    document.getElementById('session_duration').disabled = !this.checked;
});
</script>

<?php include 'tail.php'; ?>
