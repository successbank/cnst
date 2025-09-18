<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 검색 필터
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// 일괄 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $selected_ids = $_POST['selected'] ?? [];
    
    if (!empty($selected_ids)) {
        $ids_placeholder = str_repeat('?,', count($selected_ids) - 1) . '?';
        
        try {
            switch ($_POST['action']) {
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($ids_placeholder)");
                    $stmt->execute($selected_ids);
                    header("Location: admin_products.php?message=bulk_deleted");
                    exit;
                    break;
                    
                case 'activate':
                    $stmt = $pdo->prepare("UPDATE products SET is_active = 1 WHERE id IN ($ids_placeholder)");
                    $stmt->execute($selected_ids);
                    header("Location: admin_products.php?message=bulk_activated");
                    exit;
                    break;
                    
                case 'deactivate':
                    $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id IN ($ids_placeholder)");
                    $stmt->execute($selected_ids);
                    header("Location: admin_products.php?message=bulk_deactivated");
                    exit;
                    break;
            }
        } catch(PDOException $e) {
            $error = "일괄 처리 중 오류가 발생했습니다.";
        }
    } else {
        $error = "선택된 제품이 없습니다.";
    }
}

// 제품 삭제 처리
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin_products.php?message=deleted");
        exit;
    } catch(PDOException $e) {
        $error = "제품 삭제 중 오류가 발생했습니다.";
    }
}

// 제품 활성화/비활성화 처리
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin_products.php?message=updated");
        exit;
    } catch(PDOException $e) {
        $error = "상태 변경 중 오류가 발생했습니다.";
    }
}

// 카테고리 목록 가져오기
$stmt = $pdo->query("SELECT * FROM product_categories ORDER BY display_order");
$categories = $stmt->fetchAll();

// 제품 목록 쿼리 구성
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
    CONCAT(p.product_name, IF(p.specification IS NOT NULL AND p.specification != '', CONCAT(' ', p.specification), IF(p.specifications IS NOT NULL AND p.specifications != '', CONCAT(' ', p.specifications), ''))) AS display_name
    FROM products p
    JOIN product_categories pc ON p.category_code = pc.category_code
    WHERE $whereClause
    ORDER BY p.id DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// 페이지네이션
$totalPages = ceil($totalCount / $perPage);

include 'admin_head.php';
?>

<style>
.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.products-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

.btn-add {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: #28a745;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-add:hover {
    background: #218838;
    transform: translateY(-1px);
}

.filters {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.search-box {
    flex: 1;
    min-width: 300px;
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 10px 40px 10px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.search-box button {
    position: absolute;
    right: 5px;
    top: 5px;
    padding: 6px 12px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.filter-select {
    padding: 10px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.products-table {
    width: 100%;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.products-table th {
    background: #f8f9fa;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #dee2e6;
}

.products-table td {
    padding: 16px;
    border-bottom: 1px solid #f0f0f0;
}

.products-table tr:hover {
    background: #f8f9fa;
}

.product-image {
    width: 60px;
    height: 60px;
    background: #f5f5f5;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #999;
}

.product-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.product-specs {
    font-size: 13px;
    color: #666;
}

.category-badge {
    display: inline-block;
    padding: 4px 12px;
    background: #e3f2fd;
    color: #1976d2;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.stock-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.stock-status.in_stock {
    background: #d4edda;
    color: #155724;
}

.stock-status.out_of_stock {
    background: #f8d7da;
    color: #721c24;
}

.stock-status.on_order {
    background: #fff3cd;
    color: #856404;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-action {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-edit {
    background: #ffc107;
    color: #000;
}

.btn-edit:hover {
    background: #e0a800;
}

.btn-toggle {
    background: #17a2b8;
    color: white;
}

.btn-toggle:hover {
    background: #138496;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-state h3 {
    font-size: 20px;
    margin-bottom: 10px;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 30px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background: #f8f9fa;
}

.pagination .current {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.alert {
    padding: 12px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.bulk-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.bulk-actions select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.bulk-actions button {
    padding: 8px 16px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.bulk-actions button:hover {
    background: #5a6268;
}

.checkbox-cell {
    width: 40px;
    text-align: center;
}

.checkbox-cell input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.selected-count {
    font-size: 14px;
    color: #666;
    margin-left: 10px;
}

.category-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.category-tab {
    padding: 12px 20px;
    color: #666;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.category-tab:hover {
    color: #007bff;
    background: #f8f9fa;
}

.category-tab.active {
    color: #007bff;
    border-bottom-color: #007bff;
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .category-tabs {
        margin-left: -20px;
        margin-right: -20px;
        padding: 0 20px;
        border-radius: 0;
    }
}
</style>

<div class="products-header">
    <h2>제품 관리</h2>
    <a href="admin_products_edit.php" class="btn-add">
        <i class="fas fa-plus"></i> 새 제품 추가
    </a>
</div>

<?php if (isset($_GET['message'])): ?>
    <?php if ($_GET['message'] == 'deleted'): ?>
        <div class="alert success">제품이 성공적으로 삭제되었습니다.</div>
    <?php elseif ($_GET['message'] == 'updated'): ?>
        <div class="alert success">제품 상태가 변경되었습니다.</div>
    <?php elseif ($_GET['message'] == 'saved'): ?>
        <div class="alert success">제품이 저장되었습니다.</div>
    <?php elseif ($_GET['message'] == 'bulk_deleted'): ?>
        <div class="alert success">선택한 제품이 삭제되었습니다.</div>
    <?php elseif ($_GET['message'] == 'bulk_activated'): ?>
        <div class="alert success">선택한 제품이 활성화되었습니다.</div>
    <?php elseif ($_GET['message'] == 'bulk_deactivated'): ?>
        <div class="alert success">선택한 제품이 비활성화되었습니다.</div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert error"><?php echo $error; ?></div>
<?php endif; ?>

<!-- 카테고리별 서브메뉴 -->
<div class="category-tabs">
    <a href="?category=" class="category-tab <?php echo $category_filter === '' ? 'active' : ''; ?>">
        전체 (<?php 
            $stmt = $pdo->query("SELECT COUNT(*) FROM products");
            echo $stmt->fetchColumn();
        ?>)
    </a>
    <?php foreach ($categories as $category): ?>
        <?php 
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_code = ?");
            $stmt->execute([$category['category_code']]);
            $count = $stmt->fetchColumn();
        ?>
        <a href="?category=<?php echo $category['category_code']; ?>" 
           class="category-tab <?php echo $category_filter === $category['category_code'] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($category['category_name'] ?? ''); ?> (<?php echo $count; ?>)
        </a>
    <?php endforeach; ?>
</div>

<form method="GET" action="" class="filters">
    <div class="search-box">
        <input type="text" name="search" placeholder="제품명, 규격, 설명으로 검색..." 
               value="<?php echo htmlspecialchars($search ?? ''); ?>">
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter ?? ''); ?>">
        <button type="submit">검색</button>
    </div>
</form>

<?php if ($products): ?>
<form method="POST" action="" id="bulkForm">
<table class="products-table">
    <thead>
        <tr>
            <th class="checkbox-cell">
                <input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes()">
            </th>
            <th width="80">이미지</th>
            <th>제품정보</th>
            <th width="120">카테고리</th>
            <th width="100">재고상태</th>
            <th width="80">상태</th>
            <th width="80">조회수</th>
            <th width="180">관리</th>
        </tr>
    </thead>
    <tbody>
        <tr class="bulk-actions" style="display: none;" id="bulkActionsRow">
            <td colspan="8">
                <div class="bulk-actions">
                    <select name="action" id="bulkAction">
                        <option value="">일괄 작업 선택</option>
                        <option value="activate">활성화</option>
                        <option value="deactivate">비활성화</option>
                        <option value="delete">삭제</option>
                    </select>
                    <button type="submit" onclick="return confirmBulkAction()">실행</button>
                    <span class="selected-count">선택된 항목: <span id="selectedCount">0</span>개</span>
                </div>
            </td>
        </tr>
        <?php foreach ($products as $product): ?>
        <tr>
            <td class="checkbox-cell">
                <input type="checkbox" name="selected[]" value="<?php echo $product['id']; ?>" 
                       class="product-checkbox" onchange="updateSelectedCount()">
            </td>
            <td>
                <div class="product-image">
                    <?php if ($product['main_image']): ?>
                        <img src="<?php echo htmlspecialchars($product['main_image'] ?? ''); ?>" 
                             style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        📦
                    <?php endif; ?>
                </div>
            </td>
            <td>
                <div class="product-name"><?php echo htmlspecialchars($product['display_name'] ?? $product['product_name'] ?? ''); ?></div>
                <div class="product-specs"><?php echo htmlspecialchars($product['specifications'] ?? ''); ?></div>
            </td>
            <td>
                <span class="category-badge"><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></span>
            </td>
            <td>
                <span class="stock-status <?php echo $product['stock_status']; ?>">
                    <?php 
                    switch($product['stock_status']) {
                        case 'in_stock': echo '재고있음'; break;
                        case 'out_of_stock': echo '재고없음'; break;
                        case 'on_order': echo '주문가능'; break;
                    }
                    ?>
                </span>
            </td>
            <td>
                <span class="status-badge <?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                    <?php echo $product['is_active'] ? '활성' : '비활성'; ?>
                </span>
            </td>
            <td><?php echo number_format($product['view_count']); ?></td>
            <td>
                <div class="action-buttons">
                    <a href="admin_products_edit.php?id=<?php echo $product['id']; ?>" 
                       class="btn-action btn-edit">수정</a>
                    <a href="?toggle=1&id=<?php echo $product['id']; ?>" 
                       class="btn-action btn-toggle">
                        <?php echo $product['is_active'] ? '비활성화' : '활성화'; ?>
                    </a>
                    <a href="?delete=1&id=<?php echo $product['id']; ?>" 
                       class="btn-action btn-delete"
                       onclick="return confirm('정말 삭제하시겠습니까?')">삭제</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</form>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>">처음</a>
        <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>">이전</a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <?php if ($i == $page): ?>
            <span class="current"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>">다음</a>
        <a href="?page=<?php echo $totalPages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $category_filter ? '&category=' . urlencode($category_filter) : ''; ?>">마지막</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php else: ?>
<div class="empty-state">
    <h3>등록된 제품이 없습니다</h3>
    <p>새 제품을 추가해주세요.</p>
</div>
<?php endif; ?>

<script>
function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    
    // 일괄 작업 행 표시/숨김
    const bulkActionsRow = document.getElementById('bulkActionsRow');
    if (count > 0) {
        bulkActionsRow.style.display = 'table-row';
    } else {
        bulkActionsRow.style.display = 'none';
    }
}

function confirmBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    
    if (!action) {
        alert('작업을 선택해주세요.');
        return false;
    }
    
    if (checkboxes.length === 0) {
        alert('선택된 제품이 없습니다.');
        return false;
    }
    
    if (action === 'delete') {
        return confirm('선택한 ' + checkboxes.length + '개의 제품을 정말 삭제하시겠습니까?');
    }
    
    return true;
}
</script>

<?php include 'admin_tail.php'; ?>