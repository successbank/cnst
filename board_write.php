<?php
require_once 'db.php';
require_once 'board/board_template.php';
require_once 'member_check.php';
require_once 'includes/input_validator.php';
require_once 'includes/BoardPermissionHelper.php';
require_once 'includes/csrf.php';

// 게시판 타입 확인
$boardType = isset($_GET['type']) ? $_GET['type'] : '';
if (!in_array($boardType, ['quote', 'notice', 'news', 'consignment', 'sales_request'])) {
    header('Location: index.php');
    exit;
}

// 게시판 쓰기 권한 체크
if (in_array($boardType, ['consignment', 'quote', 'sales_request'])) {
    $permType = ($boardType === 'sales_request') ? 'sales_request' : $boardType;
    BoardPermissionHelper::requireAccess($permType, 'write');
}

// 게시판 객체 생성
$board = new BoardTemplate($pdo, $boardType);

// 회원 연락처/이메일 (폼 prefill용) - getMemberInfo()에는 phone이 없어 members에서 직접 조회
$memberContact = ['email' => '', 'phone' => ''];
if (isLoggedIn()) {
    try {
        $mcStmt = $pdo->prepare("SELECT email, phone FROM members WHERE id = ? LIMIT 1");
        $mcStmt->execute([$_SESSION['member_id']]);
        if ($mcRow = $mcStmt->fetch(PDO::FETCH_ASSOC)) {
            $memberContact['email'] = trim((string)($mcRow['email'] ?? ''));
            $memberContact['phone'] = trim((string)($mcRow['phone'] ?? ''));
        }
    } catch (Exception $e) {
        // prefill 조회 실패는 무시 (필수 입력으로 처리됨)
    }
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 출력 버퍼링 시작
    ob_start();

    // CSRF 토큰 검증
    if (!verifyCsrfToken(false)) {
        $error = 'CSRF 토큰이 유효하지 않습니다. 페이지를 새로고침 후 다시 시도해주세요.';
    }

    // 허니팟 필드 검증 (봇 방어)
    if (!isset($error) && !empty($_POST['website_url'])) {
        $error = '잘못된 요청입니다.';
        error_log("[SPAM] Honeypot triggered - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " Board: " . $boardType);
    }

    // 폼 타임스탬프 검증 (3초 미만이면 봇)
    if (!isset($error) && isset($_POST['_form_loaded'])) {
        $formLoadedTime = intval($_POST['_form_loaded']);
        if ($formLoadedTime > 0 && (time() - $formLoadedTime) < 3) {
            $error = '너무 빠른 제출입니다. 잠시 후 다시 시도해주세요.';
            error_log("[SPAM] Fast submit - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " Board: " . $boardType . " Time: " . (time() - $formLoadedTime) . "s");
        }
    }

    // 스팸 패턴 검사 (quote, consignment, sales_request 공통)
    if (!isset($error) && in_array($boardType, ['quote', 'consignment', 'sales_request'])) {
        $spamCheck = isSpamSubmission($_POST);
        if ($spamCheck['spam']) {
            $error = $spamCheck['reason'];
            error_log("[SPAM] Pattern detected - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " Board: " . $boardType . " Reason: " . $spamCheck['reason']);
        }
    }

    // 견적문의 게시판: SQL 인젝션 검증 + Rate Limiting
    if (!isset($error) && $boardType === 'quote') {
        $validation = validateFormInput([
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'writer' => $_POST['writer'] ?? '',
            'company' => $_POST['company'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? ''
        ]);
        if (!$validation['valid']) {
            $error = $validation['message'];
        }
        if (!isset($error) && !checkRateLimit('quote_submit', 3, 60)) {
            $error = '너무 많은 요청이 감지되었습니다. 잠시 후 다시 시도해주세요.';
        }
        // 연락처/이메일 필수
        if (!isset($error) && (trim($_POST['email'] ?? '') === '' || trim($_POST['phone'] ?? '') === '')) {
            $error = '이메일과 연락처는 필수 입력 항목입니다.';
        }
    }

    // 위탁판매/판매의뢰 게시판: SQL 인젝션 검증 + Rate Limiting
    if (!isset($error) && in_array($boardType, ['consignment', 'sales_request'])) {
        $validation = validateFormInput([
            'title' => $_POST['title'] ?? '',
            'content' => $_POST['content'] ?? '',
            'writer' => $_POST['writer'] ?? '',
            'company_name' => $_POST['company_name'] ?? '',
            'stock_quantity' => $_POST['stock_quantity'] ?? '',
            'price_info' => $_POST['price_info'] ?? '',
            'contact_person' => $_POST['contact_person'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'contact_email' => $_POST['contact_email'] ?? '',
            'location' => $_POST['location'] ?? ''
        ]);
        if (!$validation['valid']) {
            $error = $validation['message'];
        }
        if (!isset($error) && !checkRateLimit('consignment_submit', 3, 60)) {
            $error = '너무 많은 요청이 감지되었습니다. 잠시 후 다시 시도해주세요.';
        }
        // 담당자 연락처/이메일 필수
        if (!isset($error) && (trim($_POST['contact_email'] ?? '') === '' || trim($_POST['contact_phone'] ?? '') === '')) {
            $error = '담당자 이메일과 연락처는 필수 입력 항목입니다.';
        }
    }

    $data = [
        'title' => $_POST['title'],
        'content' => $_POST['content'],
        'writer' => $_POST['writer'],
        'password' => $_POST['password'],
        'attachment' => '',
        'member_id' => isLoggedIn() ? $_SESSION['member_id'] : null
    ];
    
    // 다중 파일 업로드 처리
    if ($board->allowsUpload() && !empty($_FILES['attachment']['name'][0])) {
        $uploadDir = 'uploads/' . $boardType . '/';
        $uploadedFiles = [];
        $uploadErrors = [];

        // PHP 업로드 error코드 → 사용자 메시지 (조용한 스킵 방지)
        $uploadErrorMessages = [
            UPLOAD_ERR_INI_SIZE   => '서버 허용 용량을 초과했습니다. (파일당 최대 10MB)',
            UPLOAD_ERR_FORM_SIZE  => '폼에서 허용한 용량을 초과했습니다.',
            UPLOAD_ERR_PARTIAL    => '파일이 일부만 업로드되었습니다. 다시 시도해주세요.',
            UPLOAD_ERR_NO_TMP_DIR => '서버 임시 폴더가 없어 업로드에 실패했습니다. 관리자에게 문의하세요.',
            UPLOAD_ERR_CANT_WRITE => '서버 저장에 실패했습니다. 관리자에게 문의하세요.',
            UPLOAD_ERR_EXTENSION  => '서버 설정에 의해 업로드가 차단되었습니다.',
        ];

        // 파일 배열 재구성
        $fileCount = count($_FILES['attachment']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $errCode  = $_FILES['attachment']['error'][$i];
            $origName = $_FILES['attachment']['name'][$i];

            // 선택되지 않은 슬롯은 건너뜀
            if ($errCode === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            // OK 이외 에러코드는 조용히 넘기지 않고 사용자에게 통지
            if ($errCode !== UPLOAD_ERR_OK) {
                $msg = $uploadErrorMessages[$errCode] ?? '알 수 없는 업로드 오류가 발생했습니다.';
                $uploadErrors[] = htmlspecialchars($origName, ENT_QUOTES, 'UTF-8') . ': ' . $msg;
                error_log('[UPLOAD] error code ' . $errCode . ' for file "' . $origName . '" board=' . $boardType);
                continue;
            }

            $file = [
                'name' => $_FILES['attachment']['name'][$i],
                'type' => $_FILES['attachment']['type'][$i],
                'tmp_name' => $_FILES['attachment']['tmp_name'][$i],
                'error' => $_FILES['attachment']['error'][$i],
                'size' => $_FILES['attachment']['size'][$i]
            ];

            $uploadedFile = uploadFile($file, $uploadDir);
            if ($uploadedFile) {
                $uploadedFiles[] = $uploadedFile;
            } else {
                // uploadFile 내부 검증 실패(확장자/MIME/크기/이동실패)도 통지
                $uploadErrors[] = htmlspecialchars($origName, ENT_QUOTES, 'UTF-8') . ': 허용되지 않는 형식이거나 저장에 실패했습니다.';
            }
        }

        // 업로드 실패가 있으면 게시글 저장을 막고(아래 181줄 writePost 조건) 사용자에게 알림
        if (!empty($uploadErrors)) {
            $error = '첨부파일 업로드 실패: ' . implode(' / ', $uploadErrors);
        }

        if (!empty($uploadedFiles)) {
            // 파일명들을 JSON으로 저장
            $data['attachment'] = json_encode($uploadedFiles);
        }
    }
    
    // 게시판별 추가 필드
    if ($boardType === 'quote') {
        $data['company'] = $_POST['company'] ?? '';
        $data['email'] = $_POST['email'] ?? '';
        $data['phone'] = $_POST['phone'] ?? '';
    } elseif ($boardType === 'news') {
        $data['source'] = $_POST['source'] ?? '';
    } elseif (in_array($boardType, ['consignment', 'sales_request'])) {
        $data['company_name'] = $_POST['company_name'] ?? '';
        $data['category'] = $_POST['category'] ?? '';
        $data['item_type'] = $_POST['item_type'] ?? 'steel';
        $data['stock_quantity'] = $_POST['stock_quantity'] ?? '';
        $data['price_info'] = $_POST['price_info'] ?? '';
        $data['contact_person'] = $_POST['contact_person'] ?? '';
        $data['contact_phone'] = $_POST['contact_phone'] ?? '';
        $data['contact_email'] = $_POST['contact_email'] ?? '';
        $data['location'] = $_POST['location'] ?? '';
    }
    
    // 게시글 저장
    if (!isset($error) && $board->writePost($data)) {
        ob_end_clean();
        $redirectPage = $boardType . '.php';
        if ($boardType === 'sales_request') {
            $redirectPage = 'sales_request.php?submitted=1';
        }
        header('Location: ' . $redirectPage);
        exit;
    } else {
        ob_end_flush();
        if (!isset($error)) {
            $error = '게시글 작성에 실패했습니다.';
        }
    }
}

$currentPage = $boardType;
$pageTitle = $board->getBoardTitle() . ' 작성';
$additionalCSS = ['css/board-style.css'];
include 'head.php';
?>

<style>
/* Board Write Page Specific Styles */
.board-write-section {
    background: #F8F9FA;
    padding: 40px 0;
    min-height: 600px;
}

.board-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.board-form {
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

.form-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 32px;
    padding-top: 32px;
    border-top: 1px solid #E5E5E7;
}

.write-btn {
    padding: 14px 40px;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 28px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.write-btn:hover {
    background: #0F1F7A;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
}

.write-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* 업로드 진행 오버레이 */
.upload-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}
.upload-overlay.active { display: flex; }
.upload-progress-box {
    background: #fff;
    border-radius: 16px;
    padding: 28px 32px;
    width: min(90vw, 380px);
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
}
.upload-progress-title {
    font-size: 16px;
    font-weight: 700;
    color: #1A237E;
    margin-bottom: 16px;
}
.upload-progress-track {
    width: 100%;
    height: 14px;
    background: #e9ecef;
    border-radius: 8px;
    overflow: hidden;
}
.upload-progress-fill {
    width: 0%;
    height: 100%;
    background: linear-gradient(90deg, #1A237E, #3949AB);
    border-radius: 8px;
    transition: width 0.2s ease;
}
.upload-progress-fill.indeterminate {
    width: 40% !important;
    transition: none;
    animation: upload-indet 1.1s ease-in-out infinite;
}
@keyframes upload-indet {
    0%   { margin-left: -40%; }
    100% { margin-left: 100%; }
}
.upload-progress-text {
    margin-top: 12px;
    font-size: 20px;
    font-weight: 700;
    color: #1A237E;
}
.upload-progress-hint {
    margin-top: 6px;
    font-size: 12px;
    color: #888;
}

.cancel-btn {
    display: inline-flex;
    align-items: center;
    padding: 14px 40px;
    background: white;
    color: #666;
    text-decoration: none;
    border: 2px solid #E5E5E7;
    border-radius: 28px;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
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

.write-notice {
    margin-top: 40px;
    padding: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.write-notice h4 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
}

.write-notice ul {
    list-style: none;
    padding: 0;
}

.write-notice li {
    position: relative;
    padding-left: 20px;
    margin-bottom: 12px;
    line-height: 1.6;
    color: #666;
}

.write-notice li::before {
    content: "•";
    position: absolute;
    left: 8px;
    color: var(--primary-blue);
}

/* Responsive */
@media (max-width: 768px) {
    .board-form {
        padding: 30px 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-buttons {
        flex-direction: column;
    }
    
    .write-btn,
    .cancel-btn {
        width: 100%;
    }
}

/* 파일 업로드 영역 */
.file-upload-container {
    margin-top: 10px;
}

.drop-zone {
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s ease;
    cursor: pointer;
}

.drop-zone.drag-over {
    border-color: #1A237E;
    background: #e3f2fd;
}

.drop-zone-content {
    pointer-events: none;
}

.drop-zone-content i {
    font-size: 48px;
    color: #999;
    margin-bottom: 10px;
}

.drop-zone-content p {
    margin: 0 0 20px 0;
    color: #666;
    font-size: 16px;
}

.select-files-btn {
    padding: 10px 20px;
    background: #1A237E;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    pointer-events: auto;
}

.select-files-btn:hover {
    background: #283593;
}

/* 파일 목록 */
.file-list {
    margin-top: 20px;
}

.file-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 8px;
}

.file-item.image-file {
    background: #e3f2fd;
}

.file-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 15px;
}

.file-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    border-radius: 4px;
    margin-right: 15px;
    font-size: 24px;
    color: #666;
}

.file-info {
    flex: 1;
}

.file-name {
    font-weight: 500;
    margin-bottom: 4px;
}

.file-size {
    font-size: 13px;
    color: #666;
}

.file-remove {
    padding: 6px 12px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

.file-remove:hover {
    background: #c82333;
}

/* 이미지 미리보기 슬라이더 */
.image-preview-slider {
    margin-top: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

.slider-container {
    position: relative;
    overflow: hidden;
}

.slider-wrapper {
    overflow: hidden;
    margin: 0 50px;
}

.preview-images {
    display: flex;
    transition: transform 0.3s ease;
}

.preview-image {
    flex: 0 0 100%;
    text-align: center;
}

.preview-image img {
    max-width: 100%;
    max-height: 400px;
    object-fit: contain;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.slider-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.slider-nav:hover {
    background: rgba(0,0,0,0.7);
}

.slider-nav.prev {
    left: 10px;
}

.slider-nav.next {
    right: 10px;
}

.slider-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 15px;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #ccc;
    cursor: pointer;
    transition: background 0.3s ease;
}

.dot.active {
    background: #1A237E;
}
</style>

<div class="page-header">
    <h2><?php echo $board->getBoardTitle(); ?> 작성</h2>
</div>

<section class="board-write-section">
    <div class="board-container">
        <?php if (isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" class="board-form" id="boardForm" onsubmit="return handleFormSubmit(event)">
            <?php echo csrfField(); ?>
            <input type="hidden" name="_form_loaded" value="<?php echo time(); ?>">
            <div style="display:none;position:absolute;left:-9999px;" aria-hidden="true">
                <label for="website_url">Website</label>
                <input type="text" name="website_url" id="website_url" value="" tabindex="-1" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="title">제목 <span class="required">*</span></label>
                <input type="text" id="title" name="title" required placeholder="제목을 입력하세요">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="writer">작성자 <span class="required">*</span></label>
                    <input type="text" id="writer" name="writer" required placeholder="이름을 입력하세요" 
                           value="<?php echo in_array($boardType, ['quote', 'sales_request']) && isLoggedIn() ? htmlspecialchars(getMemberInfo()['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <?php if (isLoggedIn()): ?>
                    <label for="password">비밀번호</label>
                    <input type="password" id="password" name="password" placeholder="비밀번호를 설정하지 않으면 로그인 계정으로만 접근 가능합니다">
                    <small>수정/삭제 시 필요합니다. 비워두면 로그인한 본인만 확인 가능합니다.</small>
                    <?php else: ?>
                    <label for="password">비밀번호 <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required placeholder="비밀번호를 입력하세요">
                    <small>게시글 확인 및 수정/삭제 시 필요합니다.</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($boardType === 'quote'): ?>
            <div class="form-group">
                <label for="product_category">제품 카테고리</label>
                <select id="product_category" name="product_category">
                    <option value="">선택하세요</option>
                    <option value="철근(특판)">철근(특판)</option>
                    <option value="H형강(H빔)">H형강(H빔)</option>
                    <option value="철강(강판)">철강(강판)</option>
                    <option value="메탈라스(망철판)">메탈라스(망철판)</option>
                    <option value="경량H형강">경량H형강</option>
                    <option value="I형강(빔)">I형강(빔)</option>
                    <option value="ㄱ형강(앵글)">ㄱ형강(앵글)</option>
                    <option value="ㄷ형강(찬넬)">ㄷ형강(찬넬)</option>
                    <option value="환봉(원형강)">환봉(원형강)</option>
                    <option value="평철">평철</option>
                    <option value="C형강">C형강</option>
                    <option value="테크플레이트">테크플레이트</option>
                    <option value="사각파이프(각관)">사각파이프(각관)</option>
                    <option value="원형파이프(강관)">원형파이프(강관)</option>
                    <option value="레일">레일</option>
                    <option value="강널말뚝(쉬트파일)">강널말뚝(쉬트파일)</option>
                    <option value="스테인레스(STS)">스테인레스(STS)</option>
                    <option value="기타">기타</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="company">회사명</label>
                    <input type="text" id="company" name="company" placeholder="회사명을 입력하세요">
                </div>
                
                <div class="form-group">
                    <label for="phone">연락처 <span class="required">*</span></label>
                    <input type="tel" id="phone" name="phone" required placeholder="010-0000-0000"
                           value="<?php echo htmlspecialchars($memberContact['phone']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">이메일 <span class="required">*</span></label>
                <input type="email" id="email" name="email" required placeholder="example@email.com"
                       value="<?php echo htmlspecialchars($memberContact['email']); ?>">
            </div>
            <?php endif; ?>
            
            <?php if ($boardType === 'news'): ?>
            <div class="form-group">
                <label for="source">출처</label>
                <input type="text" id="source" name="source" placeholder="예: 한국철강신문">
            </div>
            <?php endif; ?>
            
            <?php if (in_array($boardType, ['consignment', 'sales_request'])): ?>
            <div class="form-group">
                <label>유형 <span class="required">*</span></label>
                <div style="display: flex; gap: 20px; padding: 8px 0;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 400;">
                        <input type="radio" name="item_type" value="steel" checked onchange="updateCategoryOptions()"> 철강류
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 400;">
                        <input type="radio" name="item_type" value="lumber" onchange="updateCategoryOptions()"> 목재류
                    </label>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="company_name">업체명 <span class="required">*</span></label>
                    <input type="text" id="company_name" name="company_name" required placeholder="업체명을 입력하세요"
                           value="<?php echo ($boardType === 'sales_request' && isLoggedIn() && isset($memberInfo)) ? escape($memberInfo['company'] ?? '') : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="category">카테고리 <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="">선택하세요</option>
                        <!-- 철강류 카테고리 -->
                        <optgroup label="철강류" id="steel-options">
                            <option value="철근">철근(특판)</option>
                            <option value="H형강">H형강(H빔)</option>
                            <option value="철강">철강(강판)</option>
                            <option value="메탈라스">메탈라스(망철판)</option>
                            <option value="경량H형강">경량H형강</option>
                            <option value="I형강">I형강(빔)</option>
                            <option value="ㄱ형강">ㄱ형강(앵글)</option>
                            <option value="ㄷ형강">ㄷ형강(찬넬)</option>
                            <option value="환봉">환봉(원형강)</option>
                            <option value="평철">평철</option>
                            <option value="C형강">C형강</option>
                            <option value="테크플레이트">테크플레이트</option>
                            <option value="사각파이프">사각파이프(각관)</option>
                            <option value="원형파이프">원형파이프(강관)</option>
                            <option value="레일">레일</option>
                            <option value="강널말뚝">강널말뚝(쉬트파일)</option>
                            <option value="스테인레스">스테인레스(STS)</option>
                            <option value="기타">기타</option>
                        </optgroup>
                        <!-- 목재류 카테고리 -->
                        <optgroup label="목재류" id="lumber-options" style="display:none;">
                            <option value="합판">합판(Plywood)</option>
                            <option value="각재">각재(Squared Timber)</option>
                            <option value="원목">원목(Raw Lumber)</option>
                            <option value="MDF">MDF</option>
                            <option value="집성목">집성목(Laminated)</option>
                            <option value="파티클보드">파티클보드(PB)</option>
                            <option value="데크재">데크재(Deck)</option>
                            <option value="구조재">구조재(Structural)</option>
                            <option value="방부목">방부목(Treated)</option>
                            <option value="기타목재">기타 목재</option>
                        </optgroup>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="stock_quantity">재고수량</label>
                    <input type="text" id="stock_quantity" name="stock_quantity" placeholder="예: 100톤, 50장">
                </div>
                
                <div class="form-group">
                    <label for="location">보관위치</label>
                    <input type="text" id="location" name="location" placeholder="예: 충남 천안시">
                </div>
            </div>
            
            <div class="form-group">
                <label for="price_info">가격정보</label>
                <input type="text" id="price_info" name="price_info" placeholder="예: 시세 대비 5% 할인, 협의 가능">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="contact_person">담당자명</label>
                    <input type="text" id="contact_person" name="contact_person" placeholder="담당자 이름"
                           value="<?php echo ($boardType === 'sales_request' && isLoggedIn()) ? escape(getMemberInfo()['name'] ?? '') : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="contact_phone">담당자 연락처 <span class="required">*</span></label>
                    <input type="tel" id="contact_phone" name="contact_phone" required placeholder="010-0000-0000"
                           value="<?php echo htmlspecialchars($memberContact['phone']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="contact_email">담당자 이메일 <span class="required">*</span></label>
                <input type="email" id="contact_email" name="contact_email" required placeholder="example@email.com"
                       value="<?php echo htmlspecialchars($memberContact['email']); ?>">
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="content">내용 <span class="required">*</span></label>
                <textarea id="content" name="content" required placeholder="내용을 입력하세요"></textarea>
            </div>
            
            <?php if ($board->allowsUpload()): ?>
            <div class="form-group">
                <label for="attachment">첨부파일</label>
                <div class="file-upload-container">
                    <div id="drop-zone" class="drop-zone">
                        <div class="drop-zone-content">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>파일을 드래그하여 놓거나 클릭하여 선택하세요</p>
                            <input type="file" id="attachment" name="attachment[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx" style="display: none;">
                            <button type="button" class="select-files-btn">파일 선택</button>
                        </div>
                    </div>
                    <small>최대 10MB (파일당), 허용 확장자: jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx</small>
                    
                    <!-- 파일 목록 -->
                    <div id="file-list" class="file-list"></div>
                    
                    <!-- 이미지 미리보기 슬라이더 -->
                    <div id="image-preview-slider" class="image-preview-slider" style="display: none;">
                        <div class="slider-container">
                            <button type="button" class="slider-nav prev" onclick="changeSlide(-1)">‹</button>
                            <div class="slider-wrapper">
                                <div id="preview-images" class="preview-images"></div>
                            </div>
                            <button type="button" class="slider-nav next" onclick="changeSlide(1)">›</button>
                        </div>
                        <div class="slider-dots" id="slider-dots"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-buttons">
                <button type="submit" class="write-btn">작성하기</button>
                <a href="<?php echo $boardType === 'sales_request' ? 'sales_request' : $boardType; ?>.php" class="cancel-btn">취소</a>
            </div>

            <!-- 업로드 진행 오버레이 (첨부파일 전송 중 표시) -->
            <div id="upload-overlay" class="upload-overlay" role="status" aria-live="polite" aria-hidden="true">
                <div class="upload-progress-box">
                    <div class="upload-progress-title">파일 업로드 중...</div>
                    <div class="upload-progress-track">
                        <div id="upload-progress-fill" class="upload-progress-fill"></div>
                    </div>
                    <div id="upload-progress-text" class="upload-progress-text">0%</div>
                    <div class="upload-progress-hint">잠시만 기다려 주세요. 창을 닫지 마세요.</div>
                </div>
            </div>
        </form>
        
        <?php if ($boardType === 'quote'): ?>
        <div class="write-notice">
            <h4>견적문의 작성 안내</h4>
            <ul>
                <li>정확한 견적을 위해 제품 규격과 수량을 상세히 기재해 주세요.</li>
                <li>도면이나 참고 자료가 있으시면 첨부파일로 업로드해 주세요.</li>
                <li>연락처를 정확히 기재해 주시면 빠른 답변이 가능합니다.</li>
                <li>견적문의는 비밀글로 작성되며, 작성자와 관리자만 확인 가능합니다.</li>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if ($boardType === 'consignment'): ?>
        <div class="write-notice">
            <h4>중계판매 등록 안내</h4>
            <ul>
                <li>정확한 제품 정보와 규격, 수량을 상세히 기재해 주세요.</li>
                <li>제품 사진이나 규격서가 있으시면 첨부파일로 업로드해 주세요.</li>
                <li>연락처를 정확히 기재해 주시면 구매자와 빠른 연결이 가능합니다.</li>
                <li>판매 완료 시 게시글을 삭제하거나 수정해 주시기 바랍니다.</li>
                <li>허위 정보 등록 시 게시글이 삭제될 수 있습니다.</li>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($boardType === 'sales_request'): ?>
        <div class="write-notice">
            <h4>판매의뢰 등록 안내</h4>
            <ul>
                <li>정확한 제품 정보와 규격, 수량을 상세히 기재해 주세요.</li>
                <li>제품 사진이나 규격서가 있으시면 첨부파일로 업로드해 주세요.</li>
                <li>등록 후 관리자 검토를 거쳐 승인 시 중개판매 게시판에 게시됩니다.</li>
                <li>반려 시 사유를 확인하신 후 수정하여 재등록하실 수 있습니다.</li>
                <li>허위 정보 등록 시 판매의뢰가 거절될 수 있습니다.</li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
// 파일 업로드 관련 변수
let selectedFiles = [];
let fileInput;
let dropZone;
let currentSlideIndex = 0;

// DOM이 로드되면 실행
document.addEventListener('DOMContentLoaded', function() {
    fileInput = document.getElementById('attachment');
    dropZone = document.getElementById('drop-zone');
    
    if (fileInput && dropZone) {
        // 파일 선택 버튼 클릭 이벤트
        const selectBtn = document.querySelector('.select-files-btn');
        if (selectBtn) {
            selectBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Select button clicked');
                fileInput.click();
            });
        }
        
        // 드롭존 클릭 이벤트
        dropZone.addEventListener('click', function(e) {
            // 버튼 클릭은 제외
            if (!e.target.classList.contains('select-files-btn') && 
                e.target.parentElement && !e.target.parentElement.classList.contains('select-files-btn')) {
                if (e.target === dropZone || e.target.parentElement.className === 'drop-zone-content') {
                    console.log('Drop zone clicked');
                    fileInput.click();
                }
            }
        });
        
        // 파일 입력 변경 이벤트
        fileInput.addEventListener('change', function(e) {
            console.log('File input changed:', e.target.files.length, 'files');
            console.log('File input event target:', e.target);
            console.log('Files:', e.target.files);
            
            try {
                if (e.target.files && e.target.files.length > 0) {
                    handleFiles(e.target.files);
                    // 파일 입력 초기화 (같은 파일 재선택 가능하도록)
                    e.target.value = '';
                }
            } catch (error) {
                console.error('Error in file input change handler:', error);
                alert('파일 처리 중 오류가 발생했습니다: ' + error.message);
            }
        });
        
        // 드래그 앤 드롭 이벤트
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });
        
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            handleFiles(e.dataTransfer.files);
        });
    }
});

// 파일 처리 함수
function handleFiles(files) {
    console.log('handleFiles called with', files.length, 'files');
    console.log('Files object:', files);
    
    try {
        const maxFileSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf', 
                             'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                             'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        
        let addedCount = 0;
        for (let file of files) {
            console.log('Processing file:', file.name, file.type, file.size);
        
        // 파일 크기 검증
        if (file.size > maxFileSize) {
            alert(`파일 "${file.name}"의 크기가 10MB를 초과합니다.`);
            continue;
        }
        
        // 파일 타입 검증
        if (!allowedTypes.includes(file.type)) {
            alert(`파일 "${file.name}"은(는) 허용되지 않는 형식입니다.`);
            continue;
        }
        
        // 중복 파일 체크
        if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
            alert(`파일 "${file.name}"은(는) 이미 추가되었습니다.`);
            continue;
        }
        
        selectedFiles.push(file);
        addedCount++;
    }
    
        console.log('Added', addedCount, 'files. Total:', selectedFiles.length);
        
        updateFileList();
        updateImageSlider();
        updateFileInput();
    } catch (error) {
        console.error('Error in handleFiles:', error);
        alert('파일 처리 중 오류가 발생했습니다: ' + error.message);
    }
}

// 파일 목록 업데이트
function updateFileList() {
    const fileList = document.getElementById('file-list');
    fileList.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        
        if (file.type.startsWith('image/')) {
            fileItem.classList.add('image-file');
            
            // 이미지 썸네일
            const reader = new FileReader();
            reader.onload = function(e) {
                const thumbnail = document.createElement('img');
                thumbnail.src = e.target.result;
                thumbnail.className = 'file-thumbnail';
                fileItem.prepend(thumbnail);
            };
            reader.readAsDataURL(file);
        } else {
            // 파일 아이콘
            const icon = document.createElement('div');
            icon.className = 'file-icon';
            icon.innerHTML = getFileIcon(file.type);
            fileItem.appendChild(icon);
        }
        
        // 파일 정보
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        fileInfo.innerHTML = `
            <div class="file-name">${file.name}</div>
            <div class="file-size">${formatFileSize(file.size)}</div>
        `;
        fileItem.appendChild(fileInfo);
        
        // 삭제 버튼
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'file-remove';
        removeBtn.textContent = '삭제';
        removeBtn.onclick = function() {
            removeFile(index);
        };
        fileItem.appendChild(removeBtn);
        
        fileList.appendChild(fileItem);
    });
}

// 파일 제거
function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateFileList();
    updateImageSlider();
    updateFileInput();
}

// 이미지 슬라이더 업데이트
function updateImageSlider() {
    const imageFiles = selectedFiles.filter(file => file.type.startsWith('image/'));
    const slider = document.getElementById('image-preview-slider');
    const previewImages = document.getElementById('preview-images');
    const dotsContainer = document.getElementById('slider-dots');
    
    if (imageFiles.length === 0) {
        slider.style.display = 'none';
        return;
    }
    
    slider.style.display = 'block';
    previewImages.innerHTML = '';
    dotsContainer.innerHTML = '';
    
    imageFiles.forEach((file, index) => {
        // 이미지 프리뷰
        const previewDiv = document.createElement('div');
        previewDiv.className = 'preview-image';
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = file.name;
            previewDiv.appendChild(img);
        };
        reader.readAsDataURL(file);
        
        previewImages.appendChild(previewDiv);
        
        // 도트 네비게이션
        const dot = document.createElement('span');
        dot.className = 'dot' + (index === 0 ? ' active' : '');
        dot.onclick = function() {
            goToSlide(index);
        };
        dotsContainer.appendChild(dot);
    });
    
    currentSlideIndex = 0;
    updateSliderPosition();
}

// 슬라이드 변경
function changeSlide(direction) {
    const imageFiles = selectedFiles.filter(file => file.type.startsWith('image/'));
    currentSlideIndex += direction;
    
    if (currentSlideIndex < 0) {
        currentSlideIndex = imageFiles.length - 1;
    } else if (currentSlideIndex >= imageFiles.length) {
        currentSlideIndex = 0;
    }
    
    updateSliderPosition();
}

// 특정 슬라이드로 이동
function goToSlide(index) {
    currentSlideIndex = index;
    updateSliderPosition();
}

// 슬라이더 위치 업데이트
function updateSliderPosition() {
    const previewImages = document.getElementById('preview-images');
    const dots = document.querySelectorAll('.dot');
    
    previewImages.style.transform = `translateX(-${currentSlideIndex * 100}%)`;
    
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlideIndex);
    });
}

// 파일 입력 업데이트 (DataTransfer API 제거)
function updateFileInput() {
    // DataTransfer API는 일부 브라우저에서 지원하지 않으므로 제거
    // 대신 폼 제출 시 FormData로 직접 처리
    console.log('Selected files count:', selectedFiles.length);
}

// 파일 아이콘 가져오기
function getFileIcon(fileType) {
    if (fileType.includes('pdf')) return '📄';
    if (fileType.includes('word')) return '📝';
    if (fileType.includes('excel')) return '📊';
    return '📎';
}

// 파일 크기 포맷
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// 폼 제출 처리
function handleFormSubmit(event) {
    console.log('Form submit, selected files:', selectedFiles.length);
    
    if (selectedFiles.length > 0) {
        // 기본 폼 제출 방지
        event.preventDefault();
        
        // FormData 생성
        const formData = new FormData(event.target);
        
        // 기존 파일 입력 제거
        formData.delete('attachment[]');
        
        // selectedFiles 배열의 파일들을 FormData에 추가
        selectedFiles.forEach(file => {
            formData.append('attachment[]', file);
        });
        
        console.log('Submitting form with FormData');

        // 중복 제출 방지
        if (window.__isUploading) return false;
        window.__isUploading = true;

        // 폼 제출
        const form = event.target;
        const submitBtn = form.querySelector('.write-btn');
        const overlay = document.getElementById('upload-overlay');
        const fill = document.getElementById('upload-progress-fill');
        const text = document.getElementById('upload-progress-text');

        function setProgress(pct, label) {
            if (fill) {
                fill.classList.remove('indeterminate');
                fill.style.width = pct + '%';
            }
            if (text) text.textContent = (label !== undefined) ? label : (pct + '%');
        }
        function setIndeterminate(label) {
            if (fill) fill.classList.add('indeterminate');
            if (text) text.textContent = label || '업로드 중...';
        }
        function restoreUI() {
            window.__isUploading = false;
            if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = '작성하기'; }
            if (overlay) { overlay.classList.remove('active'); overlay.setAttribute('aria-hidden', 'true'); }
        }

        // UI: 오버레이 표시 + 버튼 비활성화
        if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = '업로드 중...'; }
        if (overlay) { overlay.classList.add('active'); overlay.setAttribute('aria-hidden', 'false'); }
        setProgress(0);

        const xhr = new XMLHttpRequest();

        // 업로드 진행률 표시
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                setProgress(Math.round((e.loaded / e.total) * 100));
            } else {
                setIndeterminate('업로드 중...');
            }
        };
        // 전송 완료 → 서버 처리 대기
        xhr.upload.onload = function() {
            setProgress(100, '처리 중...');
        };

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                // 성공 시 서버는 다른 페이지(quote.php 등)로 302 리다이렉트 → 경로가 바뀜.
                // 검증 실패 시 같은 경로(board_write.php)로 200 폼 재렌더 → 경로 동일.
                // pathname 기준으로 구분(쿼리/해시 무시)해야 오판이 없다.
                var finalUrl = xhr.responseURL || '';
                var samePage = true;
                try {
                    samePage = (new URL(finalUrl, window.location.href).pathname === window.location.pathname);
                } catch (e) { samePage = true; }

                if (finalUrl && !samePage) {
                    // 성공: 리다이렉트된 페이지로 이동 (오버레이는 페이지 전환으로 자연 소멸)
                    window.location.href = finalUrl;
                } else {
                    // 검증 실패 재렌더: document.write로 페이지를 갈아엎으면 첨부/미리보기가 사라지므로
                    // UI만 복원하고 오류 메시지만 표시 → 사용자가 첨부 유지한 채 수정·재제출 가능
                    restoreUI();
                    var msg = '';
                    try {
                        var m = xhr.responseText.match(/<div class="alert error">([^<]+)<\/div>/);
                        if (m) { msg = m[1].trim(); }
                    } catch (e) {}
                    alert(msg || '작성에 실패했습니다. 입력값을 확인한 뒤 다시 시도해주세요.');
                }
            } else {
                restoreUI();
                alert('업로드에 실패했습니다. (오류 ' + xhr.status + ') 잠시 후 다시 시도해주세요.');
            }
        };

        xhr.onerror = function() {
            restoreUI();
            alert('업로드 중 네트워크 오류가 발생했습니다.');
        };
        xhr.ontimeout = function() {
            restoreUI();
            alert('업로드 시간이 초과되었습니다. 다시 시도해주세요.');
        };
        xhr.onabort = function() { restoreUI(); };

        xhr.open('POST', form.action || window.location.href);
        xhr.timeout = 180000; // 3분
        xhr.send(formData);
        
        return false;
    }
    
    // 파일이 없으면 기본 폼 제출
    return true;
}

// 카테고리 옵션 업데이트 (철강류/목재류 전환)
function updateCategoryOptions() {
    const itemType = document.querySelector('input[name="item_type"]:checked')?.value;
    const steelOptions = document.getElementById('steel-options');
    const lumberOptions = document.getElementById('lumber-options');
    const categorySelect = document.getElementById('category');

    if (steelOptions && lumberOptions) {
        if (itemType === 'lumber') {
            steelOptions.style.display = 'none';
            steelOptions.querySelectorAll('option').forEach(o => o.disabled = true);
            lumberOptions.style.display = '';
            lumberOptions.querySelectorAll('option').forEach(o => o.disabled = false);
        } else {
            steelOptions.style.display = '';
            steelOptions.querySelectorAll('option').forEach(o => o.disabled = false);
            lumberOptions.style.display = 'none';
            lumberOptions.querySelectorAll('option').forEach(o => o.disabled = true);
        }
        // 카테고리 선택 초기화
        if (categorySelect) categorySelect.value = '';
    }
}

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

// URL 파라미터에서 제품 카테고리 확인
const urlParams = new URLSearchParams(window.location.search);
const productCategory = urlParams.get('product');
if (productCategory && document.getElementById('product_category')) {
    document.getElementById('product_category').value = productCategory;
}

// 이미 파일 처리는 위의 handleFiles 함수에서 처리됨
// 중복된 이벤트 리스너 제거
</script>

<?php include 'tail.php'; ?>