<?php
require_once 'db.php';
require_once 'board/board_template.php';
require_once 'member_check.php';
require_once 'includes/BoardPermissionHelper.php';

// 게시판 타입과 ID 확인
$boardType = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!in_array($boardType, ['quote', 'notice', 'news', 'consignment', 'sales_request', 'brokerage']) || !$id) {
    header('Location: index.php');
    exit;
}

// 게시판 권한 체크
if (in_array($boardType, ['consignment', 'quote', 'sales_request', 'brokerage'])) {
    $permType = in_array($boardType, ['sales_request']) ? 'sales_request' : ($boardType === 'brokerage' ? 'brokerage' : $boardType);
    BoardPermissionHelper::requireAccess($permType, 'read');
}

// 게시판 객체 생성
$board = new BoardTemplate($pdo, $boardType);

// 테이블 이름 결정 (sales_request, brokerage는 board_consignment 사용)
$tableName = in_array($boardType, ['sales_request', 'brokerage']) ? 'board_consignment' : 'board_' . $boardType;

// 게시글 조회 (조회수 증가 전에 먼저 데이터 확인)
$checkSql = "SELECT * FROM {$tableName} WHERE id = :id";
$checkStmt = $pdo->prepare($checkSql);
$checkStmt->bindParam(':id', $id);
$checkStmt->execute();
$post = $checkStmt->fetch();

if (!$post) {
    header('Location: ' . $boardType . '.php');
    exit;
}

$needPassword = false;
$passwordError = false;
$canView = false;

// 판매의뢰 - 본인과 관리자만 볼 수 있음
if ($boardType === 'sales_request') {
    if (isAdmin()) {
        $canView = true;
    } elseif (isLoggedIn() && isset($post['member_id']) && $post['member_id'] == $_SESSION['member_id']) {
        $canView = true;
    } else {
        header('Location: sales_request.php');
        exit;
    }
}
// 중개판매 - 승인된 건만 공개
elseif ($boardType === 'brokerage') {
    if (isAdmin()) {
        $canView = true;
    } elseif ($post['status'] === 'approved') {
        $canView = true;
    } else {
        header('Location: brokerage.php');
        exit;
    }
}
// 중계판매 게시판의 경우 - 본인과 관리자만 볼 수 있음
elseif ($boardType === 'consignment') {
    // 관리자인 경우
    if (isAdmin()) {
        $canView = true;
    }
    // 로그인한 사용자이고 본인 글인 경우
    elseif (isLoggedIn() && isset($post['member_id']) && $post['member_id'] == $_SESSION['member_id']) {
        $canView = true;
    }
    // 비로그인 작성 글인 경우 - 비밀번호 확인
    elseif (empty($post['member_id']) && !empty($post['password'])) {
        if (isset($_POST['password'])) {
            // bcrypt 비밀번호 검증
            if (password_verify($_POST['password'], $post['password'])) {
                $canView = true;
            } else {
                $passwordError = true;
                $needPassword = true;
            }
        } else {
            $needPassword = true;
        }
    }
    // 그 외의 경우 접근 불가 - 비밀번호 입력 폼 대신 접근 권한 없음 표시
    else {
        $needPassword = true;
    }
}

// 견적문의 게시판의 경우
elseif ($boardType === 'quote') {
    // 관리자인 경우
    if (isAdmin()) {
        $canView = true;
    }
    // 로그인한 사용자이고 본인 글인 경우
    elseif (isLoggedIn() && isset($post['member_id']) && $post['member_id'] == $_SESSION['member_id']) {
        $canView = true;
    }
    // 비로그인 작성 글인 경우 - 비밀번호 확인
    elseif (empty($post['member_id']) && !empty($post['password'])) {
        if (isset($_POST['password'])) {
            if (!$board->checkPassword($id, $_POST['password'])) {
                $passwordError = true;
                $needPassword = true;
            } else {
                $canView = true;
            }
        } else {
            $needPassword = true;
        }
    }
    else {
        // 다른 사람의 글인 경우 접근 불가
        header('Location: my_inquiries.php');
        exit;
    }
}
// 다른 게시판은 모두 볼 수 있음
else {
    $canView = true;
}

// 볼 수 있는 권한이 있고 비밀번호 확인이 필요없는 경우에만 조회수 증가
if ($canView && !$needPassword) {
    $post = $board->getPost($id);
}

$currentPage = $boardType;
$pageTitle = $board->getBoardTitle() . ' - ' . $post['title'];
$additionalCSS = ['css/board-style.css'];
include 'head.php';
?>

<div class="page-header">
    <h2><?php echo $board->getBoardTitle(); ?></h2>
</div>

<section class="board-section">
    <div class="board-container">
        <?php if (isset($needPassword) && $needPassword): ?>
            <!-- 비밀번호 입력 폼 -->
            <div class="password-form-wrapper">
                <div class="password-form-container">
                    <h3>비밀번호 확인</h3>
                    <p>이 글은 비밀글입니다. 작성시 입력한 비밀번호를 입력해주세요.</p>
                    <?php if ($boardType !== 'consignment' && isset($passwordError) && $passwordError): ?>
                        <div class="alert error">비밀번호가 일치하지 않습니다.</div>
                    <?php endif; ?>
                    <form method="post" class="password-form">
                        <input type="password" name="password" placeholder="비밀번호를 입력하세요" required>
                        <div class="form-buttons">
                            <button type="submit" class="confirm-btn">확인</button>
                            <a href="<?php echo $boardType; ?>.php" class="cancel-btn">목록으로</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- 게시글 내용 -->
            <div class="post-container">
                <div class="post-header">
                    <h3><?php echo escape($post['title']); ?></h3>
                    <div class="post-info">
                        <div class="info-item">
                            <span class="label">작성자</span>
                            <span class="value"><?php echo escape($post['writer']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">작성일</span>
                            <span class="value"><?php echo formatDate($post['created_at'], 'Y-m-d H:i'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">조회수</span>
                            <span class="value"><?php echo $post['view_count']; ?></span>
                        </div>
                        <?php if ($boardType === 'news' && $post['source']): ?>
                        <div class="info-item">
                            <span class="label">출처</span>
                            <span class="value"><?php echo escape($post['source']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($boardType === 'quote'): ?>
                <div class="quote-info">
                    <h4>문의 정보</h4>
                    <div class="info-grid">
                        <?php if ($post['company']): ?>
                        <div class="info-row">
                            <span class="info-label">회사명</span>
                            <span class="info-value"><?php echo escape($post['company']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($post['email']): ?>
                        <div class="info-row">
                            <span class="info-label">이메일</span>
                            <span class="info-value"><?php echo escape($post['email']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($post['phone']): ?>
                        <div class="info-row">
                            <span class="info-label">연락처</span>
                            <span class="info-value"><?php echo escape($post['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (in_array($boardType, ['consignment', 'sales_request', 'brokerage'])): ?>
                <div class="consignment-info">
                    <h4><?php echo $boardType === 'sales_request' ? '판매의뢰 정보' : '중개판매 정보'; ?></h4>

                    <?php if ($boardType === 'sales_request' && $post['status'] === 'rejected' && !empty($post['reject_reason'])): ?>
                    <div style="background: #FFEBEE; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #C62828;">
                        <strong style="color: #C62828;">반려 사유:</strong>
                        <span style="color: #333;"><?php echo escape($post['reject_reason']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-grid">
                        <div class="info-row">
                            <span class="info-label">업체명</span>
                            <span class="info-value"><?php echo escape($post['company_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">카테고리</span>
                            <span class="info-value"><span class="category-badge"><?php echo escape($post['category']); ?></span></span>
                        </div>
                        <?php if ($post['stock_quantity']): ?>
                        <div class="info-row">
                            <span class="info-label">재고수량</span>
                            <span class="info-value"><?php echo escape($post['stock_quantity']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($post['price_info']): ?>
                        <div class="info-row">
                            <span class="info-label">가격정보</span>
                            <span class="info-value"><?php echo escape($post['price_info']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($post['location']): ?>
                        <div class="info-row">
                            <span class="info-label">보관위치</span>
                            <span class="info-value"><?php echo escape($post['location']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($post['contact_person'] || $post['contact_phone'] || $post['contact_email']): ?>
                    <h4 style="margin-top: 30px;">담당자 정보</h4>
                    <div class="info-grid">
                        <?php if ($post['contact_person']): ?>
                        <div class="info-row">
                            <span class="info-label">담당자명</span>
                            <span class="info-value"><?php echo escape($post['contact_person']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($post['contact_phone']): ?>
                        <div class="info-row">
                            <span class="info-label">연락처</span>
                            <span class="info-value"><?php echo escape($post['contact_phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($post['contact_email']): ?>
                        <div class="info-row">
                            <span class="info-label">이메일</span>
                            <span class="info-value"><?php echo escape($post['contact_email']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="post-content">
                    <?php 
                    // 공지사항과 뉴스는 HTML 허용 (이미지 등)
                    if ($boardType === 'notice' || $boardType === 'news') {
                        // 위험한 태그는 제거하고 안전한 HTML만 허용
                        $allowed_tags = '<p><br><strong><em><u><img><a><ul><ol><li><blockquote><h3><h4><h5><h6>';
                        $content = strip_tags($post['content'], $allowed_tags);
                        echo nl2br($content);
                    } else {
                        // 다른 게시판은 HTML 이스케이프
                        echo nl2br(escape($post['content']));
                    }
                    ?>
                </div>
                
                <?php if ($post['attachment']): ?>
                <div class="post-attachment">
                    <h5>첨부파일</h5>
                    <?php
                    // JSON 형식인지 확인
                    $attachments = json_decode($post['attachment'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($attachments)) {
                        // 다중 파일
                        foreach ($attachments as $attachment) {
                            ?>
                            <div style="margin-bottom: 10px;">
                                <a href="/download.php?type=<?php echo $boardType; ?>&file=<?php echo rawurlencode($attachment); ?>" class="attachment-link">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z" fill="currentColor"/>
                                    </svg>
                                    <?php echo htmlspecialchars($attachment); ?>
                                </a>
                            </div>
                            <?php
                        }
                    } else {
                        // 단일 파일 (기존 데이터와의 호환성)
                        ?>
                        <a href="/download.php?type=<?php echo $boardType; ?>&file=<?php echo rawurlencode($post['attachment']); ?>" class="attachment-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z" fill="currentColor"/>
                            </svg>
                            <?php echo htmlspecialchars($post['attachment']); ?>
                        </a>
                        <?php
                    }
                    ?>
                </div>
                <?php endif; ?>
                
                <!-- 답변 (견적문의) -->
                <?php if ($boardType === 'quote' && $post['is_answered'] && $post['admin_reply']): ?>
                <div class="answer-section">
                    <h4>답변</h4>
                    <div class="answer-content">
                        <?php echo nl2br(escape($post['admin_reply'])); ?>
                    </div>
                    <?php if ($post['replied_at']): ?>
                    <div class="answer-date">
                        답변일: <?php echo formatDate($post['replied_at'], 'Y-m-d H:i'); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php
                // 댓글 섹션 (중계판매, 견적문의만)
                if (in_array($boardType, ['consignment', 'quote', 'brokerage'])) {
                    $permType = ($boardType === 'brokerage') ? 'brokerage' : $boardType;
                    $canComment = BoardPermissionHelper::canAccess($permType, 'comment');
                    include 'includes/comment_section.php';
                }
                ?>

                <div class="post-buttons">
                    <a href="<?php echo in_array($boardType, ['sales_request', 'brokerage']) ? $boardType : $boardType; ?>.php" class="btn-list">목록</a>
                    <div class="right-buttons">
                        <a href="board_edit.php?type=<?php echo $boardType; ?>&id=<?php echo $id; ?>" class="btn-edit">수정</a>
                        <a href="board_delete.php?type=<?php echo $boardType; ?>&id=<?php echo $id; ?>" class="btn-delete" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Board View Specific Styles */
.post-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

/* Password Form */
.password-form-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.password-form-container {
    background: white;
    padding: 48px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
    max-width: 480px;
    width: 100%;
}

.password-form-container h3 {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
}

.password-form-container p {
    font-size: 16px;
    color: #666;
    margin-bottom: 32px;
}

.password-form input {
    width: 100%;
    padding: 14px 20px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 16px;
    margin-bottom: 24px;
    transition: all 0.3s ease;
}

.password-form input:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(20, 40, 160, 0.1);
}

.password-form .form-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.confirm-btn {
    padding: 12px 32px;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 28px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.confirm-btn:hover {
    background: #0F1F7A;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
}

/* Post Header */
.post-header {
    padding: 40px;
    border-bottom: 1px solid #E5E5E7;
}

.post-header h3 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
    line-height: 1.4;
}

.post-info {
    display: flex;
    gap: 32px;
    flex-wrap: wrap;
}

.info-item {
    display: flex;
    gap: 8px;
    align-items: center;
}

.info-item .label {
    font-size: 14px;
    color: #999;
}

.info-item .value {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

/* Quote Info */
.quote-info, .consignment-info {
    background: #F8F9FA;
    padding: 32px 40px;
    border-bottom: 1px solid #E5E5E7;
}

.quote-info h4, .consignment-info h4 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
}

.category-badge {
    display: inline-block;
    padding: 4px 12px;
    background: #e7f1ff;
    color: #1428A0;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 500;
}

.info-grid {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.info-row {
    display: flex;
    gap: 16px;
}

.info-label {
    min-width: 100px;
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.info-value {
    font-size: 14px;
    color: #333;
}

/* Post Content */
.post-content {
    padding: 40px;
    min-height: 300px;
    line-height: 1.8;
    font-size: 16px;
    color: #333;
    overflow: hidden; /* float 이미지 포함을 위한 clearfix */
}

/* 본문 내 이미지 스타일 */
.post-content img {
    max-width: 100%;
    height: auto !important;
    border-radius: 8px;
    margin: 8px 0;
}

/* float 이미지 정렬 */
.post-content img.note-float-left,
.post-content img[style*="float: left"] {
    margin-right: 20px;
    margin-bottom: 10px;
}

.post-content img.note-float-right,
.post-content img[style*="float: right"] {
    margin-left: 20px;
    margin-bottom: 10px;
}

/* Attachment */
.post-attachment {
    padding: 32px 40px;
    background: #F8F9FA;
    border-top: 1px solid #E5E5E7;
}

.post-attachment h5 {
    font-size: 16px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
}

.attachment-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: white;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
}

.attachment-link:hover {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

.attachment-link svg {
    width: 16px;
    height: 16px;
}

/* Answer Section */
.answer-section {
    background: #E8F0FE;
    padding: 32px 40px;
    margin: 0;
    border-top: 1px solid #E5E5E7;
}

.answer-section h4 {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 20px;
}

.answer-content {
    line-height: 1.8;
    color: #333;
    margin-bottom: 16px;
}

.answer-date {
    font-size: 13px;
    color: #666;
    text-align: right;
    border-top: 1px solid #d4e4ff;
    padding-top: 16px;
}

/* Post Buttons */
.post-buttons {
    padding: 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #E5E5E7;
}

.right-buttons {
    display: flex;
    gap: 12px;
}

.btn-list,
.btn-edit,
.btn-delete {
    display: inline-flex;
    align-items: center;
    padding: 12px 28px;
    border-radius: 28px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-list {
    background: white;
    color: #333;
    border: 2px solid #E5E5E7;
}

.btn-list:hover {
    border-color: #999;
    background: #F8F9FA;
}

.btn-edit {
    background: var(--primary-blue);
    color: white;
    border: 2px solid var(--primary-blue);
}

.btn-edit:hover {
    background: #0F1F7A;
    border-color: #0F1F7A;
}

.btn-delete {
    background: #DC3545;
    color: white;
    border: 2px solid #DC3545;
}

.btn-delete:hover {
    background: #C82333;
    border-color: #C82333;
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
    .post-header,
    .post-content,
    .post-attachment,
    .answer-section,
    .post-buttons {
        padding: 24px;
    }
    
    .quote-info {
        padding: 24px;
    }
    
    .post-info {
        flex-direction: column;
        gap: 12px;
    }
    
    .post-buttons {
        flex-direction: column;
        gap: 16px;
    }
    
    .right-buttons {
        width: 100%;
    }
    
    .btn-list,
    .btn-edit,
    .btn-delete {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include 'tail.php'; ?>