<?php
require_once '../db.php';
session_start();
require_once 'admin_check.php';
require_once '../includes/BoardPermissionHelper.php';

// CSRF 토큰
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// 현재 매트릭스 조회
$matrix = BoardPermissionHelper::getFullMatrix();

$currentFile = 'admin_board_permissions.php';
include 'admin_head.php';

$boardTypes = [
    'brokerage' => '중계판매',
    'quote' => '견적문의'
];
$roles = [
    'guest' => '비회원',
    'normal' => '일반',
    'silver' => '실버',
    'gold' => '골드',
    'vip' => 'VIP',
    'admin' => '관리자'
];
$actions = [
    'list' => '목록보기',
    'read' => '글읽기',
    'write' => '글쓰기',
    'comment' => '댓글'
];
?>

<style>
.perm-container {
    max-width: 1000px;
    margin: 0 auto;
}
.perm-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 0;
}
.perm-tab {
    padding: 12px 24px;
    background: #f0f0f0;
    border: none;
    border-radius: 8px 8px 0 0;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    color: #666;
    transition: all 0.2s;
}
.perm-tab.active {
    background: white;
    color: #1428A0;
    box-shadow: 0 -2px 8px rgba(0,0,0,0.06);
}
.perm-panel {
    display: none;
    background: white;
    border-radius: 0 12px 12px 12px;
    padding: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.perm-panel.active {
    display: block;
}
.perm-table {
    width: 100%;
    border-collapse: collapse;
}
.perm-table th, .perm-table td {
    padding: 14px 16px;
    text-align: center;
    border-bottom: 1px solid #eee;
}
.perm-table th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 14px;
    color: #333;
}
.perm-table th:first-child,
.perm-table td:first-child {
    text-align: left;
    font-weight: 500;
}
.perm-table td label {
    display: flex;
    align-items: center;
    justify-content: center;
}
.perm-table input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    accent-color: #1428A0;
}
.perm-table input[type="checkbox"]:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.perm-save-bar {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
}
.btn-save {
    padding: 12px 32px;
    background: #1428A0;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-save:hover {
    background: #0F1F7A;
}
.btn-save:disabled {
    background: #ccc;
    cursor: not-allowed;
}
.save-status {
    display: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 16px;
}
.save-status.success {
    display: block;
    background: #E8F5E9;
    color: #2E7D32;
}
.save-status.error {
    display: block;
    background: #FFEBEE;
    color: #C62828;
}
.role-admin { color: #999; }

@media (max-width: 768px) {
    .perm-panel { padding: 16px; }
    .perm-table th, .perm-table td { padding: 10px 6px; font-size: 12px; }
    .perm-table input[type="checkbox"] { width: 16px; height: 16px; }
    .perm-tab { padding: 10px 16px; font-size: 13px; }
}
</style>

<div class="admin-content">
    <div class="admin-content-header">
        <h2>게시판 권한 설정</h2>
    </div>

    <div class="perm-container">
        <div id="saveStatus" class="save-status"></div>

        <!-- 탭 -->
        <div class="perm-tabs">
            <?php $first = true; foreach ($boardTypes as $bType => $bLabel): ?>
            <button class="perm-tab <?php echo $first ? 'active' : ''; ?>"
                    onclick="switchTab('<?php echo $bType; ?>', this)"><?php echo $bLabel; ?></button>
            <?php $first = false; endforeach; ?>
        </div>

        <!-- 패널 -->
        <?php $first = true; foreach ($boardTypes as $bType => $bLabel): ?>
        <div id="panel-<?php echo $bType; ?>" class="perm-panel <?php echo $first ? 'active' : ''; ?>">
            <table class="perm-table">
                <thead>
                    <tr>
                        <th>권한</th>
                        <?php foreach ($roles as $rCode => $rLabel): ?>
                        <th class="<?php echo $rCode === 'admin' ? 'role-admin' : ''; ?>"><?php echo $rLabel; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actions as $aCode => $aLabel): ?>
                    <tr>
                        <td><?php echo $aLabel; ?></td>
                        <?php foreach ($roles as $rCode => $rLabel):
                            $checked = isset($matrix[$bType][$rCode][$aCode]) && $matrix[$bType][$rCode][$aCode] == 1;
                            $disabled = ($rCode === 'admin');
                        ?>
                        <td>
                            <label>
                                <input type="checkbox"
                                    data-board="<?php echo $bType; ?>"
                                    data-role="<?php echo $rCode; ?>"
                                    data-action="<?php echo $aCode; ?>"
                                    <?php echo ($checked || $disabled) ? 'checked' : ''; ?>
                                    <?php echo $disabled ? 'disabled' : ''; ?>>
                            </label>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $first = false; endforeach; ?>

        <div class="perm-save-bar">
            <button class="btn-save" id="btnSave" onclick="savePermissions()">저장</button>
        </div>
    </div>
</div>

<script>
function switchTab(boardType, btn) {
    document.querySelectorAll('.perm-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.perm-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + boardType).classList.add('active');
}

function savePermissions() {
    const btn = document.getElementById('btnSave');
    const statusEl = document.getElementById('saveStatus');
    btn.disabled = true;
    btn.textContent = '저장 중...';

    const matrix = {};
    document.querySelectorAll('input[type="checkbox"][data-board]').forEach(cb => {
        const board = cb.dataset.board;
        const role = cb.dataset.role;
        const action = cb.dataset.action;

        if (!matrix[board]) matrix[board] = {};
        if (!matrix[board][role]) matrix[board][role] = {};

        matrix[board][role][action] = (role === 'admin') ? 1 : (cb.checked ? 1 : 0);
    });

    fetch('ajax/board_permissions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'save_matrix',
            csrf_token: '<?php echo $csrf_token; ?>',
            matrix: matrix
        })
    })
    .then(r => r.json())
    .then(data => {
        statusEl.className = 'save-status ' + (data.success ? 'success' : 'error');
        statusEl.textContent = data.message;
        statusEl.style.display = 'block';
        setTimeout(() => { statusEl.style.display = 'none'; }, 3000);
    })
    .catch(() => {
        statusEl.className = 'save-status error';
        statusEl.textContent = '저장 중 오류가 발생했습니다.';
        statusEl.style.display = 'block';
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '저장';
    });
}
</script>

<?php include 'admin_tail.php'; ?>
