<?php
require_once 'admin_check.php';
$pageTitle = '레이어팝업 관리';

// 추가 스타일
$additionalStyles = '
.popup-table {
    width: 100%;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.popup-table th {
    background: #f5f5f5;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #e0e0e0;
}

.popup-table td {
    padding: 16px;
    border-bottom: 1px solid #f0f0f0;
}

.popup-table tr:last-child td {
    border-bottom: none;
}

.popup-table tr:hover {
    background: #f9f9f9;
}

.popup-image {
    width: 80px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #eee;
}

.popup-image-placeholder {
    width: 80px;
    height: 100px;
    background: #f0f0f0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.badge-active {
    background: #d4edda;
    color: #155724;
}

.badge-inactive {
    background: #f8d7da;
    color: #721c24;
}

.badge-scheduled {
    background: #fff3cd;
    color: #856404;
}

.badge-expired {
    background: #e2e3e5;
    color: #6c757d;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-edit {
    background: #e3f2fd;
    color: #1976d2;
}

.btn-edit:hover {
    background: #1976d2;
    color: white;
}

.btn-delete {
    background: #ffebee;
    color: #d32f2f;
}

.btn-delete:hover {
    background: #d32f2f;
    color: white;
}

.btn-order {
    background: #f3e5f5;
    color: #7b1fa2;
}

.btn-order:hover {
    background: #7b1fa2;
    color: white;
}

.btn-preview {
    background: #e8f5e9;
    color: #388e3c;
}

.btn-preview:hover {
    background: #388e3c;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-state i {
    font-size: 48px;
    color: #ddd;
    margin-bottom: 16px;
}

.btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    cursor: pointer;
    border: none;
    font-size: 14px;
}

.btn-primary {
    background: #1A237E;
    color: white;
}

.btn-primary:hover {
    background: #303F9F;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
}

.btn i {
    margin-right: 6px;
}

.date-range {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}

.position-info {
    font-size: 12px;
    color: #888;
}

/* 미리보기 모달 */
.preview-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    justify-content: center;
    align-items: center;
}

.preview-modal.show {
    display: flex;
}

.preview-popup {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    position: relative;
    overflow: hidden;
}

.preview-close {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    z-index: 10;
}

.preview-close:hover {
    background: rgba(0,0,0,0.7);
}

.preview-image img {
    max-width: 100%;
    display: block;
}

.preview-content {
    padding: 20px;
}
';

// 추가 스크립트
$additionalScripts = '
function deletePopup(id) {
    if (confirm("정말로 이 팝업을 삭제하시겠습니까?")) {
        window.location.href = "admin_layer_popup_action.php?action=delete&id=" + id;
    }
}

function toggleActive(id, currentStatus) {
    const newStatus = currentStatus == 1 ? 0 : 1;
    window.location.href = "admin_layer_popup_action.php?action=toggle&id=" + id + "&status=" + newStatus;
}

function moveUp(id) {
    window.location.href = "admin_layer_popup_action.php?action=move&id=" + id + "&direction=up";
}

function moveDown(id) {
    window.location.href = "admin_layer_popup_action.php?action=move&id=" + id + "&direction=down";
}

function previewPopup(id) {
    fetch("ajax/get_layer_popup.php?id=" + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const popup = data.popup;
                const modal = document.getElementById("previewModal");
                const previewContainer = document.getElementById("previewContainer");

                let content = "";
                if (popup.image_path) {
                    content += "<div class=\"preview-image\"><img src=\"../uploads/popups/" + popup.image_path + "\" alt=\"\"></div>";
                }
                if (popup.content) {
                    content += "<div class=\"preview-content\">" + popup.content + "</div>";
                }

                previewContainer.innerHTML = content;
                previewContainer.style.width = popup.width + "px";

                modal.classList.add("show");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("미리보기를 불러오는 중 오류가 발생했습니다.");
        });
}

function closePreviewModal() {
    document.getElementById("previewModal").classList.remove("show");
}

document.addEventListener("click", function(e) {
    if (e.target.id === "previewModal") {
        closePreviewModal();
    }
});
';

require_once 'admin_head.php';
require_once '../db.php';

// 테이블 존재 확인 및 생성
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'layer_popups'");
    if ($stmt->rowCount() == 0) {
        // 테이블 생성
        $createSql = "CREATE TABLE layer_popups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL COMMENT '팝업 제목 (관리용)',
            image_path VARCHAR(500) COMMENT '팝업 이미지 경로',
            content TEXT COMMENT '팝업 내용 (HTML)',
            link_url VARCHAR(500) COMMENT '클릭 시 이동 URL',
            link_target VARCHAR(20) DEFAULT '_self' COMMENT '링크 타겟 (_self, _blank)',
            position_top INT DEFAULT 100 COMMENT '상단 위치 (px)',
            position_left INT DEFAULT 100 COMMENT '좌측 위치 (px)',
            width INT DEFAULT 400 COMMENT '팝업 너비 (px)',
            height INT DEFAULT 500 COMMENT '팝업 높이 (px)',
            start_date DATE COMMENT '노출 시작일',
            end_date DATE COMMENT '노출 종료일',
            is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 상태',
            display_order INT DEFAULT 0 COMMENT '표시 순서',
            hide_today TINYINT(1) DEFAULT 1 COMMENT '오늘 하루 보지 않기 옵션',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='메인 레이어 팝업'";
        $pdo->exec($createSql);
    }
} catch (PDOException $e) {
    // 테이블 생성 오류 무시
}

// 팝업 목록 가져오기
try {
    $stmt = $pdo->query("SELECT * FROM layer_popups ORDER BY display_order ASC, id DESC");
    $popups = $stmt->fetchAll();
} catch (PDOException $e) {
    $popups = [];
    $error = "팝업 목록을 불러오는 중 오류가 발생했습니다: " . $e->getMessage();
}

// 팝업 상태 확인 함수
function getPopupStatus($popup) {
    $today = date('Y-m-d');

    if (!$popup['is_active']) {
        return ['status' => 'inactive', 'label' => '비활성', 'class' => 'badge-inactive'];
    }

    if ($popup['start_date'] && $popup['start_date'] > $today) {
        return ['status' => 'scheduled', 'label' => '예약', 'class' => 'badge-scheduled'];
    }

    if ($popup['end_date'] && $popup['end_date'] < $today) {
        return ['status' => 'expired', 'label' => '만료', 'class' => 'badge-expired'];
    }

    return ['status' => 'active', 'label' => '노출중', 'class' => 'badge-active'];
}
?>

<div class="container">
    <div class="content-header">
        <h1>레이어팝업 관리</h1>
        <a href="admin_layer_popup_edit.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> 새 팝업 추가
        </a>
    </div>

    <div class="page-description" style="background: #e3f2fd; padding: 16px; border-radius: 8px; margin-bottom: 24px; color: #1565c0;">
        <i class="fas fa-info-circle"></i> 메인 페이지(index)에 표시되는 레이어 팝업을 관리합니다.
        팝업은 설정된 기간 동안 방문자에게 표시되며, "오늘 하루 보지 않기" 옵션을 제공할 수 있습니다.
    </div>

    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?>" style="background: <?php echo ($_SESSION['message_type'] ?? '') === 'success' ? '#d4edda' : '#f8d7da'; ?>; padding: 16px; border-radius: 8px; margin-bottom: 24px; color: <?php echo ($_SESSION['message_type'] ?? '') === 'success' ? '#155724' : '#721c24'; ?>;">
        <?php echo $_SESSION['message']; ?>
    </div>
    <?php
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    endif;
    ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger" style="background: #f8d7da; padding: 16px; border-radius: 8px; margin-bottom: 24px; color: #721c24;">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($popups)): ?>
    <div class="empty-state">
        <i class="fas fa-window-restore"></i>
        <h3>등록된 팝업이 없습니다</h3>
        <p>새 팝업을 추가하여 메인 페이지에 표시해보세요.</p>
        <a href="admin_layer_popup_edit.php" class="btn btn-primary" style="margin-top: 20px;">
            <i class="fas fa-plus"></i> 첫 팝업 추가하기
        </a>
    </div>
    <?php else: ?>
    <table class="popup-table">
        <thead>
            <tr>
                <th style="width: 100px;">이미지</th>
                <th>제목</th>
                <th style="width: 120px;">노출 기간</th>
                <th style="width: 100px;">위치/크기</th>
                <th style="width: 80px;">상태</th>
                <th style="width: 80px;">순서</th>
                <th style="width: 150px;">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($popups as $index => $popup):
                $status = getPopupStatus($popup);
            ?>
            <tr>
                <td>
                    <?php if ($popup['image_path']): ?>
                    <img src="../uploads/popups/<?php echo htmlspecialchars($popup['image_path']); ?>"
                         alt="<?php echo htmlspecialchars($popup['title']); ?>"
                         class="popup-image">
                    <?php else: ?>
                    <div class="popup-image-placeholder">
                        <i class="fas fa-image"></i>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($popup['title']); ?></strong>
                    <?php if ($popup['link_url']): ?>
                    <br>
                    <small style="color: #666;">
                        <i class="fas fa-link"></i> <?php echo htmlspecialchars(mb_substr($popup['link_url'], 0, 40)); ?><?php echo mb_strlen($popup['link_url']) > 40 ? '...' : ''; ?>
                    </small>
                    <?php endif; ?>
                    <?php if ($popup['hide_today']): ?>
                    <br>
                    <small style="color: #888;"><i class="fas fa-eye-slash"></i> 오늘 하루 보지 않기</small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($popup['start_date'] || $popup['end_date']): ?>
                    <div class="date-range">
                        <?php if ($popup['start_date']): ?>
                        <?php echo date('Y-m-d', strtotime($popup['start_date'])); ?>
                        <?php endif; ?>
                        ~
                        <?php if ($popup['end_date']): ?>
                        <?php echo date('Y-m-d', strtotime($popup['end_date'])); ?>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <span style="color: #999;">상시 노출</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="position-info">
                        위치: <?php echo $popup['position_left']; ?>, <?php echo $popup['position_top']; ?>
                        <br>
                        크기: <?php echo $popup['width']; ?> x <?php echo $popup['height']; ?>
                    </div>
                </td>
                <td>
                    <a href="javascript:void(0)" onclick="toggleActive(<?php echo $popup['id']; ?>, <?php echo $popup['is_active']; ?>)">
                        <span class="badge <?php echo $status['class']; ?>">
                            <?php echo $status['label']; ?>
                        </span>
                    </a>
                </td>
                <td>
                    <div class="action-buttons">
                        <?php if ($index > 0): ?>
                        <button onclick="moveUp(<?php echo $popup['id']; ?>)" class="btn-icon btn-order" title="위로">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($index < count($popups) - 1): ?>
                        <button onclick="moveDown(<?php echo $popup['id']; ?>)" class="btn-icon btn-order" title="아래로">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div class="action-buttons">
                        <button onclick="previewPopup(<?php echo $popup['id']; ?>)" class="btn-icon btn-preview" title="미리보기">
                            <i class="fas fa-eye"></i>
                        </button>
                        <a href="admin_layer_popup_edit.php?id=<?php echo $popup['id']; ?>" class="btn-icon btn-edit" title="수정">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deletePopup(<?php echo $popup['id']; ?>)" class="btn-icon btn-delete" title="삭제">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- 미리보기 모달 -->
<div id="previewModal" class="preview-modal">
    <div class="preview-popup">
        <button class="preview-close" onclick="closePreviewModal()">&times;</button>
        <div id="previewContainer"></div>
    </div>
</div>

<?php require_once 'admin_tail.php'; ?>
