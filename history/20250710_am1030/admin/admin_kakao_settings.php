<?php
$pageTitle = '카카오톡 설정';

// 추가 스타일 정의
$additionalStyles = '
.kakao-tabs {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    overflow: hidden;
}

.tab-menu {
    display: flex;
    border-bottom: 1px solid #E5E5E7;
}

.tab-item {
    flex: 1;
    padding: 16px 24px;
    text-align: center;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.tab-item:hover {
    background: #F5F5F7;
    color: #1A237E;
}

.tab-item.active {
    color: #1A237E;
    border-bottom-color: #1A237E;
    background: #F8F9FA;
}

.settings-section {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #E5E5E7;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    color: #333;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.form-group select {
    width: auto;
    padding: 10px 16px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
}

.checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
}

.submit-btn {
    padding: 12px 24px;
    background: #1A237E;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.submit-btn:hover {
    background: #283593;
}

.template-item {
    background: #F8F9FA;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 16px;
}

.template-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.template-title {
    font-size: 16px;
    font-weight: 600;
}

.template-type {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    background: #E8EAF6;
    color: #3F51B5;
}

.help-text {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}
';

require_once 'admin_head.php';
require_once '../includes/kakao_config.php';

// 설정 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_config') {
        // 설정 파일 업데이트 (실제 환경에서는 환경변수나 DB에 저장)
        $configContent = "<?php
// 카카오 API 설정
define('KAKAO_API_KEY', '" . addslashes($_POST['api_key']) . "');
define('KAKAO_API_SECRET', '" . addslashes($_POST['api_secret']) . "');
define('KAKAO_SENDER_KEY', '" . addslashes($_POST['sender_key']) . "');
define('KAKAO_CHANNEL_ID', '" . addslashes($_POST['channel_id']) . "');

// 카카오 알림톡 API 엔드포인트
define('KAKAO_API_URL', 'https://api-alimtalk.cloud.toast.com/alimtalk/v2.2');

// 알림 설정
define('KAKAO_NOTIFICATION_ENABLED', " . ($_POST['enabled'] === '1' ? 'true' : 'false') . ");
define('KAKAO_TEST_MODE', " . ($_POST['test_mode'] === '1' ? 'true' : 'false') . ");
define('KAKAO_TEST_PHONE', '" . addslashes($_POST['test_phone']) . "');

// 메시지 발송 제한
define('KAKAO_DAILY_LIMIT', " . (int)$_POST['daily_limit'] . ");
define('KAKAO_RETRY_COUNT', " . (int)$_POST['retry_count'] . ");
?>";
        
        file_put_contents('../includes/kakao_config.php', $configContent);
        $message = "설정이 저장되었습니다.";
    }
    
    if ($action === 'update_template') {
        $templateId = $_POST['template_id'];
        $stmt = $pdo->prepare("UPDATE kakao_templates SET message_format = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$_POST['message_format'], $_POST['is_active'] ?? 0, $templateId]);
        $message = "템플릿이 업데이트되었습니다.";
    }
}

// 템플릿 목록 조회
$stmt = $pdo->query("SELECT * FROM kakao_templates ORDER BY board_type, template_code");
$templates = $stmt->fetchAll();
?>

        <div class="page-header">
            <h1>카카오톡 관리</h1>
            <p>카카오톡 알림 발송 현황을 확인하고 관리합니다.</p>
        </div>
        
        <!-- 탭 메뉴 -->
        <div class="kakao-tabs">
            <div class="tab-menu">
                <a href="admin_kakao.php" class="tab-item">대시보드</a>
                <a href="admin_kakao_quote.php" class="tab-item">견적문의 알림</a>
                <a href="admin_kakao_consignment.php" class="tab-item">위탁판매 알림</a>
                <a href="admin_kakao_settings.php" class="tab-item active">설정</a>
            </div>
        </div>
        
        <?php if (isset($message)): ?>
        <div class="msg success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_config">
            
            <div class="settings-section">
                <h3 class="section-title">API 설정</h3>
                
                <div class="form-group">
                    <label>API 키</label>
                    <input type="text" name="api_key" value="<?php echo KAKAO_API_KEY; ?>">
                    <div class="help-text">카카오 비즈니스에서 발급받은 API 키</div>
                </div>
                
                <div class="form-group">
                    <label>API 시크릿</label>
                    <input type="text" name="api_secret" value="<?php echo KAKAO_API_SECRET; ?>">
                    <div class="help-text">카카오 비즈니스에서 발급받은 API 시크릿</div>
                </div>
                
                <div class="form-group">
                    <label>발신프로필 키</label>
                    <input type="text" name="sender_key" value="<?php echo KAKAO_SENDER_KEY; ?>">
                </div>
                
                <div class="form-group">
                    <label>카카오톡 채널 ID</label>
                    <input type="text" name="channel_id" value="<?php echo KAKAO_CHANNEL_ID; ?>">
                    <div class="help-text">@ 포함하여 입력 (예: @chungnamsteel)</div>
                </div>
            </div>
            
            <div class="settings-section">
                <h3 class="section-title">발송 설정</h3>
                
                <div class="form-group">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="enabled" name="enabled" value="1" <?php echo KAKAO_NOTIFICATION_ENABLED ? 'checked' : ''; ?>>
                        <label for="enabled">알림 발송 활성화</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="test_mode" name="test_mode" value="1" <?php echo KAKAO_TEST_MODE ? 'checked' : ''; ?>>
                        <label for="test_mode">테스트 모드 (실제 발송하지 않음)</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>테스트 전화번호</label>
                    <input type="text" name="test_phone" value="<?php echo KAKAO_TEST_PHONE; ?>">
                    <div class="help-text">테스트 모드에서 사용할 전화번호</div>
                </div>
                
                <div class="form-group">
                    <label>일일 발송 제한</label>
                    <input type="number" name="daily_limit" value="<?php echo KAKAO_DAILY_LIMIT; ?>">
                </div>
                
                <div class="form-group">
                    <label>재시도 횟수</label>
                    <input type="number" name="retry_count" value="<?php echo KAKAO_RETRY_COUNT; ?>" min="0" max="5">
                </div>
                
                <button type="submit" class="submit-btn">설정 저장</button>
            </div>
        </form>
        
        <div class="settings-section">
            <h3 class="section-title">메시지 템플릿</h3>
            
            <?php foreach ($templates as $template): ?>
            <div class="template-item">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_template">
                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                    
                    <div class="template-header">
                        <div>
                            <div class="template-title"><?php echo htmlspecialchars($template['template_name']); ?></div>
                            <code style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($template['template_code']); ?></code>
                        </div>
                        <span class="template-type"><?php echo $template['board_type'] === 'quote' ? '견적문의' : '위탁판매'; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <textarea name="message_format"><?php echo htmlspecialchars($template['message_format']); ?></textarea>
                        <div class="help-text">사용 가능한 변수: {title}, {writer}, {company}, {company_name}, {category}</div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="active_<?php echo $template['id']; ?>" name="is_active" value="1" <?php echo $template['is_active'] ? 'checked' : ''; ?>>
                            <label for="active_<?php echo $template['id']; ?>">활성화</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">템플릿 저장</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

<?php require_once 'admin_tail.php'; ?>