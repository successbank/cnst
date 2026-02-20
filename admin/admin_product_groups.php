<?php
$pageTitle = '제품군 단위 관리';
require_once 'admin_head.php';

// escape 함수 정의 (필요한 경우)
if (!function_exists('escape')) {
    function escape($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// 제품 카테고리별 집계 데이터 가져오기
$sql = "SELECT 
    pc.category_code,
    pc.category_name,
    COUNT(DISTINCT p.id) as product_count,
    COUNT(DISTINCT p.origin) as origin_count,
    COUNT(DISTINCT CASE WHEN p.stock_type = 'long_term' THEN p.id END) as long_term_count,
    COUNT(DISTINCT CASE WHEN p.stock_type = 'used' THEN p.id END) as used_count,
    GROUP_CONCAT(DISTINCT p.origin ORDER BY p.origin) as origins
FROM product_categories pc
LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
WHERE pc.is_active = 1
GROUP BY pc.category_code, pc.category_name
ORDER BY pc.display_order";

$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll();

// 원산지별 통계
$origin_sql = "SELECT 
    origin,
    COUNT(*) as count
FROM products
WHERE is_active = 1 AND origin IS NOT NULL AND origin != ''
GROUP BY origin
ORDER BY count DESC";

$origin_stmt = $pdo->query($origin_sql);
$origin_stats = $origin_stmt->fetchAll();
?>

<div class="admin-content">
    <div class="page-header">
        <h2>제품군 단위 관리</h2>
        <p>카테고리별 제품 현황 및 원산지/재고 상태를 관리합니다.</p>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php
        echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8');
        unset($_SESSION['success']);
        ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php
        echo htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8');
        unset($_SESSION['error']);
        ?>
    </div>
    <?php endif; ?>

    <!-- 원산지별 통계 -->
    <div class="stats-section">
        <h3>원산지별 제품 통계</h3>
        <div class="stats-grid">
            <?php foreach ($origin_stats as $origin): ?>
            <div class="stat-card">
                <div class="stat-label"><?php echo escape($origin['origin']); ?></div>
                <div class="stat-value"><?php echo number_format($origin['count']); ?>개</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 카테고리별 제품 현황 -->
    <div class="table-container">
        <h3>카테고리별 제품 현황</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>카테고리</th>
                    <th>전체 제품수</th>
                    <th>원산지 종류</th>
                    <th>장기재고</th>
                    <th>중고제품</th>
                    <th>원산지 목록</th>
                    <th>관리</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?php echo escape($category['category_name']); ?></td>
                    <td class="text-center"><?php echo number_format($category['product_count']); ?></td>
                    <td class="text-center"><?php echo number_format($category['origin_count']); ?>종</td>
                    <td class="text-center <?php echo $category['long_term_count'] > 0 ? 'text-warning' : ''; ?>">
                        <?php echo number_format($category['long_term_count']); ?>
                    </td>
                    <td class="text-center <?php echo $category['used_count'] > 0 ? 'text-info' : ''; ?>">
                        <?php echo number_format($category['used_count']); ?>
                    </td>
                    <td class="origins-list">
                        <?php 
                        if ($category['origins']) {
                            $origins = explode(',', $category['origins']);
                            foreach ($origins as $origin) {
                                echo '<span class="origin-badge">' . escape($origin) . '</span> ';
                            }
                        } else {
                            echo '<span class="text-muted">-</span>';
                        }
                        ?>
                    </td>
                    <td class="text-center">
                        <a href="admin_products_integrated.php?category=<?php echo $category['category_code']; ?>" 
                           class="btn btn-sm btn-primary">제품 관리</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 일괄 작업 섹션 -->
    <div class="bulk-action-section">
        <h3>일괄 작업</h3>
        <div class="bulk-actions">
            <div class="action-card">
                <h4>원산지 일괄 변경</h4>
                <p>카테고리를 선택하면 해당 제품 목록이 표시됩니다. 개별 제품의 원산지를 선택하여 변경할 수 있습니다.</p>
                <div class="form-group">
                    <label>카테고리 선택</label>
                    <select id="category_select" class="form-control" onchange="loadProductsForOrigin(this.value)">
                        <option value="">선택하세요</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_code']; ?>">
                            <?php echo escape($category['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="products_list_origin" style="display: none;">
                    <form method="post" action="admin_product_groups_action.php" onsubmit="return validateProductOriginForm();">
                        <input type="hidden" name="action" value="update_products_origin">
                        <input type="hidden" name="category_code" id="origin_category_code" value="">
                        <div id="products_container_origin">
                            <!-- 제품 목록이 여기에 동적으로 로드됩니다 -->
                        </div>
                        <div class="form-actions" style="margin-top: 20px; display: none;">
                            <button type="button" class="btn btn-secondary" onclick="selectAllProducts()">전체 선택</button>
                            <button type="button" class="btn btn-secondary" onclick="deselectAllProducts()">전체 해제</button>
                            <button type="submit" class="btn btn-warning">선택한 제품 원산지 변경</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="action-card">
                <h4>재고 상태 일괄 변경</h4>
                <p>선택한 카테고리의 모든 제품 재고 상태를 일괄 변경합니다.</p>
                <form method="post" action="admin_product_groups_action.php" onsubmit="return confirm('정말 재고 상태를 일괄 변경하시겠습니까?');">
                    <input type="hidden" name="action" value="update_stock_type">
                    <div class="form-group">
                        <label>카테고리 선택</label>
                        <select name="category_code" class="form-control" required>
                            <option value="">선택하세요</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_code']; ?>">
                                <?php echo escape($category['category_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>변경할 재고 상태</label>
                        <select name="new_stock_type" class="form-control" required>
                            <option value="">선택하세요</option>
                            <option value="normal">일반</option>
                            <option value="long_term">장기재고</option>
                            <option value="used">중고</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning">일괄 변경</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.admin-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1A237E;
    margin-bottom: 10px;
}

.page-header p {
    color: #666;
    font-size: 16px;
}

.stats-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.stats-section h3 {
    font-size: 20px;
    margin-bottom: 20px;
    color: #333;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.stat-card {
    background: #F8F9FA;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1A237E;
}

.table-container {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.table-container h3 {
    font-size: 20px;
    margin-bottom: 20px;
    color: #333;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #E5E5E7;
}

.admin-table th {
    background: #F8F9FA;
    font-weight: 600;
    color: #333;
}

.admin-table tr:hover {
    background: #F8F9FA;
}

.text-center {
    text-align: center;
}

.text-warning {
    color: #F57C00;
    font-weight: 600;
}

.text-info {
    color: #0288D1;
    font-weight: 600;
}

.text-muted {
    color: #999;
}

.origins-list {
    max-width: 300px;
}

.origin-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #E3F2FD;
    color: #1565C0;
    border-radius: 12px;
    font-size: 12px;
    margin: 2px;
}

.btn {
    display: inline-block;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

.btn-primary {
    background: #1A237E;
    color: white;
}

.btn-primary:hover {
    background: #0F1656;
}

.btn-warning {
    background: #F57C00;
    color: white;
}

.btn-warning:hover {
    background: #E65100;
}

.bulk-action-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.bulk-action-section h3 {
    font-size: 20px;
    margin-bottom: 20px;
    color: #333;
}

.bulk-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.action-card {
    background: #F8F9FA;
    padding: 20px;
    border-radius: 8px;
}

.action-card h4 {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.action-card p {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #333;
    margin-bottom: 5px;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    padding: 10px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    font-size: 14px;
    color: #333;
}

.checkbox-label input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.checkbox-label:hover {
    color: #1A237E;
}

.text-muted {
    color: #666;
    font-size: 12px;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .bulk-actions {
        grid-template-columns: 1fr;
    }
    
    .admin-table {
        font-size: 13px;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 8px;
    }
    
    .origin-badge {
        font-size: 11px;
        padding: 3px 8px;
    }
    
    .checkbox-group {
        flex-direction: column;
        gap: 10px;
    }
}
.product-select-table {
    width: 100%;
    margin-top: 15px;
    border-collapse: collapse;
}

.product-select-table th,
.product-select-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

.product-select-table th {
    background: #f0f0f0;
    font-weight: 600;
}

.product-select-table tr:hover {
    background: #f8f9fa;
}

.product-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.origin-select {
    width: 150px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 8px;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

</style>

<script>
function loadProductsForOrigin(categoryCode) {
    if (!categoryCode) {
        document.getElementById('products_list_origin').style.display = 'none';
        return;
    }
    
    document.getElementById('origin_category_code').value = categoryCode;
    const container = document.getElementById('products_container_origin');
    container.innerHTML = '<p>제품 목록을 불러오는 중...</p>';
    document.getElementById('products_list_origin').style.display = 'block';
    
    // AJAX로 제품 목록 가져오기
    fetch('ajax/get_products_by_category.php?category=' + categoryCode)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<table class="product-select-table">';
                html += '<thead><tr>';
                html += '<th width="50"><input type="checkbox" onchange="toggleAllProducts(this)"></th>';
                html += '<th>제품명</th>';
                html += '<th>현재 원산지</th>';
                html += '<th>변경할 원산지</th>';
                html += '</tr></thead>';
                html += '<tbody>';
                
                data.products.forEach(product => {
                    html += '<tr>';
                    html += `<td><input type="checkbox" name="product_ids[]" value="${product.id}" class="product-checkbox" onchange="updateFormActions()"></td>`;
                    html += `<td>${product.product_name}</td>`;
                    html += `<td>${product.origin || '미지정'}</td>`;
                    html += '<td>';
                    html += `<select name="product_origins[${product.id}]" class="origin-select">`;
                    html += '<option value="">변경 안함</option>';
                    html += '<option value="국산">국산</option>';
                    html += '<option value="중국산">중국산</option>';
                    html += '<option value="일본산">일본산</option>';
                    html += '<option value="베트남산">베트남산</option>';
                    html += '<option value="바레인산">바레인산</option>';
                    html += '<option value="수입산">수입산</option>';
                    html += '</select>';
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                container.innerHTML = html;
                document.querySelector('.form-actions').style.display = 'flex';
            } else {
                container.innerHTML = '<p>제품을 불러올 수 없습니다.</p>';
            }
        })
        .catch(error => {
            container.innerHTML = '<p>오류가 발생했습니다.</p>';
            console.error('Error:', error);
        });
}

function toggleAllProducts(checkbox) {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateFormActions();
}

function selectAllProducts() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    updateFormActions();
}

function deselectAllProducts() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    updateFormActions();
}

function updateFormActions() {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    const submitBtn = document.querySelector('.form-actions button[type="submit"]');
    if (submitBtn) {
        submitBtn.textContent = `선택한 제품(${checkedBoxes.length}개) 원산지 변경`;
    }
}

function validateProductOriginForm() {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('변경할 제품을 선택해주세요.');
        return false;
    }
    
    // 선택된 제품 중 원산지가 선택된 것이 있는지 확인
    let hasOriginSelected = false;
    checkedBoxes.forEach(checkbox => {
        const productId = checkbox.value;
        const originSelect = document.querySelector(`select[name="product_origins[${productId}]"]`);
        if (originSelect && originSelect.value) {
            hasOriginSelected = true;
        }
    });
    
    if (!hasOriginSelected) {
        alert('선택한 제품 중 최소 하나는 변경할 원산지를 선택해주세요.');
        return false;
    }
    
    return confirm(`선택한 ${checkedBoxes.length}개 제품의 원산지를 변경하시겠습니까?`);
}
</script>

<?php require_once 'admin_tail.php'; ?>