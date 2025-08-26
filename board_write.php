<?php
require_once 'db.php';
require_once 'board/board_template.php';
require_once 'member_check.php';

// 게시판 타입 확인
$boardType = isset($_GET['type']) ? $_GET['type'] : '';
if (!in_array($boardType, ['quote', 'notice', 'news', 'consignment'])) {
    header('Location: index.php');
    exit;
}

// 게시판 객체 생성
$board = new BoardTemplate($pdo, $boardType);

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 출력 버퍼링 시작
    ob_start();
    
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
        
        // 파일 배열 재구성
        $fileCount = count($_FILES['attachment']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['attachment']['error'][$i] === UPLOAD_ERR_OK) {
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
                }
            }
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
    
    // 게시글 저장
    if (!isset($error) && $board->writePost($data)) {
        ob_end_clean();
        header('Location: ' . $boardType . '.php');
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
</style>

<div class="page-header">
    <h2><?php echo $board->getBoardTitle(); ?> 작성</h2>
</div>

<section class="board-write-section">
    <div class="board-container">
        <?php if (isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" class="board-form">
            <div class="form-group">
                <label for="title">제목 <span class="required">*</span></label>
                <input type="text" id="title" name="title" required placeholder="제목을 입력하세요">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="writer">작성자 <span class="required">*</span></label>
                    <input type="text" id="writer" name="writer" required placeholder="이름을 입력하세요" 
                           value="<?php echo $boardType === 'quote' && isLoggedIn() ? htmlspecialchars(getMemberInfo()['name']) : ''; ?>">
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
                    <label for="phone">연락처</label>
                    <input type="tel" id="phone" name="phone" placeholder="010-0000-0000">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">이메일</label>
                <input type="email" id="email" name="email" placeholder="example@email.com"
                       value="<?php echo isLoggedIn() ? htmlspecialchars(getMemberInfo()['email']) : ''; ?>">
            </div>
            <?php endif; ?>
            
            <?php if ($boardType === 'news'): ?>
            <div class="form-group">
                <label for="source">출처</label>
                <input type="text" id="source" name="source" placeholder="예: 한국철강신문">
            </div>
            <?php endif; ?>
            
            <?php if ($boardType === 'consignment'): ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="company_name">업체명 <span class="required">*</span></label>
                    <input type="text" id="company_name" name="company_name" required placeholder="업체명을 입력하세요">
                </div>
                
                <div class="form-group">
                    <label for="category">카테고리 <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="">선택하세요</option>
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
                    <input type="text" id="contact_person" name="contact_person" placeholder="담당자 이름">
                </div>
                
                <div class="form-group">
                    <label for="contact_phone">담당자 연락처</label>
                    <input type="tel" id="contact_phone" name="contact_phone" placeholder="010-0000-0000">
                </div>
            </div>
            
            <div class="form-group">
                <label for="contact_email">담당자 이메일</label>
                <input type="email" id="contact_email" name="contact_email" placeholder="example@email.com">
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="content">내용 <span class="required">*</span></label>
                <textarea id="content" name="content" required placeholder="내용을 입력하세요"></textarea>
            </div>
            
            <?php if ($board->allowsUpload()): ?>
            <div class="form-group">
                <label for="attachment">첨부파일</label>
                <input type="file" id="attachment" name="attachment[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                <small>최대 10MB (파일당), 허용 확장자: jpg, png, pdf, doc, docx, xls, xlsx. 여러 파일을 선택할 수 있습니다.</small>
                <div id="file-list" style="margin-top: 10px;"></div>
            </div>
            <?php endif; ?>
            
            <div class="form-buttons">
                <button type="submit" class="write-btn">작성하기</button>
                <a href="<?php echo $boardType; ?>.php" class="cancel-btn">취소</a>
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

// URL 파라미터에서 제품 카테고리 확인
const urlParams = new URLSearchParams(window.location.search);
const productCategory = urlParams.get('product');
if (productCategory && document.getElementById('product_category')) {
    document.getElementById('product_category').value = productCategory;
}

// 파일 선택 시 목록 표시
document.getElementById('attachment')?.addEventListener('change', function(e) {
    const fileList = document.getElementById('file-list');
    fileList.innerHTML = '';
    
    if (this.files.length > 0) {
        const ul = document.createElement('ul');
        ul.style.listStyle = 'none';
        ul.style.padding = '0';
        ul.style.margin = '10px 0';
        
        for (let i = 0; i < this.files.length; i++) {
            const li = document.createElement('li');
            li.style.padding = '8px 12px';
            li.style.backgroundColor = '#f8f9fa';
            li.style.marginBottom = '5px';
            li.style.borderRadius = '4px';
            li.style.fontSize = '14px';
            li.style.border = '1px solid #e5e5e7';
            
            const file = this.files[i];
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            li.innerHTML = `📎 ${file.name} <span style="color: #666; font-size: 13px;">(${fileSize}MB)</span>`;
            
            ul.appendChild(li);
        }
        
        fileList.appendChild(ul);
        
        const info = document.createElement('p');
        info.style.fontSize = '14px';
        info.style.color = '#666';
        info.style.marginTop = '10px';
        info.textContent = `총 ${this.files.length}개 파일 선택됨`;
        fileList.appendChild(info);
    }
});
</script>

<?php include 'tail.php'; ?>