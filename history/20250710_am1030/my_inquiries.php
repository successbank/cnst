<?php
require_once 'member_check.php';
require_once 'db.php';
require_once 'includes/sub_layout.php';

// 로그인 체크
checkLogin();

$member_id = $_SESSION['member_id'];
$user_id = $_SESSION['user_id'] ?? '';

// 회원 정보 가져오기
$member_info = [];
if ($member_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $member_info = $stmt->fetch() ?: [];
    } catch(PDOException $e) {
        // 에러 무시
    }
}

// 문의 유형 필터
$type = $_GET['type'] ?? 'all';

// 페이지 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

try {
    // 전체 개수 조회
    $totalQuotes = 0;
    $totalConsignments = 0;
    $totalInquiries = 0;
    
    // 견적문의 개수
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_quote WHERE writer = ?");
    $stmt->execute([$user_id]);
    $totalQuotes = $stmt->fetchColumn();
    
    // 중계판매 문의 개수
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_consignment WHERE writer = ?");
    $stmt->execute([$user_id]);
    $totalConsignments = $stmt->fetchColumn();
    
    $total = $totalQuotes + $totalConsignments;
    
    // 문의 내역 조회
    $inquiries = [];
    
    if ($type === 'all' || $type === 'quote') {
        // 견적문의 조회
        $stmt = $pdo->prepare("
            SELECT 'quote' as type, id, title, company, created_at, is_answered as status
            FROM board_quote 
            WHERE writer = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        $quotes = $stmt->fetchAll();
        
        foreach($quotes as $quote) {
            $quote['type_label'] = '견적문의';
            $quote['status_label'] = $quote['status'] ? '답변완료' : '대기중';
            $inquiries[] = $quote;
        }
    }
    
    if ($type === 'all' || $type === 'consignment') {
        // 중계판매 문의 조회
        $stmt = $pdo->prepare("
            SELECT 'consignment' as type, id, title, company, created_at, status
            FROM board_consignment 
            WHERE writer = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        $consignments = $stmt->fetchAll();
        
        foreach($consignments as $consignment) {
            $consignment['type_label'] = '중계판매';
            $statusLabels = [
                'active' => '진행중',
                'sold' => '판매완료',
                'inactive' => '종료'
            ];
            $consignment['status_label'] = $statusLabels[$consignment['status']] ?? '진행중';
            $inquiries[] = $consignment;
        }
    }
    
    // 날짜순 정렬
    usort($inquiries, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // 페이징 처리
    $totalInquiries = count($inquiries);
    $inquiries = array_slice($inquiries, $offset, $limit);
    $totalPages = ceil($totalInquiries / $limit);
    
} catch(PDOException $e) {
    $inquiries = [];
    $total = 0;
    $totalPages = 0;
    $totalInquiries = 0;
}

$currentPage = 'mypage';
$pageTitle = '문의내역';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('문의내역', 'inquiries');

// 사이드바
myPageSidebar('inquiries');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>문의내역</h2>
        <p>고객님이 문의하신 내역을 확인하실 수 있습니다.</p>
    </div>
    
    <div class="content-body">
        <!-- 문의 서브메뉴 -->
        <?php inquirySubmenu($type); ?>
        
        <?php if($totalInquiries > 0): ?>
            <div class="inquiry-summary">
                <p>총 <strong><?php echo number_format($totalInquiries); ?></strong>건의 문의내역이 있습니다.</p>
                <p class="summary-desc">답변 상태를 확인하고 답변 내용을 확인하실 수 있습니다.</p>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="80">번호</th>
                        <th width="100">문의유형</th>
                        <th>제목</th>
                        <th width="150">회사명</th>
                        <th width="120">작성일</th>
                        <th width="100">상태</th>
                        <th width="80">상세</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($inquiries as $index => $inquiry): ?>
                        <tr>
                            <td class="text-center"><?php echo $total - ($offset + $index); ?></td>
                            <td class="text-center">
                                <span class="type-badge type-<?php echo $inquiry['type']; ?>">
                                    <?php echo $inquiry['type_label']; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($inquiry['title']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($inquiry['company'] ?? '-'); ?></td>
                            <td class="text-center"><?php echo date('Y-m-d', strtotime($inquiry['created_at'])); ?></td>
                            <td class="text-center">
                                <span class="status-badge <?php echo $inquiry['type'] === 'quote' ? ($inquiry['status'] ? 'status-answered' : 'status-waiting') : 'status-' . $inquiry['status']; ?>">
                                    <?php echo $inquiry['status_label']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if($inquiry['type'] === 'quote'): ?>
                                    <a href="board_view.php?board=quote&id=<?php echo $inquiry['id']; ?>" class="btn-small">보기</a>
                                <?php else: ?>
                                    <a href="board_view.php?board=consignment&id=<?php echo $inquiry['id']; ?>" class="btn-small">보기</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
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
            
        <?php else: ?>
            <div class="empty-state">
                <p>아직 문의내역이 없습니다.</p>
            </div>
        <?php endif; ?>
        
        <!-- 견적문의 폼 -->
        <div id="quoteFormSection" class="form-section" style="display: none;">
            <div class="form-header">
                <h3>견적문의 작성</h3>
                <button type="button" class="close-btn" onclick="toggleQuoteForm()">×</button>
            </div>
            <form id="quoteForm" class="ajax-form">
                <div class="form-group">
                    <label for="product">제품명 <span class="required">*</span></label>
                    <input type="text" id="product" name="product" placeholder="예: H형강, 철근, 강판 등" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity">수량</label>
                        <input type="text" id="quantity" name="quantity" placeholder="예: 10톤, 100개 등">
                    </div>
                    <div class="form-group">
                        <label for="urgency">납기</label>
                        <select id="urgency" name="urgency">
                            <option value="normal">일반</option>
                            <option value="urgent">긴급</option>
                            <option value="flexible">협의</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="specifications">규격 및 사양</label>
                    <textarea id="specifications" name="specifications" rows="3" placeholder="예: H-300x300x10x15, KS D 3503, 길이 12m 등"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company">회사명 <span class="required">*</span></label>
                        <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($member_info['company'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="author">담당자명 <span class="required">*</span></label>
                        <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($member_info['name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">연락처 <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($member_info['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">이메일 <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($member_info['email'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="additional_notes">기타 요청사항</label>
                    <textarea id="additional_notes" name="additional_notes" rows="3" placeholder="추가적으로 전달하고 싶은 내용을 입력해주세요"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">견적문의 등록</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleQuoteForm()">취소</button>
                </div>
            </form>
        </div>
        
        <!-- 중계판매 신청 폼 -->
        <div id="consignmentFormSection" class="form-section" style="display: none;">
            <div class="form-header">
                <h3>중계판매 신청</h3>
                <button type="button" class="close-btn" onclick="toggleConsignmentForm()">×</button>
            </div>
            <form id="consignmentForm" class="ajax-form">
                <div class="form-group">
                    <label for="cons_title">제품명 <span class="required">*</span></label>
                    <input type="text" id="cons_title" name="title" placeholder="판매할 제품명을 입력해주세요" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">카테고리 <span class="required">*</span></label>
                        <select id="category" name="category" required>
                            <option value="">선택하세요</option>
                            <option value="형강류">형강류</option>
                            <option value="판재류">판재류</option>
                            <option value="봉강류">봉강류</option>
                            <option value="파이프류">파이프류</option>
                            <option value="기타">기타</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="stock_quantity">재고수량</label>
                        <input type="text" id="stock_quantity" name="stock_quantity" placeholder="예: 100톤">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="price_info">가격정보</label>
                    <input type="text" id="price_info" name="price_info" placeholder="예: 협의, 톤당 100만원 등">
                </div>
                
                <div class="form-group">
                    <label for="company_name">회사명 <span class="required">*</span></label>
                    <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($member_info['company'] ?? ''); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_person">담당자명 <span class="required">*</span></label>
                        <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($member_info['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">연락처 <span class="required">*</span></label>
                        <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($member_info['phone'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_email">이메일</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($member_info['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location">소재지</label>
                        <input type="text" id="location" name="location" placeholder="예: 경기도 화성시">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="cons_content">상세내용 <span class="required">*</span></label>
                    <textarea id="cons_content" name="content" rows="5" placeholder="제품의 상태, 규격, 특이사항 등을 상세히 입력해주세요" required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">중계판매 신청</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleConsignmentForm()">취소</button>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.inquiry-submenu {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 32px;
    padding-bottom: 20px;
    border-bottom: 2px solid #E5E5E7;
}

.inquiry-submenu .menu-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 8px 16px;
    color: #666;
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.inquiry-submenu .menu-item:hover {
    background: #F8F9FA;
    color: #1A237E;
}

.inquiry-submenu .menu-item.active {
    background: #1A237E;
    color: white;
}

.inquiry-submenu .menu-item.active .submenu-desc {
    color: rgba(255, 255, 255, 0.8);
}

.submenu-desc {
    font-size: 12px;
    color: #999;
    font-weight: 400;
}

.menu-divider {
    color: #E5E5E7;
    font-size: 16px;
    margin: 0 8px;
}

.menu-action {
    padding: 8px 16px;
    color: #1A237E;
    text-decoration: none;
    font-size: 15px;
    font-weight: 600;
    border-radius: 4px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.menu-action:hover {
    background: #E8EAF6;
    color: #283593;
}

.inquiry-summary {
    background: #F8F9FA;
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.inquiry-summary p {
    font-size: 15px;
    margin: 0;
}

.inquiry-summary p + p {
    margin-top: 4px;
}

.inquiry-summary strong {
    color: #1A237E;
}

.summary-desc {
    font-size: 13px !important;
    color: #666;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.data-table th {
    background: #F8F9FA;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    color: #666;
    border-bottom: 2px solid #E5E5E7;
}

.data-table td {
    padding: 16px 12px;
    border-bottom: 1px solid #F0F0F0;
    font-size: 14px;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.data-table tr:hover {
    background: #F8F9FA;
}

.text-center {
    text-align: center;
}

.type-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.type-quote {
    background: #E8EAF6;
    color: #3F51B5;
}

.type-consignment {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.status-waiting {
    background: #FFF3E0;
    color: #F57C00;
}

.status-answered {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-active {
    background: #E3F2FD;
    color: #1976D2;
}

.status-sold {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-inactive {
    background: #F5F5F5;
    color: #666;
}

.btn-small {
    padding: 6px 16px;
    background: #1A237E;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 13px;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-small:hover {
    background: #283593;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 32px;
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

.page-link:hover,
.page-link.active {
    background: #1A237E;
    color: white;
    border-color: #1A237E;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-state p {
    font-size: 16px;
    color: #666;
    margin-bottom: 24px;
}


@media (max-width: 768px) {
    .inquiry-submenu {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    
    .menu-divider {
        display: none;
    }
    
    .inquiry-submenu .menu-item,
    .menu-action {
        width: 100%;
        text-align: center;
    }
    
    .data-table {
        font-size: 12px;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px;
    }
    
}


/* 폼 섹션 스타일 */
.form-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-top: 32px;
    overflow: hidden;
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    background: #F8F9FA;
    border-bottom: 1px solid #E5E5E7;
}

.form-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.close-btn:hover {
    background: #E5E5E7;
    color: #333;
}

.ajax-form {
    padding: 32px 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.required {
    color: #E53935;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #1A237E;
    box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #E5E5E7;
}

.btn {
    padding: 14px 28px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #1A237E;
    color: white;
}

.btn-primary:hover {
    background: #283593;
}

.btn-secondary {
    background: white;
    color: #666;
    border: 2px solid #E5E5E7;
}

.btn-secondary:hover {
    background: #F8F9FA;
    border-color: #D0D0D2;
}

/* 알림 메시지 */
.alert {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    z-index: 1000;
    animation: slideIn 0.3s ease;
}

.alert-success {
    background: #4CAF50;
    color: white;
}

.alert-error {
    background: #F44336;
    color: white;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .ajax-form {
        padding: 20px 16px;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
// 견적문의 폼 토글
function toggleQuoteForm() {
    const formSection = document.getElementById('quoteFormSection');
    const isVisible = formSection.style.display === 'block';
    
    if (!isVisible) {
        // 다른 폼 닫기
        document.getElementById('consignmentFormSection').style.display = 'none';
        formSection.style.display = 'block';
        formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        formSection.style.display = 'none';
        document.getElementById('quoteForm').reset();
    }
}

// 중계판매 폼 토글
function toggleConsignmentForm() {
    const formSection = document.getElementById('consignmentFormSection');
    const isVisible = formSection.style.display === 'block';
    
    if (!isVisible) {
        // 다른 폼 닫기
        document.getElementById('quoteFormSection').style.display = 'none';
        formSection.style.display = 'block';
        formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        formSection.style.display = 'none';
        document.getElementById('consignmentForm').reset();
    }
}

// 알림 메시지 표시
function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => alert.remove(), 300);
    }, 3000);
}

// 견적문의 폼 제출
document.getElementById('quoteForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    try {
        submitBtn.disabled = true;
        submitBtn.textContent = '처리중...';
        
        const response = await fetch('ajax/submit_quote.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message);
            this.reset();
            toggleQuoteForm();
            
            // 3초 후 페이지 새로고침
            setTimeout(() => location.reload(), 3000);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        showAlert('오류가 발생했습니다. 다시 시도해주세요.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '견적문의 등록';
    }
});

// 중계판매 폼 제출
document.getElementById('consignmentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    
    try {
        submitBtn.disabled = true;
        submitBtn.textContent = '처리중...';
        
        const response = await fetch('ajax/submit_consignment.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message);
            this.reset();
            toggleConsignmentForm();
            
            // 3초 후 페이지 새로고침
            setTimeout(() => location.reload(), 3000);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        showAlert('오류가 발생했습니다. 다시 시도해주세요.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '중계판매 신청';
    }
});
</script>

<?php 
endSubPage();
include 'tail.php'; 
?>