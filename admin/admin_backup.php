<?php
$pageTitle = '백업/복원 관리';

// 추가 스타일 정의
$additionalStyles = '
.backup-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

.backup-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.backup-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.backup-title i {
    color: #1A237E;
}

.form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    align-items: flex-end;
}

.form-group {
    flex: 1;
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group input[type="text"],
.form-group input[type="time"],
.form-group input[type="number"],
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #1A237E;
}

.switch-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.4s;
    border-radius: 26px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #1A237E;
}

input:checked + .slider:before {
    transform: translateX(24px);
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #1A237E;
    color: white;
}

.btn-primary:hover {
    background: #283593;
}

.btn-secondary {
    background: #E5E5E7;
    color: #333;
}

.btn-secondary:hover {
    background: #D5D5D7;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.last-backup {
    margin-top: 16px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 14px;
    color: #666;
}

.last-backup strong {
    color: #333;
}

.status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.status-success {
    background: #d4edda;
    color: #155724;
}

.status-failed {
    background: #f8d7da;
    color: #721c24;
}

.status-progress {
    background: #fff3cd;
    color: #856404;
}

.backup-list-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.backup-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}

.backup-table th,
.backup-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #E5E5E7;
}

.backup-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.backup-table td {
    font-size: 14px;
    color: #666;
}

.backup-table tr:hover {
    background: #f8f9fa;
}

.backup-table .checkbox-col {
    width: 40px;
    text-align: center;
}

.backup-actions {
    display: flex;
    gap: 8px;
    margin-top: 16px;
    flex-wrap: wrap;
}

.file-size {
    font-family: monospace;
}

.backup-type {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.type-auto {
    background: #e3f2fd;
    color: #1565c0;
}

.type-manual {
    background: #f3e5f5;
    color: #7b1fa2;
}

.empty-message {
    text-align: center;
    padding: 40px;
    color: #999;
}

.progress-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.progress-box {
    background: white;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    min-width: 300px;
}

.progress-box .spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #1A237E;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
';

require_once 'admin_head.php';
require_once '../includes/settings.php';

// 백업 설정 가져오기
$backupAutoEnabled = getSetting('backup_auto_enabled', '1');
$backupScheduleTime = getSetting('backup_schedule_time', '01:00');
$backupRetentionDays = getSetting('backup_retention_days', '30');
$backupMaxFiles = getSetting('backup_max_files', '30');

// 이메일 알림 설정 가져오기
$emailAutoEnabled = getSetting('backup_email_auto_enabled', '0');
$emailManualEnabled = getSetting('backup_email_manual_enabled', '0');
$emailRecipients = getSetting('backup_email_recipients', '');
$emailOnSuccess = getSetting('backup_email_on_success', '1');
$emailOnFailure = getSetting('backup_email_on_failure', '1');
$emailAttachFile = getSetting('backup_email_attach_file', '1');

// 마지막 백업 정보 가져오기
$lastBackup = null;
try {
    $stmt = $pdo->query("SELECT * FROM backup_logs WHERE status = 'success' ORDER BY created_at DESC LIMIT 1");
    $lastBackup = $stmt->fetch();
} catch (PDOException $e) {
    // 테이블이 없을 경우 무시
}

// 백업 파일 목록 가져오기
$backupDir = dirname(__DIR__) . '/backups';
$backupFiles = [];

if (is_dir($backupDir)) {
    $files = glob($backupDir . '/*.sql');
    foreach ($files as $file) {
        $filename = basename($file);
        $backupFiles[] = [
            'filename' => $filename,
            'size' => filesize($file),
            'created_at' => filemtime($file)
        ];
    }
    // 최신순 정렬
    usort($backupFiles, function($a, $b) {
        return $b['created_at'] - $a['created_at'];
    });
}

// backup_logs 테이블에서 추가 정보 가져오기
$backupLogs = [];
try {
    $stmt = $pdo->query("SELECT filename, backup_type, status FROM backup_logs");
    while ($row = $stmt->fetch()) {
        $backupLogs[$row['filename']] = $row;
    }
} catch (PDOException $e) {
    // 테이블이 없을 경우 무시
}

// 파일 크기 포맷 함수
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-database"></i> 백업/복원 관리</h1>
    </div>

    <div class="backup-grid">
        <!-- 자동 백업 설정 -->
        <div class="backup-card">
            <h2 class="backup-title"><i class="fas fa-clock"></i> 자동 백업 설정</h2>
            <form id="settingsForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>자동 백업</label>
                        <div class="switch-wrapper">
                            <label class="switch">
                                <input type="checkbox" name="backup_auto_enabled" id="backupAutoEnabled" <?php echo $backupAutoEnabled === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span id="autoBackupStatus"><?php echo $backupAutoEnabled === '1' ? '활성화' : '비활성화'; ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="backupTime">백업 시간</label>
                        <input type="time" name="backup_schedule_time" id="backupTime" value="<?php echo escape($backupScheduleTime); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="retentionDays">보관 일수</label>
                        <input type="number" name="backup_retention_days" id="retentionDays" value="<?php echo escape($backupRetentionDays); ?>" min="1" max="365">
                    </div>
                    <div class="form-group">
                        <label for="maxFiles">최대 파일 수</label>
                        <input type="number" name="backup_max_files" id="maxFiles" value="<?php echo escape($backupMaxFiles); ?>" min="1" max="100">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 설정 저장
                </button>
            </form>
        </div>

        <!-- 수동 백업 -->
        <div class="backup-card">
            <h2 class="backup-title"><i class="fas fa-download"></i> 수동 백업</h2>
            <p style="color: #666; margin-bottom: 16px;">데이터베이스를 즉시 백업합니다.</p>
            <button type="button" class="btn btn-success" id="createBackupBtn">
                <i class="fas fa-play"></i> 즉시 백업 실행
            </button>

            <?php if ($lastBackup): ?>
            <div class="last-backup">
                <strong>마지막 백업:</strong>
                <?php echo date('Y-m-d H:i', strtotime($lastBackup['created_at'])); ?>
                <span class="status-badge status-<?php echo $lastBackup['status']; ?>">
                    <?php echo $lastBackup['status'] === 'success' ? '성공' : ($lastBackup['status'] === 'failed' ? '실패' : '진행중'); ?>
                </span>
            </div>
            <?php else: ?>
            <div class="last-backup">
                <strong>마지막 백업:</strong> 기록 없음
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 이메일 알림 설정 -->
    <div class="backup-grid" style="margin-bottom: 24px;">
        <div class="backup-card" style="grid-column: 1 / -1;">
            <h2 class="backup-title"><i class="fas fa-envelope"></i> 이메일 알림 설정</h2>
            <form id="emailSettingsForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>자동 백업 알림</label>
                        <div class="switch-wrapper">
                            <label class="switch">
                                <input type="checkbox" name="backup_email_auto_enabled" id="emailAutoEnabled" <?php echo $emailAutoEnabled === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span id="emailAutoStatus"><?php echo $emailAutoEnabled === '1' ? '활성화' : '비활성화'; ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>수동 백업 알림</label>
                        <div class="switch-wrapper">
                            <label class="switch">
                                <input type="checkbox" name="backup_email_manual_enabled" id="emailManualEnabled" <?php echo $emailManualEnabled === '1' ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span id="emailManualStatus"><?php echo $emailManualEnabled === '1' ? '활성화' : '비활성화'; ?></span>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="emailRecipients">알림 수신 이메일</label>
                        <input type="text" name="backup_email_recipients" id="emailRecipients" value="<?php echo escape($emailRecipients); ?>" placeholder="example@email.com (여러 이메일은 쉼표로 구분)">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="backup_email_on_success" id="emailOnSuccess" <?php echo $emailOnSuccess === '1' ? 'checked' : ''; ?> style="width: auto;">
                            <span>성공 시 알림</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="backup_email_on_failure" id="emailOnFailure" <?php echo $emailOnFailure === '1' ? 'checked' : ''; ?> style="width: auto;">
                            <span>실패 시 알림</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="backup_email_attach_file" id="emailAttachFile" <?php echo $emailAttachFile === '1' ? 'checked' : ''; ?> style="width: auto;">
                            <span>성공 알림에 백업 파일 첨부</span>
                        </label>
                        <small style="color: #999; font-size: 11px; margin-left: 22px;">20MB 초과 시 자동 제외</small>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 8px; align-items: center; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 이메일 설정 저장
                    </button>
                    <button type="button" class="btn btn-secondary" id="testEmailBtn">
                        <i class="fas fa-paper-plane"></i> 테스트 발송
                    </button>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; margin-left: 10px; font-size: 14px;">
                        <input type="checkbox" id="attachBackupFile" style="width: auto;">
                        <span>최신 백업 파일 첨부</span>
                    </label>
                </div>
            </form>
        </div>
    </div>

    <!-- 백업 파일 목록 -->
    <div class="backup-list-card">
        <h2 class="backup-title"><i class="fas fa-list"></i> 백업 파일 목록</h2>

        <?php if (empty($backupFiles)): ?>
        <div class="empty-message">
            <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 16px;"></i>
            <p>백업 파일이 없습니다.</p>
        </div>
        <?php else: ?>
        <table class="backup-table">
            <thead>
                <tr>
                    <th class="checkbox-col">
                        <input type="checkbox" id="selectAll">
                    </th>
                    <th>파일명</th>
                    <th>크기</th>
                    <th>유형</th>
                    <th>생성일시</th>
                    <th>작업</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backupFiles as $file):
                    $logInfo = $backupLogs[$file['filename']] ?? null;
                    $backupType = $logInfo['backup_type'] ?? 'manual';
                ?>
                <tr>
                    <td class="checkbox-col">
                        <input type="checkbox" class="file-checkbox" value="<?php echo escape($file['filename']); ?>">
                    </td>
                    <td><?php echo escape($file['filename']); ?></td>
                    <td class="file-size"><?php echo formatFileSize($file['size']); ?></td>
                    <td>
                        <span class="backup-type type-<?php echo $backupType; ?>">
                            <?php echo $backupType === 'auto' ? '자동' : '수동'; ?>
                        </span>
                    </td>
                    <td><?php echo date('Y-m-d H:i:s', $file['created_at']); ?></td>
                    <td>
                        <button type="button" class="btn btn-secondary btn-sm download-btn" data-filename="<?php echo escape($file['filename']); ?>">
                            <i class="fas fa-download"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm delete-btn" data-filename="<?php echo escape($file['filename']); ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="backup-actions">
            <button type="button" class="btn btn-secondary" id="downloadSelectedBtn">
                <i class="fas fa-download"></i> 선택 다운로드
            </button>
            <button type="button" class="btn btn-danger" id="deleteSelectedBtn">
                <i class="fas fa-trash"></i> 선택 삭제
            </button>
            <button type="button" class="btn btn-primary" id="restoreSelectedBtn">
                <i class="fas fa-undo"></i> 선택 복원
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 진행 상태 오버레이 -->
<div class="progress-overlay" id="progressOverlay">
    <div class="progress-box">
        <div class="spinner"></div>
        <p id="progressMessage">백업 진행 중...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 자동 백업 토글 상태 텍스트 업데이트
    const autoBackupCheckbox = document.getElementById('backupAutoEnabled');
    const autoBackupStatus = document.getElementById('autoBackupStatus');

    autoBackupCheckbox.addEventListener('change', function() {
        autoBackupStatus.textContent = this.checked ? '활성화' : '비활성화';
    });

    // 이메일 알림 토글 상태 텍스트 업데이트
    const emailAutoCheckbox = document.getElementById('emailAutoEnabled');
    const emailManualCheckbox = document.getElementById('emailManualEnabled');
    const emailAutoStatus = document.getElementById('emailAutoStatus');
    const emailManualStatus = document.getElementById('emailManualStatus');

    emailAutoCheckbox?.addEventListener('change', function() {
        emailAutoStatus.textContent = this.checked ? '활성화' : '비활성화';
    });

    emailManualCheckbox?.addEventListener('change', function() {
        emailManualStatus.textContent = this.checked ? '활성화' : '비활성화';
    });

    // 설정 저장
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('backup_auto_enabled', autoBackupCheckbox.checked ? '1' : '0');

        fetch('ajax/backup_settings_save.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('설정이 저장되었습니다.');
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            alert('요청 중 오류가 발생했습니다.');
            console.error(error);
        });
    });

    // 이메일 설정 저장
    document.getElementById('emailSettingsForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('backup_email_auto_enabled', document.getElementById('emailAutoEnabled').checked ? '1' : '0');
        formData.append('backup_email_manual_enabled', document.getElementById('emailManualEnabled').checked ? '1' : '0');
        formData.append('backup_email_recipients', document.getElementById('emailRecipients').value);
        formData.append('backup_email_on_success', document.getElementById('emailOnSuccess').checked ? '1' : '0');
        formData.append('backup_email_on_failure', document.getElementById('emailOnFailure').checked ? '1' : '0');
        formData.append('backup_email_attach_file', document.getElementById('emailAttachFile').checked ? '1' : '0');
        formData.append('save_email_settings', '1');

        fetch('ajax/backup_settings_save.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('이메일 설정이 저장되었습니다.');
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            alert('요청 중 오류가 발생했습니다.');
            console.error(error);
        });
    });

    // 테스트 이메일 발송
    document.getElementById('testEmailBtn')?.addEventListener('click', function() {
        const recipients = document.getElementById('emailRecipients').value.trim();
        const attachBackup = document.getElementById('attachBackupFile')?.checked || false;

        if (!recipients) {
            alert('테스트 발송할 이메일 주소를 입력하세요.');
            return;
        }

        let confirmMsg = '입력한 이메일 주소로 테스트 메일을 발송하시겠습니까?\n\n수신자: ' + recipients;
        if (attachBackup) {
            confirmMsg += '\n\n※ 최신 백업 파일이 첨부됩니다.';
        }

        if (!confirm(confirmMsg)) {
            return;
        }

        const progressMsg = attachBackup ? '테스트 이메일 발송 중 (백업 파일 첨부)...' : '테스트 이메일 발송 중...';
        showProgress(progressMsg);

        const formData = new FormData();
        formData.append('recipients', recipients);
        formData.append('attach_backup', attachBackup ? '1' : '0');

        fetch('ajax/backup_email_test.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideProgress();
            if (data.success) {
                alert('테스트 이메일이 발송되었습니다.\n' + data.message);
            } else {
                alert('테스트 발송 실패: ' + data.message);
            }
        })
        .catch(error => {
            hideProgress();
            alert('요청 중 오류가 발생했습니다.');
            console.error(error);
        });
    });

    // 즉시 백업 실행
    document.getElementById('createBackupBtn').addEventListener('click', function() {
        if (!confirm('데이터베이스 백업을 실행하시겠습니까?')) return;

        showProgress('백업 진행 중...');

        fetch('ajax/backup_create.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            hideProgress();
            if (data.success) {
                alert('백업이 완료되었습니다.\n파일명: ' + data.filename);
                location.reload();
            } else {
                alert('백업 실패: ' + data.message);
            }
        })
        .catch(error => {
            hideProgress();
            alert('요청 중 오류가 발생했습니다.');
            console.error(error);
        });
    });

    // 전체 선택
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.file-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
    });

    // 개별 다운로드
    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const filename = this.dataset.filename;
            window.location.href = 'ajax/backup_download.php?filename=' + encodeURIComponent(filename);
        });
    });

    // 개별 삭제
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const filename = this.dataset.filename;
            if (!confirm('정말 이 백업 파일을 삭제하시겠습니까?\n' + filename)) return;

            deleteFiles([filename]);
        });
    });

    // 선택 다운로드
    document.getElementById('downloadSelectedBtn')?.addEventListener('click', function() {
        const selected = getSelectedFiles();
        if (selected.length === 0) {
            alert('다운로드할 파일을 선택하세요.');
            return;
        }

        // 여러 파일 다운로드 (하나씩)
        selected.forEach((filename, index) => {
            setTimeout(() => {
                window.location.href = 'ajax/backup_download.php?filename=' + encodeURIComponent(filename);
            }, index * 500);
        });
    });

    // 선택 삭제
    document.getElementById('deleteSelectedBtn')?.addEventListener('click', function() {
        const selected = getSelectedFiles();
        if (selected.length === 0) {
            alert('삭제할 파일을 선택하세요.');
            return;
        }

        if (!confirm('선택한 ' + selected.length + '개 파일을 삭제하시겠습니까?')) return;

        deleteFiles(selected);
    });

    // 선택 복원
    document.getElementById('restoreSelectedBtn')?.addEventListener('click', function() {
        const selected = getSelectedFiles();
        if (selected.length === 0) {
            alert('복원할 파일을 선택하세요.');
            return;
        }
        if (selected.length > 1) {
            alert('복원은 하나의 파일만 선택할 수 있습니다.');
            return;
        }

        if (!confirm('정말 이 백업으로 복원하시겠습니까?\n현재 데이터가 백업 시점으로 덮어씌워집니다.\n\n파일: ' + selected[0])) return;

        showProgress('복원 진행 중...');

        const formData = new FormData();
        formData.append('filename', selected[0]);

        fetch('ajax/backup_restore.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideProgress();
            if (data.success) {
                alert('복원이 완료되었습니다.');
                location.reload();
            } else {
                alert('복원 실패: ' + data.message);
            }
        })
        .catch(error => {
            hideProgress();
            alert('요청 중 오류가 발생했습니다.');
            console.error(error);
        });
    });

    // 유틸리티 함수
    function getSelectedFiles() {
        const selected = [];
        document.querySelectorAll('.file-checkbox:checked').forEach(cb => {
            selected.push(cb.value);
        });
        return selected;
    }

    function deleteFiles(filenames) {
        showProgress('삭제 진행 중...');

        const formData = new FormData();
        filenames.forEach(f => formData.append('filenames[]', f));

        fetch('ajax/backup_delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideProgress();
            if (data.success) {
                alert('삭제가 완료되었습니다.');
                location.reload();
            } else {
                alert('삭제 실패: ' + data.message);
            }
        })
        .catch(error => {
            hideProgress();
            alert('요청 중 오류가 발생했습니다.');
            console.error(error);
        });
    }

    function showProgress(message) {
        document.getElementById('progressMessage').textContent = message;
        document.getElementById('progressOverlay').style.display = 'flex';
    }

    function hideProgress() {
        document.getElementById('progressOverlay').style.display = 'none';
    }
});
</script>

<?php require_once 'admin_tail.php'; ?>
