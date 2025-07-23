<?php
require_once 'member_check.php';
require_once 'db.php';
require_once 'includes/sub_layout.php';

// 로그인 체크
checkLogin();

$currentPage = 'mypage';
$pageTitle = '제품견적서';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('제품견적서', 'quote_cart');

// 사이드바
myPageSidebar('quote_cart');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>제품견적서</h2>
        <p>견적 요청할 제품들을 관리하고 한번에 견적을 요청할 수 있습니다.</p>
    </div>
    
    <div class="content-body">
        <!-- 제출된 견적서 현황 -->
        <?php
        // 사용자가 제출한 견적서 조회
        try {
            $stmt = $pdo->prepare("SELECT * FROM product_quotes WHERE member_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$_SESSION['member_id']]);
            $submitted_quotes = $stmt->fetchAll();
            
            if (!empty($submitted_quotes)):
        ?>
        <div class="submitted-quotes-section">
            <h3>최근 견적 요청 현황</h3>
            <table class="submitted-quotes-table">
                <thead>
                    <tr>
                        <th>요청일</th>
                        <th>제품</th>
                        <th>상태</th>
                        <th>비고</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submitted_quotes as $quote): ?>
                    <tr>
                        <td><?php echo date('Y-m-d', strtotime($quote['created_at'])); ?></td>
                        <td>
                            <?php 
                            $products = $quote['products'];
                            if (strlen($products) > 50) {
                                echo htmlspecialchars(mb_substr($products, 0, 50)) . '...';
                            } else {
                                echo htmlspecialchars($products);
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch($quote['status']) {
                                case 'pending':
                                    $status_class = 'status-pending';
                                    $status_text = '대기중';
                                    break;
                                case 'processing':
                                    $status_class = 'status-processing';
                                    $status_text = '처리중';
                                    break;
                                case 'completed':
                                    $status_class = 'status-completed';
                                    $status_text = '완료';
                                    break;
                                case 'cancelled':
                                    $status_class = 'status-cancelled';
                                    $status_text = '취소';
                                    break;
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td>
                            <?php if ($quote['admin_notes']): ?>
                                <span class="admin-note" title="<?php echo htmlspecialchars($quote['admin_notes']); ?>">메모 있음</span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
            endif;
        } catch (Exception $e) {
            // 테이블이 없거나 오류 발생 시 무시
        }
        ?>
        
        <!-- 견적 카트 내용 -->
        <div id="noItemsMessage" style="display: none; text-align: center; padding: 60px 20px;">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="#ddd" style="margin-bottom: 20px;">
                <path d="M7 18C5.9 18 5.01 18.9 5.01 20C5.01 21.1 5.9 22 7 22C8.1 22 9 21.1 9 20C9 18.9 8.1 18 7 18ZM1 2V4H3L6.6 11.59L5.25 14.04C5.09 14.32 5 14.65 5 15C5 16.1 5.9 17 7 17H19V15H7.42C7.28 15 7.17 14.89 7.17 14.75L7.2 14.63L8.1 13H15.55C16.3 13 16.96 12.59 17.3 11.97L20.88 5.48C20.96 5.34 21 5.17 21 5C21 4.45 20.55 4 20 4H5.21L4.27 2H1ZM17 18C15.9 18 15.01 18.9 15.01 20C15.01 21.1 15.9 22 17 22C18.1 22 19 21.1 19 20C19 18.9 18.1 18 17 18Z"/>
            </svg>
            <h3 style="color: #666; margin-bottom: 10px;">견적서가 비어있습니다</h3>
            <p style="color: #999; margin-bottom: 20px;">제품 목록에서 견적을 원하는 제품을 추가해주세요.</p>
            <a href="products.php" class="btn btn-primary">제품 둘러보기</a>
        </div>
        
        <div id="cartContent" style="display: none;">
            <div class="cart-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e5e5e7;">
                <div>
                    <h3 style="margin: 0; color: #333; font-size: 20px;">선택된 제품 목록</h3>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;" id="totalItems"></p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="downloadPDF()" class="btn btn-outline-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 5px;">
                            <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                        </svg>
                        PDF 다운로드
                    </button>
                    <button type="button" onclick="clearAllItems()" class="btn btn-outline-danger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 5px;">
                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
                        전체 삭제
                    </button>
                </div>
            </div>
            
            <div class="cart-table">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="50" style="text-align: center;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th width="60" style="text-align: center;">번호</th>
                            <th>제품명</th>
                            <th>규격</th>
                            <th width="120" style="text-align: center;">수량</th>
                            <th width="80" style="text-align: center;">삭제</th>
                        </tr>
                    </thead>
                    <tbody id="cartItems">
                        <!-- 동적으로 추가됨 -->
                    </tbody>
                </table>
            </div>
            
            <div class="cart-bottom" style="margin-top: 30px; display: flex; justify-content: space-between; align-items: center;">
                <div class="selected-info" style="display: flex; align-items: center; gap: 20px;">
                    <span style="color: #666;">선택된 제품: <strong id="selectedCount" style="color: var(--primary-blue);">0</strong>개</span>
                    <span style="color: #666;">선택된 수량: <strong id="selectedQuantity" style="color: var(--primary-blue);">0</strong>개</span>
                </div>
                <button type="button" onclick="requestQuote()" class="btn btn-primary btn-lg">
                    선택 제품 견적 요청
                </button>
            </div>
        </div>
    </div>
</main>

<style>
.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border: 1px solid #e5e5e7;
}

.data-table th,
.data-table td {
    padding: 15px 12px;
    text-align: left;
    border-bottom: 1px solid #e5e5e7;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

.quantity-input {
    width: 60px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
    font-size: 14px;
}

.quantity-input:focus {
    outline: none;
    border-color: var(--primary-blue);
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.btn-primary {
    background: var(--primary-blue);
    color: white;
}

.btn-primary:hover {
    background: #0F1F7A;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
}

.btn-outline-danger {
    background: white;
    color: #dc3545;
    border: 1px solid #dc3545;
}

.btn-outline-danger:hover {
    background: #dc3545;
    color: white;
}

.btn-outline-primary {
    background: white;
    color: var(--primary-blue);
    border: 1px solid var(--primary-blue);
}

.btn-outline-primary:hover {
    background: var(--primary-blue);
    color: white;
}

.btn-lg {
    padding: 12px 30px;
    font-size: 16px;
}

.btn-delete {
    padding: 6px 12px;
    background: white;
    border: 1px solid #dc3545;
    color: #dc3545;
    font-size: 13px;
    border-radius: 4px;
}

.btn-delete:hover {
    background: #dc3545;
    color: white;
}

input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* 제출된 견적서 현황 스타일 */
.submitted-quotes-section {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.submitted-quotes-section h3 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
}

.submitted-quotes-table {
    width: 100%;
    background: white;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.submitted-quotes-table th {
    background: #f1f3f5;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
    color: #495057;
}

.submitted-quotes-table td {
    padding: 12px;
    border-bottom: 1px solid #e9ecef;
    font-size: 14px;
}

.submitted-quotes-table tbody tr:last-child td {
    border-bottom: none;
}

.submitted-quotes-table tbody tr:hover {
    background: #f8f9fa;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #cfe2ff;
    color: #084298;
}

.status-completed {
    background: #d1e7dd;
    color: #0f5132;
}

.status-cancelled {
    background: #f8d7da;
    color: #842029;
}

.admin-note {
    color: #6c757d;
    font-size: 12px;
    cursor: help;
}

@media (max-width: 768px) {
    .cart-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .data-table {
        font-size: 14px;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px;
    }
    
    .quantity-input {
        width: 60px;
    }
}
</style>

<script>
let cartItems = [];
let selectedItems = new Set();

// 페이지 로드 시 카트 아이템 표시
document.addEventListener('DOMContentLoaded', function() {
    loadCartItems();
});

// 카트 아이템 로드
function loadCartItems() {
    cartItems = JSON.parse(sessionStorage.getItem('quoteCart') || '[]');
    
    if (cartItems.length === 0) {
        document.getElementById('noItemsMessage').style.display = 'block';
        document.getElementById('cartContent').style.display = 'none';
    } else {
        document.getElementById('noItemsMessage').style.display = 'none';
        document.getElementById('cartContent').style.display = 'block';
        displayCartItems();
        updateSummary();
    }
}

// 카트 아이템 표시
function displayCartItems() {
    const tbody = document.getElementById('cartItems');
    const totalItemsSpan = document.getElementById('totalItems');
    
    tbody.innerHTML = '';
    let totalQuantity = 0;
    
    cartItems.forEach((item, index) => {
        totalQuantity += item.quantity;
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="text-align: center;">
                <input type="checkbox" data-index="${index}" onchange="toggleItemSelection(${index})">
            </td>
            <td style="text-align: center;">${index + 1}</td>
            <td>${item.name}</td>
            <td>${item.specifications || '-'}</td>
            <td style="text-align: center;">
                <input type="number" class="quantity-input" value="${item.quantity}" min="1" 
                       onchange="updateQuantity(${index}, this.value)">
            </td>
            <td style="text-align: center;">
                <button type="button" class="btn-delete" onclick="removeItem(${index})">삭제</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    totalItemsSpan.textContent = `총 ${cartItems.length}개 제품`;
}

// 수량 업데이트
function updateQuantity(index, newQuantity) {
    const quantity = parseInt(newQuantity) || 1;
    cartItems[index].quantity = quantity;
    sessionStorage.setItem('quoteCart', JSON.stringify(cartItems));
    
    // 카트 카운트 업데이트
    updateCartCount();
    displayCartItems();
    updateSummary();
}

// 아이템 삭제
function removeItem(index) {
    if (confirm('이 제품을 견적서에서 제거하시겠습니까?')) {
        cartItems.splice(index, 1);
        selectedItems.delete(index);
        sessionStorage.setItem('quoteCart', JSON.stringify(cartItems));
        
        // 카트 카운트 업데이트
        updateCartCount();
        loadCartItems();
    }
}

// 전체 삭제
function clearAllItems() {
    if (confirm('모든 제품을 견적서에서 제거하시겠습니까?')) {
        sessionStorage.removeItem('quoteCart');
        cartItems = [];
        selectedItems.clear();
        
        // 카트 카운트 업데이트
        updateCartCount();
        loadCartItems();
    }
}

// 전체 선택 토글
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('#cartItems input[type="checkbox"]');
    
    selectedItems.clear();
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        if (selectAll.checked) {
            selectedItems.add(parseInt(checkbox.dataset.index));
        }
    });
    
    updateSummary();
}

// 개별 아이템 선택 토글
function toggleItemSelection(index) {
    const checkbox = document.querySelector(`input[data-index="${index}"]`);
    
    if (checkbox.checked) {
        selectedItems.add(index);
    } else {
        selectedItems.delete(index);
    }
    
    // 전체 선택 체크박스 상태 업데이트
    const selectAll = document.getElementById('selectAll');
    const allCheckboxes = document.querySelectorAll('#cartItems input[type="checkbox"]');
    selectAll.checked = selectedItems.size === allCheckboxes.length && allCheckboxes.length > 0;
    
    updateSummary();
}

// 선택된 제품 요약 업데이트
function updateSummary() {
    const selectedCountEl = document.getElementById('selectedCount');
    const selectedQuantityEl = document.getElementById('selectedQuantity');
    
    let totalQuantity = 0;
    
    selectedItems.forEach(index => {
        const item = cartItems[index];
        totalQuantity += item.quantity;
    });
    
    selectedCountEl.textContent = selectedItems.size;
    selectedQuantityEl.textContent = totalQuantity;
}

// 견적 요청
function requestQuote() {
    if (selectedItems.size === 0) {
        alert('견적을 요청할 제품을 선택해주세요.');
        return;
    }
    
    // 선택된 제품만 필터링
    const selectedProducts = Array.from(selectedItems).map(index => cartItems[index]);
    
    // 선택된 제품 정보를 세션 스토리지에 저장
    sessionStorage.setItem('selectedQuoteItems', JSON.stringify(selectedProducts));
    
    // 견적 요청 페이지로 이동
    window.location.href = 'product_quote_form.php';
}

// 카트 카운트 업데이트 (헤더)
function updateCartCount() {
    const quoteCart = JSON.parse(sessionStorage.getItem('quoteCart') || '[]');
    const cartCount = quoteCart.reduce((sum, item) => sum + item.quantity, 0);
    
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = cartCount;
        cartCountElement.style.display = cartCount > 0 ? 'flex' : 'none';
    }
}

// PDF 다운로드
function downloadPDF() {
    if (cartItems.length === 0) {
        alert('견적서가 비어있습니다.');
        return;
    }
    
    // 선택된 제품이 있으면 선택된 것만, 없으면 전체
    let itemsToExport = [];
    if (selectedItems.size > 0) {
        itemsToExport = Array.from(selectedItems).map(index => cartItems[index]);
    } else {
        itemsToExport = cartItems;
    }
    
    // PDF 생성 요청
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'ajax/generate_quote_pdf.php';
    form.target = '_blank';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'items';
    input.value = JSON.stringify(itemsToExport);
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>

<?php 
endSubPage();
include 'tail.php'; 
?>