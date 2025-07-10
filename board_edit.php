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

// 비밀번호 확인
$authorized = false;
if (isset($_POST['check_password'])) {
    if ($board->checkPassword($id, $_POST['password'])) {
        $authorized = true;
        $_SESSION['board_edit_' . $id] = true;
    } else {
        $passwordError = '비밀번호가 일치하지 않습니다.';
    }
}

// 세션에서 인증 확인
if (isset($_SESSION['board_edit_' . $id]) && $_SESSION['board_edit_' . $id] === true) {
    $authorized = true;
}

// POST 처리 (수정)
if ($authorized && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // 출력 버퍼링 시작
    ob_start();
    
    $data = [
        'title' => $_POST['title'],
        'content' => $_POST['content'],
        'writer' => $_POST['writer']
    ];
    
    // 파일 업로드 처리
    if ($board->allowsUpload() && !empty($_FILES['attachment']['name'])) {
        $uploadDir = 'uploads/' . $boardType . '/';
        $uploadedFile = @uploadFile($_FILES['attachment'], $uploadDir);
        if ($uploadedFile) {
            // 기존 파일 삭제
            if ($post['attachment'] && file_exists($uploadDir . $post['attachment'])) {
                @unlink($uploadDir . $post['attachment']);
            }
            $data['attachment'] = $uploadedFile;
        } else {
            $error = '파일 업로드에 실패했습니다. 파일 크기나 형식을 확인해주세요.';
        }
    }
    
    // 게시판별 추가 필드
    if ($boardType === 'quote') {
        $data['company'] = $_POST['company'] ?? '';
        $data['email'] = $_POST['email'] ?? '';
        $data['phone'] = $_POST['phone'] ?? '';
    } elseif ($boardType === 'news') {
        $data['source'] = $_POST['source'] ?? '';
    } elseif ($boardType === 'consignment') {
        $data['company_name'] = $_POST['company_name'] ?? '';
        $data['category'] = $_POST['category'] ?? '';
        $data['stock_quantity'] = $_POST['stock_quantity'] ?? '';
        $data['price_info'] = $_POST['price_info'] ?? '';
        $data['contact_person'] = $_POST['contact_person'] ?? '';
        $data['contact_phone'] = $_POST['contact_phone'] ?? '';
        $data['contact_email'] = $_POST['contact_email'] ?? '';
        $data['location'] = $_POST['location'] ?? '';
    }
    
    // 게시글 수정
    if (!isset($error) && $board->updatePost($id, $data)) {
        unset($_SESSION['board_edit_' . $id]);
        ob_end_clean();
        header('Location: board_view.php?type=' . $boardType . '&id=' . $id);
        exit;
    } else {
        ob_end_flush();
        if (!isset($error)) {
            $error = '게시글 수정에 실패했습니다.';
        }
    }
}

$currentPage = $boardType;
$pageTitle = $board->getBoardTitle() . ' 수정';
$additionalCSS = ['css/board-style.css'];
include 'head.php';
?>

<style>
/* Board Edit Specific Styles */
.board-edit-section {
    background: #F8F9FA;
    padding: 40px 0;
    min-height: 600px;
}

.edit-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.password-check-form {
    background: white;
    padding: 48px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
}

.password-check-form h3 {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
}

.password-check-form p {
    font-size: 16px;
    color: #666;
    margin-bottom: 32px;
}

.password-input-group {
    margin-bottom: 24px;
}

.password-input-group input {
    width: 100%;
    max-width: 400px;
    padding: 14px 20px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.3s ease;
}

.password-input-group input:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(20, 40, 160, 0.1);
}

.edit-form {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
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

.required {
    color: #FF6900;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 16px;
    font-family: inherit;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(20, 40, 160, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 300px;
}

.form-group small {
    display: block;
    margin-top: 6px;
    font-size: 13px;
    color: #666;
}

.form-group input[type="file"] {
    padding: 10px;
    background: #F8F9FA;
}

.current-file {
    margin-top: 8px;
    padding: 12px;
    background: #F8F9FA;
    border-radius: 8px;
    font-size: 14px;
    color: #666;
}

.current-file strong {
    color: #333;
}

.form-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 32px;
    padding-top: 32px;
    border-top: 1px solid #E5E5E7;
}

.update-btn,
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
}

.update-btn {
    background: var(--primary-blue);
    color: white;
    border: none;
}

.update-btn:hover {
    background: #0F1F7A;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
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
    .edit-form {
        padding: 30px 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-buttons {
        flex-direction: column;
    }
    
    .update-btn,
    .cancel-btn {
        width: 100%;
    }
}
</style>

<div class="page-header">
    <h2><?php echo $board->getBoardTitle(); ?> 수정</h2>
</div>

<section class="board-edit-section">
    <div class="edit-container">
        <?php if (!$authorized): ?>
            <!-- 비밀번호 확인 폼 -->
            <form method="post" class="password-check-form">
                <h3>비밀번호 확인</h3>
                <p>게시글을 수정하려면 작성시 입력한 비밀번호를 입력해주세요.</p>
                
                <?php if (isset($passwordError)): ?>
                    <div class="alert error"><?php echo $passwordError; ?></div>
                <?php endif; ?>
                
                <div class="password-input-group">
                    <input type="password" name="password" placeholder="비밀번호를 입력하세요" required autofocus>
                </div>
                
                <div class="form-buttons">
                    <button type="submit" name="check_password" value="1" class="update-btn">확인</button>
                    <a href="board_view.php?type=<?php echo $boardType; ?>&id=<?php echo $id; ?>" class="cancel-btn">취소</a>
                </div>
            </form>
        <?php else: ?>
            <!-- 수정 폼 -->
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" class="edit-form">
                <input type="hidden" name="update" value="1">
                
                <div class="form-group">
                    <label for="title">제목 <span class="required">*</span></label>
                    <input type="text" id="title" name="title" value="<?php echo escape($post['title']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="writer">작성자 <span class="required">*</span></label>
                        <input type="text" id="writer" name="writer" value="<?php echo escape($post['writer']); ?>" required>
                    </div>
                    
                    <?php if ($boardType === 'quote'): ?>
                    <div class="form-group">
                        <label for="company">회사명</label>
                        <input type="text" id="company" name="company" value="<?php echo escape($post['company'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($boardType === 'quote'): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">이메일</label>
                        <input type="email" id="email" name="email" value="<?php echo escape($post['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">연락처</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo escape($post['phone'] ?? ''); ?>">
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($boardType === 'news'): ?>
                <div class="form-group">
                    <label for="source">출처</label>
                    <input type="text" id="source" name="source" value="<?php echo escape($post['source'] ?? ''); ?>" placeholder="예: 한국철강신문">
                </div>
                <?php endif; ?>
                
                <?php if ($boardType === 'consignment'): ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_name">업체명 <span class="required">*</span></label>
                        <input type="text" id="company_name" name="company_name" value="<?php echo escape($post['company_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">카테고리 <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value="">선택하세요</option>
                            <?php 
                            $categories = [
                                '철근' => '철근(특판)',
                                'H형강' => 'H형강(H빔)',
                                '철강' => '철강(강판)',
                                '메탈라스' => '메탈라스(망철판)',
                                '경량H형강' => '경량H형강',
                                'I형강' => 'I형강(빔)',
                                'ㄱ형강' => 'ㄱ형강(앵글)',
                                'ㄷ형강' => 'ㄷ형강(찬넬)',
                                '환봉' => '환봉(원형강)',
                                '평철' => '평철',
                                'C형강' => 'C형강',
                                '테크플레이트' => '테크플레이트',
                                '사각파이프' => '사각파이프(각관)',
                                '원형파이프' => '원형파이프(강관)',
                                '레일' => '레일',
                                '강널말뚝' => '강널말뚝(쉬트파일)',
                                '스테인레스' => '스테인레스(STS)',
                                '기타' => '기타'
                            ];
                            foreach ($categories as $key => $value): 
                            ?>
                                <option value="<?php echo $key; ?>" <?php echo ($post['category'] ?? '') == $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="stock_quantity">재고수량</label>
                        <input type="text" id="stock_quantity" name="stock_quantity" value="<?php echo escape($post['stock_quantity'] ?? ''); ?>" placeholder="예: 100톤, 50장">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">보관위치</label>
                        <input type="text" id="location" name="location" value="<?php echo escape($post['location'] ?? ''); ?>" placeholder="예: 충남 천안시">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="price_info">가격정보</label>
                    <input type="text" id="price_info" name="price_info" value="<?php echo escape($post['price_info'] ?? ''); ?>" placeholder="예: 시세 대비 5% 할인, 협의 가능">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_person">담당자명</label>
                        <input type="text" id="contact_person" name="contact_person" value="<?php echo escape($post['contact_person'] ?? ''); ?>" placeholder="담당자 이름">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_phone">담당자 연락처</label>
                        <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo escape($post['contact_phone'] ?? ''); ?>" placeholder="010-0000-0000">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="contact_email">담당자 이메일</label>
                    <input type="email" id="contact_email" name="contact_email" value="<?php echo escape($post['contact_email'] ?? ''); ?>" placeholder="example@email.com">
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="content">내용 <span class="required">*</span></label>
                    <textarea id="content" name="content" required><?php echo escape($post['content']); ?></textarea>
                </div>
                
                <?php if ($board->allowsUpload()): ?>
                <div class="form-group">
                    <label for="attachment">첨부파일</label>
                    <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                    <small>최대 10MB, 허용 확장자: jpg, png, pdf, doc, docx, xls, xlsx</small>
                    <?php if ($post['attachment']): ?>
                    <div class="current-file">
                        <strong>현재 파일:</strong> <?php echo escape($post['attachment']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="form-buttons">
                    <button type="submit" class="update-btn">수정하기</button>
                    <a href="board_view.php?type=<?php echo $boardType; ?>&id=<?php echo $id; ?>" class="cancel-btn">취소</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<script>
// 전화번호 포맷팅
function formatPhoneNumber(e) {
    let value = e.target.value.replace(/[^\d]/g, '');
    let formatted = '';
    
    if (value.length <= 3) {
        formatted = value;
    } else if (value.length <= 7) {
        formatted = value.slice(0, 3) + '-' + value.slice(3);
    } else if (value.length <= 11) {
        formatted = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7);
    }
    
    e.target.value = formatted;
}

document.getElementById('phone')?.addEventListener('input', formatPhoneNumber);
document.getElementById('contact_phone')?.addEventListener('input', formatPhoneNumber);
</script>

<?php include 'tail.php'; ?>