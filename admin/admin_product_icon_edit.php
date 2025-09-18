<?php
$pageTitle = '제품 아이콘 편집';
require_once 'admin_head.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$icon = null;

// 수정 모드인 경우 기존 데이터 가져오기
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM product_icons WHERE id = ?");
        $stmt->execute([$id]);
        $icon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$icon) {
            $_SESSION['msg'] = '존재하지 않는 아이콘입니다.';
            $_SESSION['msgType'] = 'error';
            header('Location: admin_product_icons.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['msg'] = '데이터를 불러오는 중 오류가 발생했습니다.';
        $_SESSION['msgType'] = 'error';
        header('Location: admin_product_icons.php');
        exit;
    }
}

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_code = trim($_POST['category_code'] ?? '');
    $icon_name = trim($_POST['icon_name'] ?? '');
    $icon_url = trim($_POST['icon_url'] ?? '');
    $url_target = $_POST['url_target'] ?? '_self';
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // 유효성 검사
    if (empty($icon_name)) {
        $error = '표시명을 입력해주세요.';
    } else {
        try {
            // 이미지 업로드 처리
            $icon_image_path = $icon['icon_image'] ?? ''; // 기존 이미지 경로
            
            if (isset($_FILES['icon_image']) && $_FILES['icon_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/product_icons/';
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                $fileType = $_FILES['icon_image']['type'];
                $fileSize = $_FILES['icon_image']['size'];
                
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception('허용되지 않는 파일 형식입니다. (JPG, PNG, GIF, SVG만 가능)');
                }
                
                if ($fileSize > $maxSize) {
                    throw new Exception('파일 크기는 2MB를 초과할 수 없습니다.');
                }
                
                // 파일명 생성
                $extension = pathinfo($_FILES['icon_image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                $uploadPath = $uploadDir . $filename;
                
                // 기존 이미지 삭제
                if ($icon_image_path && file_exists('../' . $icon_image_path)) {
                    unlink('../' . $icon_image_path);
                }
                
                // 새 이미지 업로드
                if (move_uploaded_file($_FILES['icon_image']['tmp_name'], $uploadPath)) {
                    $icon_image_path = 'uploads/product_icons/' . $filename;
                } else {
                    throw new Exception('파일 업로드에 실패했습니다.');
                }
            }
            
            if ($id > 0) {
                // 수정
                $sql = "UPDATE product_icons SET 
                        category_code = ?,
                        icon_name = ?,
                        icon_image = ?,
                        icon_url = ?,
                        url_target = ?,
                        display_order = ?,
                        is_active = ?,
                        updated_at = NOW()
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $category_code,
                    $icon_name,
                    $icon_image_path,
                    $icon_url,
                    $url_target,
                    $display_order,
                    $is_active,
                    $id
                ]);
                $_SESSION['msg'] = '아이콘이 수정되었습니다.';
            } else {
                // 추가
                $sql = "INSERT INTO product_icons 
                        (category_code, icon_name, icon_image, icon_url, url_target, display_order, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $category_code,
                    $icon_name,
                    $icon_image_path,
                    $icon_url,
                    $url_target,
                    $display_order,
                    $is_active
                ]);
                $_SESSION['msg'] = '아이콘이 추가되었습니다.';
            }
            
            $_SESSION['msgType'] = 'success';
            header('Location: admin_product_icons.php');
            exit;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<style>
.form-container {
    background: white;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    max-width: 800px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group select {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #1A237E;
    box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
}

.form-group .help-text {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}

.icon-preview-container {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-top: 10px;
}

.current-icon-preview {
    width: 80px;
    height: 80px;
    background: #1A237E;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 36px;
}

.current-icon-preview img {
    width: 50px;
    height: 50px;
    object-fit: contain;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
}

.form-buttons {
    display: flex;
    gap: 12px;
    margin-top: 32px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-block;
}

.btn-primary {
    background: #1A237E;
    color: white;
}

.btn-primary:hover {
    background: #283593;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #E5E5E7;
    color: #666;
}

.btn-secondary:hover {
    background: #D5D5D7;
}

.error-message {
    background: #FFEBEE;
    color: #C62828;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.radio-group {
    display: flex;
    gap: 20px;
    margin-top: 10px;
}

.radio-label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: normal;
}
</style>

<div class="page-header">
    <h1><?php echo $id ? '제품 아이콘 수정' : '제품 아이콘 추가'; ?></h1>
    <p>메인페이지에 표시될 제품 아이콘 정보를 입력합니다.</p>
</div>

<?php if (isset($error)): ?>
<div class="error-message">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="form-container">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>카테고리 코드</label>
            <input type="text" name="category_code" value="<?php echo htmlspecialchars($icon['category_code'] ?? ''); ?>" 
                   placeholder="예: rebar, hbeam">
            <div class="help-text">영문 소문자와 하이픈만 사용</div>
        </div>
        
        <div class="form-group">
            <label>표시명 <span style="color: red;">*</span></label>
            <input type="text" name="icon_name" value="<?php echo htmlspecialchars($icon['icon_name'] ?? ''); ?>" 
                   placeholder="예: 철근(특판)" required>
        </div>
        
        <div class="form-group">
            <label>아이콘 이미지</label>
            <?php if ($icon && $icon['icon_image']): ?>
            <div class="icon-preview-container">
                <div class="current-icon-preview">
                    <img src="../<?php echo htmlspecialchars($icon['icon_image']); ?>" alt="">
                </div>
                <div>
                    <p style="font-size: 13px; color: #666;">현재 이미지</p>
                </div>
            </div>
            <?php endif; ?>
            <input type="file" name="icon_image" accept="image/jpeg,image/png,image/gif,image/svg+xml">
            <div class="help-text">권장 크기: 60x60px, 최대 2MB (JPG, PNG, GIF, SVG)</div>
        </div>
        
        <div class="form-group">
            <label>링크 URL</label>
            <input type="text" name="icon_url" value="<?php echo htmlspecialchars($icon['icon_url'] ?? ''); ?>" 
                   placeholder="예: products.php#rebar">
            <div class="help-text">아이콘 클릭 시 이동할 URL</div>
        </div>
        
        <div class="form-group">
            <label>링크 타겟</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="url_target" value="_self" 
                           <?php echo (!$icon || $icon['url_target'] === '_self') ? 'checked' : ''; ?>>
                    현재창에서 열기
                </label>
                <label class="radio-label">
                    <input type="radio" name="url_target" value="_blank" 
                           <?php echo ($icon && $icon['url_target'] === '_blank') ? 'checked' : ''; ?>>
                    새창에서 열기
                </label>
            </div>
        </div>
        
        <div class="form-group">
            <label>표시 순서</label>
            <input type="number" name="display_order" value="<?php echo $icon['display_order'] ?? 0; ?>" min="0">
            <div class="help-text">숫자가 작을수록 먼저 표시됩니다</div>
        </div>
        
        <div class="form-group">
            <label>상태</label>
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" 
                           <?php echo (!$icon || $icon['is_active']) ? 'checked' : ''; ?>>
                    활성화
                </label>
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="submit" class="btn btn-primary">
                <?php echo $id ? '수정하기' : '추가하기'; ?>
            </button>
            <a href="admin_product_icons.php" class="btn btn-secondary">취소</a>
        </div>
    </form>
</div>

<?php require_once 'admin_tail.php'; ?>