<?php
$pageTitle = '제품 아이콘 관리';
require_once 'admin_head.php';

// 메시지 처리
$msg = '';
$msgType = '';
if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    $msgType = $_SESSION['msgType'] ?? 'info';
    unset($_SESSION['msg']);
    unset($_SESSION['msgType']);
}

// 제품 아이콘 목록 가져오기
try {
    $sql = "SELECT * FROM product_icons ORDER BY display_order ASC, id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $icons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $msg = "데이터를 불러오는 중 오류가 발생했습니다.";
    $msgType = 'error';
    $icons = [];
}
?>

<style>
.icon-table {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.icon-preview {
    width: 50px;
    height: 50px;
    background: #1A237E;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.icon-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.order-controls {
    display: flex;
    gap: 5px;
}

.order-btn {
    padding: 5px 10px;
    background: #E8EAF6;
    color: #3F51B5;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.order-btn:hover {
    background: #C5CAE9;
}

.order-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.add-icon-btn {
    padding: 12px 24px;
    background: #1A237E;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    display: inline-block;
    transition: all 0.3s ease;
}

.add-icon-btn:hover {
    background: #283593;
    transform: translateY(-2px);
}

.status-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.status-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.status-slider {
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

.status-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .status-slider {
    background-color: #4CAF50;
}

input:checked + .status-slider:before {
    transform: translateX(26px);
}
</style>

<div class="page-header">
    <h1>제품 아이콘 관리</h1>
    <p>메인페이지에 표시되는 주요 제품 아이콘을 관리합니다.</p>
</div>

<?php if ($msg): ?>
<div class="msg <?php echo $msgType; ?>">
    <?php echo $msg; ?>
</div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
        <p style="color: #666; font-size: 14px;">
            * 첫 번째 줄: 1~8번 (8개), 두 번째 줄: 9~16번 (8개)
        </p>
    </div>
    <a href="admin_product_icon_edit.php" class="add-icon-btn">+ 새 아이콘 추가</a>
</div>

<div class="icon-table">
    <div class="data-table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 80px;">순서</th>
                    <th style="width: 80px;">아이콘</th>
                    <th>카테고리 코드</th>
                    <th>표시명</th>
                    <th>링크 URL</th>
                    <th style="width: 80px;">타겟</th>
                    <th style="width: 80px;">상태</th>
                    <th style="width: 150px;">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($icons)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px 0; color: #666;">
                        등록된 아이콘이 없습니다.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($icons as $index => $icon): ?>
                <tr data-id="<?php echo $icon['id']; ?>">
                    <td style="text-align: center;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                            <span><?php echo $icon['display_order']; ?></span>
                            <div class="order-controls">
                                <button class="order-btn" onclick="changeOrder(<?php echo $icon['id']; ?>, 'up')" 
                                        <?php echo $index === 0 ? 'disabled' : ''; ?>>↑</button>
                                <button class="order-btn" onclick="changeOrder(<?php echo $icon['id']; ?>, 'down')"
                                        <?php echo $index === count($icons) - 1 ? 'disabled' : ''; ?>>↓</button>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="icon-preview">
                            <?php if ($icon['icon_image']): ?>
                                <img src="../<?php echo htmlspecialchars($icon['icon_image']); ?>" alt="">
                            <?php else: ?>
                                📦
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($icon['category_code'] ?? ''); ?></td>
                    <td><strong><?php echo htmlspecialchars($icon['icon_name']); ?></strong></td>
                    <td style="font-size: 13px;"><?php echo htmlspecialchars($icon['icon_url'] ?? ''); ?></td>
                    <td style="text-align: center;">
                        <?php echo $icon['url_target'] === '_blank' ? '새창' : '현재창'; ?>
                    </td>
                    <td style="text-align: center;">
                        <label class="status-switch">
                            <input type="checkbox" <?php echo $icon['is_active'] ? 'checked' : ''; ?> 
                                   onchange="toggleStatus(<?php echo $icon['id']; ?>, this.checked)">
                            <span class="status-slider"></span>
                        </label>
                    </td>
                    <td>
                        <div class="action-links">
                            <a href="admin_product_icon_edit.php?id=<?php echo $icon['id']; ?>" class="btn-edit">수정</a>
                            <a href="#" onclick="deleteIcon(<?php echo $icon['id']; ?>, '<?php echo htmlspecialchars($icon['icon_name']); ?>'); return false;" class="btn-delete">삭제</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// 순서 변경
function changeOrder(id, direction) {
    fetch('ajax/update_icon_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            direction: direction
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '순서 변경에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('순서 변경 중 오류가 발생했습니다.');
    });
}

// 활성화 상태 토글
function toggleStatus(id, isActive) {
    fetch('ajax/toggle_icon_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            is_active: isActive
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || '상태 변경에 실패했습니다.');
            location.reload(); // 원래 상태로 되돌리기
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('상태 변경 중 오류가 발생했습니다.');
        location.reload();
    });
}

// 삭제
function deleteIcon(id, name) {
    if (!confirm(`"${name}" 아이콘을 삭제하시겠습니까?`)) {
        return;
    }
    
    fetch('ajax/delete_icon.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '삭제에 실패했습니다.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('삭제 중 오류가 발생했습니다.');
    });
}
</script>

<?php require_once 'admin_tail.php'; ?>