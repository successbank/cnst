<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$weight = null;

// 수정 모드인 경우 기존 데이터 가져오기
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM unit_weights WHERE id = ?");
    $stmt->execute([$id]);
    $weight = $stmt->fetch();
    
    if (!$weight) {
        header("Location: admin_unit_weights.php");
        exit;
    }
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_type = trim($_POST['product_type'] ?? '');
    $specification = trim($_POST['specification'] ?? '');
    $unit_weight = (float)($_POST['unit_weight'] ?? 0);
    $material = trim($_POST['material'] ?? '');
    $height = (int)($_POST['height'] ?? 0);
    $width = (int)($_POST['width'] ?? 0);
    $web_thickness = (float)($_POST['web_thickness'] ?? 0);
    $flange_thickness = (float)($_POST['flange_thickness'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // 유효성 검사
    $errors = [];
    if (!$product_type) $errors[] = "제품 타입을 입력해주세요.";
    if (!$specification) $errors[] = "규격을 입력해주세요.";
    if ($unit_weight <= 0) $errors[] = "단위중량을 올바르게 입력해주세요.";
    
    if (!$errors) {
        try {
            if ($id > 0) {
                // 수정
                $stmt = $pdo->prepare("
                    UPDATE unit_weights SET 
                        product_type = ?, specification = ?, unit_weight = ?,
                        material = ?, height = ?, width = ?,
                        web_thickness = ?, flange_thickness = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $product_type, $specification, $unit_weight,
                    $material, $height, $width,
                    $web_thickness, $flange_thickness, $is_active,
                    $id
                ]);
            } else {
                // 신규 등록
                $stmt = $pdo->prepare("
                    INSERT INTO unit_weights (
                        product_type, specification, unit_weight,
                        material, height, width,
                        web_thickness, flange_thickness, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $product_type, $specification, $unit_weight,
                    $material, $height, $width,
                    $web_thickness, $flange_thickness, $is_active
                ]);
            }
            
            header("Location: admin_unit_weights.php?message=saved");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $errors[] = "동일한 제품 타입과 규격이 이미 존재합니다.";
            } else {
                $errors[] = "저장 중 오류가 발생했습니다: " . $e->getMessage();
            }
        }
    }
}

// 제품 타입 목록
$product_types = ['H형강', 'I형강', 'ㄱ형강', 'ㄷ형강', '환봉', '평철', 'C형강', '사각파이프', '원형파이프'];

include 'admin_head.php';
?>

<style>
.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.form-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

.btn-back {
    padding: 10px 20px;
    background: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-back:hover {
    background: #5a6268;
}

.form-container {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.error-messages {
    background: #f8d7da;
    color: #721c24;
    padding: 12px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}

.error-messages ul {
    margin: 0;
    padding-left: 20px;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.required {
    color: #dc3545;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #eee;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: white;
    color: #333;
    border: 2px solid #ddd;
}

.btn-secondary:hover {
    background: #f8f9fa;
}

.help-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.spec-preview {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 4px;
    font-family: monospace;
    margin-top: 10px;
}
</style>

<div class="form-header">
    <h2><?php echo $id > 0 ? '단중표 수정' : '단중표 추가'; ?></h2>
    <a href="admin_unit_weights.php" class="btn-back">목록으로</a>
</div>

<?php if (!empty($errors)): ?>
<div class="error-messages">
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="" class="form-container">
    <!-- 기본 정보 -->
    <div class="form-section">
        <h3 class="section-title">기본 정보</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="product_type">제품 타입 <span class="required">*</span></label>
                <select id="product_type" name="product_type" required onchange="updateSpecification()">
                    <option value="">선택하세요</option>
                    <?php foreach ($product_types as $type): ?>
                        <option value="<?php echo $type; ?>"
                                <?php echo ($weight['product_type'] ?? '') == $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="material">재질</label>
                <input type="text" id="material" name="material" 
                       value="<?php echo htmlspecialchars($weight['material'] ?? ''); ?>"
                       placeholder="예: SS400, SM490">
            </div>
        </div>
        
        <div class="form-group">
            <label for="specification">규격 <span class="required">*</span></label>
            <input type="text" id="specification" name="specification" 
                   value="<?php echo htmlspecialchars($weight['specification'] ?? ''); ?>"
                   placeholder="예: 100*100*6*8" required onchange="updateSpecPreview()">
            <div class="spec-preview" id="specPreview" style="display: none;"></div>
            <div class="help-text">H형강: 높이*너비*웹두께*플랜지두께 형식으로 입력</div>
        </div>
        
        <div class="form-group">
            <label for="unit_weight">단위중량(kg/m) <span class="required">*</span></label>
            <input type="number" id="unit_weight" name="unit_weight" step="0.1"
                   value="<?php echo $weight['unit_weight'] ?? ''; ?>"
                   placeholder="0.0" required>
        </div>
    </div>
    
    <!-- 상세 치수 -->
    <div class="form-section">
        <h3 class="section-title">상세 치수 (선택사항)</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="height">높이(mm)</label>
                <input type="number" id="height" name="height" 
                       value="<?php echo $weight['height'] ?? ''; ?>"
                       placeholder="0">
            </div>
            
            <div class="form-group">
                <label for="width">너비(mm)</label>
                <input type="number" id="width" name="width" 
                       value="<?php echo $weight['width'] ?? ''; ?>"
                       placeholder="0">
            </div>
            
            <div class="form-group">
                <label for="web_thickness">웹두께(mm)</label>
                <input type="number" id="web_thickness" name="web_thickness" step="0.1"
                       value="<?php echo $weight['web_thickness'] ?? ''; ?>"
                       placeholder="0.0">
            </div>
            
            <div class="form-group">
                <label for="flange_thickness">플랜지두께(mm)</label>
                <input type="number" id="flange_thickness" name="flange_thickness" step="0.1"
                       value="<?php echo $weight['flange_thickness'] ?? ''; ?>"
                       placeholder="0.0">
            </div>
        </div>
        
        <div class="help-text">
            규격에서 자동으로 파싱할 수 있는 경우 자동 입력됩니다.
        </div>
    </div>
    
    <!-- 상태 설정 -->
    <div class="form-section">
        <h3 class="section-title">상태 설정</h3>
        
        <div class="checkbox-group">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   <?php echo ($weight['is_active'] ?? 1) ? 'checked' : ''; ?>>
            <label for="is_active">활성화</label>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <?php echo $id > 0 ? '수정하기' : '등록하기'; ?>
        </button>
        <a href="admin_unit_weights.php" class="btn btn-secondary">취소</a>
    </div>
</form>

<script>
function updateSpecification() {
    const productType = document.getElementById('product_type').value;
    const spec = document.getElementById('specification');
    
    if (productType === 'H형강' && !spec.value) {
        spec.placeholder = '예: 100*100*6*8';
    }
}

function updateSpecPreview() {
    const spec = document.getElementById('specification').value;
    const preview = document.getElementById('specPreview');
    
    if (spec && spec.includes('*')) {
        const parts = spec.split('*');
        if (parts.length === 4) {
            preview.innerHTML = `높이: ${parts[0]}mm, 너비: ${parts[1]}mm, 웹: ${parts[2]}mm, 플랜지: ${parts[3]}mm`;
            preview.style.display = 'block';
            
            // 자동으로 치수 입력
            document.getElementById('height').value = parts[0];
            document.getElementById('width').value = parts[1];
            document.getElementById('web_thickness').value = parts[2];
            document.getElementById('flange_thickness').value = parts[3];
        }
    }
}

// 페이지 로드시 실행
window.onload = function() {
    updateSpecPreview();
};
</script>

<?php include 'admin_tail.php'; ?>