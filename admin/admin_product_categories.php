<?php
session_start();
require_once 'admin_check.php';
require_once '../db.php';

$pageTitle = '카테고리 관리';
$currentPage = 'categories';

// 카테고리 목록 조회
$stmt = $pdo->query("
    SELECT
        pc.*,
        COUNT(p.id) as product_count
    FROM product_categories pc
    LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
    GROUP BY pc.id
    ORDER BY pc.display_order, pc.id
");
$categories = $stmt->fetchAll();

// 메시지 처리
$msg = '';
$msgType = '';
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    $msgType = $_SESSION['msg_type'] ?? 'success';
    unset($_SESSION['msg']);
    unset($_SESSION['msg_type']);
}

require_once 'admin_head.php';
?>

<div class="admin-content">
    <?php if ($msg): ?>
        <div class="msg <?php echo $msgType; ?>"><?php echo $msg; ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h1>카테고리 관리</h1>
        <p>제품 카테고리를 관리하고, 카테고리 병합 및 제품 이동을 수행할 수 있습니다.</p>
    </div>

    <!-- 액션 버튼 -->
    <div class="action-buttons" style="margin-bottom: 20px; display: flex; justify-content: space-between;">
        <div>
            <button class="btn btn-primary" onclick="showAddCategoryModal()">새 카테고리 추가</button>
            <button class="btn btn-warning" onclick="showMergeCategoriesModal()">카테고리 병합</button>
            <button class="btn btn-info" onclick="showMoveProductsModal()">제품 이동</button>
            <button class="btn btn-secondary" onclick="saveOrder()">순서 저장</button>
        </div>
        <div>
            <a href="admin_product_categories_tree_v2.php" class="btn btn-success" style="background: #4CAF50;">
                <span style="margin-right: 5px;">🌳</span> 트리 뷰
            </a>
        </div>
    </div>

    <!-- 카테고리 목록 테이블 -->
    <div class="data-table">
        <div class="data-table-wrapper">
            <table id="categoryTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">순서</th>
                        <th>카테고리 코드</th>
                        <th>카테고리 이름</th>
                        <th>제품 수</th>
                        <th>상태</th>
                        <th style="width: 200px;">관리</th>
                    </tr>
                </thead>
                <tbody id="sortableCategories">
                    <?php foreach ($categories as $category): ?>
                    <tr data-id="<?php echo $category['id']; ?>" data-code="<?php echo escape($category['category_code']); ?>">
                        <td class="drag-handle" style="cursor: move;">
                            ☰ <?php echo $category['display_order']; ?>
                        </td>
                        <td><?php echo escape($category['category_code']); ?></td>
                        <td><?php echo escape($category['category_name']); ?></td>
                        <td>
                            <a href="admin_products_integrated.php?category=<?php echo urlencode($category['category_code']); ?>"
                               style="color: #1976D2;">
                                <?php echo number_format($category['product_count']); ?>개
                            </a>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $category['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $category['is_active'] ? '활성' : '비활성'; ?>
                            </span>
                        </td>
                        <td class="action-links">
                            <a href="#" onclick="editCategory('<?php echo $category['id']; ?>')" class="btn-edit">수정</a>
                            <a href="#" onclick="toggleStatus('<?php echo $category['id']; ?>', <?php echo $category['is_active'] ? '0' : '1'; ?>)"
                               class="btn-toggle"><?php echo $category['is_active'] ? '비활성화' : '활성화'; ?></a>
                            <?php if ($category['product_count'] == 0): ?>
                            <a href="#" onclick="deleteCategory('<?php echo $category['id']; ?>')" class="btn-delete">삭제</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 새 카테고리 추가 모달 -->
<div id="addCategoryModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>새 카테고리 추가</h2>
            <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
        </div>
        <form id="addCategoryForm">
            <div class="form-group">
                <label>카테고리 코드 *</label>
                <input type="text" name="category_code" required placeholder="예: steel-plates">
                <small>영문 소문자와 하이픈(-)만 사용</small>
            </div>
            <div class="form-group">
                <label>카테고리 이름 *</label>
                <input type="text" name="category_name" required placeholder="예: 철판류">
            </div>
            <div class="form-group">
                <label>표시 순서</label>
                <input type="number" name="display_order" value="99" min="1">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" checked> 활성화
                </label>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">추가</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCategoryModal')">취소</button>
            </div>
        </form>
    </div>
</div>

<!-- 카테고리 병합 모달 -->
<div id="mergeCategoriesModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>카테고리 병합</h2>
            <span class="close" onclick="closeModal('mergeCategoriesModal')">&times;</span>
        </div>
        <form id="mergeCategoriesForm">
            <div class="form-group">
                <label>병합할 카테고리들 (소스)</label>
                <div class="checkbox-list">
                    <?php foreach ($categories as $category): ?>
                    <label>
                        <input type="checkbox" name="source_categories[]" value="<?php echo $category['category_code']; ?>">
                        <?php echo escape($category['category_name']); ?>
                        (<?php echo number_format($category['product_count']); ?>개 제품)
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>대상 카테고리 (타겟)</label>
                <select name="target_category" required>
                    <option value="">선택하세요</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_code']; ?>">
                        <?php echo escape($category['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="deactivate_source" value="1" checked>
                    병합 후 소스 카테고리 비활성화
                </label>
            </div>
            <div class="merge-preview" id="mergePreview" style="display: none;">
                <h4>병합 미리보기</h4>
                <div id="mergePreviewContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-info" onclick="previewMerge()">미리보기</button>
                <button type="submit" class="btn btn-warning">병합 실행</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('mergeCategoriesModal')">취소</button>
            </div>
        </form>
    </div>
</div>

<!-- 제품 이동 모달 -->
<div id="moveProductsModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2>제품 카테고리 이동</h2>
            <span class="close" onclick="closeModal('moveProductsModal')">&times;</span>
        </div>
        <div class="form-group">
            <label>카테고리 선택</label>
            <select id="sourceCategorySelect" onchange="loadCategoryProducts()">
                <option value="">카테고리를 선택하세요</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['category_code']; ?>">
                    <?php echo escape($category['category_name']); ?>
                    (<?php echo number_format($category['product_count']); ?>개)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="productsList" style="max-height: 300px; overflow-y: auto; margin: 20px 0;">
            <!-- AJAX로 제품 목록 로드 -->
        </div>
        <form id="moveProductsForm" style="display: none;">
            <div class="form-group">
                <label>이동할 대상 카테고리</label>
                <select name="target_category" required>
                    <option value="">선택하세요</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_code']; ?>">
                        <?php echo escape($category['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">선택한 제품 이동</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('moveProductsModal')">취소</button>
            </div>
        </form>
    </div>
</div>

<!-- 카테고리 수정 모달 -->
<div id="editCategoryModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>카테고리 수정</h2>
            <span class="close" onclick="closeModal('editCategoryModal')">&times;</span>
        </div>
        <form id="editCategoryForm">
            <input type="hidden" name="category_id" id="edit_category_id">
            <div class="form-group">
                <label>카테고리 코드</label>
                <input type="text" name="category_code" id="edit_category_code" readonly>
                <small>카테고리 코드는 수정할 수 없습니다</small>
            </div>
            <div class="form-group">
                <label>카테고리 이름 *</label>
                <input type="text" name="category_name" id="edit_category_name" required>
            </div>
            <div class="form-group">
                <label>표시 순서</label>
                <input type="number" name="display_order" id="edit_display_order" min="1">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">수정</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('editCategoryModal')">취소</button>
            </div>
        </form>
    </div>
</div>

<style>
/* 모달 스타일 */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: white;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 80vh;
    overflow: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.modal-content.modal-large {
    max-width: 900px;
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
    color: #333;
    font-size: 20px;
}

.close {
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
    line-height: 20px;
}

.close:hover {
    color: #000;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e5e7;
    text-align: right;
}

.form-group {
    padding: 0 20px;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #e5e5e7;
    border-radius: 6px;
    font-size: 14px;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

/* 버튼 스타일 */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-right: 8px;
}

.btn-primary {
    background: #1976D2;
    color: white;
}

.btn-primary:hover {
    background: #1565C0;
}

.btn-warning {
    background: #FFA726;
    color: white;
}

.btn-warning:hover {
    background: #FB8C00;
}

.btn-info {
    background: #42A5F5;
    color: white;
}

.btn-info:hover {
    background: #2196F3;
}

.btn-secondary {
    background: #757575;
    color: white;
}

.btn-secondary:hover {
    background: #616161;
}

.btn-danger {
    background: #EF5350;
    color: white;
}

.btn-danger:hover {
    background: #E53935;
}

/* 상태 배지 */
.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.active {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-badge.inactive {
    background: #FFEBEE;
    color: #C62828;
}

/* 체크박스 리스트 */
.checkbox-list {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #e5e5e7;
    border-radius: 6px;
    padding: 10px;
}

.checkbox-list label {
    display: block;
    padding: 8px;
    margin: 0;
    cursor: pointer;
    transition: background 0.2s;
}

.checkbox-list label:hover {
    background: #f5f5f7;
}

.checkbox-list input[type="checkbox"] {
    margin-right: 8px;
}

/* 병합 미리보기 */
.merge-preview {
    margin: 20px;
    padding: 15px;
    background: #f5f5f7;
    border-radius: 6px;
}

.merge-preview h4 {
    margin-top: 0;
    color: #333;
}

/* 제품 목록 */
.product-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #e5e5e7;
}

.product-item:hover {
    background: #f8f9fa;
}

.product-item input[type="checkbox"] {
    margin-right: 12px;
}

/* 액션 버튼 영역 */
.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* 드래그 가능 표시 */
.drag-handle {
    color: #999;
}

.sortable-ghost {
    opacity: 0.4;
    background: #f5f5f7;
}

.sortable-drag {
    background: white !important;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    // Sortable 초기화
    const el = document.getElementById('sortableCategories');
    if (el) {
        Sortable.create(el, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                // 순서가 변경되면 저장 버튼 활성화
                document.querySelector('.btn-secondary').style.background = '#FFA726';
            }
        });
    }
});

// 모달 관련 함수
function showAddCategoryModal() {
    document.getElementById('addCategoryModal').style.display = 'flex';
}

function showMergeCategoriesModal() {
    document.getElementById('mergeCategoriesModal').style.display = 'flex';
}

function showMoveProductsModal() {
    document.getElementById('moveProductsModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// 카테고리 추가
document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('ajax/add_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '추가 실패');
        }
    })
    .catch(error => {
        alert('오류가 발생했습니다.');
        console.error(error);
    });
});

// 카테고리 수정
function editCategory(categoryId) {
    // 카테고리 정보 로드
    fetch(`ajax/get_category.php?id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_category_id').value = data.category.id;
                document.getElementById('edit_category_code').value = data.category.category_code;
                document.getElementById('edit_category_name').value = data.category.category_name;
                document.getElementById('edit_display_order').value = data.category.display_order;
                document.getElementById('editCategoryModal').style.display = 'flex';
            }
        })
        .catch(error => {
            alert('카테고리 정보를 불러올 수 없습니다.');
            console.error(error);
        });
}

document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('ajax/update_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '수정 실패');
        }
    })
    .catch(error => {
        alert('오류가 발생했습니다.');
        console.error(error);
    });
});

// 상태 토글
function toggleStatus(categoryId, newStatus) {
    if (!confirm(newStatus ? '활성화하시겠습니까?' : '비활성화하시겠습니까?')) {
        return;
    }

    const formData = new FormData();
    formData.append('category_id', categoryId);
    formData.append('is_active', newStatus);

    fetch('ajax/toggle_category_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '상태 변경 실패');
        }
    })
    .catch(error => {
        alert('오류가 발생했습니다.');
        console.error(error);
    });
}

// 카테고리 삭제
function deleteCategory(categoryId) {
    if (!confirm('정말 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        return;
    }

    const formData = new FormData();
    formData.append('category_id', categoryId);

    fetch('ajax/delete_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '삭제 실패');
        }
    })
    .catch(error => {
        alert('오류가 발생했습니다.');
        console.error(error);
    });
}

// 순서 저장
function saveOrder() {
    const rows = document.querySelectorAll('#sortableCategories tr');
    const orderData = [];

    rows.forEach((row, index) => {
        orderData.push({
            id: row.dataset.id,
            order: index + 1
        });
    });

    const formData = new FormData();
    formData.append('order_data', JSON.stringify(orderData));

    fetch('ajax/update_category_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('순서가 저장되었습니다.');
            location.reload();
        } else {
            alert(data.message || '순서 저장 실패');
        }
    })
    .catch(error => {
        alert('오류가 발생했습니다.');
        console.error(error);
    });
}

// 카테고리 병합 미리보기
function previewMerge() {
    const form = document.getElementById('mergeCategoriesForm');
    const sourceCategories = Array.from(form.querySelectorAll('input[name="source_categories[]"]:checked'))
        .map(cb => cb.value);
    const targetCategory = form.querySelector('select[name="target_category"]').value;

    if (sourceCategories.length === 0) {
        alert('병합할 카테고리를 선택하세요.');
        return;
    }

    if (!targetCategory) {
        alert('대상 카테고리를 선택하세요.');
        return;
    }

    if (sourceCategories.includes(targetCategory)) {
        alert('소스 카테고리와 대상 카테고리가 같을 수 없습니다.');
        return;
    }

    const formData = new FormData();
    formData.append('source_categories', JSON.stringify(sourceCategories));
    formData.append('target_category', targetCategory);

    fetch('ajax/preview_merge.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const preview = document.getElementById('mergePreview');
            const content = document.getElementById('mergePreviewContent');

            content.innerHTML = `
                <p><strong>이동될 제품 수:</strong> ${data.product_count}개</p>
                <p><strong>소스 카테고리:</strong> ${data.source_names.join(', ')}</p>
                <p><strong>대상 카테고리:</strong> ${data.target_name}</p>
            `;

            preview.style.display = 'block';
        }
    })
    .catch(error => {
        alert('미리보기를 불러올 수 없습니다.');
        console.error(error);
    });
}

// 카테고리 병합 실행
document.getElementById('mergeCategoriesForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (!confirm('정말 병합하시겠습니까? 이 작업은 되돌릴 수 없습니다.')) {
        return;
    }

    const sourceCategories = Array.from(this.querySelectorAll('input[name="source_categories[]"]:checked'))
        .map(cb => cb.value);

    const formData = new FormData();
    formData.append('source_categories', JSON.stringify(sourceCategories));
    formData.append('target_category', this.querySelector('select[name="target_category"]').value);
    formData.append('deactivate_source', this.querySelector('input[name="deactivate_source"]').checked ? 1 : 0);

    fetch('ajax/merge_categories.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`성공적으로 ${data.moved_count}개의 제품을 이동했습니다.`);
            location.reload();
        } else {
            alert(data.message || '병합 실패');
        }
    })
    .catch(error => {
        alert('오류가 발생했습니다.');
        console.error(error);
    });
});

// 카테고리 제품 로드
function loadCategoryProducts() {
    const categoryCode = document.getElementById('sourceCategorySelect').value;
    const productsList = document.getElementById('productsList');
    const moveForm = document.getElementById('moveProductsForm');

    if (!categoryCode) {
        productsList.innerHTML = '';
        moveForm.style.display = 'none';
        return;
    }

    fetch(`ajax/get_products_by_category.php?category=${categoryCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.products.length === 0) {
                    productsList.innerHTML = '<p style="padding: 20px; text-align: center;">이 카테고리에 제품이 없습니다.</p>';
                    moveForm.style.display = 'none';
                } else {
                    let html = '<div style="padding: 10px;">';
                    html += '<label><input type="checkbox" id="selectAll" onchange="toggleAllProducts()"> 전체 선택</label>';
                    html += '</div>';

                    data.products.forEach(product => {
                        html += `
                            <div class="product-item">
                                <input type="checkbox" name="product_ids[]" value="${product.id}" class="product-checkbox">
                                <div style="flex: 1;">
                                    <strong>${product.product_name}</strong>
                                    ${product.specifications ? ` - ${product.specifications}` : ''}
                                </div>
                            </div>
                        `;
                    });

                    productsList.innerHTML = html;
                    moveForm.style.display = 'block';
                }
            }
        })
        .catch(error => {
            alert('제품 목록을 불러올 수 없습니다.');
            console.error(error);
        });
}

// 전체 선택/해제
function toggleAllProducts() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.product-checkbox');

    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
}

// 제품 이동
document.getElementById('moveProductsForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const selectedProducts = Array.from(document.querySelectorAll('.product-checkbox:checked'))
        .map(cb => cb.value);

    if (selectedProducts.length === 0) {
        alert('이동할 제품을 선택하세요.');
        return;
    }

    const formData = new FormData();
    formData.append('product_ids', JSON.stringify(selectedProducts));
    formData.append('target_category', this.querySelector('select[name="target_category"]').value);

    fetch('ajax/move_products_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`${data.moved_count}개의 제품을 이동했습니다.`);
            location.reload();
        } else {
            alert(data.message || '제품 이동 실패');
        }
    })
    .catch(error => {
        alert('오류가 발생했습니다.');
        console.error(error);
    });
});

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display === 'flex') {
                modal.style.display = 'none';
            }
        });
    }
});

// 모달 외부 클릭으로 닫기
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'admin_tail.php'; ?>