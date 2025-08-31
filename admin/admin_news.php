<?php
$pageTitle = '철강뉴스 관리';

// 추가 스타일 정의
$additionalStyles = '
.news-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.news-table th,
.news-table td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid #F0F0F0;
}

.news-table th {
    font-weight: 600;
    color: #666;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #F8F9FA;
    border-bottom: 2px solid #E5E5E7;
}

.news-table td {
    font-size: 14px;
    color: #333;
}

.news-table tr:hover {
    background: #F8F9FA;
}

.news-table tr:last-child td {
    border-bottom: none;
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
.form-group input[type="url"],
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-group input[type="text"]:focus,
.form-group input[type="url"]:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #1A237E;
}

.form-group textarea {
    min-height: 200px;
    resize: vertical;
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

.filter-section {
    margin: 24px 0;
}

.filter-form {
    display: flex;
    gap: 12px;
    align-items: center;
}

.filter-form input[type="text"] {
    flex: 1;
    max-width: 400px;
    padding: 10px 16px;
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
}

.filter-form input[type="text"]:focus {
    outline: none;
    border-color: #1A237E;
}

.filter-form button {
    padding: 10px 24px;
    background: #1A237E;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-form button:hover {
    background: #283593;
}

.msg {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 500;
}

.msg.success {
    background: #E8F5E9;
    color: #2E7D32;
    border: 1px solid #C8E6C9;
}

.msg.error {
    background: #FFEBEE;
    color: #C62828;
    border: 1px solid #FFCDD2;
}

.pagination {
    display: flex;
    gap: 8px;
    margin-top: 24px;
    justify-content: center;
}

.page-link {
    padding: 8px 12px;
    background: white;
    border: 1px solid #E5E5E7;
    color: #666;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.page-link:hover {
    background: #F8F9FA;
    border-color: #D0D0D2;
}

.page-link.active {
    background: #1A237E;
    color: white;
    border-color: #1A237E;
}
';

// 헤더 출력 전에 필요한 처리를 위해 먼저 세션과 DB 연결
require_once 'admin_check.php';
require_once '../db.php';

// 액션 처리
$action = $_GET['action'] ?? 'list';

// 뉴스 삭제 처리 (헤더 출력 전에 처리)
if($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM board_news WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: admin_news.php?msg=deleted');
        exit;
    } catch(PDOException $e) {
        $error = "삭제 중 오류가 발생했습니다.";
    }
}

// 뉴스 작성/수정 처리 (헤더 출력 전에 처리)
if($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'write' || $action === 'edit')) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $source = $_POST['source'] ?? '';
    $source_url = $_POST['source_url'] ?? '';
    
    if($title && $content) {
        try {
            if($action === 'write') {
                $stmt = $pdo->prepare("INSERT INTO board_news (title, content, source, source_url, writer, password, created_at) VALUES (?, ?, ?, ?, 'admin', '', NOW())");
                $stmt->execute([$title, $content, $source, $source_url]);
                header('Location: admin_news.php?msg=created');
                exit;
            } else {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE board_news SET title = ?, content = ?, source = ?, source_url = ? WHERE id = ?");
                $stmt->execute([$title, $content, $source, $source_url, $id]);
                header('Location: admin_news.php?msg=updated');
                exit;
            }
        } catch(PDOException $e) {
            $error = "저장 중 오류가 발생했습니다: " . $e->getMessage();
        }
    } else {
        $error = "제목과 내용을 모두 입력해주세요.";
    }
}

// 이제 HTML 출력을 시작
require_once 'admin_head.php';

// 수정할 뉴스 가져오기
$news = null;
if($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM board_news WHERE id = ?");
        $stmt->execute([$id]);
        $news = $stmt->fetch();
        if(!$news) {
            header('Location: admin_news.php');
            exit;
        }
    } catch(PDOException $e) {
        $error = "뉴스를 불러올 수 없습니다.";
    }
}

// 뉴스 목록 가져오기
if($action === 'list') {
    try {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // 검색 조건
        $search = $_GET['search'] ?? '';
        $where = '';
        $params = [];
        
        if($search) {
            $where = "WHERE title LIKE ? OR content LIKE ? OR source LIKE ?";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam];
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_news $where");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT * FROM board_news $where ORDER BY id DESC LIMIT ? OFFSET ?");
        $paramIndex = 1;
        foreach($params as $param) {
            $stmt->bindValue($paramIndex++, $param);
        }
        $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $newsList = $stmt->fetchAll();
        
        $totalPages = ceil($total / $limit);
    } catch(PDOException $e) {
        $newsList = [];
        $totalPages = 0;
        $error = "목록을 불러올 수 없습니다: " . $e->getMessage();
    }
}
?>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg success">
                <?php
                switch($_GET['msg']) {
                    case 'created': echo "철강뉴스가 등록되었습니다."; break;
                    case 'updated': echo "철강뉴스가 수정되었습니다."; break;
                    case 'deleted': echo "철강뉴스가 삭제되었습니다."; break;
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
                    <h1>철강뉴스 관리</h1>
                    <p>철강 관련 뉴스를 관리합니다.</p>
                </div>
                <a href="?action=write" class="action-btn">뉴스 작성</a>
            </div>
            
            <div class="filter-section">
                <form method="GET" action="" class="filter-form">
                    <input type="text" name="search" placeholder="제목, 내용, 출처로 검색" 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <button type="submit">검색</button>
                    <?php if(isset($_GET['search'])): ?>
                        <a href="admin_news.php" style="padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">초기화</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="content-box">
                <table class="news-table">
                    <thead>
                        <tr>
                            <th width="60" style="text-align: center;">번호</th>
                            <th>제목</th>
                            <th width="150">출처</th>
                            <th width="120">작성일</th>
                            <th width="80" style="text-align: center;">조회수</th>
                            <th width="140" style="text-align: center;">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($newsList)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    등록된 뉴스가 없습니다.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($newsList as $news): ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo $news['id']; ?></td>
                                    <td>
                                        <a href="#" onclick="viewNews(<?php echo $news['id']; ?>); return false;" 
                                           style="color: #1A237E; text-decoration: none; font-weight: 500;">
                                            <?php echo htmlspecialchars($news['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($news['source'] ?? '-'); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($news['created_at'])); ?></td>
                                    <td style="text-align: center;"><?php echo $news['view_count'] ?? 0; ?></td>
                                    <td>
                                        <div class="action-links">
                                            <a href="?action=edit&id=<?php echo $news['id']; ?>" class="btn-edit">수정</a>
                                            <a href="?action=delete&id=<?php echo $news['id']; ?>" 
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
                        <?php
                        $queryString = http_build_query(array_merge($_GET, ['page' => '']));
                        for($i = 1; $i <= $totalPages; $i++):
                        ?>
                            <a href="?<?php echo $queryString . $i; ?>" 
                               class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        
        <?php elseif($action === 'write' || $action === 'edit'): ?>
            <div class="page-header">
                <h1><?php echo $action === 'write' ? '철강뉴스 작성' : '철강뉴스 수정'; ?></h1>
                <p><?php echo $action === 'write' ? '새로운 철강뉴스를 작성합니다.' : '철강뉴스를 수정합니다.'; ?></p>
            </div>
            
            <div class="content-box">
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php if($action === 'edit' && $news): ?>
                        <input type="hidden" name="id" value="<?php echo $news['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">제목</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo $news ? htmlspecialchars($news['title']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">내용</label>
                        <div class="editor-container">
                            <div class="editor-toolbar">
                                <button type="button" onclick="insertImageTag()" class="toolbar-btn" title="이미지 태그 삽입">
                                    <span>🖼️</span> 이미지 삽입
                                </button>
                            </div>
                            <textarea id="content" name="content" required><?php echo $news ? htmlspecialchars($news['content']) : ''; ?></textarea>
                            <div id="dropZone" class="drop-zone">
                                <p>이미지를 여기에 드래그 앤 드롭하거나 클릭하여 업로드</p>
                                <input type="file" id="imageInput" multiple accept="image/*" style="display: none;">
                            </div>
                            <div id="uploadedImages" class="uploaded-images"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="source">출처</label>
                        <input type="text" id="source" name="source" 
                               value="<?php echo $news ? htmlspecialchars($news['source']) : ''; ?>" 
                               placeholder="예: 철강신문">
                    </div>
                    
                    <div class="form-group">
                        <label for="source_url">출처 URL</label>
                        <input type="url" id="source_url" name="source_url" 
                               value="<?php echo $news && isset($news['source_url']) ? htmlspecialchars($news['source_url']) : ''; ?>" 
                               placeholder="https://example.com">
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <?php echo $action === 'write' ? '등록' : '수정'; ?>
                    </button>
                    <a href="admin_news.php" class="cancel-btn">취소</a>
                </form>
            </div>
        <?php endif; ?>

<!-- 뉴스 보기 모달 -->
<div id="newsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="modal-info">
                <p><strong>출처:</strong> <span id="modalSource"></span></p>
                <p><strong>URL:</strong> <a id="modalSourceUrl" href="#" target="_blank"></a></p>
                <p><strong>작성일:</strong> <span id="modalDate"></span></p>
                <p><strong>조회수:</strong> <span id="modalViews"></span></p>
            </div>
            <div class="modal-content-text" id="modalContent"></div>
        </div>
    </div>
</div>

<style>
/* 에디터 스타일 */
.editor-container {
    border: 2px solid #E5E5E7;
    border-radius: 8px;
    overflow: hidden;
}

.editor-toolbar {
    background: #F8F9FA;
    padding: 8px;
    border-bottom: 1px solid #E5E5E7;
}

.toolbar-btn {
    padding: 6px 12px;
    background: white;
    border: 1px solid #D0D0D2;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

.toolbar-btn:hover {
    background: #E8EAF6;
    border-color: #1A237E;
}

#content {
    border: none;
    min-height: 300px;
    padding: 16px;
    font-family: inherit;
}

.drop-zone {
    border: 2px dashed #D0D0D2;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    margin: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #F8F9FA;
}

.drop-zone:hover {
    border-color: #1A237E;
    background: #E8EAF6;
}

.drop-zone.dragover {
    border-color: #1A237E;
    background: #E8EAF6;
    transform: scale(1.02);
}

.drop-zone p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.uploaded-images {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
    padding: 16px;
}

.uploaded-image {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.uploaded-image img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.uploaded-image .image-url {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
}

.uploaded-image .remove-btn {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(244, 67, 54, 0.9);
    color: white;
    border: none;
    border-radius: 4px;
    padding: 4px 8px;
    cursor: pointer;
    font-size: 12px;
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

/* 제목 링크 호버 효과 */
.news-table td a:hover {
    text-decoration: underline !important;
}
</style>

<script>
function viewNews(id) {
    // AJAX로 뉴스 상세 정보 가져오기
    fetch(`ajax/get_news.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = data.news.title;
                document.getElementById('modalSource').textContent = data.news.source || '-';
                
                const sourceUrl = document.getElementById('modalSourceUrl');
                if (data.news.source_url) {
                    sourceUrl.href = data.news.source_url;
                    sourceUrl.textContent = data.news.source_url;
                    sourceUrl.style.display = 'inline';
                } else {
                    sourceUrl.style.display = 'none';
                }
                
                document.getElementById('modalDate').textContent = data.news.created_at;
                document.getElementById('modalViews').textContent = data.news.view_count || 0;
                document.getElementById('modalContent').innerHTML = data.news.content;
                
                document.getElementById('newsModal').style.display = 'flex';
            } else {
                alert('뉴스를 불러올 수 없습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('오류가 발생했습니다.');
        });
}

function closeModal() {
    document.getElementById('newsModal').style.display = 'none';
}

// 모달 외부 클릭시 닫기
window.onclick = function(event) {
    const modal = document.getElementById('newsModal');
    if (event.target == modal) {
        closeModal();
    }
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// 이미지 업로드 기능
const dropZone = document.getElementById('dropZone');
const imageInput = document.getElementById('imageInput');
const uploadedImages = document.getElementById('uploadedImages');

if (dropZone && imageInput) {
    // 드롭존 클릭시 파일 선택
    dropZone.addEventListener('click', () => {
        imageInput.click();
    });

    // 파일 선택시
    imageInput.addEventListener('change', handleFiles);

    // 드래그 앤 드롭 이벤트
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        handleFiles({ target: { files: e.dataTransfer.files } });
    });
}

function handleFiles(e) {
    const files = Array.from(e.target.files);
    files.forEach(uploadFile);
}

function uploadFile(file) {
    if (!file.type.startsWith('image/')) {
        alert('이미지 파일만 업로드 가능합니다.');
        return;
    }

    const formData = new FormData();
    formData.append('image', file);

    fetch('ajax/upload_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayUploadedImage(data.url, data.filename);
        } else {
            alert(data.message || '업로드 실패');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('업로드 중 오류가 발생했습니다.');
    });
}

function displayUploadedImage(url, filename) {
    const imageDiv = document.createElement('div');
    imageDiv.className = 'uploaded-image';
    imageDiv.innerHTML = `
        <img src="${url}" alt="${filename}">
        <div class="image-url" onclick="copyToClipboard('${url}')" title="클릭하여 URL 복사">${filename}</div>
        <button class="remove-btn" onclick="removeImage(this, '${filename}')">삭제</button>
    `;
    uploadedImages.appendChild(imageDiv);
}

function removeImage(btn, filename) {
    if (confirm('이미지를 삭제하시겠습니까?')) {
        fetch('ajax/delete_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ filename: filename })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                btn.parentElement.remove();
            }
        });
    }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('URL이 클립보드에 복사되었습니다.');
    });
}

function insertImageTag() {
    const url = prompt('이미지 URL을 입력하세요:');
    if (url) {
        const textarea = document.getElementById('content');
        const imageTag = `\n<img src="${url}" alt="뉴스 이미지" style="max-width: 100%; height: auto;">\n`;
        
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        
        textarea.value = text.substring(0, start) + imageTag + text.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + imageTag.length;
        textarea.focus();
    }
}
</script>

<?php require_once 'admin_tail.php'; ?>