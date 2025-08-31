<?php
require_once 'admin_check.php';
require_once '../db.php';

$isEdit = false;
$banner = null;

// 수정 모드인 경우 기존 데이터 가져오기
if (isset($_GET['id'])) {
    $isEdit = true;
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $banner = $stmt->fetch();
    
    if (!$banner) {
        $_SESSION['message'] = '배너를 찾을 수 없습니다.';
        $_SESSION['message_type'] = 'danger';
        header('Location: admin_banners.php');
        exit;
    }
}

$pageTitle = $isEdit ? '배너 수정' : '배너 추가';

// 추가 스타일
$additionalStyles = '
.form-container {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    max-width: 800px;
    margin: 0 auto;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #FF6B6B;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.form-help {
    font-size: 14px;
    color: #666;
    margin-top: 4px;
}

.image-preview {
    margin-top: 16px;
    max-width: 400px;
}

.image-preview img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-check input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e0e0e0;
}

.file-input-wrapper {
    position: relative;
    display: inline-block;
    cursor: pointer;
    width: 100%;
}

.file-input-wrapper input[type="file"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-input-label {
    display: block;
    padding: 12px 16px;
    border: 2px dashed #e0e0e0;
    border-radius: 8px;
    text-align: center;
    color: #666;
    transition: all 0.2s;
}

.file-input-wrapper:hover .file-input-label {
    border-color: #FF6B6B;
    color: #FF6B6B;
}

.current-image {
    margin-bottom: 16px;
}

.current-image img {
    max-width: 300px;
    height: auto;
    border-radius: 8px;
}
';

// 추가 스크립트
$additionalScripts = '
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById("image-preview");
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            preview.style.display = "block";
        }
        
        reader.readAsDataURL(input.files[0]);
        
        // 파일명 표시
        const fileName = input.files[0].name;
        document.querySelector(".file-input-label").innerHTML = `<i class="fas fa-check"></i> ${fileName}`;
    }
}
';

require_once 'admin_head.php';
?>

<div class="container">
    <div class="content-header">
        <h1><?php echo $pageTitle; ?></h1>
    </div>

    <form action="admin_banner_action.php" method="POST" enctype="multipart/form-data" class="form-container">
        <?php if ($isEdit): ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
        <?php else: ?>
        <input type="hidden" name="action" value="create">
        <?php endif; ?>

        <div class="form-group">
            <label for="title" class="form-label">배너 제목 *</label>
            <input type="text" id="title" name="title" class="form-control" 
                   value="<?php echo $isEdit ? escape($banner['title']) : ''; ?>" required>
            <div class="form-help">메인 페이지에 표시될 배너의 제목입니다.</div>
        </div>

        <div class="form-group">
            <label for="subtitle" class="form-label">부제목</label>
            <textarea id="subtitle" name="subtitle" class="form-control"><?php echo $isEdit ? escape($banner['subtitle']) : ''; ?></textarea>
            <div class="form-help">배너 제목 아래에 표시될 부제목입니다.</div>
        </div>

        <div class="form-group">
            <label for="image" class="form-label">배너 이미지</label>
            
            <?php if ($isEdit && $banner['image_path']): ?>
            <div class="current-image">
                <p style="margin-bottom: 8px; font-weight: 500;">현재 이미지:</p>
                <img src="../uploads/banners/<?php echo escape($banner['image_path']); ?>" 
                     alt="Current banner">
            </div>
            <?php endif; ?>
            
            <div class="file-input-wrapper">
                <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                <label for="image" class="file-input-label">
                    <i class="fas fa-upload"></i> 이미지 선택 (JPG, PNG)
                </label>
            </div>
            <div class="form-help">권장 크기: 1920x600px</div>
            <div id="image-preview" class="image-preview" style="display: none;"></div>
        </div>

        <div class="form-group">
            <label for="link_url" class="form-label">링크 URL</label>
            <input type="url" id="link_url" name="link_url" class="form-control" 
                   placeholder="https://example.com"
                   value="<?php echo $isEdit ? escape($banner['link_url']) : ''; ?>">
            <div class="form-help">배너 클릭 시 이동할 URL입니다. 비워두면 링크가 없습니다.</div>
        </div>

        <div class="form-group">
            <label for="link_target" class="form-label">링크 타겟</label>
            <select id="link_target" name="link_target" class="form-control">
                <option value="_self" <?php echo ($isEdit && $banner['link_target'] == '_self') ? 'selected' : ''; ?>>
                    현재 창에서 열기
                </option>
                <option value="_blank" <?php echo ($isEdit && $banner['link_target'] == '_blank') ? 'selected' : ''; ?>>
                    새 창에서 열기
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="display_order" class="form-label">표시 순서</label>
            <input type="number" id="display_order" name="display_order" class="form-control" 
                   value="<?php echo $isEdit ? $banner['display_order'] : '0'; ?>" min="0">
            <div class="form-help">낮은 숫자가 먼저 표시됩니다.</div>
        </div>

        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" id="is_active" name="is_active" value="1" 
                       <?php echo (!$isEdit || $banner['is_active']) ? 'checked' : ''; ?>>
                <label for="is_active">활성화</label>
            </div>
            <div class="form-help">체크 해제 시 배너가 표시되지 않습니다.</div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?php echo $isEdit ? '수정' : '등록'; ?>
            </button>
            <a href="admin_banners.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> 취소
            </a>
        </div>
    </form>
</div>

<?php require_once 'admin_tail.php'; ?>