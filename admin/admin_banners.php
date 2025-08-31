<?php
require_once 'admin_check.php';
$pageTitle = '배너 관리';

// 추가 스타일
$additionalStyles = '
.banner-table {
    width: 100%;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.banner-table th {
    background: #f5f5f5;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #e0e0e0;
}

.banner-table td {
    padding: 16px;
    border-bottom: 1px solid #f0f0f0;
}

.banner-table tr:last-child td {
    border-bottom: none;
}

.banner-table tr:hover {
    background: #f9f9f9;
}

.banner-image {
    width: 100px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
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
    background: #FF6B6B;
    color: white;
}

.btn-primary:hover {
    background: #FF5252;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

.btn i {
    margin-right: 6px;
}
';

// 추가 스크립트
$additionalScripts = '
function deleteBanner(id) {
    if (confirm("정말로 이 배너를 삭제하시겠습니까?")) {
        window.location.href = "admin_banner_action.php?action=delete&id=" + id;
    }
}

function toggleActive(id, currentStatus) {
    const newStatus = currentStatus == 1 ? 0 : 1;
    window.location.href = "admin_banner_action.php?action=toggle&id=" + id + "&status=" + newStatus;
}

function moveUp(id) {
    window.location.href = "admin_banner_action.php?action=move&id=" + id + "&direction=up";
}

function moveDown(id) {
    window.location.href = "admin_banner_action.php?action=move&id=" + id + "&direction=down";
}
';

require_once 'admin_head.php';
require_once '../db.php';

// 배너 목록 가져오기
try {
    $stmt = $pdo->query("SELECT * FROM banners ORDER BY display_order ASC, id DESC");
    $banners = $stmt->fetchAll();
} catch (PDOException $e) {
    $banners = [];
    $error = "배너 목록을 불러오는 중 오류가 발생했습니다.";
}
?>

<div class="container">
    <div class="content-header">
        <h1>배너 관리</h1>
        <a href="admin_banner_edit.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> 새 배너 추가
        </a>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?>">
        <?php echo $_SESSION['message']; ?>
    </div>
    <?php 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    endif; 
    ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (empty($banners)): ?>
    <div class="empty-state">
        <i class="fas fa-images"></i>
        <h3>등록된 배너가 없습니다</h3>
        <p>새 배너를 추가하여 메인 페이지에 표시해보세요.</p>
        <a href="admin_banner_edit.php" class="btn btn-primary" style="margin-top: 20px;">
            <i class="fas fa-plus"></i> 첫 배너 추가하기
        </a>
    </div>
    <?php else: ?>
    <table class="banner-table">
        <thead>
            <tr>
                <th style="width: 120px;">이미지</th>
                <th>제목</th>
                <th>부제목</th>
                <th style="width: 100px;">상태</th>
                <th style="width: 100px;">순서</th>
                <th style="width: 150px;">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($banners as $index => $banner): ?>
            <tr>
                <td>
                    <?php if ($banner['image_path']): ?>
                    <img src="../uploads/banners/<?php echo escape($banner['image_path']); ?>" 
                         alt="<?php echo escape($banner['title']); ?>" 
                         class="banner-image">
                    <?php else: ?>
                    <div style="width: 100px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #999;">
                        <i class="fas fa-image"></i>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?php echo escape($banner['title']); ?></strong>
                    <?php if ($banner['link_url']): ?>
                    <br>
                    <small style="color: #666;">
                        <i class="fas fa-link"></i> <?php echo escape($banner['link_url']); ?>
                    </small>
                    <?php endif; ?>
                </td>
                <td><?php echo escape(mb_substr($banner['subtitle'], 0, 50)); ?><?php echo mb_strlen($banner['subtitle']) > 50 ? '...' : ''; ?></td>
                <td>
                    <a href="javascript:void(0)" onclick="toggleActive(<?php echo $banner['id']; ?>, <?php echo $banner['is_active']; ?>)">
                        <span class="badge <?php echo $banner['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php echo $banner['is_active'] ? '활성' : '비활성'; ?>
                        </span>
                    </a>
                </td>
                <td>
                    <div class="action-buttons">
                        <?php if ($index > 0): ?>
                        <button onclick="moveUp(<?php echo $banner['id']; ?>)" class="btn-icon btn-order" title="위로">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <?php endif; ?>
                        <?php if ($index < count($banners) - 1): ?>
                        <button onclick="moveDown(<?php echo $banner['id']; ?>)" class="btn-icon btn-order" title="아래로">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="admin_banner_edit.php?id=<?php echo $banner['id']; ?>" class="btn-icon btn-edit" title="수정">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteBanner(<?php echo $banner['id']; ?>)" class="btn-icon btn-delete" title="삭제">
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

<?php require_once 'admin_tail.php'; ?>