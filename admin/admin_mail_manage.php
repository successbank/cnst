<?php
$pageTitle = '메일 관리';
$additionalStyles = '
    .mail-stats { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); flex: 1; min-width: 150px; text-align: center; }
    .stat-card .stat-value { font-size: 28px; font-weight: 700; color: #1A237E; }
    .stat-card .stat-label { font-size: 13px; color: #666; margin-top: 4px; }
    .btn-add-mail { display: inline-block; padding: 10px 20px; background: #1A237E; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; }
    .btn-add-mail:hover { background: #283593; }
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; justify-content: center; align-items: center; }
    .modal-overlay.active { display: flex; }
    .modal-box { background: white; border-radius: 12px; padding: 32px; width: 90%; max-width: 500px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .modal-box h2 { font-size: 20px; margin-bottom: 24px; color: #333; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 14px; font-weight: 600; color: #333; margin-bottom: 6px; }
    .form-group input, .form-group select { width: 100%; padding: 10px 14px; border: 1px solid #E5E5E7; border-radius: 8px; font-size: 14px; }
    .form-group input:focus, .form-group select:focus { outline: none; border-color: #1A237E; box-shadow: 0 0 0 3px rgba(26,35,126,0.1); }
    .form-group .input-group { display: flex; align-items: center; }
    .form-group .input-group input { border-radius: 8px 0 0 8px; }
    .form-group .input-group span { padding: 10px 14px; background: #F5F5F7; border: 1px solid #E5E5E7; border-left: none; border-radius: 0 8px 8px 0; font-size: 14px; color: #666; white-space: nowrap; }
    .form-group .help-text { font-size: 12px; color: #999; margin-top: 4px; }
    .modal-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; }
    .modal-actions button { padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; border: none; }
    .btn-cancel { background: #F5F5F7; color: #666; }
    .btn-cancel:hover { background: #E5E5E7; }
    .btn-submit { background: #1A237E; color: white; }
    .btn-submit:hover { background: #283593; }
    .btn-submit:disabled { background: #ccc; cursor: not-allowed; }
    .mail-status-active { color: #2E7D32; background: #E8F5E9; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .mail-status-inactive { color: #C62828; background: #FFEBEE; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .usage-bar { height: 6px; background: #E5E5E7; border-radius: 3px; overflow: hidden; width: 80px; display: inline-block; vertical-align: middle; margin-right: 6px; }
    .usage-bar-fill { height: 100%; border-radius: 3px; }
    .usage-bar-fill.low { background: #4CAF50; }
    .usage-bar-fill.mid { background: #FF9800; }
    .usage-bar-fill.high { background: #F44336; }
    .loading-spinner { text-align: center; padding: 40px; color: #999; }
    .toast { position: fixed; top: 20px; right: 20px; padding: 14px 24px; border-radius: 8px; color: white; font-size: 14px; font-weight: 600; z-index: 10001; transform: translateX(120%); transition: transform 0.3s ease; }
    .toast.show { transform: translateX(0); }
    .toast.success { background: #2E7D32; }
    .toast.error { background: #C62828; }
    .mailcow-link { display: inline-block; padding: 10px 20px; background: #E8EAF6; color: #1A237E; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; margin-left: 12px; transition: all 0.3s ease; }
    .mailcow-link:hover { background: #C5CAE9; }
';
require_once 'admin_head.php';
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1>메일 관리</h1>
        <p>cnst.co.kr 도메인의 메일 계정을 관리합니다.</p>
    </div>
    <div>
        <button class="btn-add-mail" onclick="openAddModal()">+ 메일 계정 추가</button>
        <a href="https://mail.cnst.co.kr/admin/mailbox" target="_blank" class="mailcow-link">Mailcow 관리</a>
    </div>
</div>

<div class="mail-stats" id="mailStats">
    <div class="stat-card">
        <div class="stat-value" id="totalMailboxes">-</div>
        <div class="stat-label">전체 메일박스</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="activeMailboxes">-</div>
        <div class="stat-label">활성 계정</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" id="maxMailboxes">100</div>
        <div class="stat-label">최대 생성 가능</div>
    </div>
</div>

<div class="data-table" id="mailTable">
    <div class="loading-spinner" id="loadingSpinner">메일 계정 목록을 불러오는 중...</div>
    <div class="data-table-wrapper" id="tableWrapper" style="display: none;">
        <table>
            <thead>
                <tr>
                    <th>메일 주소</th>
                    <th>이름</th>
                    <th>용량</th>
                    <th>사용량</th>
                    <th>마지막 로그인</th>
                    <th>상태</th>
                    <th style="text-align:center;">관리</th>
                </tr>
            </thead>
            <tbody id="mailboxList"></tbody>
        </table>
    </div>
</div>

<!-- 메일 추가 모달 -->
<div class="modal-overlay" id="addModal">
    <div class="modal-box">
        <h2>메일 계정 추가</h2>
        <form id="addMailForm" onsubmit="return addMailbox(event)">
            <div class="form-group">
                <label>메일 주소</label>
                <div class="input-group">
                    <input type="text" id="localPart" name="local_part" placeholder="아이디" required pattern="[a-zA-Z0-9._-]+" autocomplete="off">
                    <span>@cnst.co.kr</span>
                </div>
                <div class="help-text">영문, 숫자, ., _, - 사용 가능</div>
            </div>
            <div class="form-group">
                <label>이름</label>
                <input type="text" id="mailName" name="name" placeholder="사용자 이름">
            </div>
            <div class="form-group">
                <label>비밀번호</label>
                <input type="password" id="mailPassword" name="password" placeholder="6자 이상" required minlength="6" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label>용량 (MB)</label>
                <select id="mailQuota" name="quota">
                    <option value="1024">1 GB</option>
                    <option value="3072" selected>3 GB</option>
                    <option value="5120">5 GB</option>
                    <option value="10240">10 GB</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAddModal()">취소</button>
                <button type="submit" class="btn-submit" id="btnSubmitAdd">추가</button>
            </div>
        </form>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <h2>메일 계정 삭제</h2>
        <p style="margin-bottom: 16px; color: #666;">다음 메일 계정을 삭제하시겠습니까?</p>
        <p style="font-size: 18px; font-weight: 700; color: #C62828; margin-bottom: 8px;" id="deleteTarget"></p>
        <p style="font-size: 13px; color: #999;">삭제하면 해당 메일함의 모든 메일이 영구 삭제됩니다.</p>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeDeleteModal()">취소</button>
            <button type="button" class="btn-submit" style="background: #C62828;" onclick="confirmDelete()" id="btnConfirmDelete">삭제</button>
        </div>
    </div>
</div>

<!-- 토스트 메시지 -->
<div class="toast" id="toast"></div>

<script>
let deleteUsername = '';

function showToast(message, type) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast ' + type + ' show';
    setTimeout(() => { toast.classList.remove('show'); }, 3000);
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function formatQuota(bytes) {
    return parseFloat((bytes / (1024 * 1024 * 1024)).toFixed(1)) + ' GB';
}

function formatDate(timestamp) {
    if (!timestamp || timestamp === 0) return '-';
    const d = new Date(timestamp * 1000);
    return d.getFullYear() + '-' +
           String(d.getMonth()+1).padStart(2,'0') + '-' +
           String(d.getDate()).padStart(2,'0') + ' ' +
           String(d.getHours()).padStart(2,'0') + ':' +
           String(d.getMinutes()).padStart(2,'0');
}

function loadMailboxes() {
    document.getElementById('loadingSpinner').style.display = '';
    document.getElementById('tableWrapper').style.display = 'none';

    fetch('ajax/mail_action.php?action=list')
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                showToast(res.message, 'error');
                document.getElementById('loadingSpinner').textContent = '오류: ' + res.message;
                return;
            }

            const data = res.data;
            const totalCount = data.length;
            const activeCount = data.filter(m => m.active === 1).length;

            document.getElementById('totalMailboxes').textContent = totalCount;
            document.getElementById('activeMailboxes').textContent = activeCount;

            const tbody = document.getElementById('mailboxList');
            tbody.innerHTML = '';

            data.forEach(m => {
                const percent = m.percent_in_use || 0;
                const barClass = percent < 50 ? 'low' : (percent < 80 ? 'mid' : 'high');
                const statusClass = m.active === 1 ? 'mail-status-active' : 'mail-status-inactive';
                const statusText = m.active === 1 ? '활성' : '비활성';

                const lastLogin = Math.max(m.last_imap_login || 0, m.last_smtp_login || 0, m.last_pop3_login || 0);

                const isAdmin = m.username === 'admin@cnst.co.kr';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${m.username}</strong></td>
                    <td>${m.name || '-'}</td>
                    <td>${formatQuota(m.quota)}</td>
                    <td>
                        <div class="usage-bar"><div class="usage-bar-fill ${barClass}" style="width:${percent}%"></div></div>
                        ${formatBytes(m.quota_used)} (${percent}%)
                    </td>
                    <td>${formatDate(lastLogin)}</td>
                    <td><span class="${statusClass}">${statusText}</span></td>
                    <td>
                        <div class="action-links">
                            ${!isAdmin ? `
                                <a href="#" class="btn-toggle" onclick="toggleActive('${m.username}', ${m.active === 1 ? 0 : 1}); return false;">
                                    ${m.active === 1 ? '비활성화' : '활성화'}
                                </a>
                                <a href="#" class="btn-delete" onclick="openDeleteModal('${m.username}'); return false;">삭제</a>
                            ` : '<span style="color:#999; font-size:12px;">관리자</span>'}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('tableWrapper').style.display = '';
        })
        .catch(err => {
            document.getElementById('loadingSpinner').textContent = '네트워크 오류가 발생했습니다.';
            console.error(err);
        });
}

function openAddModal() {
    document.getElementById('addModal').classList.add('active');
    document.getElementById('addMailForm').reset();
    document.getElementById('localPart').focus();
}

function closeAddModal() {
    document.getElementById('addModal').classList.remove('active');
}

function addMailbox(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitAdd');
    btn.disabled = true;
    btn.textContent = '추가 중...';

    const data = {
        local_part: document.getElementById('localPart').value.trim(),
        name: document.getElementById('mailName').value.trim(),
        password: document.getElementById('mailPassword').value,
        quota: parseInt(document.getElementById('mailQuota').value),
    };

    fetch('ajax/mail_action.php?action=add', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message, 'success');
            closeAddModal();
            loadMailboxes();
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(() => showToast('네트워크 오류가 발생했습니다.', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '추가';
    });

    return false;
}

function openDeleteModal(username) {
    deleteUsername = username;
    document.getElementById('deleteTarget').textContent = username;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deleteUsername = '';
}

function confirmDelete() {
    if (!deleteUsername) return;
    const btn = document.getElementById('btnConfirmDelete');
    btn.disabled = true;
    btn.textContent = '삭제 중...';

    fetch('ajax/mail_action.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({username: deleteUsername}),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message, 'success');
            closeDeleteModal();
            loadMailboxes();
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(() => showToast('네트워크 오류가 발생했습니다.', 'error'))
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '삭제';
    });
}

function toggleActive(username, active) {
    fetch('ajax/mail_action.php?action=toggle_active', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({username: username, active: active}),
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message, 'success');
            loadMailboxes();
        } else {
            showToast(res.message, 'error');
        }
    })
    .catch(() => showToast('네트워크 오류가 발생했습니다.', 'error'));
}

// 모달 외부 클릭 시 닫기
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});

// 페이지 로드 시 목록 조회
loadMailboxes();
</script>

<?php require_once 'admin_tail.php'; ?>
