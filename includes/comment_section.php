<?php
/**
 * 댓글 섹션 UI 컴포넌트
 *
 * 필요 변수:
 *   $boardType  - 게시판 타입 (consignment, quote)
 *   $id         - 게시글 ID
 *   $canComment - 댓글 작성 가능 여부 (boolean)
 */
$isLoggedInUser = isLoggedIn();
$commentWriter = $isLoggedInUser ? htmlspecialchars($_SESSION['member_name'] ?? '') : '';
?>

<div class="comment-section" id="commentSection">
    <h4 class="comment-title">댓글 <span id="commentCount">0</span></h4>

    <!-- 댓글 목록 -->
    <div id="commentList" class="comment-list"></div>

    <!-- 댓글 작성 폼 -->
    <?php if ($canComment): ?>
    <div class="comment-form-wrap">
        <div class="comment-form-row">
            <div class="comment-form-fields">
                <input type="text" id="commentWriter" placeholder="작성자"
                       value="<?php echo $commentWriter; ?>"
                       <?php echo $isLoggedInUser ? 'readonly' : ''; ?>
                       class="comment-input-writer">
                <?php if (!$isLoggedInUser): ?>
                <input type="password" id="commentPassword" placeholder="비밀번호" class="comment-input-pw">
                <?php endif; ?>
            </div>
        </div>
        <div class="comment-form-content">
            <textarea id="commentContent" placeholder="댓글을 입력하세요" maxlength="1000" class="comment-textarea"></textarea>
            <button type="button" onclick="submitComment()" class="comment-submit-btn">등록</button>
        </div>
    </div>
    <?php else: ?>
    <div class="comment-login-notice">
        댓글을 작성하려면 <a href="/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">로그인</a>이 필요합니다.
    </div>
    <?php endif; ?>
</div>

<style>
.comment-section {
    margin-top: 0;
    padding: 32px 40px;
    border-top: 1px solid #E5E5E7;
}
.comment-title {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
}
.comment-title span {
    color: #1428A0;
}
.comment-list {
    margin-bottom: 24px;
}
.comment-item {
    padding: 16px 0;
    border-bottom: 1px solid #f0f0f0;
}
.comment-item:last-child {
    border-bottom: none;
}
.comment-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.comment-writer {
    font-weight: 600;
    font-size: 14px;
    color: #333;
}
.comment-admin-badge {
    display: inline-block;
    padding: 2px 8px;
    background: #1428A0;
    color: white;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}
.comment-date {
    font-size: 12px;
    color: #999;
    margin-left: auto;
}
.comment-body {
    font-size: 14px;
    color: #333;
    line-height: 1.6;
    white-space: pre-wrap;
    word-break: break-word;
}
.comment-actions {
    margin-top: 8px;
    text-align: right;
}
.comment-delete-btn {
    background: none;
    border: none;
    color: #999;
    font-size: 12px;
    cursor: pointer;
    padding: 4px 8px;
}
.comment-delete-btn:hover {
    color: #DC3545;
}
.comment-empty {
    text-align: center;
    padding: 32px;
    color: #999;
    font-size: 14px;
}
/* 작성 폼 */
.comment-form-wrap {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
}
.comment-form-row {
    margin-bottom: 12px;
}
.comment-form-fields {
    display: flex;
    gap: 8px;
}
.comment-input-writer,
.comment-input-pw {
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    width: 160px;
}
.comment-input-writer:read-only {
    background: #e9ecef;
    color: #666;
}
.comment-form-content {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}
.comment-textarea {
    flex: 1;
    padding: 12px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    min-height: 60px;
    max-height: 200px;
}
.comment-textarea:focus,
.comment-input-writer:focus,
.comment-input-pw:focus {
    outline: none;
    border-color: #1428A0;
}
.comment-submit-btn {
    padding: 12px 24px;
    background: #1428A0;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.2s;
}
.comment-submit-btn:hover {
    background: #0F1F7A;
}
.comment-login-notice {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 12px;
    color: #666;
    font-size: 14px;
}
.comment-login-notice a {
    color: #1428A0;
    font-weight: 600;
    text-decoration: none;
}
.comment-login-notice a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .comment-section { padding: 24px; }
    .comment-form-fields { flex-direction: column; }
    .comment-input-writer, .comment-input-pw { width: 100%; }
    .comment-form-content { flex-direction: column; }
    .comment-submit-btn { width: 100%; }
}
</style>

<script>
const COMMENT_BOARD_TYPE = '<?php echo htmlspecialchars($boardType); ?>';
const COMMENT_BOARD_ID = <?php echo (int)$id; ?>;

document.addEventListener('DOMContentLoaded', function() {
    loadComments();
});

function loadComments() {
    fetch('/ajax/board_comments.php?action=list&board_type=' + COMMENT_BOARD_TYPE + '&board_id=' + COMMENT_BOARD_ID)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderComments(data.comments);
            }
        })
        .catch(err => console.error('댓글 로드 오류:', err));
}

function renderComments(comments) {
    const list = document.getElementById('commentList');
    const countEl = document.getElementById('commentCount');
    countEl.textContent = comments.length;

    if (comments.length === 0) {
        list.innerHTML = '<div class="comment-empty">등록된 댓글이 없습니다.</div>';
        return;
    }

    list.innerHTML = comments.map(c => {
        const adminBadge = c.is_admin == 1 ? '<span class="comment-admin-badge">관리자</span>' : '';
        let deleteBtn = '';
        if (c.can_delete === true) {
            deleteBtn = '<button class="comment-delete-btn" onclick="deleteComment(' + c.id + ')">삭제</button>';
        } else if (c.can_delete === 'password_required') {
            deleteBtn = '<button class="comment-delete-btn" onclick="deleteCommentWithPassword(' + c.id + ')">삭제</button>';
        }

        return '<div class="comment-item" id="comment-' + c.id + '">' +
            '<div class="comment-header">' +
                '<span class="comment-writer">' + escapeHtml(c.writer) + '</span>' +
                adminBadge +
                '<span class="comment-date">' + c.created_at + '</span>' +
            '</div>' +
            '<div class="comment-body">' + escapeHtml(c.content) + '</div>' +
            (deleteBtn ? '<div class="comment-actions">' + deleteBtn + '</div>' : '') +
        '</div>';
    }).join('');
}

function submitComment() {
    const content = document.getElementById('commentContent').value.trim();
    const writer = document.getElementById('commentWriter').value.trim();
    const pwEl = document.getElementById('commentPassword');
    const password = pwEl ? pwEl.value : '';

    if (!writer) { alert('작성자명을 입력해주세요.'); return; }
    if (!content) { alert('댓글 내용을 입력해주세요.'); return; }

    fetch('/ajax/board_comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'create',
            board_type: COMMENT_BOARD_TYPE,
            board_id: COMMENT_BOARD_ID,
            content: content,
            writer: writer,
            password: password || null
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('commentContent').value = '';
            if (pwEl) pwEl.value = '';
            loadComments();
        } else {
            alert(data.message);
        }
    })
    .catch(() => alert('댓글 등록 중 오류가 발생했습니다.'));
}

function deleteComment(commentId) {
    if (!confirm('댓글을 삭제하시겠습니까?')) return;

    fetch('/ajax/board_comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'delete',
            comment_id: commentId
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadComments();
        } else {
            alert(data.message);
        }
    })
    .catch(() => alert('댓글 삭제 중 오류가 발생했습니다.'));
}

function deleteCommentWithPassword(commentId) {
    const password = prompt('댓글 작성 시 입력한 비밀번호를 입력해주세요.');
    if (password === null) return;

    fetch('/ajax/board_comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'delete',
            comment_id: commentId,
            password: password
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadComments();
        } else {
            alert(data.message);
        }
    })
    .catch(() => alert('댓글 삭제 중 오류가 발생했습니다.'));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
