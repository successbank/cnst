<?php
// 제품 목록 include 파일
// 검색 필터
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
$homepage_filter = isset($_GET['homepage']) ? $_GET['homepage'] : '';

// 제품 목록 쿼리
$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(p.product_name LIKE ? OR p.specification LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where[] = "p.category_code = ?";
    $params[] = $category_filter;
}

if ($homepage_filter === '1') {
    $where[] = "p.show_on_homepage = 1";
}

$whereClause = implode(" AND ", $where);

// 전체 개수 조회
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereClause");
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();

// 제품 목록 조회
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name,
           uw.unit_weight
    FROM products p
    JOIN product_categories pc ON p.category_code = pc.category_code
    LEFT JOIN unit_weights uw ON p.specification = uw.specification
    WHERE $whereClause 
    ORDER BY p.id DESC 
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$totalPages = ceil($totalCount / $perPage);
?>

<style>
/* 기존 admin_style.css의 변수 사용 */
/* 모든 링크와 버튼 항상 표시 */
a, button, .btn, .category-tab {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.content-card {
    background: var(--admin-white);
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    overflow: hidden;
    margin-bottom: 20px;
}

.card-header {
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.card-body {
    padding: 20px;
}

/* 필터 및 액션 영역 */
.action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.search-form {
    display: flex;
    gap: 10px;
    flex: 1;
    max-width: 400px;
}

/* 드롭다운 스타일 */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-toggle {
    cursor: pointer;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    min-width: 180px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1000;
    margin-top: 4px;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: block;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.2s;
}

.dropdown-item:hover {
    background: #f5f5f5;
}

.dropdown-divider {
    height: 1px;
    background: #e9ecef;
    margin: 5px 0;
}

.action-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.search-form input {
    flex: 1;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.search-form input:focus {
    outline: none;
    border-color: #1428A0;
}

/* 버튼 스타일 */
.btn {
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none !important;
    font-weight: 500;
    transition: background-color 0.2s ease;
    border: none;
    cursor: pointer;
    font-size: 14px;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.btn-primary {
    background: #1428A0 !important;
    color: white !important;
}

.btn-primary:hover {
    background: #0F1F7A !important;
    color: white !important;
}

.btn-success {
    background: #27ae60 !important;
    color: white !important;
}

.btn-success:hover {
    background: #229954 !important;
    color: white !important;
}

.btn-info {
    background: #17a2b8 !important;
    color: white !important;
}

.btn-info:hover {
    background: #138496 !important;
    color: white !important;
}

/* 버튼 간격 */
.admin-table td .btn {
    margin-right: 5px;
}

.admin-table td .btn:last-child {
    margin-right: 0;
}

.btn-warning {
    background: #f39c12 !important;
    color: white !important;
}

.btn-warning:hover {
    background: #e67e22 !important;
    color: white !important;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* 카테고리 탭 */
.category-filter {
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.category-tabs {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.category-tab {
    padding: 8px 16px;
    background: white;
    color: #666 !important;
    text-decoration: none !important;
    border-radius: 4px;
    border: 1px solid #e9ecef;
    font-size: 14px;
    transition: background-color 0.2s ease;
    white-space: nowrap;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.category-tab:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #666 !important;
}

.category-tab.active {
    background: #1428A0 !important;
    color: white !important;
    border-color: #1428A0 !important;
}

.category-tab .count {
    opacity: 0.8;
    font-size: 12px;
}

/* 테이블 스타일 */
.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th {
    background: #f8f9fa;
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    font-size: 14px;
}

.admin-table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
}

.admin-table tr:hover {
    background: #f8f9fa;
}

/* 체크박스 셀 */
.check-cell {
    width: 40px;
    text-align: center;
}

/* 상태 배지 */
.badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-danger {
    background: #f8d7da;
    color: #721c24;
}

.badge-info {
    background: #e3f2fd;
    color: #1565c0;
}

/* 단위중량 배지 */
.weight-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
}

/* 일괄 작업 영역 */
.bulk-actions {
    display: none;
    padding: 15px;
    background: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 4px;
    margin-bottom: 20px;
    align-items: center;
    gap: 15px;
}

.bulk-actions.show {
    display: flex;
}

.bulk-actions select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

/* 페이지네이션 */
.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 30px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    text-decoration: none !important;
    color: #666 !important;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: background-color 0.2s ease;
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.pagination a:hover {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #666 !important;
}

.pagination .active {
    background: #1428A0 !important;
    color: white !important;
    border-color: #1428A0 !important;
}

/* 토글 스위치 */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: #2196F3;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

/* 노출 순서 입력 */
.order-input {
    width: 60px;
    padding: 4px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-align: center;
    font-size: 13px;
}

.order-input:focus {
    outline: none;
    border-color: #2196F3;
}

/* 토스트 알림 */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    background: #333;
    color: white;
    padding: 12px 20px;
    border-radius: 4px;
    margin-bottom: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    animation: slideIn 0.3s ease;
}

.toast.success {
    background: #28a745;
}

.toast.error {
    background: #dc3545;
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

/* 반응형 */
@media (max-width: 768px) {
    .action-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-form {
        max-width: 100%;
    }
    
    .search-form .btn {
        /* 검색 버튼 항상 표시 */
        display: inline-block !important;
        width: auto !important;
        padding: 10px 20px !important;
    }
    
    .category-tabs {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 5px;
    }
    
    .admin-table {
        font-size: 13px;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 10px 8px;
    }
    
    /* 수정 버튼 항상 표시 */
    .admin-table .btn-sm {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    
    /* 모바일에서 일부 컬럼 숨기기 */
    .hide-mobile {
        display: none;
    }
}
</style>

<!-- 토스트 알림 컨테이너 -->
<div class="toast-container" id="toastContainer"></div>

<!-- 액션 바 -->
<div class="action-bar">
    <form method="GET" action="" class="search-form">
        <input type="hidden" name="tab" value="products">
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
        <input type="text" name="search" placeholder="제품명, 규격으로 검색..." 
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary">검색</button>
    </form>
    
    <div class="action-buttons">
        <?php if ($homepage_filter === '1'): ?>
            <a href="?tab=products" class="btn btn-secondary">전체 보기</a>
        <?php else: ?>
            <a href="?tab=products&homepage=1" class="btn btn-warning">메인노출 제품만</a>
        <?php endif; ?>

        <!-- 다운로드 드롭다운 -->
        <div class="dropdown" id="downloadDropdown">
            <button type="button" class="btn btn-success dropdown-toggle" onclick="toggleProductDropdown('downloadDropdown')">
                📥 다운로드 ▾
            </button>
            <div class="dropdown-menu">
                <a href="ajax/products_export_v2.php?category=<?php echo urlencode($category_filter); ?>" class="dropdown-item">
                    전체 다운로드 (v2)
                </a>
                <a href="#" class="dropdown-item" onclick="downloadSelected(); return false;">
                    선택한 제품만 (<span id="downloadSelectedCount">0</span>건)
                </a>
                <div class="dropdown-divider"></div>
                <a href="ajax/products_template_v2.php?category=<?php echo urlencode($category_filter); ?>" class="dropdown-item">
                    빈 템플릿 (v2)
                </a>
                <a href="ajax/products_template_v2.php?category=<?php echo urlencode($category_filter); ?>&sample" class="dropdown-item">
                    샘플 템플릿 (v2)
                </a>
            </div>
        </div>

        <!-- 업로드 버튼 -->
        <button type="button" class="btn btn-info" onclick="ImV2.open()">
            📤 엑셀 업로드
        </button>
    </div>
</div>

<!-- 카테고리 필터 -->
<div class="category-filter">
    <div class="category-tabs">
        <a href="?tab=products" class="category-tab <?php echo $category_filter === '' ? 'active' : ''; ?>">
            전체 <span class="count">(<?php 
                $stmt = $pdo->query("SELECT COUNT(*) FROM products");
                echo $stmt->fetchColumn();
            ?>)</span>
        </a>
        <?php foreach ($categories as $category): ?>
            <?php 
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_code = ?");
                $stmt->execute([$category['category_code']]);
                $count = $stmt->fetchColumn();
            ?>
            <a href="?tab=products&category=<?php echo $category['category_code']; ?>" 
               class="category-tab <?php echo $category_filter === $category['category_code'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($category['category_name']); ?> 
                <span class="count">(<?php echo $count; ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- 일괄 작업 -->
<div class="bulk-actions" id="bulkActions">
    <span>선택한 항목:</span>
    <select id="bulkAction">
        <option value="">작업 선택</option>
        <option value="activate">활성화</option>
        <option value="deactivate">비활성화</option>
        <option value="show_homepage">메인페이지 노출</option>
        <option value="hide_homepage">메인페이지 숨김</option>
        <option value="delete">삭제</option>
    </select>
    <button type="button" onclick="executeBulkAction()" class="btn btn-primary btn-sm">실행</button>
    <span style="margin-left: auto;">선택: <strong id="selectedCount">0</strong>개</span>
</div>

<!-- 제품 목록 -->
<div class="content-card">
    <form method="POST" id="productsForm">
        <input type="hidden" name="action" id="formAction">
        <table class="admin-table">
            <thead>
                <tr>
                    <th class="check-cell">
                        <input type="checkbox" id="selectAll" onchange="toggleAll()">
                    </th>
                    <th style="width: 60px;">번호</th>
                    <th>제품명</th>
                    <th>규격</th>
                    <th>카테고리</th>
                    <th class="hide-mobile">단위중량</th>
                    <th>재고상태</th>
                    <th class="hide-mobile">상태</th>
                    <th class="hide-mobile">메인노출</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $rowNumber = $totalCount - $offset;
                foreach ($products as $product): 
                ?>
                <tr>
                    <td class="check-cell">
                        <input type="checkbox" name="selected[]" value="<?php echo $product['id']; ?>" 
                               onchange="updateSelectedCount()">
                    </td>
                    <td style="text-align: center;"><?php echo $rowNumber--; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($product['product_name'] ?? ''); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($product['specification'] ?? ''); ?></td>
                    <td>
                        <span class="badge badge-info"><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></span>
                    </td>
                    <td class="hide-mobile">
                        <?php if ($product['unit_weight']): ?>
                            <span class="weight-badge"><?php echo number_format($product['unit_weight'], 1); ?> kg/m</span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($product['stock_status'] === 'in_stock'): ?>
                            <span class="badge badge-success">재고있음</span>
                        <?php else: ?>
                            <span class="badge badge-danger">재고없음</span>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile">
                        <?php if ($product['is_active']): ?>
                            <span class="badge badge-success">활성</span>
                        <?php else: ?>
                            <span class="badge badge-danger">비활성</span>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       onchange="toggleHomepage(<?php echo $product['id']; ?>, this.checked, event)"
                                       <?php echo $product['show_on_homepage'] ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                            <?php if ($product['show_on_homepage']): ?>
                            <input type="number" 
                                   class="order-input" 
                                   value="<?php echo $product['homepage_display_order']; ?>"
                                   min="0"
                                   onchange="updateHomepageOrder(<?php echo $product['id']; ?>, this.value)"
                                   placeholder="순서">
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <a href="../product_detail.php?id=<?php echo $product['id']; ?>" 
                           class="btn btn-info btn-sm" target="_blank">보기</a>
                        <a href="admin_products_edit.php?id=<?php echo $product['id']; ?>" 
                           class="btn btn-primary btn-sm">수정</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </form>
</div>

<!-- 페이지네이션 -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?tab=products&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>&page=1">처음</a>
        <a href="?tab=products&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">이전</a>
    <?php endif; ?>
    
    <?php 
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    
    for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i == $page): ?>
            <span class="active"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?tab=products&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="?tab=products&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">다음</a>
        <a href="?tab=products&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $totalPages; ?>">마지막</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function toggleAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="selected[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('input[name="selected[]"]:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;

    // 다운로드 드롭다운의 선택 건수도 업데이트
    const downloadCount = document.getElementById('downloadSelectedCount');
    if (downloadCount) {
        downloadCount.textContent = count;
    }

    const bulkActions = document.getElementById('bulkActions');
    if (count > 0) {
        bulkActions.classList.add('show');
    } else {
        bulkActions.classList.remove('show');
    }
}

// 드롭다운 토글 (제품 페이지용 - admin_head.php의 toggleDropdown과 충돌 방지)
function toggleProductDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    const menu = dropdown.querySelector('.dropdown-menu');
    menu.classList.toggle('show');
}

// 클릭 외부 영역 클릭 시 드롭다운 닫기
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

// 선택한 제품만 다운로드
function downloadSelected() {
    const checkboxes = document.querySelectorAll('input[name="selected[]"]:checked');
    if (checkboxes.length === 0) {
        alert('다운로드할 제품을 선택해주세요.');
        return;
    }

    const ids = Array.from(checkboxes).map(cb => cb.value).join(',');
    const category = '<?php echo htmlspecialchars($category_filter); ?>';
    window.location.href = 'ajax/products_export_v2.php?category=' + encodeURIComponent(category) + '&ids=' + ids;
}

// v2 모달은 ImV2 객체에서 관리 (products_import_modal_v2.php)

function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    if (!action) {
        alert('작업을 선택해주세요.');
        return;
    }
    
    const checkboxes = document.querySelectorAll('input[name="selected[]"]:checked');
    if (checkboxes.length === 0) {
        alert('제품을 선택해주세요.');
        return;
    }
    
    let confirmMsg = '';
    switch(action) {
        case 'delete':
            confirmMsg = '선택한 제품을 삭제하시겠습니까?';
            break;
        case 'activate':
            confirmMsg = '선택한 제품을 활성화하시겠습니까?';
            break;
        case 'deactivate':
            confirmMsg = '선택한 제품을 비활성화하시겠습니까?';
            break;
        case 'show_homepage':
            confirmMsg = '선택한 제품을 메인페이지에 노출하시겠습니까?';
            break;
        case 'hide_homepage':
            confirmMsg = '선택한 제품을 메인페이지에서 숨기시겠습니까?';
            break;
    }
    
    if (confirm(confirmMsg)) {
        document.getElementById('formAction').value = action;
        document.getElementById('productsForm').submit();
    }
}

// 토스트 알림 표시
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    const container = document.getElementById('toastContainer');
    container.appendChild(toast);
    
    // 3초 후 제거
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// 메인페이지 노출 토글
function toggleHomepage(productId, isChecked, event) {
    const orderInput = event.target.closest('td').querySelector('.order-input');
    
    fetch('ajax/toggle_homepage.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&show_on_homepage=${isChecked ? 1 : 0}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            
            // 순서 입력 필드 표시/숨김
            if (isChecked && !orderInput) {
                const div = event.target.closest('div');
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'order-input';
                input.value = '0';
                input.min = '0';
                input.placeholder = '순서';
                input.onchange = function() {
                    updateHomepageOrder(productId, this.value);
                };
                div.appendChild(input);
            } else if (!isChecked && orderInput) {
                orderInput.remove();
            }
        } else {
            showToast(data.error || '오류가 발생했습니다.', 'error');
            // 체크박스 원래 상태로 되돌리기
            event.target.checked = !isChecked;
        }
    })
    .catch(error => {
        showToast('서버 오류가 발생했습니다.', 'error');
        event.target.checked = !isChecked;
    });
}

// 메인페이지 노출 순서 업데이트
function updateHomepageOrder(productId, order) {
    fetch('ajax/update_homepage_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&order=${order}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.error || '오류가 발생했습니다.', 'error');
        }
    })
    .catch(error => {
        showToast('서버 오류가 발생했습니다.', 'error');
    });
}
</script>

<?php
// 업로드 모달 포함 (v2 - 4단계 위자드)
include __DIR__ . '/products_import_modal_v2.php';
?>