<?php
$pageTitle = '로그 관리';

// 추가 스타일 정의
$additionalStyles = '
.logs-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
}

.stat-card .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1A237E;
    margin-bottom: 8px;
}

.stat-card .stat-label {
    font-size: 14px;
    color: #666;
}

.stat-card.loading .stat-value {
    color: #ccc;
}

.logs-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.logs-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.logs-title i {
    color: #1A237E;
}

.log-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.log-tab {
    padding: 10px 20px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    background: white;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #666;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.log-tab:hover {
    border-color: #1A237E;
    color: #1A237E;
}

.log-tab.active {
    background: #1A237E;
    border-color: #1A237E;
    color: white;
}

.log-tab .badge {
    background: rgba(0,0,0,0.1);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
}

.log-tab.active .badge {
    background: rgba(255,255,255,0.2);
}

.filter-row {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #666;
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 2px solid #E5E5E7;
    border-radius: 6px;
    font-size: 14px;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #1A237E;
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

.logs-table {
    width: 100%;
    border-collapse: collapse;
}

.logs-table th,
.logs-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #E5E5E7;
}

.logs-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    font-size: 13px;
}

.logs-table td {
    font-size: 13px;
    color: #666;
}

.logs-table tr:hover {
    background: #f8f9fa;
}

.logs-table .text-truncate {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
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

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-sent {
    background: #d4edda;
    color: #155724;
}

.empty-message {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-message i {
    font-size: 48px;
    margin-bottom: 16px;
    display: block;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
}

.pagination button {
    padding: 8px 14px;
    border: 2px solid #E5E5E7;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    font-size: 13px;
}

.pagination button:hover:not(:disabled) {
    border-color: #1A237E;
    color: #1A237E;
}

.pagination button.active {
    background: #1A237E;
    border-color: #1A237E;
    color: white;
}

.pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 24px;
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

.checkbox-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

.checkbox-list label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: normal;
    font-size: 14px;
}

.checkbox-list input[type="checkbox"] {
    width: 16px;
    height: 16px;
}

.cleanup-preview {
    margin-top: 16px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
}

.cleanup-preview h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #333;
}

.cleanup-preview-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cleanup-preview-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #E5E5E7;
    font-size: 13px;
}

.cleanup-preview-item:last-child {
    border-bottom: none;
}

.cleanup-preview-item .count {
    font-weight: 600;
    color: #dc3545;
}

.cleanup-history {
    margin-top: 20px;
}

.cleanup-history h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #333;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.history-table th,
.history-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #E5E5E7;
}

.history-table th {
    background: #f8f9fa;
    font-weight: 600;
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
require_once '../includes/LogService.php';
require_once '../includes/LogCleanupService.php';

// 서비스 초기화
$logService = new LogService($pdo);
$cleanupService = new LogCleanupService($pdo);

// 로그 유형 목록
$logTypes = $logService->getLogTypes();

// 설정 가져오기
$settings = $cleanupService->getAllSettings();
$cleanableTables = $cleanupService->getCleanableTables();
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-clipboard-list"></i> 로그 관리</h1>
        <p style="color: #666; margin-top: 8px;">시스템 로그 조회, 정리, 통계 및 내보내기</p>
    </div>

    <!-- 통계 카드 -->
    <div class="logs-stats-grid" id="statsGrid">
        <div class="stat-card loading">
            <div class="stat-value" id="statTotal">-</div>
            <div class="stat-label">총 로그 수</div>
        </div>
        <div class="stat-card loading">
            <div class="stat-value" id="statToday">-</div>
            <div class="stat-label">오늘 생성</div>
        </div>
        <div class="stat-card loading">
            <div class="stat-value" id="statCleanup">-</div>
            <div class="stat-label">정리 대상</div>
        </div>
        <div class="stat-card loading">
            <div class="stat-value" id="statStorage">-</div>
            <div class="stat-label">저장 용량</div>
        </div>
    </div>

    <!-- 로그 조회 카드 -->
    <div class="logs-card">
        <h2 class="logs-title"><i class="fas fa-search"></i> 로그 조회</h2>

        <!-- 로그 유형 탭 -->
        <div class="log-tabs" id="logTabs">
            <?php foreach ($logTypes as $key => $name): ?>
            <button type="button" class="log-tab <?php echo $key === 'login' ? 'active' : ''; ?>" data-type="<?php echo $key; ?>">
                <?php echo escape($name); ?>
                <span class="badge" id="badge-<?php echo $key; ?>">-</span>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- 필터 -->
        <div class="filter-row">
            <div class="filter-group">
                <label>시작일</label>
                <input type="date" id="filterDateFrom" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
            </div>
            <div class="filter-group">
                <label>종료일</label>
                <input type="date" id="filterDateTo" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="filter-group">
                <label>검색어</label>
                <input type="text" id="filterSearch" placeholder="IP, 이름 등...">
            </div>
            <button type="button" class="btn btn-primary" id="searchBtn">
                <i class="fas fa-search"></i> 조회
            </button>
            <button type="button" class="btn btn-secondary" id="exportBtn">
                <i class="fas fa-download"></i> CSV 내보내기
            </button>
        </div>

        <!-- 로그 테이블 -->
        <div id="logsTableContainer">
            <div class="empty-message">
                <i class="fas fa-spinner fa-spin"></i>
                <p>로그를 불러오는 중...</p>
            </div>
        </div>

        <!-- 페이지네이션 -->
        <div class="pagination" id="pagination" style="display: none;"></div>
    </div>

    <!-- 설정 그리드 -->
    <div class="settings-grid">
        <!-- 자동 정리 설정 -->
        <div class="logs-card">
            <h2 class="logs-title"><i class="fas fa-clock"></i> 자동 정리 설정</h2>
            <form id="settingsForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>자동 정리</label>
                        <div class="switch-wrapper">
                            <label class="switch">
                                <input type="checkbox" name="log_auto_cleanup_enabled" id="autoCleanupEnabled" <?php echo $settings['log_auto_cleanup_enabled'] ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span id="autoCleanupStatus"><?php echo $settings['log_auto_cleanup_enabled'] ? '활성화' : '비활성화'; ?></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cleanupTime">실행 시간</label>
                        <input type="time" name="log_cleanup_schedule_time" id="cleanupTime" value="<?php echo escape($settings['log_cleanup_schedule_time']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="retentionDays">보관 기간 (일)</label>
                        <input type="number" name="log_retention_days" id="retentionDays" value="<?php echo escape($settings['log_retention_days']); ?>" min="7" max="365">
                    </div>
                </div>
                <div class="form-group">
                    <label>정리 대상</label>
                    <div class="checkbox-list">
                        <?php foreach ($cleanableTables as $table => $config): ?>
                        <label>
                            <input type="checkbox" name="cleanup_targets[]" value="<?php echo $table; ?>"
                                <?php echo in_array($table, $settings['log_cleanup_targets']) ? 'checked' : ''; ?>>
                            <?php echo escape($config['name']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 16px;">
                    <i class="fas fa-save"></i> 설정 저장
                </button>
            </form>
        </div>

        <!-- 수동 정리 -->
        <div class="logs-card">
            <h2 class="logs-title"><i class="fas fa-broom"></i> 수동 정리</h2>
            <form id="cleanupForm">
                <div class="form-group">
                    <label for="cleanupDays">정리 기준 (일)</label>
                    <input type="number" id="cleanupDays" value="90" min="1" max="365">
                    <small style="color: #666; display: block; margin-top: 4px;">입력한 일수 이전의 로그가 삭제됩니다.</small>
                </div>
                <div class="form-group">
                    <label>정리 대상</label>
                    <div class="checkbox-list" id="cleanupTargetsList">
                        <?php foreach ($cleanableTables as $table => $config): ?>
                        <label>
                            <input type="checkbox" name="manual_cleanup_targets[]" value="<?php echo $table; ?>">
                            <?php echo escape($config['name']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" id="previewCleanupBtn" style="margin-top: 16px;">
                    <i class="fas fa-eye"></i> 미리보기
                </button>
                <button type="submit" class="btn btn-danger" style="margin-top: 16px;">
                    <i class="fas fa-trash"></i> 정리 실행
                </button>
            </form>

            <div class="cleanup-preview" id="cleanupPreview" style="display: none;">
                <h4>삭제 예정 건수</h4>
                <div class="cleanup-preview-list" id="cleanupPreviewList"></div>
            </div>

            <div class="cleanup-history">
                <h4>최근 정리 이력</h4>
                <table class="history-table" id="cleanupHistoryTable">
                    <thead>
                        <tr>
                            <th>일시</th>
                            <th>유형</th>
                            <th>삭제 건수</th>
                            <th>상태</th>
                        </tr>
                    </thead>
                    <tbody id="cleanupHistoryBody">
                        <tr><td colspan="4" style="text-align: center;">로딩 중...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 진행 상태 오버레이 -->
<div class="progress-overlay" id="progressOverlay">
    <div class="progress-box">
        <div class="spinner"></div>
        <p id="progressMessage">처리 중...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentLogType = 'login';
    let currentPage = 1;
    let totalPages = 1;

    // 초기 로딩
    loadStats();
    loadLogs();
    loadCleanupHistory();

    // 탭 전환
    document.querySelectorAll('.log-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.log-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentLogType = this.dataset.type;
            currentPage = 1;
            loadLogs();
        });
    });

    // 조회 버튼
    document.getElementById('searchBtn').addEventListener('click', function() {
        currentPage = 1;
        loadLogs();
    });

    // 엔터키로 검색
    document.getElementById('filterSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            currentPage = 1;
            loadLogs();
        }
    });

    // 내보내기 버튼
    document.getElementById('exportBtn').addEventListener('click', exportLogs);

    // 자동 정리 토글
    document.getElementById('autoCleanupEnabled').addEventListener('change', function() {
        document.getElementById('autoCleanupStatus').textContent = this.checked ? '활성화' : '비활성화';
    });

    // 설정 저장
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveSettings();
    });

    // 미리보기 버튼
    document.getElementById('previewCleanupBtn').addEventListener('click', previewCleanup);

    // 정리 실행
    document.getElementById('cleanupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        executeCleanup();
    });

    // 통계 로딩
    function loadStats() {
        fetch('ajax/logs_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('statTotal').textContent = numberFormat(data.data.total_logs) + '건';
                    document.getElementById('statToday').textContent = numberFormat(data.data.today_logs) + '건';
                    document.getElementById('statCleanup').textContent = numberFormat(data.data.cleanup_target) + '건';
                    document.getElementById('statStorage').textContent = data.data.storage_formatted;

                    // 탭 배지 업데이트
                    for (const [key, info] of Object.entries(data.data.by_type)) {
                        const badge = document.getElementById('badge-' + key);
                        if (badge) {
                            badge.textContent = numberFormat(info.total);
                        }
                    }

                    document.querySelectorAll('.stat-card').forEach(card => card.classList.remove('loading'));
                }
            })
            .catch(error => console.error('Stats error:', error));
    }

    // 로그 로딩
    function loadLogs() {
        const container = document.getElementById('logsTableContainer');
        container.innerHTML = '<div class="empty-message"><i class="fas fa-spinner fa-spin"></i><p>로그를 불러오는 중...</p></div>';

        const params = new URLSearchParams({
            log_type: currentLogType,
            date_from: document.getElementById('filterDateFrom').value,
            date_to: document.getElementById('filterDateTo').value,
            search: document.getElementById('filterSearch').value,
            page: currentPage,
            per_page: 20
        });

        fetch('ajax/logs_list.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderLogsTable(data.data);
                    totalPages = data.data.total_pages;
                    renderPagination();
                } else {
                    container.innerHTML = '<div class="empty-message"><i class="fas fa-exclamation-triangle"></i><p>' + data.message + '</p></div>';
                }
            })
            .catch(error => {
                container.innerHTML = '<div class="empty-message"><i class="fas fa-exclamation-triangle"></i><p>로그 로딩 중 오류가 발생했습니다.</p></div>';
                console.error('Logs error:', error);
            });
    }

    // 로그 테이블 렌더링
    function renderLogsTable(data) {
        const container = document.getElementById('logsTableContainer');

        if (!data.logs || data.logs.length === 0) {
            container.innerHTML = '<div class="empty-message"><i class="fas fa-inbox"></i><p>로그가 없습니다.</p></div>';
            document.getElementById('pagination').style.display = 'none';
            return;
        }

        let html = '<table class="logs-table"><thead><tr>';

        // 컬럼 헤더 (로그 유형별)
        const headers = getTableHeaders(currentLogType);
        headers.forEach(h => {
            html += '<th>' + h + '</th>';
        });
        html += '</tr></thead><tbody>';

        // 데이터 행
        data.logs.forEach(log => {
            html += '<tr>';
            html += renderLogRow(currentLogType, log);
            html += '</tr>';
        });

        html += '</tbody></table>';
        container.innerHTML = html;
        document.getElementById('pagination').style.display = 'flex';
    }

    // 테이블 헤더 가져오기
    function getTableHeaders(logType) {
        switch (logType) {
            case 'login':
                return ['일시', '회원명', 'ID', 'IP', 'User-Agent'];
            case 'delete':
                return ['일시', '제품명', '카테고리', '삭제자', '삭제 방법'];
            case 'backup':
                return ['일시', '파일명', '크기', '유형', '상태'];
            case 'email':
                return ['일시', '수신자', '제목', '상태', '발송일'];
            case 'calculator':
                return ['일시', '제품명', '규격', '재질', 'IP'];
            default:
                return [];
        }
    }

    // 로그 행 렌더링
    function renderLogRow(logType, log) {
        let html = '';
        switch (logType) {
            case 'login':
                html += '<td>' + formatDate(log.login_date) + '</td>';
                html += '<td>' + escapeHtml(log.member_name || '-') + '</td>';
                html += '<td>' + escapeHtml(log.member_user_id || '-') + '</td>';
                html += '<td>' + escapeHtml(log.login_ip || '-') + '</td>';
                html += '<td class="text-truncate" title="' + escapeHtml(log.user_agent || '') + '">' + escapeHtml(log.user_agent || '-') + '</td>';
                break;
            case 'delete':
                html += '<td>' + formatDate(log.deleted_at) + '</td>';
                html += '<td>' + escapeHtml(log.product_name || '-') + '</td>';
                html += '<td>' + escapeHtml(log.category_code || '-') + '</td>';
                html += '<td>' + escapeHtml(log.deleted_by || '-') + '</td>';
                html += '<td>' + escapeHtml(log.deleted_via || '-') + '</td>';
                break;
            case 'backup':
                html += '<td>' + formatDate(log.created_at) + '</td>';
                html += '<td>' + escapeHtml(log.filename || '-') + '</td>';
                html += '<td>' + escapeHtml(log.file_size_formatted || '-') + '</td>';
                html += '<td>' + (log.backup_type === 'auto' ? '자동' : '수동') + '</td>';
                html += '<td><span class="status-badge status-' + log.status + '">' + escapeHtml(log.status_label || log.status) + '</span></td>';
                break;
            case 'email':
                html += '<td>' + formatDate(log.created_at) + '</td>';
                html += '<td class="text-truncate">' + escapeHtml(log.recipient || '-') + '</td>';
                html += '<td class="text-truncate">' + escapeHtml(log.subject || '-') + '</td>';
                html += '<td><span class="status-badge status-' + log.status + '">' + escapeHtml(log.status_label || log.status) + '</span></td>';
                html += '<td>' + (log.sent_at ? formatDate(log.sent_at) : '-') + '</td>';
                break;
            case 'calculator':
                html += '<td>' + formatDate(log.created_at) + '</td>';
                html += '<td>' + escapeHtml(log.product_name || '-') + '</td>';
                html += '<td>' + escapeHtml(log.specification || '-') + '</td>';
                html += '<td>' + escapeHtml(log.material || '-') + '</td>';
                html += '<td>' + escapeHtml(log.user_ip || '-') + '</td>';
                break;
        }
        return html;
    }

    // 페이지네이션 렌더링
    function renderPagination() {
        const container = document.getElementById('pagination');
        if (totalPages <= 1) {
            container.style.display = 'none';
            return;
        }

        let html = '';
        html += '<button ' + (currentPage === 1 ? 'disabled' : '') + ' onclick="goToPage(1)"><i class="fas fa-angle-double-left"></i></button>';
        html += '<button ' + (currentPage === 1 ? 'disabled' : '') + ' onclick="goToPage(' + (currentPage - 1) + ')"><i class="fas fa-angle-left"></i></button>';

        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
            html += '<button class="' + (i === currentPage ? 'active' : '') + '" onclick="goToPage(' + i + ')">' + i + '</button>';
        }

        html += '<button ' + (currentPage === totalPages ? 'disabled' : '') + ' onclick="goToPage(' + (currentPage + 1) + ')"><i class="fas fa-angle-right"></i></button>';
        html += '<button ' + (currentPage === totalPages ? 'disabled' : '') + ' onclick="goToPage(' + totalPages + ')"><i class="fas fa-angle-double-right"></i></button>';

        container.innerHTML = html;
        container.style.display = 'flex';
    }

    // 페이지 이동 (전역 함수)
    window.goToPage = function(page) {
        currentPage = page;
        loadLogs();
    };

    // CSV 내보내기
    function exportLogs() {
        const params = new URLSearchParams({
            log_type: currentLogType,
            date_from: document.getElementById('filterDateFrom').value,
            date_to: document.getElementById('filterDateTo').value,
            search: document.getElementById('filterSearch').value
        });

        window.location.href = 'ajax/logs_export.php?' + params.toString();
    }

    // 설정 저장
    function saveSettings() {
        showProgress('설정 저장 중...');

        const targets = [];
        document.querySelectorAll('input[name="cleanup_targets[]"]:checked').forEach(cb => {
            targets.push(cb.value);
        });

        const formData = new FormData();
        formData.append('log_retention_days', document.getElementById('retentionDays').value);
        formData.append('log_auto_cleanup_enabled', document.getElementById('autoCleanupEnabled').checked ? '1' : '0');
        formData.append('log_cleanup_schedule_time', document.getElementById('cleanupTime').value);
        formData.append('log_cleanup_targets', JSON.stringify(targets));

        fetch('ajax/logs_settings_save.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideProgress();
            if (data.success) {
                alert('설정이 저장되었습니다.');
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            hideProgress();
            alert('요청 중 오류가 발생했습니다.');
            console.error(error);
        });
    }

    // 정리 미리보기
    function previewCleanup() {
        const days = document.getElementById('cleanupDays').value;
        const targets = [];
        document.querySelectorAll('input[name="manual_cleanup_targets[]"]:checked').forEach(cb => {
            targets.push(cb.value);
        });

        if (targets.length === 0) {
            alert('정리 대상을 선택하세요.');
            return;
        }

        showProgress('미리보기 로딩 중...');

        const params = new URLSearchParams({ days: days });

        fetch('ajax/logs_cleanup.php?preview=1&' + params.toString())
            .then(response => response.json())
            .then(data => {
                hideProgress();
                if (data.success) {
                    const preview = data.data;
                    let html = '';
                    let totalCount = 0;

                    for (const [table, info] of Object.entries(preview.tables)) {
                        if (targets.includes(table)) {
                            html += '<div class="cleanup-preview-item">';
                            html += '<span>' + info.name + '</span>';
                            html += '<span class="count">' + numberFormat(info.count) + '건</span>';
                            html += '</div>';
                            totalCount += info.count;
                        }
                    }

                    html += '<div class="cleanup-preview-item" style="font-weight: bold; border-top: 2px solid #333; margin-top: 8px; padding-top: 12px;">';
                    html += '<span>총 삭제 예정</span>';
                    html += '<span class="count">' + numberFormat(totalCount) + '건</span>';
                    html += '</div>';

                    document.getElementById('cleanupPreviewList').innerHTML = html;
                    document.getElementById('cleanupPreview').style.display = 'block';
                } else {
                    alert('미리보기 실패: ' + data.message);
                }
            })
            .catch(error => {
                hideProgress();
                alert('요청 중 오류가 발생했습니다.');
                console.error(error);
            });
    }

    // 정리 실행
    function executeCleanup() {
        const days = document.getElementById('cleanupDays').value;
        const targets = [];
        document.querySelectorAll('input[name="manual_cleanup_targets[]"]:checked').forEach(cb => {
            targets.push(cb.value);
        });

        if (targets.length === 0) {
            alert('정리 대상을 선택하세요.');
            return;
        }

        if (!confirm('선택한 ' + targets.length + '개 로그 테이블에서 ' + days + '일 이전의 로그를 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.')) {
            return;
        }

        showProgress('로그 정리 중...');

        const formData = new FormData();
        formData.append('days_before', days);
        targets.forEach(t => formData.append('targets[]', t));

        fetch('ajax/logs_cleanup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideProgress();
            if (data.success) {
                alert(data.message);
                loadStats();
                loadCleanupHistory();
                document.getElementById('cleanupPreview').style.display = 'none';
            } else {
                alert('정리 실패: ' + data.message);
            }
        })
        .catch(error => {
            hideProgress();
            alert('요청 중 오류가 발생했습니다.');
            console.error(error);
        });
    }

    // 정리 이력 로딩
    function loadCleanupHistory() {
        fetch('ajax/logs_stats.php?history=1')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.history) {
                    const tbody = document.getElementById('cleanupHistoryBody');
                    if (data.data.history.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">정리 이력이 없습니다.</td></tr>';
                        return;
                    }

                    let html = '';
                    data.data.history.forEach(h => {
                        html += '<tr>';
                        html += '<td>' + formatDate(h.created_at) + '</td>';
                        html += '<td>' + (h.cleanup_type === 'auto' ? '자동' : '수동') + '</td>';
                        html += '<td>' + numberFormat(h.deleted_count) + '건</td>';
                        html += '<td><span class="status-badge status-' + h.status + '">' + (h.status === 'success' ? '성공' : '실패') + '</span></td>';
                        html += '</tr>';
                    });
                    tbody.innerHTML = html;
                }
            })
            .catch(error => console.error('History error:', error));
    }

    // 유틸리티 함수
    function showProgress(message) {
        document.getElementById('progressMessage').textContent = message;
        document.getElementById('progressOverlay').style.display = 'flex';
    }

    function hideProgress() {
        document.getElementById('progressOverlay').style.display = 'none';
    }

    function numberFormat(num) {
        return (num || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.getFullYear() + '-' +
               String(d.getMonth() + 1).padStart(2, '0') + '-' +
               String(d.getDate()).padStart(2, '0') + ' ' +
               String(d.getHours()).padStart(2, '0') + ':' +
               String(d.getMinutes()).padStart(2, '0');
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
});
</script>

<?php require_once 'admin_tail.php'; ?>
