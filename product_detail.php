<?php
require_once 'db.php';
$currentPage = 'products';

// 파라미터 처리
$category_code = $_GET['category'] ?? '';
$product_id = $_GET['id'] ?? 0;
$selected_spec = $_GET['spec'] ?? '';

if (empty($category_code)) {
    header('Location: products.php');
    exit;
}

// 제품 정보 조회
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name 
    FROM products p 
    JOIN product_categories pc ON p.category_code = pc.category_code 
    WHERE p.category_code = ? AND p.has_calculator = 1
    LIMIT 1
");
$stmt->execute([$category_code]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit;
}

// JSON 데이터 파싱
$unit_weight_data = json_decode($product['unit_weight_data'], true) ?? [];
$available_materials = json_decode($product['available_materials'], true) ?? [];
$available_sizes = json_decode($product['available_sizes'], true) ?? [];

$pageTitle = $product['product_name'] . ' 계산기';
$additionalCSS = [];
require_once 'head.php';
?>

<style>
.calculator-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
}

.product-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 12px;
}

.product-header h1 {
    font-size: 32px;
    color: #333;
    margin-bottom: 10px;
}

.product-header .category-name {
    font-size: 18px;
    color: #666;
}

.calculator-form {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 16px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #1428A0;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.calculation-result {
    margin-top: 30px;
    padding: 25px;
    background: #f0f4ff;
    border-radius: 12px;
    display: none;
}

.calculation-result.show {
    display: block;
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.result-header {
    font-size: 20px;
    font-weight: 600;
    color: #1428A0;
    margin-bottom: 15px;
}

.result-value {
    font-size: 36px;
    font-weight: 700;
    color: #1428A0;
    margin: 20px 0;
}

.calculation-steps {
    margin-top: 20px;
    padding: 20px;
    background: white;
    border-radius: 8px;
}

.calculation-steps h4 {
    font-size: 16px;
    margin-bottom: 10px;
    color: #666;
}

.help-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.form-control.error {
    border-color: #dc3545;
    background-color: #fff5f5;
}

.step {
    padding: 8px 0;
    color: #333;
    border-bottom: 1px solid #eee;
}

.step:last-child {
    border-bottom: none;
}

.unit-weight-info {
    display: inline-block;
    margin-left: 10px;
    padding: 4px 12px;
    background: #e8f0ff;
    border-radius: 20px;
    font-size: 14px;
    color: #1428A0;
}

.btn-calculate {
    width: 100%;
    padding: 16px;
    background: #1428A0;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-calculate:hover {
    background: #0F1F7A;
}

.btn-calculate:disabled {
    background: #ccc;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .calculator-container {
        padding: 10px;
    }
    
    .calculator-form {
        padding: 20px;
    }
}
</style>

<div class="calculator-container">
    <div class="product-header">
        <h1><?php echo htmlspecialchars($product['product_name']); ?> 중량 계산기</h1>
        <p class="category-name"><?php echo htmlspecialchars($product['category_name']); ?></p>
    </div>

    <form class="calculator-form" id="calculatorForm">
        <input type="hidden" id="categoryCode" value="<?php echo htmlspecialchars($category_code); ?>">
        <input type="hidden" id="calculationType" value="<?php echo htmlspecialchars($product['calculation_type']); ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="specification">규격 선택</label>
                <select class="form-control" id="specification" required>
                    <option value="">규격을 선택하세요</option>
                    <?php foreach ($available_sizes as $size): ?>
                        <option value="<?php echo htmlspecialchars($size); ?>" <?php echo $selected_spec === $size ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($size); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="unit-weight-info" id="unitWeightInfo" style="display: none;">
                    단위중량: <span id="unitWeightValue">0</span> kg
                </span>
            </div>

            <?php
            // 원산지 정보 가져오기
            $available_origins = [];
            if (!empty($product['available_origins'])) {
                $available_origins = json_decode($product['available_origins'], true) ?: [];
            }
            ?>
            <?php if (count($available_origins) > 0): ?>
            <div class="form-group">
                <label for="origin">원산지 선택</label>
                <select class="form-control" id="origin">
                    <?php foreach ($available_origins as $index => $origin): ?>
                        <option value="<?php echo htmlspecialchars($origin); ?>" <?php echo $index === 0 ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($origin); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="material">재질 선택</label>
                <select class="form-control" id="material">
                    <option value="">기본 재질</option>
                    <?php foreach ($available_materials as $material): ?>
                        <option value="<?php echo htmlspecialchars($material); ?>">
                            <?php echo htmlspecialchars($material); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <?php if ($product['calculation_type'] === 'linear'): ?>
        <div class="form-row">
            <div class="form-group">
                <label for="length">길이 (미터)</label>
                <input type="number" class="form-control" id="length"
                       min="<?php echo $product['min_length'] ?? 0.1; ?>"
                       max="<?php echo $product['max_length'] ?? 100; ?>"
                       step="0.1"
                       placeholder="예: <?php echo $product['standard_length'] ?? 6; ?>" required>
                <div class="length-error-message" id="length-error"></div>
                <?php if (!empty($product['min_length']) && !empty($product['max_length'])): ?>
                <div class="help-text">입력 가능 범위: <?php echo $product['min_length']; ?>m ~ <?php echo $product['max_length']; ?>m</div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="quantity">수량 (본)</label>
                <input type="number" class="form-control" id="quantity" min="1" placeholder="예: 10" required>
            </div>
        </div>
        <?php else: ?>
        <div class="form-group">
            <label for="quantity">수량 (장)</label>
            <input type="number" class="form-control" id="quantity" min="1" placeholder="예: 10" required>
        </div>
        <?php endif; ?>
        
        <button type="submit" class="btn-calculate" id="calculateBtn">중량 계산하기</button>
    </form>
    
    <div class="calculation-result" id="calculationResult">
        <div class="result-header">계산 결과</div>
        <div class="result-value" id="resultValue">0 kg</div>
        
        <div class="calculation-steps">
            <h4>계산 과정</h4>
            <div id="calculationSteps"></div>
        </div>
    </div>
</div>

<script>
// 단위중량 데이터
const unitWeightData = <?php echo json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE); ?>;
const calculationType = '<?php echo $product['calculation_type']; ?>';

// 길이 제한값
const minLength = <?php echo floatval($product['min_length'] ?? 0.1); ?>;
const maxLength = <?php echo floatval($product['max_length'] ?? 100); ?>;

// 길이 검증 함수
function validateLength(value) {
    const lengthInput = document.getElementById('length');
    const errorDiv = document.getElementById('length-error');

    if (!lengthInput || !errorDiv) return true;

    const length = parseFloat(value);

    if (isNaN(length) || length === 0) {
        lengthInput.classList.remove('error');
        errorDiv.classList.remove('show');
        errorDiv.textContent = '';
        return true;
    }

    if (length < minLength) {
        lengthInput.classList.add('error');
        errorDiv.classList.add('show');
        errorDiv.textContent = `최소 ${minLength}m 이상 입력해주세요.`;
        return false;
    }

    if (length > maxLength) {
        lengthInput.classList.add('error');
        errorDiv.classList.add('show');
        errorDiv.textContent = `최대 ${maxLength}m까지 입력 가능합니다.`;
        return false;
    }

    lengthInput.classList.remove('error');
    errorDiv.classList.remove('show');
    errorDiv.textContent = '';
    return true;
}

// 규격 선택 시 단위중량 표시
document.getElementById('specification').addEventListener('change', function() {
    const specification = this.value;
    const material = document.getElementById('material').value;
    
    if (specification && unitWeightData[specification]) {
        let unitWeight = 0;
        if (material && unitWeightData[specification][material]) {
            unitWeight = unitWeightData[specification][material];
        } else {
            // 첫 번째 재질의 단위중량 사용
            unitWeight = Object.values(unitWeightData[specification])[0];
        }
        
        document.getElementById('unitWeightValue').textContent = unitWeight;
        document.getElementById('unitWeightInfo').style.display = 'inline-block';
    } else {
        document.getElementById('unitWeightInfo').style.display = 'none';
    }
});

// 재질 변경 시 단위중량 업데이트
document.getElementById('material').addEventListener('change', function() {
    const specSelect = document.getElementById('specification');
    if (specSelect.value) {
        specSelect.dispatchEvent(new Event('change'));
    }
});

// 폼 제출 처리
document.getElementById('calculatorForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const lengthInput = document.getElementById('length');
    const lengthValue = lengthInput ? parseFloat(lengthInput.value) : 0;

    // 선형 제품인 경우 길이 검증
    if (calculationType === 'linear' && lengthInput) {
        if (!validateLength(lengthValue)) {
            return; // 검증 실패 시 제출 중단
        }
    }

    const btn = document.getElementById('calculateBtn');
    btn.disabled = true;
    btn.textContent = '계산 중...';

    try {
        const formData = {
            category: document.getElementById('categoryCode').value,
            specification: document.getElementById('specification').value,
            origin: document.getElementById('origin')?.value || '',
            material: document.getElementById('material').value,
            length: lengthValue,
            quantity: parseInt(document.getElementById('quantity').value)
        };
        
        const response = await fetch('/api/calculate_weight.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // 결과 표시
            document.getElementById('resultValue').textContent = result.data.calculated_weight + ' kg';
            
            // 계산 과정 표시
            const stepsHtml = result.data.calculation_steps.map(step => 
                `<div class="step">${step}</div>`
            ).join('');
            document.getElementById('calculationSteps').innerHTML = stepsHtml;
            
            // 결과 영역 표시
            document.getElementById('calculationResult').classList.add('show');
            
            // 스크롤 이동
            document.getElementById('calculationResult').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        } else {
            alert('계산 중 오류가 발생했습니다: ' + result.error);
        }
    } catch (error) {
        alert('서버 연결 오류가 발생했습니다.');
        console.error(error);
    } finally {
        btn.disabled = false;
        btn.textContent = '중량 계산하기';
    }
});

// 길이 필드 표시/숨김 (선형 제품만)
if (calculationType !== 'linear') {
    const lengthField = document.getElementById('length');
    if (lengthField) {
        lengthField.closest('.form-group').style.display = 'none';
    }
}

// 길이 입력 이벤트 리스너
const lengthInput = document.getElementById('length');
if (lengthInput) {
    lengthInput.addEventListener('input', function() {
        validateLength(this.value);
    });

    lengthInput.addEventListener('blur', function() {
        validateLength(this.value);
    });
}

// 페이지 로드 시 선택된 규격이 있으면 단위중량 표시
window.addEventListener('DOMContentLoaded', function() {
    const specSelect = document.getElementById('specification');
    if (specSelect.value) {
        specSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<?php require_once 'tail.php'; ?>