<?php
session_start();
require_once 'admin_check.php';
require_once '../db.php';

$pageTitle = '카테고리 관리';
$currentPage = 'categories';

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
        <p>드래그 앤 드롭으로 카테고리 순서 변경 및 계층 구조를 관리합니다.</p>
    </div>

    <!-- 액션 버튼 -->
    <div class="action-buttons" style="margin-bottom: 20px; display: flex; justify-content: space-between;">
        <div>
            <button class="btn btn-primary" onclick="addCategory()">
                <span style="margin-right: 5px;">➕</span> 새 카테고리
            </button>
            <button class="btn btn-info" onclick="expandAll()">
                <span style="margin-right: 5px;">📂</span> 모두 펼치기
            </button>
            <button class="btn btn-secondary" onclick="collapseAll()">
                <span style="margin-right: 5px;">📁</span> 모두 접기
            </button>
            <button class="btn btn-warning" onclick="saveChanges()">
                <span style="margin-right: 5px;">💾</span> 변경사항 저장
            </button>
        </div>
        <div>
            <a href="admin_product_categories.php" class="btn btn-secondary">
                <span style="margin-right: 5px;">📋</span> 리스트 뷰
            </a>
        </div>
    </div>

    <!-- 트리 컨테이너 -->
    <div class="tree-container" id="categoryTree">
        <div class="tree-loading">트리 데이터를 불러오는 중...</div>
    </div>
</div>

<!-- 카테고리 추가/수정 모달 -->
<div id="categoryModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">카테고리 추가</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="categoryForm">
            <input type="hidden" id="categoryId" name="id">
            <div class="form-group">
                <label>부모 카테고리</label>
                <select id="parentId" name="parent_id">
                    <option value="">최상위 카테고리</option>
                </select>
            </div>
            <div class="form-group">
                <label>카테고리 코드 *</label>
                <input type="text" id="categoryCode" name="category_code" required placeholder="예: steel-plates">
                <small>영문 소문자와 하이픈(-)만 사용</small>
            </div>
            <div class="form-group">
                <label>카테고리 이름 *</label>
                <input type="text" id="categoryName" name="category_name" required placeholder="예: 철판류">
            </div>
            <div class="form-group">
                <label>표시 순서</label>
                <input type="number" id="displayOrder" name="display_order" value="99" min="1">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="isActive" name="is_active" value="1" checked> 활성화
                </label>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">저장</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">취소</button>
            </div>
        </form>
    </div>
</div>

<style>
/* 트리 컨테이너 */
.tree-container {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    min-height: 400px;
}

.tree-loading {
    text-align: center;
    padding: 40px;
    color: #999;
}

/* 트리 노드 스타일 */
.tree-node {
    position: relative;
    margin: 2px 0;
}

.tree-node-wrapper {
    position: relative;
}

.tree-node-content {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: move;
    transition: all 0.2s;
    background: white;
    border: 1px solid transparent;
}

.tree-node-content:hover {
    background: #f5f5f7;
}

.tree-node-content.selected {
    background: #e3f2fd;
    border-left: 3px solid #2196F3;
}

.tree-node-content.dragging {
    opacity: 0.5;
    background: #e3f2fd;
    border: 2px dashed #2196F3;
}

/* 드롭 인디케이터 */
.drop-indicator {
    display: none;
    height: 2px;
    background: #2196F3;
    margin: 2px 0;
    position: relative;
}

.drop-indicator::before {
    content: '';
    position: absolute;
    left: -4px;
    top: -3px;
    width: 8px;
    height: 8px;
    background: #2196F3;
    border-radius: 50%;
}

.drop-indicator.active {
    display: block;
    animation: pulse 0.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* 드롭 존 */
.drop-zone {
    min-height: 10px;
    transition: all 0.2s;
    border-radius: 4px;
}

.drop-zone.drag-over {
    background: rgba(33, 150, 243, 0.1);
    border: 2px dashed #2196F3;
    min-height: 40px;
    margin: 4px 0;
}

/* 트리 토글 아이콘 */
.tree-toggle {
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s;
}

.tree-toggle.collapsed {
    transform: rotate(-90deg);
}

.tree-toggle.leaf {
    visibility: hidden;
}

/* 트리 아이콘 */
.tree-icon {
    margin: 0 8px;
    font-size: 16px;
}

.tree-icon.folder {
    color: #ffa726;
}

.tree-icon.folder-open {
    color: #ffb74d;
}

.tree-icon.file {
    color: #42a5f5;
}

/* 트리 라벨 */
.tree-label {
    flex: 1;
    font-size: 14px;
    font-weight: 500;
}

.tree-label.inactive {
    color: #999;
    text-decoration: line-through;
}

/* 트리 카운트 */
.tree-count {
    margin-left: 8px;
    padding: 2px 8px;
    background: #f5f5f7;
    border-radius: 12px;
    font-size: 12px;
    color: #666;
}

/* 트리 액션 */
.tree-actions {
    display: none;
    gap: 8px;
    margin-left: auto;
}

.tree-node-content:hover .tree-actions {
    display: flex;
}

.tree-action-btn {
    padding: 4px 8px;
    background: transparent;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.tree-action-btn:hover {
    background: rgba(0,0,0,0.08);
}

/* 트리 자식 노드 */
.tree-children {
    margin-left: 24px;
    position: relative;
}

.tree-children.collapsed {
    display: none;
}

/* 연결선 */
.tree-line {
    position: absolute;
    left: 8px;
    top: -10px;
    bottom: 10px;
    width: 1px;
    background: #e0e0e0;
}

/* 수정 표시 */
.modified-indicator {
    display: none;
    width: 8px;
    height: 8px;
    background: #ff9800;
    border-radius: 50%;
    margin-left: 8px;
    animation: blink 1s infinite;
}

.modified-indicator.show {
    display: inline-block;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

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
    max-width: 500px;
    max-height: 80vh;
    overflow: auto;
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
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #1976D2;
    color: white;
}

.btn-primary:hover {
    background: #1565C0;
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

.btn-warning {
    background: #FFA726;
    color: white;
}

.btn-warning:hover {
    background: #FF9800;
}

.btn-danger {
    background: #EF5350;
    color: white;
}

.btn-danger:hover {
    background: #E53935;
}

/* 드래그 가능 표시 */
.drag-handle {
    cursor: move;
    margin-right: 8px;
    color: #999;
    font-size: 12px;
}

.drag-handle:hover {
    color: #666;
}
</style>

<script>
// 전역 변수
let treeData = [];
let draggedNode = null;
let dropPosition = null; // 'before', 'after', 'inside'
let dropTarget = null;
let hasChanges = false;
let originalOrder = {};

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    loadTreeData();
});

// 트리 데이터 로드
function loadTreeData() {
    fetch('ajax/get_categories_tree.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                treeData = data.categories;
                saveOriginalOrder();
                renderTree();
            } else {
                alert('트리 데이터를 불러올 수 없습니다: ' + (data.message || ''));
            }
        })
        .catch(error => {
            console.error('Error loading tree:', error);
            alert('트리 데이터 로드 중 오류가 발생했습니다.');
        });
}

// 원래 순서 저장
function saveOriginalOrder() {
    originalOrder = {};

    function saveOrder(nodes, parentId = null) {
        if (!nodes) return;
        nodes.forEach((node, index) => {
            originalOrder[node.id] = {
                parent_id: parentId,
                display_order: index
            };
            if (node.children && node.children.length > 0) {
                saveOrder(node.children, node.id);
            }
        });
    }

    saveOrder(treeData);
}

// 트리 렌더링
function renderTree() {
    const container = document.getElementById('categoryTree');
    if (treeData.length === 0) {
        container.innerHTML = '<div class="tree-loading">카테고리가 없습니다. 새 카테고리를 추가해주세요.</div>';
        return;
    }

    container.innerHTML = buildTreeHTML(treeData, 0);
    attachDragEvents();
}

// 트리 HTML 생성
function buildTreeHTML(nodes, level) {
    let html = '';

    nodes.forEach((node, index) => {
        const hasChildren = node.children && node.children.length > 0;

        html += `
            <div class="tree-node" data-id="${node.id}" data-level="${level}" data-index="${index}">
                <div class="drop-indicator" data-position="before"></div>
                <div class="tree-node-wrapper">
                    <div class="tree-node-content"
                         draggable="true"
                         data-node-id="${node.id}">
                        <span class="drag-handle">⋮⋮</span>
                        <span class="tree-toggle ${hasChildren ? '' : 'leaf'} ${node.expanded ? '' : 'collapsed'}"
                              onclick="toggleNode(event, this)">
                            ▼
                        </span>
                        <span class="tree-icon ${hasChildren ? (node.expanded ? 'folder-open' : 'folder') : 'file'}">
                            ${hasChildren ? (node.expanded ? '📂' : '📁') : '📄'}
                        </span>
                        <span class="tree-label ${node.is_active ? '' : 'inactive'}">
                            ${node.category_name}
                        </span>
                        <span class="tree-count">${node.product_count || 0}</span>
                        <span class="modified-indicator" id="mod-${node.id}"></span>
                        <div class="tree-actions">
                            <button class="tree-action-btn" onclick="editCategory(event, ${node.id})">수정</button>
                            ${hasChildren ? '' : `<button class="tree-action-btn" onclick="deleteCategory(event, ${node.id})">삭제</button>`}
                        </div>
                    </div>
                    ${hasChildren ? `
                        <div class="tree-children ${node.expanded ? '' : 'collapsed'}">
                            <div class="tree-line"></div>
                            ${buildTreeHTML(node.children, level + 1)}
                        </div>
                    ` : ''}
                </div>
                <div class="drop-indicator" data-position="after"></div>
            </div>
        `;
    });

    return html;
}

// 드래그 이벤트 연결
function attachDragEvents() {
    const nodes = document.querySelectorAll('.tree-node-content');

    nodes.forEach(node => {
        // 드래그 시작
        node.addEventListener('dragstart', handleDragStart);

        // 드래그 중
        node.addEventListener('dragover', handleDragOver);
        node.addEventListener('dragenter', handleDragEnter);
        node.addEventListener('dragleave', handleDragLeave);

        // 드롭
        node.addEventListener('drop', handleDrop);
        node.addEventListener('dragend', handleDragEnd);
    });

    // 인디케이터에도 이벤트 연결
    const indicators = document.querySelectorAll('.drop-indicator');
    indicators.forEach(indicator => {
        indicator.addEventListener('dragover', handleIndicatorDragOver);
        indicator.addEventListener('drop', handleIndicatorDrop);
    });
}

// 드래그 시작
function handleDragStart(e) {
    draggedNode = e.currentTarget.dataset.nodeId;
    e.currentTarget.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', e.currentTarget.innerHTML);
}

// 드래그 오버
function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';

    // 드롭 위치 계산
    const rect = e.currentTarget.getBoundingClientRect();
    const y = e.clientY - rect.top;
    const height = rect.height;

    // 이전 인디케이터 모두 숨기기
    document.querySelectorAll('.drop-indicator').forEach(ind => {
        ind.classList.remove('active');
    });

    const nodeElement = e.currentTarget.closest('.tree-node');

    if (y < height * 0.25) {
        // 위쪽 1/4: 이전에 삽입
        dropPosition = 'before';
        const indicator = nodeElement.querySelector('.drop-indicator[data-position="before"]');
        if (indicator) indicator.classList.add('active');
    } else if (y > height * 0.75) {
        // 아래쪽 1/4: 다음에 삽입
        dropPosition = 'after';
        const indicator = nodeElement.querySelector('.drop-indicator[data-position="after"]');
        if (indicator) indicator.classList.add('active');
    } else {
        // 중간: 자식으로 삽입
        dropPosition = 'inside';
        e.currentTarget.style.background = 'rgba(33, 150, 243, 0.1)';
    }

    dropTarget = e.currentTarget.dataset.nodeId;

    return false;
}

// 인디케이터 드래그 오버
function handleIndicatorDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';

    // 인디케이터 활성화
    document.querySelectorAll('.drop-indicator').forEach(ind => {
        ind.classList.remove('active');
    });
    e.currentTarget.classList.add('active');

    dropPosition = e.currentTarget.dataset.position;
    dropTarget = e.currentTarget.closest('.tree-node').dataset.id;

    return false;
}

// 드래그 엔터
function handleDragEnter(e) {
    if (e.currentTarget.dataset.nodeId !== draggedNode) {
        // 시각적 피드백
    }
}

// 드래그 리브
function handleDragLeave(e) {
    e.currentTarget.style.background = '';
}

// 드롭
function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }

    if (draggedNode && dropTarget && draggedNode !== dropTarget) {
        moveNode(draggedNode, dropTarget, dropPosition);
    }

    return false;
}

// 인디케이터 드롭
function handleIndicatorDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }

    if (draggedNode && dropTarget && draggedNode !== dropTarget) {
        moveNode(draggedNode, dropTarget, dropPosition);
    }

    return false;
}

// 드래그 종료
function handleDragEnd(e) {
    // 모든 시각적 효과 제거
    document.querySelectorAll('.tree-node-content').forEach(node => {
        node.classList.remove('dragging');
        node.style.background = '';
    });

    document.querySelectorAll('.drop-indicator').forEach(ind => {
        ind.classList.remove('active');
    });

    draggedNode = null;
    dropTarget = null;
    dropPosition = null;
}

// 노드 이동
function moveNode(sourceId, targetId, position) {
    // 트리 데이터에서 노드 이동
    const sourceNode = findAndRemoveNode(treeData, sourceId);
    if (!sourceNode) return;

    if (position === 'inside') {
        // 대상의 자식으로 추가
        const targetNode = findNodeById(treeData, targetId);
        if (targetNode) {
            if (!targetNode.children) {
                targetNode.children = [];
            }
            targetNode.children.push(sourceNode);
        }
    } else {
        // 대상의 이전/다음에 삽입
        insertNodeRelative(treeData, targetId, sourceNode, position);
    }

    // 변경 표시
    hasChanges = true;
    document.getElementById('mod-' + sourceId)?.classList.add('show');

    // 트리 다시 렌더링
    renderTree();
}

// 노드 찾기 및 제거
function findAndRemoveNode(nodes, nodeId) {
    for (let i = 0; i < nodes.length; i++) {
        if (nodes[i].id == nodeId) {
            return nodes.splice(i, 1)[0];
        }
        if (nodes[i].children && nodes[i].children.length > 0) {
            const found = findAndRemoveNode(nodes[i].children, nodeId);
            if (found) return found;
        }
    }
    return null;
}

// 상대적 위치에 노드 삽입
function insertNodeRelative(nodes, targetId, nodeToInsert, position) {
    for (let i = 0; i < nodes.length; i++) {
        if (nodes[i].id == targetId) {
            if (position === 'before') {
                nodes.splice(i, 0, nodeToInsert);
            } else if (position === 'after') {
                nodes.splice(i + 1, 0, nodeToInsert);
            }
            return true;
        }
        if (nodes[i].children && nodes[i].children.length > 0) {
            if (insertNodeRelative(nodes[i].children, targetId, nodeToInsert, position)) {
                return true;
            }
        }
    }
    return false;
}

// 노드 찾기
function findNodeById(nodes, id) {
    for (let node of nodes) {
        if (node.id == id) {
            return node;
        }
        if (node.children && node.children.length > 0) {
            const found = findNodeById(node.children, id);
            if (found) return found;
        }
    }
    return null;
}

// 노드 토글
function toggleNode(event, element) {
    event.stopPropagation();

    const node = element.closest('.tree-node');
    const children = node.querySelector('.tree-children');
    const icon = node.querySelector('.tree-icon');

    if (children) {
        element.classList.toggle('collapsed');
        children.classList.toggle('collapsed');

        if (children.classList.contains('collapsed')) {
            icon.textContent = '📁';
            icon.classList.remove('folder-open');
            icon.classList.add('folder');
        } else {
            icon.textContent = '📂';
            icon.classList.remove('folder');
            icon.classList.add('folder-open');
        }
    }
}

// 모두 펼치기
function expandAll() {
    document.querySelectorAll('.tree-children').forEach(el => {
        el.classList.remove('collapsed');
    });
    document.querySelectorAll('.tree-toggle:not(.leaf)').forEach(el => {
        el.classList.remove('collapsed');
    });
    document.querySelectorAll('.tree-icon.folder').forEach(el => {
        el.textContent = '📂';
        el.classList.remove('folder');
        el.classList.add('folder-open');
    });
}

// 모두 접기
function collapseAll() {
    document.querySelectorAll('.tree-children').forEach(el => {
        el.classList.add('collapsed');
    });
    document.querySelectorAll('.tree-toggle:not(.leaf)').forEach(el => {
        el.classList.add('collapsed');
    });
    document.querySelectorAll('.tree-icon.folder-open').forEach(el => {
        el.textContent = '📁';
        el.classList.remove('folder-open');
        el.classList.add('folder');
    });
}

// 변경사항 저장
function saveChanges() {
    if (!hasChanges) {
        alert('변경사항이 없습니다.');
        return;
    }

    // 현재 트리 구조를 서버로 전송
    const updates = [];

    function collectUpdates(nodes, parentId = null, orderIndex = 0) {
        nodes.forEach((node, index) => {
            updates.push({
                id: node.id,
                parent_id: parentId,
                display_order: orderIndex + index
            });

            if (node.children && node.children.length > 0) {
                collectUpdates(node.children, node.id, 0);
            }
        });
    }

    collectUpdates(treeData);

    // AJAX로 전송
    const formData = new FormData();
    formData.append('updates', JSON.stringify(updates));

    fetch('ajax/update_category_structure.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('변경사항이 저장되었습니다.');
            hasChanges = false;
            document.querySelectorAll('.modified-indicator').forEach(el => {
                el.classList.remove('show');
            });
            loadTreeData();
        } else {
            alert(data.message || '저장 실패');
        }
    })
    .catch(error => {
        alert('저장 중 오류가 발생했습니다.');
        console.error(error);
    });
}

// 카테고리 추가
function addCategory(parentId = null) {
    document.getElementById('modalTitle').textContent = '카테고리 추가';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';

    if (parentId) {
        document.getElementById('parentId').value = parentId;
    }

    loadParentOptions();
    document.getElementById('categoryModal').style.display = 'flex';
}

// 카테고리 수정
function editCategory(event, categoryId) {
    event.stopPropagation();

    fetch(`ajax/get_category.php?id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = '카테고리 수정';
                document.getElementById('categoryId').value = data.category.id;
                document.getElementById('categoryCode').value = data.category.category_code;
                document.getElementById('categoryName').value = data.category.category_name;
                document.getElementById('displayOrder').value = data.category.display_order;
                document.getElementById('isActive').checked = data.category.is_active == 1;
                document.getElementById('parentId').value = data.category.parent_id || '';

                loadParentOptions(categoryId);
                document.getElementById('categoryModal').style.display = 'flex';
            }
        });
}

// 부모 카테고리 옵션 로드
function loadParentOptions(excludeId = null) {
    const select = document.getElementById('parentId');
    const currentValue = select.value;

    select.innerHTML = '<option value="">최상위 카테고리</option>';

    function addOptions(nodes, level = 0, excludeId = null) {
        nodes.forEach(node => {
            if (node.id != excludeId) {
                const option = document.createElement('option');
                option.value = node.id;
                option.textContent = '　'.repeat(level) + node.category_name;
                select.appendChild(option);

                if (node.children && node.children.length > 0) {
                    addOptions(node.children, level + 1, excludeId);
                }
            }
        });
    }

    addOptions(treeData, 0, excludeId);
    select.value = currentValue;
}

// 카테고리 삭제
function deleteCategory(event, categoryId) {
    event.stopPropagation();

    if (!confirm('정말 이 카테고리를 삭제하시겠습니까?')) {
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
            loadTreeData();
            alert('카테고리가 삭제되었습니다.');
        } else {
            alert(data.message || '삭제 실패');
        }
    });
}

// 폼 제출
document.getElementById('categoryForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const categoryId = document.getElementById('categoryId').value;
    const url = categoryId
        ? 'ajax/update_category_tree.php'
        : 'ajax/add_category_tree.php';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            loadTreeData();
            alert(data.message);
        } else {
            alert(data.message || '저장 실패');
        }
    })
    .catch(error => {
        alert('오류가 발생했습니다.');
        console.error(error);
    });
});

// 모달 닫기
function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// 페이지 나가기 전 확인
window.addEventListener('beforeunload', function(e) {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '저장하지 않은 변경사항이 있습니다. 페이지를 나가시겠습니까?';
    }
});
</script>

<?php require_once 'admin_tail.php'; ?>