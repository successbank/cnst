<?php
session_start();
require_once 'db.php';
require_once 'includes/SteelCalculator.php';

// 카테고리 코드 확인
$category = $_GET['category'] ?? '';
if (empty($category)) {
    header('Location: products.php');
    exit;
}

// 카테고리 정보 조회
$stmt = $pdo->prepare("
    SELECT * FROM product_categories 
    WHERE category_code = ? AND is_active = 1
");
$stmt->execute([$category]);
$categoryInfo = $stmt->fetch();

if (!$categoryInfo) {
    header('Location: products.php');
    exit;
}

// 해당 카테고리 제품 조회
$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE category_code = ? AND is_active = 1 
    ORDER BY product_name
");
$stmt->execute([$category]);
$products = $stmt->fetchAll();

$calculator = new SteelCalculator($pdo);

// 계산 예제 가져오기
$examples = $calculator->getBeamCalculationExamples($category);

$pageTitle = $categoryInfo['category_name'] . ' 중량 계산기';
$currentPage = 'calculator';
$additionalCSS = ['css/calculator.css'];
require_once 'head.php';
?>

<div class="calculator-container">
    <div class="calculator-header">
        <h1><?php echo htmlspecialchars($categoryInfo['category_name']); ?> 중량 계산기</h1>
        <p>규격을 선택하고 수량을 입력하면 중량과 예상 금액을 자동으로 계산합니다.</p>

        <?php if (!empty($examples) && $category == 'h-beam'): ?>
        <div class="calculation-formula">
            <h4>계산식</h4>
            <p>단위중량 × 길이 = 1본중량 (소수점 첫째자리 반올림)</p>
            <p>1본중량 × 총본수 = 총중량</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="calculator-form">
        <div class="form-group">
            <label for="product">제품 선택</label>
            <select id="product" class="form-control">
                <option value="">제품을 선택하세요</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" id="specificationGroup" style="display: none;">
            <label for="specification">규격 선택</label>
            <select id="specification" class="form-control">
                <option value="">규격을 선택하세요</option>
            </select>
        </div>

        <div class="specification-details" id="specDetails" style="display: none;">
            <!-- 동적으로 규격 상세 정보 표시 -->
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="length">길이 (m)</label>
                <input type="number" id="length" class="form-control" min="0" step="0.1" value="6">
            </div>
            <div class="form-group col-md-6">
                <label for="quantity">수량</label>
                <input type="number" id="quantity" class="form-control" min="1" value="1">
            </div>
        </div>

        <button type="button" class="btn btn-primary btn-calculate" onclick="calculateWeight()">
            중량 계산
        </button>
    </div>

    <div class="calculation-result" id="calculationResult" style="display: none;">
        <h3>계산 결과</h3>
        <div class="result-grid">
            <div class="result-item">
                <span class="label">단위 중량</span>
                <span class="value" id="unitWeight">-</span>
            </div>
            <div class="result-item">
                <span class="label">총 중량</span>
                <span class="value" id="totalWeight">-</span>
            </div>
            <div class="result-item">
                <span class="label">예상 금액</span>
                <span class="value" id="estimatedPrice">-</span>
            </div>
        </div>
        
        <div class="result-actions">
            <button type="button" class="btn btn-secondary" onclick="resetCalculator()">
                다시 계산
            </button>
            <button type="button" class="btn btn-primary" onclick="requestQuote()">
                견적 요청
            </button>
        </div>
    </div>

    <?php if (!empty($examples) && $category == 'h-beam'): ?>
    <div class="calculation-examples">
        <h3>계산 예제</h3>
        <?php foreach ($examples as $index => $example): ?>
        <div class="example-box">
            <h5>예제 <?php echo $index + 1; ?></h5>
            <p>규격: <?php echo $example['specification']; ?>,
               길이: <?php echo $example['length']; ?>미터,
               수량: <?php echo $example['quantity']; ?>본</p>
            <div class="example-calculation">
                <p>① <?php echo $example['unit_weight']; ?> × <?php echo $example['length']; ?> =
                   <?php echo $example['unit_weight'] * $example['length']; ?>kg
                   → <?php echo $example['weight_per_piece']; ?>kg (반올림)</p>
                <p>② <?php echo $example['weight_per_piece']; ?>kg × <?php echo $example['quantity']; ?>본 =
                   <?php echo number_format($example['total_weight']); ?>kg</p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
const categoryCode = '<?php echo $category; ?>';
let currentProduct = null;
let currentSpec = null;

// 제품 선택 시
document.getElementById('product').addEventListener('change', function() {
    const productId = this.value;
    if (!productId) {
        document.getElementById('specificationGroup').style.display = 'none';
        return;
    }
    
    // 규격 목록 로드
    fetch(`api/get_product_specs.php?product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const specSelect = document.getElementById('specification');
                specSelect.innerHTML = '<option value="">규격을 선택하세요</option>';
                
                data.specifications.forEach(spec => {
                    const option = document.createElement('option');
                    option.value = spec.id;
                    option.textContent = spec.spec_name;
                    option.dataset.spec = JSON.stringify(spec);
                    specSelect.appendChild(option);
                });
                
                document.getElementById('specificationGroup').style.display = 'block';
            }
        });
});

// 규격 선택 시
document.getElementById('specification').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (!selectedOption.value) {
        document.getElementById('specDetails').style.display = 'none';
        return;
    }
    
    currentSpec = JSON.parse(selectedOption.dataset.spec);
    
    // 규격 상세 정보 표시
    let detailsHtml = '<div class="spec-info">';
    
    switch (categoryCode) {
        case 'h-beam':
        case 'i-beam':
            detailsHtml += `
                <p>높이: ${currentSpec.height}mm × 폭: ${currentSpec.width}mm</p>
                <p>웨브: ${currentSpec.web_thickness}mm × 플랜지: ${currentSpec.flange_thickness}mm</p>
            `;
            break;
        case 'steel-plate':
            detailsHtml += `
                <p>두께: ${currentSpec.plate_thickness}mm × 폭: ${currentSpec.plate_width}mm</p>
            `;
            break;
        case 'square-pipe':
            detailsHtml += `
                <p>크기: ${currentSpec.width}×${currentSpec.height}mm × 두께: ${currentSpec.thickness}mm</p>
            `;
            break;
        case 'round-pipe':
            detailsHtml += `
                <p>외경: ${currentSpec.outer_diameter}mm × 두께: ${currentSpec.thickness}mm</p>
            `;
            break;
    }
    
    detailsHtml += `<p>단위중량: ${currentSpec.unit_weight} kg/m</p>`;
    detailsHtml += '</div>';
    
    document.getElementById('specDetails').innerHTML = detailsHtml;
    document.getElementById('specDetails').style.display = 'block';
});

// 중량 계산
function calculateWeight() {
    if (!currentSpec) {
        alert('제품과 규격을 선택해주세요.');
        return;
    }
    
    const length = parseFloat(document.getElementById('length').value) || 0;
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    
    if (length <= 0) {
        alert('길이를 입력해주세요.');
        return;
    }
    
    // API 호출로 계산
    const params = new URLSearchParams({
        category: categoryCode,
        spec_id: currentSpec.id,
        length: length,
        quantity: quantity
    });
    
    fetch(`api/calculate_weight.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('unitWeight').textContent = 
                    `${currentSpec.unit_weight} kg/m`;
                document.getElementById('totalWeight').textContent = 
                    `${data.weight.toFixed(2)} kg (${(data.weight/1000).toFixed(3)} ton)`;
                
                if (data.price) {
                    document.getElementById('estimatedPrice').textContent = 
                        `₩ ${data.price.toLocaleString()}`;
                } else {
                    document.getElementById('estimatedPrice').textContent = '견적 문의';
                }
                
                document.getElementById('calculationResult').style.display = 'block';
            } else {
                alert('계산 중 오류가 발생했습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('계산 중 오류가 발생했습니다.');
        });
}

// 초기화
function resetCalculator() {
    document.getElementById('product').value = '';
    document.getElementById('specification').value = '';
    document.getElementById('length').value = '6';
    document.getElementById('quantity').value = '1';
    document.getElementById('specificationGroup').style.display = 'none';
    document.getElementById('specDetails').style.display = 'none';
    document.getElementById('calculationResult').style.display = 'none';
    currentProduct = null;
    currentSpec = null;
}

// 견적 요청
function requestQuote() {
    // 견적 요청 페이지로 데이터 전달
    const data = {
        category: categoryCode,
        product_id: document.getElementById('product').value,
        spec_id: currentSpec.id,
        length: document.getElementById('length').value,
        quantity: document.getElementById('quantity').value,
        weight: document.getElementById('totalWeight').textContent
    };
    
    // 세션에 저장하고 견적 페이지로 이동
    sessionStorage.setItem('calculationData', JSON.stringify(data));
    window.location.href = 'quote_request.php';
}
</script>

<style>
.calculator-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.calculator-header {
    text-align: center;
    margin-bottom: 40px;
}

.calculator-header h1 {
    color: #333;
    margin-bottom: 10px;
}

.calculator-form {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.form-row .form-group {
    flex: 1;
}

.specification-details {
    background: #fff;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    margin-bottom: 20px;
}

.spec-info p {
    margin: 5px 0;
    color: #666;
}

.btn-calculate {
    width: 100%;
    padding: 15px;
    font-size: 18px;
    font-weight: 600;
}

.calculation-result {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.calculation-result h3 {
    margin-bottom: 20px;
    color: #333;
}

.result-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.result-item {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 4px;
}

.result-item .label {
    display: block;
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.result-item .value {
    display: block;
    font-size: 20px;
    font-weight: 600;
    color: #1428A0;
}

.result-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.result-actions .btn {
    padding: 10px 30px;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }

    .result-grid {
        grid-template-columns: 1fr;
    }

    .result-actions {
        flex-direction: column;
    }

    .result-actions .btn {
        width: 100%;
    }
}

.calculation-formula {
    background: #e8f4f8;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
    border-left: 4px solid #1428A0;
}

.calculation-formula h4 {
    color: #1428A0;
    margin-bottom: 10px;
}

.calculation-formula p {
    margin: 5px 0;
    font-size: 15px;
    color: #333;
}

.calculation-examples {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 8px;
    margin-top: 30px;
}

.calculation-examples h3 {
    color: #333;
    margin-bottom: 20px;
}

.example-box {
    background: #fff;
    padding: 20px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #e0e0e0;
}

.example-box h5 {
    color: #1428A0;
    margin-bottom: 10px;
}

.example-calculation {
    background: #f0f7ff;
    padding: 15px;
    border-radius: 4px;
    margin-top: 10px;
}

.example-calculation p {
    margin: 5px 0;
    font-family: monospace;
    font-size: 14px;
}
</style>

<?php require_once 'tail.php'; ?>