<?php
session_start();
require_once 'db.php';
include 'head.php';

// 규격 목록 조회
$stmt = $pdo->query("
    SELECT DISTINCT p_standard 
    FROM rebar_bundle_data 
    WHERE p_bd_count > 0 AND p_bd_weight > 0
    ORDER BY p_standard
");
$standards = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 재질 목록 조회
$stmt = $pdo->query("
    SELECT DISTINCT p_material 
    FROM rebar_bundle_data 
    ORDER BY p_material
");
$materials = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 원산지 목록 조회
$stmt = $pdo->query("
    SELECT DISTINCT p_country 
    FROM rebar_bundle_data 
    WHERE p_country IS NOT NULL AND p_country != ''
    ORDER BY p_country
");
$countries = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 재질별 추가단가 조회
$stmt = $pdo->query("SELECT material, additional_price FROM rebar_material_prices WHERE is_active = 1");
$material_prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 원산지별 추가단가 조회
$stmt = $pdo->query("SELECT country, additional_price FROM rebar_country_prices WHERE is_active = 1");
$country_prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<style>
.quote-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.quote-header {
    text-align: center;
    margin-bottom: 40px;
}

.quote-header h1 {
    font-size: 2.5em;
    color: #333;
    margin-bottom: 10px;
}

.quote-form {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.btn-calculate {
    background: #0066cc;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 5px;
    font-size: 18px;
    cursor: pointer;
    margin-top: 20px;
    width: 100%;
}

.btn-calculate:hover {
    background: #0052a3;
}

.result-section {
    margin-top: 40px;
    display: none;
}

.result-box {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.result-title {
    font-size: 1.8em;
    color: #0066cc;
    margin-bottom: 20px;
    text-align: center;
}

.result-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.result-item {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.result-label {
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.result-value {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.result-value.highlight {
    color: #0066cc;
}

.calculation-details {
    background: #e3f2fd;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.calculation-step {
    margin: 10px 0;
    padding-left: 20px;
    position: relative;
}

.calculation-step:before {
    content: '▶';
    position: absolute;
    left: 0;
    color: #0066cc;
}

.bundle-info {
    background: #fff3cd;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.loading {
    text-align: center;
    padding: 20px;
}

.loading-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #0066cc;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 5px;
    margin-top: 20px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.info-table th,
.info-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.info-table th {
    background: #f5f5f5;
    font-weight: bold;
}
</style>

<div class="quote-container">
    <div class="quote-header">
        <h1>철근 번들 견적 계산기</h1>
        <p>번들 단위로 철근 견적을 계산합니다</p>
    </div>

    <div class="quote-form">
        <form id="bundleQuoteForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="standard">규격</label>
                    <select class="form-control" id="standard" name="standard" required>
                        <option value="">선택하세요</option>
                        <?php foreach ($standards as $std): ?>
                            <option value="<?php echo htmlspecialchars($std); ?>"><?php echo htmlspecialchars($std); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="material">재질</label>
                    <select class="form-control" id="material" name="material" required>
                        <option value="">선택하세요</option>
                        <?php foreach ($materials as $mat): ?>
                            <option value="<?php echo htmlspecialchars($mat); ?>"><?php echo htmlspecialchars($mat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="length">길이 (m)</label>
                    <select class="form-control" id="length" name="length" required disabled>
                        <option value="">먼저 규격과 재질을 선택하세요</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="country">원산지</label>
                    <select class="form-control" id="country" name="country" required>
                        <option value="">선택하세요</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo htmlspecialchars($country); ?>"><?php echo htmlspecialchars($country); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="bundle_qty">번들 수량</label>
                    <input type="number" class="form-control" id="bundle_qty" name="bundle_qty" min="1" required placeholder="번들 수량 입력">
                </div>

                <div class="form-group">
                    <label for="base_price">기준 단가 (원/kg)</label>
                    <input type="number" class="form-control" id="base_price" name="base_price" value="700" required>
                </div>
            </div>

            <div class="bundle-info" id="bundleInfo" style="display: none;">
                <strong>번들 정보:</strong> <span id="bundleInfoText"></span>
            </div>

            <button type="submit" class="btn-calculate">견적 계산하기</button>
        </form>
    </div>

    <div class="result-section" id="resultSection">
        <div class="result-box">
            <h2 class="result-title">견적 계산 결과</h2>
            
            <div class="result-grid">
                <div class="result-item">
                    <div class="result-label">규격/재질</div>
                    <div class="result-value" id="resultSpec"></div>
                </div>
                
                <div class="result-item">
                    <div class="result-label">길이</div>
                    <div class="result-value" id="resultLength"></div>
                </div>
                
                <div class="result-item">
                    <div class="result-label">번들 수량</div>
                    <div class="result-value highlight" id="resultBundles"></div>
                </div>
                
                <div class="result-item">
                    <div class="result-label">총 본수</div>
                    <div class="result-value" id="resultPieces"></div>
                </div>
                
                <div class="result-item">
                    <div class="result-label">총 중량</div>
                    <div class="result-value highlight" id="resultWeight"></div>
                </div>
                
                <div class="result-item">
                    <div class="result-label">총 금액</div>
                    <div class="result-value highlight" id="resultPrice"></div>
                </div>
            </div>

            <table class="info-table">
                <thead>
                    <tr>
                        <th>항목</th>
                        <th>단가</th>
                        <th>금액</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>기본 단가</td>
                        <td id="baseUnitPrice"></td>
                        <td id="baseAmount"></td>
                    </tr>
                    <tr>
                        <td>재질 추가단가 (<span id="materialName"></span>)</td>
                        <td id="materialUnitPrice"></td>
                        <td id="materialAmount"></td>
                    </tr>
                    <tr>
                        <td>원산지 추가단가 (<span id="countryName"></span>)</td>
                        <td id="countryUnitPrice"></td>
                        <td id="countryAmount"></td>
                    </tr>
                    <tr style="font-weight: bold; background: #f0f0f0;">
                        <td>합계</td>
                        <td id="totalUnitPrice"></td>
                        <td id="totalAmount"></td>
                    </tr>
                </tbody>
            </table>

            <div class="calculation-details">
                <h4>계산 상세</h4>
                <div id="calculationSteps"></div>
            </div>
        </div>
    </div>
</div>

<script>
// 재질별 추가단가
const materialPrices = <?php echo json_encode($material_prices); ?>;

// 원산지별 추가단가
const countryPrices = <?php echo json_encode($country_prices); ?>;

// 규격과 재질 선택 시 길이 목록 로드
document.getElementById('standard').addEventListener('change', loadLengths);
document.getElementById('material').addEventListener('change', loadLengths);

function loadLengths() {
    const standard = document.getElementById('standard').value;
    const material = document.getElementById('material').value;
    const lengthSelect = document.getElementById('length');
    
    if (!standard || !material) {
        lengthSelect.innerHTML = '<option value="">먼저 규격과 재질을 선택하세요</option>';
        lengthSelect.disabled = true;
        return;
    }
    
    // AJAX로 길이 목록 로드
    fetch(`get_bundle_lengths.php?standard=${encodeURIComponent(standard)}&material=${encodeURIComponent(material)}`)
        .then(response => response.json())
        .then(data => {
            lengthSelect.innerHTML = '<option value="">길이를 선택하세요</option>';
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.length;
                option.textContent = `${item.length}m (번들당 ${item.bd_count}본, ${item.bd_weight}kg)`;
                option.dataset.bdCount = item.bd_count;
                option.dataset.bdWeight = item.bd_weight;
                lengthSelect.appendChild(option);
            });
            lengthSelect.disabled = false;
        });
}

// 길이 선택 시 번들 정보 표시
document.getElementById('length').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const bundleInfo = document.getElementById('bundleInfo');
    const bundleInfoText = document.getElementById('bundleInfoText');
    
    if (this.value && selectedOption.dataset.bdCount) {
        bundleInfoText.textContent = `번들당 ${selectedOption.dataset.bdCount}본, ${selectedOption.dataset.bdWeight}kg`;
        bundleInfo.style.display = 'block';
    } else {
        bundleInfo.style.display = 'none';
    }
});

// 폼 제출 처리
document.getElementById('bundleQuoteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const lengthSelect = document.getElementById('length');
    const selectedOption = lengthSelect.options[lengthSelect.selectedIndex];
    
    // 번들 정보 추가
    formData.append('bd_count', selectedOption.dataset.bdCount);
    formData.append('bd_weight', selectedOption.dataset.bdWeight);
    
    // 계산 수행
    calculateQuote(formData);
});

function calculateQuote(formData) {
    const data = Object.fromEntries(formData);
    
    // 기본 값
    const bundleQty = parseInt(data.bundle_qty);
    const bdCount = parseInt(data.bd_count);
    const bdWeight = parseInt(data.bd_weight);
    const basePrice = parseFloat(data.base_price);
    
    // 재질 추가단가
    const materialPrice = materialPrices[data.material] || 0;
    
    // 원산지 추가단가
    const countryPrice = countryPrices[data.country] || 0;
    
    // 계산
    const totalWeight = bundleQty * bdWeight;
    const totalPieces = bundleQty * bdCount;
    const totalUnitPrice = basePrice + materialPrice + countryPrice;
    
    const baseAmount = totalWeight * basePrice;
    const materialAmount = totalWeight * materialPrice;
    const countryAmount = totalWeight * countryPrice;
    const totalAmount = baseAmount + materialAmount + countryAmount;
    
    // 결과 표시
    document.getElementById('resultSpec').textContent = `${data.standard} / ${data.material}`;
    document.getElementById('resultLength').textContent = `${data.length}m`;
    document.getElementById('resultBundles').textContent = `${bundleQty}번들`;
    document.getElementById('resultPieces').textContent = `${totalPieces.toLocaleString()}본`;
    document.getElementById('resultWeight').textContent = `${totalWeight.toLocaleString()}kg`;
    document.getElementById('resultPrice').textContent = `${totalAmount.toLocaleString()}원`;
    
    // 단가 정보
    document.getElementById('baseUnitPrice').textContent = `${basePrice.toLocaleString()}원/kg`;
    document.getElementById('baseAmount').textContent = `${baseAmount.toLocaleString()}원`;
    
    document.getElementById('materialName').textContent = data.material;
    document.getElementById('materialUnitPrice').textContent = `${materialPrice > 0 ? '+' : ''}${materialPrice.toLocaleString()}원/kg`;
    document.getElementById('materialAmount').textContent = `${materialAmount > 0 ? '+' : ''}${materialAmount.toLocaleString()}원`;
    
    document.getElementById('countryName').textContent = data.country;
    document.getElementById('countryUnitPrice').textContent = `${countryPrice > 0 ? '+' : ''}${countryPrice.toLocaleString()}원/kg`;
    document.getElementById('countryAmount').textContent = `${countryAmount > 0 ? '+' : ''}${countryAmount.toLocaleString()}원`;
    
    document.getElementById('totalUnitPrice').textContent = `${totalUnitPrice.toLocaleString()}원/kg`;
    document.getElementById('totalAmount').textContent = `${totalAmount.toLocaleString()}원`;
    
    // 계산 과정
    const steps = [
        `번들 정보: 번들당 ${bdCount}본, ${bdWeight}kg`,
        `총 중량 = ${bundleQty}번들 × ${bdWeight}kg = ${totalWeight.toLocaleString()}kg`,
        `총 본수 = ${bundleQty}번들 × ${bdCount}본 = ${totalPieces.toLocaleString()}본`,
        `기본 금액 = ${totalWeight.toLocaleString()}kg × ${basePrice}원 = ${baseAmount.toLocaleString()}원`
    ];
    
    if (materialPrice !== 0) {
        steps.push(`재질 추가 = ${totalWeight.toLocaleString()}kg × ${materialPrice}원 = ${materialAmount.toLocaleString()}원`);
    }
    
    if (countryPrice !== 0) {
        steps.push(`원산지 추가 = ${totalWeight.toLocaleString()}kg × ${countryPrice}원 = ${countryAmount.toLocaleString()}원`);
    }
    
    steps.push(`최종 금액 = ${totalAmount.toLocaleString()}원`);
    
    document.getElementById('calculationSteps').innerHTML = steps.map(step => 
        `<div class="calculation-step">${step}</div>`
    ).join('');
    
    // 결과 섹션 표시
    document.getElementById('resultSection').style.display = 'block';
    document.getElementById('resultSection').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php include 'tail.php'; ?>