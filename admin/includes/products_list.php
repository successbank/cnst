<?php
// 제품 목록 include 파일
// 검색 필터
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// 일괄 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $current_tab === 'products') {
    $selected_ids = $_POST['selected'] ?? [];
    
    if (!empty($selected_ids)) {
        $ids_placeholder = str_repeat('?,', count($selected_ids) - 1) . '?';
        
        try {
            switch ($_POST['action']) {
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($ids_placeholder)");
                    $stmt->execute($selected_ids);
                    $message = "bulk_deleted";
                    break;
                    
                case 'activate':
                    $stmt = $pdo->prepare("UPDATE products SET is_active = 1 WHERE id IN ($ids_placeholder)");
                    $stmt->execute($selected_ids);
                    $message = "bulk_activated";
                    break;
                    
                case 'deactivate':
                    $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id IN ($ids_placeholder)");
                    $stmt->execute($selected_ids);
                    $message = "bulk_deactivated";
                    break;
            }
            header("Location: admin_products_integrated.php?tab=products&message=$message");
            exit;
        } catch(PDOException $e) {
            $error = "일괄 처리 중 오류가 발생했습니다.";
        }
    }
}

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
.filters {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.search-box {
    flex: 1;
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.category-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    overflow-x: auto;
    padding: 5px;
}

.category-tab {
    padding: 8px 16px;
    color: #666;
    text-decoration: none;
    border-radius: 4px;
    white-space: nowrap;
    transition: all 0.3s ease;
}

.category-tab:hover {
    background: #e9ecef;
}

.category-tab.active {
    background: #007bff;
    color: white;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
}

.products-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.products-table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.checkbox-cell {
    width: 40px;
    text-align: center;
}

.bulk-actions {
    display: none;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-bottom: 10px;
}

.unit-weight-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

/* 엑셀 버튼 스타일 */
.excel-buttons {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.excel-btn {
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 6px;
    display: inline-block;
    font-weight: 600;
    transition: all 0.3s ease;
}

.excel-btn:hover {
    opacity: 0.8;
    transform: translateY(-1px);
}

/* 모바일 반응형 */
@media (max-width: 768px) {
    .excel-buttons {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }
    
    .excel-btn {
        width: 100%;
        text-align: center;
    }
    .filters {
        flex-direction: column;
    }
    
    .search-box {
        width: 100%;
    }
    
    .category-tabs {
        padding: 3px;
        gap: 5px;
    }
    
    .category-tab {
        padding: 6px 12px;
        font-size: 13px;
    }
    
    .bulk-actions {
        padding: 10px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    #bulkAction {
        width: 100%;
    }
    
    /* 모바일 테이블 스크롤 */
    .products-table {
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .products-table tbody {
        display: table;
        width: 100%;
    }
    
    /* 모바일에서 일부 컬럼 숨기기 */
    @media (max-width: 576px) {
        .products-table th:nth-child(5),
        .products-table td:nth-child(5),
        .products-table th:nth-child(6),
        .products-table td:nth-child(6) {
            display: none;
        }
    }
}
</style>

<!-- 엑셀 다운로드/업로드 버튼 -->
<div class="excel-buttons">
    <div>
        <a href="products_export.php?category=<?php echo urlencode($category_filter); ?>" 
           class="excel-btn" style="background: #28a745; color: white;">
            📥 엑셀 다운로드
        </a>
        <a href="products_import.php" 
           class="excel-btn" style="background: #17a2b8; color: white; margin-left: 10px;">
            📤 엑셀 업로드
        </a>
    </div>
    <div style="font-size: 14px; color: #666;">
        <?php if ($category_filter): ?>
            <?php 
            $cat_name = '';
            foreach ($categories as $cat) {
                if ($cat['category_code'] == $category_filter) {
                    $cat_name = $cat['category_name'];
                    break;
                }
            }
            ?>
            현재 카테고리: <strong><?php echo htmlspecialchars($cat_name); ?></strong>
        <?php else: ?>
            전체 제품
        <?php endif; ?>
    </div>
</div>

<!-- 카테고리 필터 -->
<div class="category-tabs">
    <a href="?tab=products" class="category-tab <?php echo $category_filter === '' ? 'active' : ''; ?>">
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
        <a href="?tab=products&category=<?php echo $category['category_code']; ?>" 
           class="category-tab <?php echo $category_filter === $category['category_code'] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($category['category_name']); ?> (<?php echo $count; ?>)
        </a>
    <?php endforeach; ?>
</div>

<!-- 검색 -->
<form method="GET" action="" class="filters">
    <input type="hidden" name="tab" value="products">
    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
    <div class="search-box">
        <input type="text" name="search" placeholder="제품명, 규격으로 검색..." 
               value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <button type="submit" class="btn btn-primary">검색</button>
</form>

<!-- 일괄 작업 -->
<div class="bulk-actions" id="bulkActions">
    <select name="bulk_action" id="bulkAction">
        <option value="">일괄 작업 선택</option>
        <option value="activate">활성화</option>
        <option value="deactivate">비활성화</option>
        <option value="delete">삭제</option>
    </select>
    <button type="button" onclick="executeBulkAction()" class="btn btn-primary">실행</button>
    <span style="margin-left: 10px;">선택: <span id="selectedCount">0</span>개</span>
</div>

<!-- 제품 목록 -->
<form method="POST" id="productsForm">
    <input type="hidden" name="action" id="formAction">
    <table class="products-table">
        <thead>
            <tr>
                <th class="checkbox-cell">
                    <input type="checkbox" id="selectAll" onchange="toggleAll()">
                </th>
                <th>제품명</th>
                <th>규격</th>
                <th>카테고리</th>
                <th>단위중량</th>
                <th>재고상태</th>
                <th>상태</th>
                <th>관리</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td class="checkbox-cell">
                    <input type="checkbox" name="selected[]" value="<?php echo $product['id']; ?>" 
                           onchange="updateSelected()">
                </td>
                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                <td><?php echo htmlspecialchars($product['specifications']); ?></td>
                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                <td>
                    <?php if ($product['unit_weight']): ?>
                        <span class="unit-weight-badge"><?php echo number_format($product['unit_weight'], 1); ?> kg/m</span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                    switch($product['stock_status']) {
                        case 'in_stock': echo '<span style="color: #28a745;">재고있음</span>'; break;
                        case 'out_of_stock': echo '<span style="color: #dc3545;">재고없음</span>'; break;
                        case 'on_order': echo '<span style="color: #ffc107;">주문가능</span>'; break;
                    }
                    ?>
                </td>
                <td>
                    <?php echo $product['is_active'] ? 
                        '<span style="color: #28a745;">활성</span>' : 
                        '<span style="color: #dc3545;">비활성</span>'; ?>
                </td>
                <td>
                    <a href="admin_products_edit.php?id=<?php echo $product['id']; ?>" 
                       class="btn btn-sm" style="background: #ffc107; color: #000;">수정</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</form>

<!-- 페이지네이션 -->
<?php if ($totalPages > 1): ?>
<div style="display: flex; justify-content: center; gap: 5px; margin-top: 20px;">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?tab=products&page=<?php echo $i; ?>&category=<?php echo urlencode($category_filter); ?>&search=<?php echo urlencode($search); ?>" 
           style="padding: 5px 10px; border: 1px solid #ddd; text-decoration: none; 
                  <?php echo $i == $page ? 'background: #007bff; color: white;' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<script>
function toggleAll() {
    const checkboxes = document.querySelectorAll('input[name="selected[]"]');
    const selectAll = document.getElementById('selectAll');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSelected();
}

function updateSelected() {
    const checked = document.querySelectorAll('input[name="selected[]"]:checked').length;
    document.getElementById('selectedCount').textContent = checked;
    document.getElementById('bulkActions').style.display = checked > 0 ? 'block' : 'none';
}

function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    if (!action) {
        alert('작업을 선택해주세요.');
        return;
    }
    
    if (action === 'delete' && !confirm('선택한 제품을 삭제하시겠습니까?')) {
        return;
    }
    
    document.getElementById('formAction').value = action;
    document.getElementById('productsForm').submit();
}
</script>