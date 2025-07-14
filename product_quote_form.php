<?php
require_once 'member_check.php';
require_once 'db.php';
require_once 'includes/sub_layout.php';

// 로그인 체크
checkLogin();

$member_id = $_SESSION['member_id'] ?? '';

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

$currentPage = 'mypage';
$pageTitle = '제품 견적 요청';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('제품 견적 요청', 'quote_cart');

// 사이드바
myPageSidebar('quote_cart');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>제품 견적 요청</h2>
        <p>선택하신 제품에 대한 견적을 요청합니다.</p>
    </div>
    
    <div class="content-body">
        <form id="quoteForm" method="POST" class="quote-form">
            <!-- 선택된 제품 목록 -->
            <div class="form-section">
                <h3>견적 요청 제품</h3>
                <div id="selectedProducts" class="selected-products">
                    <!-- 자바스크립트로 채워짐 -->
                </div>
            </div>
            
            <!-- 고객 정보 -->
            <div class="form-section">
                <h3>고객 정보</h3>
                
                <div class="form-group">
                    <label for="customer_name">담당자명 <span class="required">*</span></label>
                    <input type="text" id="customer_name" name="customer_name" 
                           value="<?php echo htmlspecialchars($member_info['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company">회사명</label>
                        <input type="text" id="company" name="company" 
                               value="<?php echo htmlspecialchars($member_info['company'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">연락처 <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($member_info['phone'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">이메일</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($member_info['email'] ?? ''); ?>">
                </div>
            </div>
            
            <!-- 추가 요청사항 -->
            <div class="form-section">
                <h3>추가 요청사항</h3>
                <div class="form-group">
                    <textarea id="notes" name="notes" rows="5" 
                              placeholder="납기일, 배송 조건, 특별 요구사항 등을 입력해주세요."></textarea>
                </div>
            </div>
            
            <!-- 제출 버튼 -->
            <div class="form-actions">
                <button type="button" onclick="history.back()" class="btn btn-secondary">이전으로</button>
                <button type="submit" class="btn btn-primary">견적 요청하기</button>
            </div>
        </form>
    </div>
</main>

<style>
.quote-form {
    max-width: 800px;
}

.form-section {
    background: white;
    padding: 30px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e5e7;
    font-size: 18px;
    color: #333;
}

.selected-products {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    max-height: 300px;
    overflow-y: auto;
}

.product-item {
    padding: 10px;
    margin-bottom: 10px;
    background: white;
    border-radius: 4px;
    border: 1px solid #e5e5e7;
}

.product-item:last-child {
    margin-bottom: 0;
}

.product-item-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.product-item-spec {
    color: #666;
    font-size: 14px;
}

.product-item-quantity {
    color: var(--primary-blue);
    font-weight: 500;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-blue);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.required {
    color: #dc3545;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--primary-blue);
    color: white;
}

.btn-primary:hover {
    background: #0F1F7A;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 선택된 제품 가져오기
    const selectedItems = JSON.parse(sessionStorage.getItem('selectedQuoteItems') || '[]');
    
    if (selectedItems.length === 0) {
        alert('선택된 제품이 없습니다.');
        window.location.href = 'my_quote_cart.php';
        return;
    }
    
    // 제품 목록 표시
    const productsDiv = document.getElementById('selectedProducts');
    let productsText = '';
    
    selectedItems.forEach(item => {
        const productHtml = `
            <div class="product-item">
                <div class="product-item-name">${item.name}</div>
                <div class="product-item-spec">규격: ${item.specifications || '-'}</div>
                <div class="product-item-quantity">수량: ${item.quantity}개</div>
            </div>
        `;
        productsDiv.innerHTML += productHtml;
        
        productsText += `${item.name} (${item.specifications || '규격 미정'}) - 수량: ${item.quantity}\n`;
    });
    
    // 폼 제출 처리
    document.getElementById('quoteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('products', productsText.trim());
        formData.append('items', JSON.stringify(selectedItems));
        
        // AJAX로 제출
        fetch('ajax/submit_product_quote.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                
                // 카트에서 선택된 아이템 제거
                if (data.clear_cart) {
                    const quoteCart = JSON.parse(sessionStorage.getItem('quoteCart') || '[]');
                    const remainingItems = quoteCart.filter(item => {
                        return !selectedItems.some(selected => 
                            selected.id === item.id && selected.name === item.name
                        );
                    });
                    
                    sessionStorage.setItem('quoteCart', JSON.stringify(remainingItems));
                    sessionStorage.removeItem('selectedQuoteItems');
                }
                
                // 견적 내역 페이지로 이동
                window.location.href = 'my_inquiries.php';
            } else {
                alert(data.message || '견적 요청 중 오류가 발생했습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('견적 요청 중 오류가 발생했습니다.');
        });
    });
});
</script>

<?php 
endSubPage();
include 'tail.php'; 
?>