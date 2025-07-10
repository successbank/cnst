<?php
session_start();
require_once 'db.php';
require_once 'board/board_template.php';

// 게시판 타입과 ID 확인
$boardType = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!in_array($boardType, ['quote', 'notice', 'news', 'consignment']) || !$id) {
    header('Location: index.php');
    exit;
}

// 게시판 객체 생성
$board = new BoardTemplate($pdo, $boardType);

// 게시글 조회
$post = $board->getPost($id);
if (!$post) {
    header('Location: ' . $boardType . '.php');
    exit;
}

// POST 처리 (삭제)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($board->checkPassword($id, $_POST['password'])) {
        // 첨부파일이 있으면 삭제
        if ($post['attachment']) {
            $filePath = 'uploads/' . $boardType . '/' . $post['attachment'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // 게시글 삭제
        if ($board->deletePost($id, $_POST['password'])) {
            header('Location: ' . $boardType . '.php?deleted=1');
            exit;
        } else {
            $error = '게시글 삭제에 실패했습니다.';
        }
    } else {
        $error = '비밀번호가 일치하지 않습니다.';
    }
}

$currentPage = $boardType;
$pageTitle = $board->getBoardTitle() . ' 삭제';
$additionalCSS = ['css/board-style.css'];
include 'head.php';
?>

<style>
/* Board Delete Specific Styles */
.board-delete-section {
    background: #F8F9FA;
    padding: 60px 0;
    min-height: 600px;
}

.delete-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 20px;
}

.delete-form-wrapper {
    background: white;
    padding: 48px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.delete-header {
    text-align: center;
    margin-bottom: 40px;
}

.delete-header h3 {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
}

.delete-header p {
    font-size: 16px;
    color: #666;
    line-height: 1.6;
}

.post-info-box {
    background: #F8F9FA;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 32px;
}

.post-info-item {
    display: flex;
    margin-bottom: 12px;
}

.post-info-item:last-child {
    margin-bottom: 0;
}

.info-label {
    min-width: 80px;
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.info-value {
    font-size: 14px;
    color: #333;
    flex: 1;
}

.warning-box {
    background: #FFF0F0;
    border: 1px solid #FFD6D6;
    border-radius: 8px;
    padding: 16px 20px;
    margin-bottom: 32px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.warning-icon {
    color: #DC3545;
    font-size: 20px;
    line-height: 1;
}

.warning-text {
    font-size: 14px;
    color: #DC3545;
    line-height: 1.6;
}

.delete-form {
    margin-top: 32px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-group input {
    width: 100%;
    padding: 14px 20px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(20, 40, 160, 0.1);
}

.form-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 32px;
}

.delete-btn,
.cancel-btn {
    padding: 14px 40px;
    border-radius: 28px;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
}

.delete-btn {
    background: #DC3545;
    color: white;
}

.delete-btn:hover {
    background: #C82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.cancel-btn {
    background: white;
    color: #666;
    border: 2px solid #E5E5E7;
}

.cancel-btn:hover {
    border-color: #999;
    color: #333;
}

.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
}

.alert.error {
    background: #FFF0F0;
    color: #DC3545;
    border-left: 4px solid #DC3545;
}

/* Responsive */
@media (max-width: 768px) {
    .delete-form-wrapper {
        padding: 32px 24px;
    }
    
    .form-buttons {
        flex-direction: column;
    }
    
    .delete-btn,
    .cancel-btn {
        width: 100%;
    }
}
</style>

<div class="page-header">
    <h2><?php echo $board->getBoardTitle(); ?> 삭제</h2>
</div>

<section class="board-delete-section">
    <div class="delete-container">
        <div class="delete-form-wrapper">
            <div class="delete-header">
                <h3>게시글 삭제</h3>
                <p>삭제하시려는 게시글의 정보를 확인하고<br>비밀번호를 입력해주세요.</p>
            </div>
            
            <!-- 게시글 정보 표시 -->
            <div class="post-info-box">
                <div class="post-info-item">
                    <span class="info-label">제목</span>
                    <span class="info-value"><?php echo escape($post['title']); ?></span>
                </div>
                <div class="post-info-item">
                    <span class="info-label">작성자</span>
                    <span class="info-value"><?php echo escape($post['writer']); ?></span>
                </div>
                <div class="post-info-item">
                    <span class="info-label">작성일</span>
                    <span class="info-value"><?php echo formatDate($post['created_at'], 'Y-m-d H:i'); ?></span>
                </div>
                <?php if ($post['attachment']): ?>
                <div class="post-info-item">
                    <span class="info-label">첨부파일</span>
                    <span class="info-value"><?php echo escape($post['attachment']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 경고 메시지 -->
            <div class="warning-box">
                <span class="warning-icon">⚠️</span>
                <div class="warning-text">
                    삭제된 게시글은 복구할 수 없습니다.<br>
                    정말 삭제하시겠습니까?
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- 삭제 폼 -->
            <form method="post" class="delete-form">
                <div class="form-group">
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" placeholder="게시글 작성시 입력한 비밀번호" required autofocus>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" class="delete-btn" onclick="return confirm('정말 삭제하시겠습니까?');">삭제하기</button>
                    <a href="board_view.php?type=<?php echo $boardType; ?>&id=<?php echo $id; ?>" class="cancel-btn">취소</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include 'tail.php'; ?>