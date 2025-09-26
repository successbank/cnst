<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$pageTitle = '철근 제품 데이터 관리 (임시)';

// 메시지 처리
$msg = $_SESSION['msg'] ?? '';
$msgType = $_SESSION['msgType'] ?? 'info';
unset($_SESSION['msg'], $_SESSION['msgType']);

// 철근 규격 목록 가져오기 (실제 철근 제품 정보)
$query = "SELECT * FROM rebar_specifications ORDER BY diameter ASC";
$stmt = $pdo->query($query);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 철근 재질 목록
$materialsQuery = "SELECT * FROM rebar_materials ORDER BY display_order, material_name";
$materialsStmt = $pdo->query($materialsQuery);
$materials = $materialsStmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'admin_head.php';
?>

<style>
.rebar-manage-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.warning-box {
    background: #FFF3CD;
    border: 2px solid #FFC107;
    color: #856404;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.section-box {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #E5E5E7;
    color: #1A237E;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #F5F5F7;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border: 1px solid #E5E5E7;
}

.data-table td {
    padding: 10px 12px;
    border: 1px solid #E5E5E7;
}

.data-table tr:hover {
    background: #F8F9FA;
}

.btn-action {
    padding: 5px 12px;
    margin: 0 2px;
    border: none;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-edit {
    background: #007BFF;
    color: white;
}

.btn-delete {
    background: #DC3545;
    color: white;
}

.btn-add {
    background: #28A745;
    color: white;
    padding: 10px 20px;
    margin-bottom: 15px;
}

.quick-edit-form {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 15px;
    padding: 15px;
    background: #F8F9FA;
    border-radius: 8px;
}

.quick-edit-form input,
.quick-edit-form select {
    padding: 8px;
    border: 1px solid #DDD;
    border-radius: 4px;
}

.status-badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.status-active {
    background: #D4EDDA;
    color: #155724;
}

.status-inactive {
    background: #F8D7DA;
    color: #721C24;
}

.msg-box {
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.msg-success {
    background: #D4EDDA;
    color: #155724;
    border: 1px solid #C3E6CB;
}

.msg-error {
    background: #F8D7DA;
    color: #721C24;
    border: 1px solid #F5C6CB;
}
</style>

<div class="rebar-manage-container">
    <h1>🔧 철근 제품 데이터 관리</h1>

    <div class="warning-box">
        ⚠️ <strong>임시 관리 페이지</strong> - 이 페이지는 데이터 관리 후 삭제 예정입니다.
    </div>

    <?php if ($msg): ?>
    <div class="msg-box msg-<?php echo $msgType; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <!-- 철근 규격 목록 섹션 -->
    <div class="section-box">
        <h2 class="section-title">철근 규격 목록 (rebar_specifications)</h2>

        <!-- 빠른 추가 폼 -->
        <form method="POST" action="admin_rebar_process.php" class="quick-edit-form">
            <input type="hidden" name="action" value="add_spec">
            <input type="text" name="spec_name" placeholder="규격명 (예: D10)" required style="width: 100px;">
            <input type="number" name="diameter" placeholder="직경(mm)" step="0.1" required style="width: 100px;">
            <input type="number" name="weight_per_meter" placeholder="중량(kg/m)" step="0.001" required style="width: 120px;">
            <select name="is_active">
                <option value="1">활성</option>
                <option value="0">비활성</option>
            </select>
            <button type="submit" class="btn-action btn-add">추가</button>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">ID</th>
                    <th width="15%">규격명</th>
                    <th width="15%">직경(mm)</th>
                    <th width="15%">중량(kg/m)</th>
                    <th width="10%">상태</th>
                    <th width="20%">생성일</th>
                    <th width="20%">액션</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($product['spec_name']); ?></strong></td>
                    <td><?php echo number_format($product['diameter'], 1); ?></td>
                    <td><?php echo number_format($product['weight_per_meter'], 3); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $product['is_active'] ? '활성' : '비활성'; ?>
                        </span>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($product['created_at'])); ?></td>
                    <td>
                        <a href="admin_rebar_spec_edit.php?id=<?php echo $product['id']; ?>" class="btn-action btn-edit">수정</a>
                        <button onclick="deleteSpec(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['spec_name']); ?>')"
                                class="btn-action btn-delete">삭제</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 철근 재질 목록 섹션 -->
    <div class="section-box">
        <h2 class="section-title">철근 재질 목록 (rebar_materials)</h2>

        <!-- 빠른 추가 폼 -->
        <form method="POST" action="admin_rebar_process.php" class="quick-edit-form">
            <input type="hidden" name="action" value="add_material">
            <input type="text" name="material_name" placeholder="재질명 (예: SD400)" required style="width: 120px;">
            <input type="number" name="price_per_kg" placeholder="kg당 가격" step="10" style="width: 120px;">
            <input type="text" name="description" placeholder="설명" style="width: 250px;">
            <input type="number" name="display_order" placeholder="표시순서" value="0" style="width: 100px;">
            <select name="is_active">
                <option value="1">활성</option>
                <option value="0">비활성</option>
            </select>
            <button type="submit" class="btn-action btn-add">추가</button>
        </form>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">ID</th>
                    <th width="15%">재질명</th>
                    <th width="15%">kg당 가격</th>
                    <th width="30%">설명</th>
                    <th width="10%">표시순서</th>
                    <th width="8%">상태</th>
                    <th width="17%">액션</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $material): ?>
                <tr>
                    <td><?php echo $material['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($material['material_name']); ?></strong></td>
                    <td><?php echo number_format($material['price_per_kg']); ?>원</td>
                    <td><?php echo htmlspecialchars($material['description'] ?? '-'); ?></td>
                    <td><?php echo $material['display_order']; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $material['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $material['is_active'] ? '활성' : '비활성'; ?>
                        </span>
                    </td>
                    <td>
                        <a href="admin_rebar_material_edit.php?id=<?php echo $material['id']; ?>" class="btn-action btn-edit">수정</a>
                        <button onclick="deleteMaterial(<?php echo $material['id']; ?>, '<?php echo htmlspecialchars($material['material_name']); ?>')"
                                class="btn-action btn-delete">삭제</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 철근 길이별 데이터 섹션 -->
    <div class="section-box">
        <h2 class="section-title">철근 길이별 데이터 (rebar_length_data)</h2>

        <?php
        // 규격별 데이터 개수 조회
        $lengthDataQuery = "SELECT spec_name, COUNT(*) as count,
                                   MIN(length) as min_length, MAX(length) as max_length,
                                   MIN(piece_weight) as min_weight, MAX(piece_weight) as max_weight
                            FROM rebar_length_data
                            GROUP BY spec_name
                            ORDER BY spec_name";
        $lengthDataStmt = $pdo->query($lengthDataQuery);
        $lengthDataStats = $lengthDataStmt->fetchAll(PDO::FETCH_ASSOC);

        // 전체 데이터 개수
        $totalCountQuery = "SELECT COUNT(*) as total FROM rebar_length_data";
        $totalCountStmt = $pdo->query($totalCountQuery);
        $totalCount = $totalCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
        ?>

        <div style="background: #E8F5E9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <strong>📊 전체 데이터: <?php echo number_format($totalCount); ?>개</strong>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th width="15%">규격</th>
                    <th width="15%">데이터 수</th>
                    <th width="20%">길이 범위(m)</th>
                    <th width="25%">본중 범위(kg)</th>
                    <th width="25%">액션</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lengthDataStats as $stat): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($stat['spec_name']); ?></strong></td>
                    <td><?php echo number_format($stat['count']); ?>개</td>
                    <td><?php echo number_format($stat['min_length'], 1); ?> ~ <?php echo number_format($stat['max_length'], 1); ?></td>
                    <td><?php echo number_format($stat['min_weight'], 2); ?> ~ <?php echo number_format($stat['max_weight'], 2); ?></td>
                    <td>
                        <button onclick="viewLengthData('<?php echo htmlspecialchars($stat['spec_name']); ?>')"
                                class="btn-action btn-edit">상세보기</button>
                        <button onclick="deleteLengthData('<?php echo htmlspecialchars($stat['spec_name']); ?>')"
                                class="btn-action btn-delete">삭제</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- 길이별 데이터 상세보기 모달 -->
        <div id="lengthDataModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
            <div style="position: relative; width: 90%; max-width: 1200px; margin: 50px auto; background: white; border-radius: 12px; padding: 25px; max-height: 80vh; overflow-y: auto;">
                <button onclick="closeLengthDataModal()" style="position: absolute; right: 15px; top: 15px; background: #DC3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">✕</button>
                <h3 id="modalTitle" style="margin-bottom: 20px;"></h3>
                <div id="modalContent"></div>
            </div>
        </div>

        <!-- 엑셀 데이터 재임포트 -->
        <div style="margin-top: 20px; padding: 15px; background: #F0F8FF; border-radius: 8px;">
            <h4 style="margin-bottom: 10px;">📥 엑셀 데이터 재임포트</h4>
            <button onclick="reimportExcelData()" class="btn-action btn-add">엑셀 데이터 재임포트</button>
            <span style="margin-left: 10px; color: #666;">※ 기존 데이터를 백업 후 새로운 데이터로 교체합니다.</span>
        </div>
    </div>

    <!-- 데이터베이스 직접 쿼리 실행 (위험!) -->
    <div class="section-box" style="background: #FFF3CD;">
        <h2 class="section-title" style="color: #856404;">⚠️ 직접 쿼리 실행 (주의!)</h2>
        <form method="POST" action="admin_rebar_process.php">
            <input type="hidden" name="action" value="execute_query">
            <textarea name="query" rows="4" style="width: 100%; padding: 10px; font-family: monospace;"
                      placeholder="SELECT * FROM rebar_products WHERE ..."></textarea>
            <div style="margin-top: 10px;">
                <button type="submit" class="btn-action btn-edit">실행</button>
                <span style="margin-left: 10px; color: #856404;">※ SELECT 쿼리만 실행 가능</span>
            </div>
        </form>
    </div>
</div>

<script>
function deleteSpec(id, name) {
    if (confirm(`정말 '${name}' 규격을 삭제하시겠습니까?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin_rebar_process.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_spec';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;

        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteMaterial(id, name) {
    if (confirm(`정말 '${name}' 재질을 삭제하시겠습니까?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin_rebar_process.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_material';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;

        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function viewLengthData(specName) {
    // AJAX로 데이터 가져오기
    fetch(`admin_rebar_length_data.php?spec_name=${encodeURIComponent(specName)}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalTitle').textContent = `${specName} 길이별 데이터`;
            document.getElementById('modalContent').innerHTML = html;
            document.getElementById('lengthDataModal').style.display = 'block';
        })
        .catch(error => {
            alert('데이터를 불러오는 중 오류가 발생했습니다.');
            console.error(error);
        });
}

function closeLengthDataModal() {
    document.getElementById('lengthDataModal').style.display = 'none';
}

function deleteLengthData(specName) {
    if (confirm(`정말 '${specName}' 규격의 모든 길이 데이터를 삭제하시겠습니까?\n이 작업은 되돌릴 수 없습니다.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin_rebar_process.php';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_length_data';

        const specInput = document.createElement('input');
        specInput.type = 'hidden';
        specInput.name = 'spec_name';
        specInput.value = specName;

        form.appendChild(actionInput);
        form.appendChild(specInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function reimportExcelData() {
    if (confirm('엑셀 데이터를 재임포트 하시겠습니까?\n기존 데이터는 백업되고 새로운 데이터로 교체됩니다.')) {
        window.location.href = 'admin_rebar_import.php';
    }
}
</script>

<?php require_once 'admin_tail.php'; ?>