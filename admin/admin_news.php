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

// base64 이미지를 파일로 변환하는 함수 (아래한글 복사-붙여넣기 대응)
function processBase64Images($content) {
    // base64 이미지 패턴 매칭
    $pattern = '/<img[^>]+src=["\']data:image\/(png|jpeg|jpg|gif|webp|bmp);base64,([^"\']+)["\'][^>]*>/i';

    return preg_replace_callback($pattern, function($matches) {
        $extension = strtolower($matches[1]);
        // jpeg로 통일
        if ($extension === 'jpg') {
            $extension = 'jpeg';
        }
        $base64Data = $matches[2];

        // base64 디코딩
        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            return $matches[0]; // 디코딩 실패 시 원본 유지
        }

        // 업로드 디렉토리 설정
        $uploadDir = __DIR__ . '/../uploads/news/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // 고유 파일명 생성
        $filename = 'news_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // 파일 저장
        if (file_put_contents($filepath, $imageData)) {
            chmod($filepath, 0644);
            return '<img src="/uploads/news/' . $filename . '">';
        }

        return $matches[0]; // 저장 실패 시 원본 유지
    }, $content);
}

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

    // base64 이미지를 파일로 변환 (아래한글 복사-붙여넣기 대응)
    $content = processBase64Images($content);

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
                        <textarea id="content" name="content"><?php echo $news ? htmlspecialchars($news['content']) : ''; ?></textarea>
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

<!-- jQuery + Summernote Lite CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>

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

// Summernote 에디터 초기화
$(document).ready(function() {
    // content textarea가 있을 때만 Summernote 초기화
    if ($('#content').length > 0) {
        $('#content').summernote({
            lang: 'ko-KR',
            height: 400,
            placeholder: '뉴스 내용을 입력하세요...',
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