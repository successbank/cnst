<?php
require_once 'member_check.php';
require_once 'db.php';
require_once 'includes/sub_layout.php';

// 로그인 체크
checkLogin();

$member_id = $_SESSION['member_id'] ?? '';

// 회원 정보 가져오기
$member_info = [];
$member_addresses = [];
if ($member_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $member_info = $stmt->fetch() ?: [];
        
        // 회원 주소록 가져오기
        $stmt = $pdo->prepare("SELECT * FROM member_addresses WHERE member_id = ? ORDER BY is_default DESC, id DESC");
        $stmt->execute([$member_id]);
        $member_addresses = $stmt->fetchAll() ?: [];
        
        // 회원 정보에 주소가 있지만 주소록이 비어있는 경우, 자동으로 주소록에 추가
        if (empty($member_addresses) && !empty($member_info['zipcode']) && !empty($member_info['address'])) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO member_addresses (member_id, address_name, recipient_name, recipient_phone, zipcode, address, address_detail, is_default) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $member_id,
                    '기본주소',
                    $member_info['name'],
                    $member_info['phone'],
                    $member_info['zipcode'],
                    $member_info['address'],
                    $member_info['address_detail']
                ]);
                
                // 다시 주소록 가져오기
                $stmt = $pdo->prepare("SELECT * FROM member_addresses WHERE member_id = ? ORDER BY is_default DESC, id DESC");
                $stmt->execute([$member_id]);
                $member_addresses = $stmt->fetchAll() ?: [];
            } catch(PDOException $e) {
                // 에러 무시
            }
        }
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
                
                <div class="form-group">
                    <label for="address">배송지 주소</label>
                    
                    <!-- 주소 표시 영역 -->
                    <div id="selected_address_display" style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 10px;">
                        <?php
                        $default_address = null;
                        foreach ($member_addresses as $addr) {
                            if ($addr['is_default']) {
                                $default_address = $addr;
                                break;
                            }
                        }
                        if ($default_address): ?>
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <div style="font-weight: 600; margin-bottom: 5px;">
                                        <?php echo htmlspecialchars($default_address['address_name'] ?? '기본주소'); ?>
                                    </div>
                                    <div style="color: #666; font-size: 14px;">
                                        (<?php echo htmlspecialchars($default_address['zipcode']); ?>) 
                                        <?php echo htmlspecialchars($default_address['address']); ?>
                                        <?php echo htmlspecialchars($default_address['address_detail'] ?? ''); ?>
                                    </div>
                                </div>
                                <button type="button" onclick="showAddressModal()" class="btn btn-outline">변경</button>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; color: #999;">
                                <p style="margin-bottom: 10px;">배송지를 선택해주세요</p>
                                <button type="button" onclick="showAddressModal()" class="btn btn-primary">배송지 선택</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Hidden inputs for address -->
                    <input type="hidden" id="zipcode" name="zipcode" value="<?php echo htmlspecialchars($default_address['zipcode'] ?? ''); ?>">
                    <input type="hidden" id="address" name="address" value="<?php echo htmlspecialchars($default_address['address'] ?? ''); ?>">
                    <input type="hidden" id="address_detail" name="address_detail" value="<?php echo htmlspecialchars($default_address['address_detail'] ?? ''); ?>">
                    <input type="hidden" id="selected_address_id" name="selected_address_id" value="<?php echo $default_address['id'] ?? ''; ?>">
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

<!-- 배송지 선택 모달 -->
<div id="addressModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>배송지 선택</h2>
            <span class="modal-close" onclick="closeAddressModal()">&times;</span>
        </div>
        <div class="modal-body">
            <!-- 저장된 주소 목록 -->
            <div class="address-list">
                <?php if (!empty($member_addresses)): ?>
                    <?php foreach ($member_addresses as $addr): ?>
                    <div class="address-item <?php echo $addr['is_default'] ? 'default' : ''; ?>" 
                         data-id="<?php echo $addr['id']; ?>"
                         data-zipcode="<?php echo htmlspecialchars($addr['zipcode']); ?>"
                         data-address="<?php echo htmlspecialchars($addr['address']); ?>"
                         data-address-detail="<?php echo htmlspecialchars($addr['address_detail'] ?? ''); ?>"
                         data-name="<?php echo htmlspecialchars($addr['address_name'] ?? '기본주소'); ?>">
                        <div class="address-radio">
                            <input type="radio" name="modal_address" value="<?php echo $addr['id']; ?>" 
                                   id="addr_<?php echo $addr['id']; ?>"
                                   <?php echo ($addr['is_default'] || $addr['id'] == ($default_address['id'] ?? 0)) ? 'checked' : ''; ?>>
                        </div>
                        <label for="addr_<?php echo $addr['id']; ?>" class="address-content">
                            <div class="address-name">
                                <?php echo htmlspecialchars($addr['address_name'] ?? '기본주소'); ?>
                                <?php if ($addr['is_default']): ?>
                                <span class="default-badge">기본</span>
                                <?php endif; ?>
                            </div>
                            <div class="address-info">
                                (<?php echo htmlspecialchars($addr['zipcode']); ?>) 
                                <?php echo htmlspecialchars($addr['address']); ?>
                                <?php echo htmlspecialchars($addr['address_detail'] ?? ''); ?>
                            </div>
                        </label>
                        <div class="address-actions">
                            <button type="button" onclick="editAddress(<?php echo $addr['id']; ?>)" class="btn-small btn-edit">수정</button>
                            <button type="button" onclick="deleteAddress(<?php echo $addr['id']; ?>)" class="btn-small btn-delete">삭제</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- 새 주소 추가 버튼 -->
            <div class="add-address-section">
                <button type="button" onclick="showNewAddressForm()" class="btn btn-outline" style="width: 100%;">
                    + 새 배송지 추가
                </button>
            </div>
            
            <!-- 새 주소 입력 폼 (숨김) -->
            <div id="newAddressForm" style="display: none;" class="new-address-form">
                <h3>새 배송지 추가</h3>
                <div class="form-group">
                    <label>주소 별칭</label>
                    <input type="text" id="new_address_name" placeholder="예: 본사, 공장, 창고">
                </div>
                <div class="form-group">
                    <label>수령인명</label>
                    <input type="text" id="new_recipient_name" value="<?php echo htmlspecialchars($member_info['name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>연락처</label>
                    <input type="text" id="new_recipient_phone" value="<?php echo htmlspecialchars($member_info['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>주소</label>
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="text" id="new_zipcode" placeholder="우편번호" style="width: 120px;" readonly>
                        <button type="button" onclick="findNewZipcode()" class="btn btn-outline">우편번호 찾기</button>
                    </div>
                    <input type="text" id="new_address" placeholder="기본주소" style="margin-bottom: 10px;" readonly>
                    <input type="text" id="new_address_detail" placeholder="상세주소">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="new_is_default"> 기본 배송지로 설정
                    </label>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="saveNewAddress()" class="btn btn-primary">저장</button>
                    <button type="button" onclick="cancelNewAddress()" class="btn btn-outline">취소</button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeAddressModal()" class="btn btn-secondary">취소</button>
            <button type="button" onclick="applySelectedAddress()" class="btn btn-primary">선택 완료</button>
        </div>
    </div>
</div>

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

.btn-outline {
    background: white;
    color: var(--primary-blue);
    border: 1px solid var(--primary-blue);
    padding: 8px 16px;
    font-size: 14px;
}

.btn-outline:hover {
    background: var(--primary-blue);
    color: white;
}

/* 모달 스타일 */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e5e5e7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #333;
}

.modal-close {
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
    line-height: 20px;
}

.modal-close:hover {
    color: #000;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e5e7;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.address-list {
    margin-bottom: 20px;
}

.address-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 1px solid #e5e5e7;
    border-radius: 8px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.address-item:hover {
    border-color: var(--primary-blue);
}

.address-item.default {
    border-color: var(--primary-blue);
    background: #f0f4ff;
}

.address-radio {
    margin-right: 15px;
}

.address-content {
    flex: 1;
    cursor: pointer;
}

.address-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.default-badge {
    background: var(--primary-blue);
    color: white;
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 4px;
}

.address-info {
    color: #666;
    font-size: 14px;
}

.address-actions {
    display: flex;
    gap: 5px;
}

.btn-small {
    padding: 4px 10px;
    font-size: 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
}

.btn-edit {
    color: #666;
}

.btn-delete {
    color: #dc3545;
    border-color: #dc3545;
}

.btn-small:hover {
    background: #f5f5f5;
}

.add-address-section {
    margin-top: 20px;
}

.new-address-form {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.new-address-form h3 {
    margin-bottom: 15px;
    color: #333;
    font-size: 18px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .modal-content {
        width: 95%;
        max-height: 90vh;
    }
    
    .address-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .address-radio {
        margin-bottom: 10px;
    }
    
    .address-actions {
        margin-top: 10px;
        width: 100%;
        justify-content: flex-end;
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
        .then(response => response.text())
        .then(text => {
            console.log('Response:', text);
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text:', text);
                throw new Error('서버 응답을 파싱할 수 없습니다.');
            }
        })
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
            alert('견적 요청 중 오류가 발생했습니다. 콘솔을 확인해주세요.');
        });
    });
});
</script>

<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
// 모달 관련 함수들
function showAddressModal() {
    document.getElementById('addressModal').style.display = 'flex';
}

function closeAddressModal() {
    document.getElementById('addressModal').style.display = 'none';
    document.getElementById('newAddressForm').style.display = 'none';
}

function showNewAddressForm() {
    document.getElementById('newAddressForm').style.display = 'block';
    document.querySelector('.address-list').style.display = 'none';
    document.querySelector('.add-address-section').style.display = 'none';
}

function cancelNewAddress() {
    document.getElementById('newAddressForm').style.display = 'none';
    document.querySelector('.address-list').style.display = 'block';
    document.querySelector('.add-address-section').style.display = 'block';
    
    // 폼 초기화
    document.getElementById('new_address_name').value = '';
    document.getElementById('new_recipient_name').value = '';
    document.getElementById('new_recipient_phone').value = '';
    document.getElementById('new_zipcode').value = '';
    document.getElementById('new_address').value = '';
    document.getElementById('new_address_detail').value = '';
    document.getElementById('new_is_default').checked = false;
    
    // 폼 제목과 버튼 원래대로 복원
    document.querySelector('#newAddressForm h3').textContent = '새 배송지 추가';
    const saveButton = document.querySelector('#newAddressForm button[onclick^="updateAddress"]');
    if (saveButton) {
        saveButton.setAttribute('onclick', 'saveNewAddress()');
        saveButton.textContent = '저장';
    }
}

function applySelectedAddress() {
    const selectedRadio = document.querySelector('input[name="modal_address"]:checked');
    if (!selectedRadio) {
        alert('배송지를 선택해주세요.');
        return;
    }
    
    const addressItem = selectedRadio.closest('.address-item');
    if (!addressItem) {
        alert('주소 정보를 찾을 수 없습니다.');
        return;
    }
    
    // 디버깅을 위한 로그
    console.log('Address Item:', addressItem);
    console.log('Dataset:', addressItem.dataset);
    
    const addressData = {
        id: addressItem.dataset.id || addressItem.getAttribute('data-id'),
        name: addressItem.dataset.name || addressItem.getAttribute('data-name'),
        zipcode: addressItem.dataset.zipcode || addressItem.getAttribute('data-zipcode'),
        address: addressItem.dataset.address || addressItem.getAttribute('data-address'),
        address_detail: addressItem.dataset.addressDetail || addressItem.getAttribute('data-address-detail') || ''
    };
    
    // 데이터 검증
    if (!addressData.zipcode || !addressData.address) {
        alert('주소 정보가 올바르지 않습니다.');
        return;
    }
    
    // Hidden inputs 업데이트
    document.getElementById('selected_address_id').value = addressData.id;
    document.getElementById('zipcode').value = addressData.zipcode;
    document.getElementById('address').value = addressData.address;
    document.getElementById('address_detail').value = addressData.address_detail;
    
    // 화면에 표시
    document.getElementById('selected_address_display').innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <div style="font-weight: 600; margin-bottom: 5px;">
                    ${addressData.name}
                </div>
                <div style="color: #666; font-size: 14px;">
                    (${addressData.zipcode}) ${addressData.address} ${addressData.address_detail}
                </div>
            </div>
            <button type="button" onclick="showAddressModal()" class="btn btn-outline">변경</button>
        </div>
    `;
    
    closeAddressModal();
}

// 새 주소 저장
function saveNewAddress() {
    const addressName = document.getElementById('new_address_name').value.trim();
    const recipientName = document.getElementById('new_recipient_name').value.trim();
    const recipientPhone = document.getElementById('new_recipient_phone').value.trim();
    const zipcode = document.getElementById('new_zipcode').value.trim();
    const address = document.getElementById('new_address').value.trim();
    const addressDetail = document.getElementById('new_address_detail').value.trim();
    const isDefault = document.getElementById('new_is_default').checked;
    
    if (!addressName || !zipcode || !address) {
        alert('필수 항목을 입력해주세요.');
        return;
    }
    
    // AJAX로 저장
    const formData = new FormData();
    formData.append('action', 'save_address');
    formData.append('address_name', addressName);
    formData.append('recipient_name', recipientName);
    formData.append('recipient_phone', recipientPhone);
    formData.append('zipcode', zipcode);
    formData.append('address', address);
    formData.append('address_detail', addressDetail);
    formData.append('is_default', isDefault ? '1' : '0');
    
    fetch('ajax/save_address.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('배송지가 추가되었습니다.');
            
            // 새로 추가된 주소를 바로 적용
            const newAddressData = {
                id: data.address_id,
                name: addressName,
                zipcode: zipcode,
                address: address,
                address_detail: addressDetail
            };
            
            // Hidden inputs 업데이트
            document.getElementById('selected_address_id').value = newAddressData.id;
            document.getElementById('zipcode').value = newAddressData.zipcode;
            document.getElementById('address').value = newAddressData.address;
            document.getElementById('address_detail').value = newAddressData.address_detail;
            
            // 화면에 표시
            document.getElementById('selected_address_display').innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <div style="font-weight: 600; margin-bottom: 5px;">
                            ${newAddressData.name}
                        </div>
                        <div style="color: #666; font-size: 14px;">
                            (${newAddressData.zipcode}) ${newAddressData.address} ${newAddressData.address_detail}
                        </div>
                    </div>
                    <button type="button" onclick="showAddressModal()" class="btn btn-outline">변경</button>
                </div>
            `;
            
            // 모달 닫기
            closeAddressModal();
            
            // 나중에 페이지 새로고침이 필요한 경우를 위해 플래그 설정
            // location.reload();
        } else {
            alert(data.message || '저장 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('저장 중 오류가 발생했습니다.');
    });
}

// 주소 삭제
function deleteAddress(addressId) {
    if (!confirm('이 배송지를 삭제하시겠습니까?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_address');
    formData.append('address_id', addressId);
    
    fetch('ajax/save_address.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('배송지가 삭제되었습니다.');
            location.reload();
        } else {
            alert(data.message || '삭제 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('삭제 중 오류가 발생했습니다.');
    });
}

// 주소 수정
function editAddress(addressId) {
    // 주소 정보 가져오기
    const addressItem = document.querySelector(`.address-item[data-id="${addressId}"]`);
    if (!addressItem) {
        alert('주소 정보를 찾을 수 없습니다.');
        return;
    }
    
    // 기존 주소 정보 추출
    const addressData = {
        id: addressItem.dataset.id,
        name: addressItem.dataset.name,
        recipientName: addressItem.querySelector('.address-info').textContent.includes('수령인:') ? 
            addressItem.querySelector('.address-info').textContent.match(/수령인: ([^\n]+)/)?.[1]?.trim() : '',
        recipientPhone: addressItem.querySelector('.address-info').textContent.includes('연락처:') ? 
            addressItem.querySelector('.address-info').textContent.match(/연락처: ([^\n]+)/)?.[1]?.trim() : '',
        zipcode: addressItem.dataset.zipcode,
        address: addressItem.dataset.address,
        addressDetail: addressItem.dataset.addressDetail || ''
    };
    
    // 수정 폼 표시
    showEditAddressForm(addressData);
}

// 수정 폼 표시
function showEditAddressForm(addressData) {
    // 새 주소 추가 폼을 수정 폼으로 사용
    document.getElementById('newAddressForm').style.display = 'block';
    document.querySelector('.address-list').style.display = 'none';
    document.querySelector('.add-address-section').style.display = 'none';
    
    // 폼 제목 변경
    document.querySelector('#newAddressForm h3').textContent = '배송지 수정';
    
    // 폼에 기존 데이터 채우기
    document.getElementById('new_address_name').value = addressData.name;
    document.getElementById('new_recipient_name').value = addressData.recipientName || '';
    document.getElementById('new_recipient_phone').value = addressData.recipientPhone || '';
    document.getElementById('new_zipcode').value = addressData.zipcode;
    document.getElementById('new_address').value = addressData.address;
    document.getElementById('new_address_detail').value = addressData.addressDetail;
    
    // 저장 버튼 동작 변경
    const saveButton = document.querySelector('#newAddressForm button[onclick="saveNewAddress()"]');
    saveButton.setAttribute('onclick', `updateAddress(${addressData.id})`);
    saveButton.textContent = '수정';
}

// 주소 업데이트
function updateAddress(addressId) {
    const addressName = document.getElementById('new_address_name').value.trim();
    const recipientName = document.getElementById('new_recipient_name').value.trim();
    const recipientPhone = document.getElementById('new_recipient_phone').value.trim();
    const zipcode = document.getElementById('new_zipcode').value.trim();
    const address = document.getElementById('new_address').value.trim();
    const addressDetail = document.getElementById('new_address_detail').value.trim();
    const isDefault = document.getElementById('new_is_default').checked;
    
    if (!addressName || !zipcode || !address) {
        alert('필수 항목을 입력해주세요.');
        return;
    }
    
    // AJAX로 수정
    const formData = new FormData();
    formData.append('action', 'update_address');
    formData.append('address_id', addressId);
    formData.append('address_name', addressName);
    formData.append('recipient_name', recipientName);
    formData.append('recipient_phone', recipientPhone);
    formData.append('zipcode', zipcode);
    formData.append('address', address);
    formData.append('address_detail', addressDetail);
    formData.append('is_default', isDefault ? '1' : '0');
    
    fetch('ajax/save_address.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('배송지가 수정되었습니다.');
            location.reload();
        } else {
            alert(data.message || '수정 중 오류가 발생했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('수정 중 오류가 발생했습니다.');
    });
}

// 새 주소 우편번호 찾기
function findNewZipcode() {
    new daum.Postcode({
        oncomplete: function(data) {
            var roadAddr = data.roadAddress;
            var extraRoadAddr = '';

            if(data.bname !== '' && /[동|로|가]$/g.test(data.bname)){
                extraRoadAddr += data.bname;
            }
            if(data.buildingName !== '' && data.apartment === 'Y'){
               extraRoadAddr += (extraRoadAddr !== '' ? ', ' + data.buildingName : data.buildingName);
            }
            if(extraRoadAddr !== ''){
                extraRoadAddr = ' (' + extraRoadAddr + ')';
            }

            document.getElementById('new_zipcode').value = data.zonecode;
            document.getElementById("new_address").value = roadAddr;
            document.getElementById("new_address_detail").focus();
        }
    }).open();
}

function findZipcode() {
    // 저장된 주소 선택 해제
    if (savedAddressSelect) {
        savedAddressSelect.value = '';
    }
    // 주소 저장 체크박스 표시
    document.getElementById('save_address').checked = true;
    document.getElementById('address_name').style.display = 'inline-block';
    
    new daum.Postcode({
        oncomplete: function(data) {
            // 팝업에서 검색결과 항목을 클릭했을때 실행할 코드를 작성하는 부분.

            // 도로명 주소의 노출 규칙에 따라 주소를 표시한다.
            // 내려오는 변수가 값이 없는 경우엔 공백('')값을 가지므로, 이를 참고하여 분기 한다.
            var roadAddr = data.roadAddress; // 도로명 주소 변수
            var extraRoadAddr = ''; // 참고 항목 변수

            // 법정동명이 있을 경우 추가한다. (법정리는 제외)
            // 법정동의 경우 마지막 문자가 "동/로/가"로 끝난다.
            if(data.bname !== '' && /[동|로|가]$/g.test(data.bname)){
                extraRoadAddr += data.bname;
            }
            // 건물명이 있고, 공동주택일 경우 추가한다.
            if(data.buildingName !== '' && data.apartment === 'Y'){
               extraRoadAddr += (extraRoadAddr !== '' ? ', ' + data.buildingName : data.buildingName);
            }
            // 표시할 참고항목이 있을 경우, 괄호까지 추가한 최종 문자열을 만든다.
            if(extraRoadAddr !== ''){
                extraRoadAddr = ' (' + extraRoadAddr + ')';
            }

            // 우편번호와 주소 정보를 해당 필드에 넣는다.
            document.getElementById('zipcode').value = data.zonecode;
            document.getElementById("address").value = roadAddr;
            
            // 커서를 상세주소 필드로 이동한다.
            document.getElementById("address_detail").focus();
        }
    }).open();
}

// 주소록 관리 팝업
function showAddressManager() {
    window.open('address_manager.php', 'addressManager', 'width=800,height=600,scrollbars=yes');
}

// 주소록에서 선택한 주소 적용 (팝업에서 호출) - 레거시 함수
function applyAddressFromPopup(addressData) {
    document.getElementById('zipcode').value = addressData.zipcode;
    document.getElementById('address').value = addressData.address;
    document.getElementById('address_detail').value = addressData.address_detail || '';
    
    // 저장된 주소 선택 업데이트
    if (typeof savedAddressSelect !== 'undefined' && savedAddressSelect) {
        savedAddressSelect.value = addressData.id;
    }
}
</script>

<?php 
endSubPage();
include 'tail.php'; 
?>