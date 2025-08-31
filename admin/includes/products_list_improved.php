<?php
// 제품 목록 include 파일
// 검색 필터
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// 제품 목록 쿼리
$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(p.product_name LIKE ? OR p.specifications LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where[] = "p.category_code = ?";
    $params[] = $category_filter;
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
    LEFT JOIN unit_weights uw ON p.specifications = uw.specification
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

<!-- 액션 바 -->
<div class="action-bar">
    <form method="GET" action="" class="search-form">
        <input type="hidden" name="tab" value="products">
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
        <input type="text" name="search" placeholder="제품명, 규격으로 검색..." 
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary">검색</button>
    </form>
    
    <div>
        <a href="products_export.php?category=<?php echo urlencode($category_filter); ?>" 
           class="btn btn-success">
            📥 엑셀 다운로드
        </a>
        <a href="products_import.php" 
           class="btn btn-info">
            📤 엑셀 업로드
        </a>
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
                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($product['specifications']); ?></td>
                    <td>
                        <span class="badge badge-info"><?php echo htmlspecialchars($product['category_name']); ?></span>
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
    
    const bulkActions = document.getElementById('bulkActions');
    if (count > 0) {
        bulkActions.classList.add('show');
    } else {
        bulkActions.classList.remove('show');
    }
}

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
    }
    
    if (confirm(confirmMsg)) {
        document.getElementById('formAction').value = action;
        document.getElementById('productsForm').submit();
    }
}
</script>