<?php
// 세션 체크 먼저
require_once 'admin_check.php';
require_once '../db.php';

// 액션 처리
$action = $_GET['action'] ?? 'list';

// 폼 제출 처리 (헤더 전송 전에 처리)
if($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'write' || $action === 'edit')) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $is_important = isset($_POST['is_important']) ? 1 : 0;
    
    if($title && $content) {
        try {
            if($action === 'write') {
                $stmt = $pdo->prepare("INSERT INTO board_notice (title, content, is_important, writer, password, created_at) VALUES (?, ?, ?, 'admin', '', NOW())");
                $stmt->execute([$title, $content, $is_important]);
                header('Location: admin_notices.php?msg=created');
                exit;
            } else {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE board_notice SET title = ?, content = ?, is_important = ? WHERE id = ?");
                $stmt->execute([$title, $content, $is_important, $id]);
                header('Location: admin_notices.php?msg=updated');
                exit;
            }
        } catch(PDOException $e) {
            $error = "저장 중 오류가 발생했습니다: " . $e->getMessage();
        }
    } else {
        $error = "제목과 내용을 모두 입력해주세요.";
    }
}

// 공지사항 삭제 처리
if($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM board_notice WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: admin_notices.php?msg=deleted');
        exit;
    } catch(PDOException $e) {
        $error = "삭제 중 오류가 발생했습니다.";
    }
}

$pageTitle = '공지사항 관리';

// 추가 스타일 정의
$additionalStyles = '
.notice-table {
    width: 100%;
    border-collapse: collapse;
}

.notice-table th,
.notice-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #E5E5E7;
}

.notice-table th {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.notice-table tr:hover {
    background: #F5F5F7;
}

.important-badge {
    display: inline-block;
    padding: 4px 8px;
    background: #FF5252;
    color: white;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}


.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input[type="text"],
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-group input[type="text"]:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #1A237E;
}

.form-group textarea {
    min-height: 400px;
    resize: vertical;
    line-height: 1.6;
    white-space: pre-wrap;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.submit-btn {
    padding: 12px 32px;
    background: #1A237E;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.submit-btn:hover {
    background: #283593;
    transform: translateY(-1px);
}

.cancel-btn {
    margin-left: 12px;
    padding: 12px 32px;
    background: #E5E5E7;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    display: inline-block;
    transition: all 0.3s ease;
}

.cancel-btn:hover {
    background: #D0D0D2;
}

.content-box {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.action-btn {
    padding: 12px 24px;
    background: #1A237E;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.action-btn:hover {
    background: #283593;
    transform: translateY(-1px);
}

/* Summernote 커스텀 스타일 */
.note-editor.note-frame {
    border: 2px solid #E5E5E7 !important;
    border-radius: 8px !important;
    overflow: hidden;
}

.note-editor .note-toolbar {
    background: #F8F9FA !important;
    border-bottom: 1px solid #E5E5E7 !important;
    padding: 8px !important;
}

.note-editor .note-editing-area {
    background: white;
}

.note-editor .note-editing-area .note-editable {
    padding: 16px !important;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    min-height: 350px;
}

.note-editor .note-statusbar {
    background: #F8F9FA !important;
    border-top: 1px solid #E5E5E7 !important;
}

.note-btn {
    border-radius: 4px !important;
}

.note-btn:hover {
    background: #E8EAF6 !important;
}

/* 코드뷰 스타일 */
.note-editor .note-codable {
    background: #1e1e1e !important;
    color: #d4d4d4 !important;
    font-family: "Consolas", "Monaco", "Courier New", monospace !important;
    font-size: 14px !important;
    padding: 16px !important;
}

/* 에디터 내 이미지 스타일 */
.note-editable img {
    max-width: 100%;
    height: auto;
    margin: 16px 0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* 내용 보기 시 이미지 스타일 */
.notice-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 16px 0;
    display: block;
}

/* 모달 스타일 */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #fefefe;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px 30px;
    border-bottom: 1px solid #E5E5E7;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #F8F9FA;
    border-radius: 12px 12px 0 0;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #333;
}

.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 20px;
}

.close:hover,
.close:focus {
    color: #000;
}

.modal-body {
    padding: 30px;
}

.modal-info {
    background: #F8F9FA;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.modal-info p {
    margin: 8px 0;
    font-size: 14px;
    color: #666;
}

.modal-info strong {
    color: #333;
    margin-right: 8px;
}

.modal-content-text {
    font-size: 16px;
    line-height: 1.8;
    color: #333;
    white-space: pre-wrap;
}

.modal-content-text img {
    max-width: 100%;
    height: auto;
    margin: 16px 0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* 제목 링크 스타일 */
.notice-link {
    color: #1A237E;
    text-decoration: none;
    font-weight: 500;
    cursor: pointer;
}

.notice-link:hover {
    text-decoration: underline;
}
';

require_once 'admin_head.php';

// 수정할 공지사항 가져오기
$notice = null;
if($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM board_notice WHERE id = ?");
        $stmt->execute([$id]);
        $notice = $stmt->fetch();
        if(!$notice) {
            header('Location: admin_notices.php');
            exit;
        }
    } catch(PDOException $e) {
        $error = "공지사항을 불러올 수 없습니다.";
    }
}

// 공지사항 목록 가져오기
if($action === 'list') {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM board_notice");
        $total = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT * FROM board_notice ORDER BY is_important DESC, id DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $notices = $stmt->fetchAll();
        
        $totalPages = ceil($total / $limit);
    } catch(PDOException $e) {
        $notices = [];
        $totalPages = 0;
        $error = "목록을 불러올 수 없습니다: " . $e->getMessage();
    }
}
?>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg success">
                <?php
                switch($_GET['msg']) {
                    case 'created': echo "공지사항이 등록되었습니다."; break;
                    case 'updated': echo "공지사항이 수정되었습니다."; break;
                    case 'deleted': echo "공지사항이 삭제되었습니다."; break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($action === 'list'): ?>
            <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>공지사항 관리</h1>
                    <p>사이트 공지사항을 관리합니다.</p>
                </div>
                <a href="?action=write" class="action-btn">공지사항 작성</a>
            </div>
            
            <div class="content-box">
                <table class="notice-table">
                    <thead>
                        <tr>
                            <th width="60">번호</th>
                            <th>제목</th>
                            <th width="100">중요도</th>
                            <th width="120">작성일</th>
                            <th width="120">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($notices)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px;">
                                    등록된 공지사항이 없습니다.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($notices as $notice): ?>
                                <tr>
                                    <td><?php echo $notice['id']; ?></td>
                                    <td>
                                        <a href="#" onclick="viewNotice(<?php echo $notice['id']; ?>); return false;" class="notice-link">
                                            <?php echo htmlspecialchars($notice['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if($notice['is_important']): ?>
                                            <span class="important-badge">중요</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($notice['created_at'])); ?></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="?action=edit&id=<?php echo $notice['id']; ?>" class="btn-edit">수정</a>
                                            <a href="?action=delete&id=<?php echo $notice['id']; ?>" 
                                               class="btn-delete" 
                                               onclick="return confirm('정말 삭제하시겠습니까?')">삭제</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        
        <?php elseif($action === 'write' || $action === 'edit'): ?>
            <div class="page-header">
                <h1><?php echo $action === 'write' ? '공지사항 작성' : '공지사항 수정'; ?></h1>
                <p><?php echo $action === 'write' ? '새로운 공지사항을 작성합니다.' : '공지사항을 수정합니다.'; ?></p>
            </div>
            
            <div class="content-box">
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php if($action === 'edit' && $notice): ?>
                        <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="title">제목</label>
                        <input type="text" id="title" name="title"
                               value="<?php echo $notice ? htmlspecialchars($notice['title']) : ''; ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="content">내용</label>
                        <textarea id="content" name="content"><?php echo $notice ? htmlspecialchars($notice['content']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_important" name="is_important" 
                                   <?php echo ($notice && $notice['is_important']) ? 'checked' : ''; ?>>
                            <label for="is_important">중요 공지사항으로 설정</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <?php echo $action === 'write' ? '등록' : '수정'; ?>
                    </button>
                    <a href="admin_notices.php" class="cancel-btn">취소</a>
                </form>
            </div>
        <?php endif; ?>

<!-- 공지사항 보기 모달 -->
<div id="noticeModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="modal-info">
                <p><strong>작성자:</strong> <span id="modalWriter"></span></p>
                <p><strong>작성일:</strong> <span id="modalDate"></span></p>
                <p><strong>중요도:</strong> <span id="modalImportant"></span></p>
            </div>
            <div class="modal-content-text" id="modalContent"></div>
        </div>
    </div>
</div>

<!-- jQuery + Summernote Lite CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>

<script>
// 공지사항 보기 함수
function viewNotice(id) {
    // AJAX로 공지사항 상세 정보 가져오기
    fetch(`ajax/get_notice.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = data.notice.title;
                document.getElementById('modalWriter').textContent = data.notice.writer || 'admin';
                document.getElementById('modalDate').textContent = data.notice.created_at;
                document.getElementById('modalImportant').textContent = data.notice.is_important == 1 ? '중요' : '일반';
                document.getElementById('modalImportant').style.color = data.notice.is_important == 1 ? '#FF5252' : '#666';
                document.getElementById('modalContent').innerHTML = data.notice.content;
                
                document.getElementById('noticeModal').style.display = 'flex';
            } else {
                alert('공지사항을 불러올 수 없습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('오류가 발생했습니다.');
        });
}

// 모달 닫기
function closeModal() {
    document.getElementById('noticeModal').style.display = 'none';
}

// 모달 외부 클릭시 닫기
window.onclick = function(event) {
    const modal = document.getElementById('noticeModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// Summernote 에디터 초기화
$(document).ready(function() {
    // content textarea가 있을 때만 Summernote 초기화
    if ($('#content').length > 0) {
        $('#content').summernote({
            lang: 'ko-KR',
            height: 400,
            placeholder: '공지사항 내용을 입력하세요...',
            tabsize: 2,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video', 'hr']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            fontNames: ['맑은 고딕', '굴림', '돋움', '바탕', 'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Helvetica', 'Impact', 'Tahoma', 'Times New Roman', 'Verdana'],
            fontNamesIgnoreCheck: ['맑은 고딕', '굴림', '돋움', '바탕'],
            callbacks: {
                onImageUpload: function(files) {
                    // 이미지 업로드 처리
                    for (let i = 0; i < files.length; i++) {
                        uploadImage(files[i], this);
                    }
                }
            }
        });
    }
});

// 이미지 업로드 함수 (Summernote 콜백용)
function uploadImage(file, editor) {
    const formData = new FormData();
    formData.append('image', file);

    $.ajax({
        url: 'ajax/upload_image.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // 에디터에 이미지 삽입
                const imgNode = $('<img>').attr('src', response.url).css({
                    'max-width': '100%',
                    'height': 'auto'
                });
                $(editor).summernote('insertNode', imgNode[0]);
            } else {
                alert(response.message || '이미지 업로드 실패');
            }
        },
        error: function(xhr, status, error) {
            console.error('Upload error:', error);
            alert('이미지 업로드 중 오류가 발생했습니다.');
        }
    });
}
</script>

<?php require_once 'admin_tail.php'; ?>