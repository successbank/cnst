<?php
/**
 * 철근 가격 계산기 페이지
 */

session_start();
require_once 'db.php';
require_once 'includes/RebarPriceCalculator.php';

$currentPage = 'products';
$pageTitle = '철근 가격 계산기';
$additionalCSS = [];
require_once 'head.php';

$calculator = new RebarPriceCalculator($pdo);
?>

<style>
.calculator-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.calculator-header {
    text-align: center;
    margin-bottom: 40px;
}

.calculator-header h1 {
    font-size: 32px;
    color: #2c3e50;
    margin-bottom: 10px;
}

.calculator-header p {
    color: #6c757d;
    font-size: 16px;
}

.calculator-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 40px;
    margin-bottom: 30px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-control {
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,.15);
}

.btn-calculate {
    width: 100%;
    padding: 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-calculate:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.btn-calculate:active {
    transform: translateY(0);
}

.result-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    padding: 30px;
    margin-top: 30px;
    display: none;
    animation: slideIn 0.4s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.result-header {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid rgba(255,255,255,0.3);
}

.result-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.result-item {
    background: rgba(255,255,255,0.15);
    padding: 20px;
    border-radius: 8px;
    backdrop-filter: blur(10px);
}

.result-label {
    font-size: 13px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.result-value {
    font-size: 20px;
    font-weight: 700;
}

.result-total {
    background: rgba(255,255,255,0.2);
    padding: 25px;
    border-radius: 8px;
    text-align: center;
    margin-top: 20px;
}

.result-total-label {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 10px;
}

.result-total-value {
    font-size: 36px;
    font-weight: 800;
}

.info-card {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 20px;
    border-radius: 8px;
    margin-top: 30px;
}

.info-card h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 18px;
}

.info-card ul {
    margin: 0;
    padding-left: 20px;
    color: #6c757d;
}

.loading {
    text-align: center;
    padding: 20px;
    color: #6c757d;
    display: none;
}

.error {
    background: #f8d7da;
    color: #721c24;
    padding: 15px 20px;
    border-radius: 8px;
    margin-top: 20px;
    display: none;
}

/* 모달 스타일 */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease;
}

.modal-overlay.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 16px;
    padding: 40px;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
    text-align: center;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.modal-title {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 15px;
}

.modal-message {
    font-size: 16px;
    color: #6c757d;
    margin-bottom: 25px;
    line-height: 1.6;
}

.modal-phone {
    font-size: 28px;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 25px;
    letter-spacing: 1px;
}

.modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.modal-btn {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.modal-btn-primary {
    background: #007bff;
    color: white;
}

.modal-btn-primary:hover {
    background: #0056b3;
    transform: translateY(-2px);
}

.modal-btn-secondary {
    background: #6c757d;
    color: white;
}

.modal-btn-secondary:hover {
    background: #545b62;
}
</style>

<div class="calculator-container">
    <div class="calculator-header">
        <h1>💰 철근 가격 계산기</h1>
        <p>규격, 길이, 수량을 입력하면 실시간으로 가격을 계산합니다</p>
    </div>

    <div class="calculator-card">
        <form id="calculatorForm">
            <div class="form-grid">
                <div class="form-group">
                    <label for="spec">규격 *</label>
                    <select id="spec" class="form-control" required onchange="loadLengths()">
                        <option value="">선택하세요</option>
                        <?php
                        $specs = $calculator->getAvailableSpecs();
                        foreach ($specs as $spec) {
                            echo "<option value=\"$spec\">$spec</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="length">길이 (m) *</label>
                    <select id="length" class="form-control" required disabled>
                        <option value="">먼저 규격을 선택하세요</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">수량 (번들) *</label>
                    <input type="number" id="quantity" class="form-control" value="1" min="1" step="1" required>
                </div>

                <div class="form-group">
                    <label for="origin">원산지</label>
                    <select id="origin" class="form-control">
                        <?php
                        $origins = $calculator->getAvailableOrigins();
                        foreach ($origins as $origin) {
                            echo "<option value=\"$origin\">$origin</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="material">재질</label>
                    <select id="material" class="form-control">
                        <option value="SD400" selected>SD400</option>
                        <option value="SD500">SD500 (+10,000원/톤)</option>
                        <option value="SD600">SD600 (+20,000원/톤)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-calculate">💡 가격 계산하기</button>
        </form>

        <div class="loading" id="loading">
            계산 중입니다...
        </div>

        <div class="error" id="error"></div>

        <div class="result-card" id="result">
            <div class="result-header">📊 계산 결과</div>

            <div class="result-grid">
                <div class="result-item">
                    <div class="result-label">규격</div>
                    <div class="result-value" id="result-spec">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">길이</div>
                    <div class="result-value" id="result-length">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">수량</div>
                    <div class="result-value" id="result-quantity">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">1본 중량</div>
                    <div class="result-value" id="result-piece-weight">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">번들당 본수</div>
                    <div class="result-value" id="result-pieces">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">번들당 중량</div>
                    <div class="result-value" id="result-bundle-weight">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">총 중량</div>
                    <div class="result-value" id="result-total-weight">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">단가 (원/톤)</div>
                    <div class="result-value" id="result-unit-price">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">번들당 가격</div>
                    <div class="result-value" id="result-bundle-price">-</div>
                </div>
            </div>

            <div class="result-total">
                <div class="result-total-label">총 금액 (부가세 별도)</div>
                <div class="result-total-value" id="result-total-price">0원</div>
                <div style="margin-top: 10px; font-size: 16px; opacity: 0.9;">
                    부가세 포함: <span id="result-with-vat">0원</span>
                </div>
            </div>
        </div>
    </div>

    <div class="info-card">
        <h3>ℹ️ 계산 정보</h3>
        <ul>
            <li>가격 = (기준단가 + 원산지단가 + 재질단가) × 길이 × 수량</li>
            <li>기준단가는 톤당 가격입니다</li>
            <li>번들은 일정 길이의 철근을 묶은 단위입니다</li>
            <li>부가세(10%)는 별도 계산됩니다</li>
            <li>실제 가격은 시장 상황에 따라 변동될 수 있습니다</li>
        </ul>
    </div>
</div>

<!-- 가격 정보 없음 모달 -->
<div class="modal-overlay" id="priceUnavailableModal">
    <div class="modal-content">
        <div class="modal-icon">📞</div>
        <div class="modal-title">가격 정보가 없습니다</div>
        <div class="modal-message">
            선택하신 규격은 현재 가격 정보가 등록되어 있지 않습니다.<br>
            정확한 가격은 아래 전화번호로 문의해주세요.
        </div>
        <div class="modal-phone">☎ 010-9820-0495</div>
        <div class="modal-buttons">
            <a href="tel:010-9820-0495" class="modal-btn modal-btn-primary">전화 걸기</a>
            <button class="modal-btn modal-btn-secondary" onclick="closeModal()">닫기</button>
        </div>
    </div>
</div>

<script>
// 가격 정보가 있는 규격 목록 (D10, D13, D16만 가격 정보 있음)
const specsWithPrice = ['D10', 'D13', 'D16'];

// 모달 열기
function showModal() {
    document.getElementById('priceUnavailableModal').classList.add('show');
}

// 모달 닫기
function closeModal() {
    document.getElementById('priceUnavailableModal').classList.remove('show');
}

// 모달 외부 클릭 시 닫기
document.getElementById('priceUnavailableModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// 길이 목록 로드
async function loadLengths() {
    const spec = document.getElementById('spec').value;
    const lengthSelect = document.getElementById('length');

    if (!spec) {
        lengthSelect.innerHTML = '<option value="">먼저 규격을 선택하세요</option>';
        lengthSelect.disabled = true;
        return;
    }

    try {
        const response = await fetch(`ajax/get_rebar_options.php?type=lengths&spec_name=${spec}`);
        const data = await response.json();

        if (data.success) {
            lengthSelect.innerHTML = '<option value="">길이를 선택하세요</option>';

            data.data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.length;
                option.textContent = `${item.length}m (${item.piece_weight}kg/본, ${item.pieces_per_length}본/번들)`;
                lengthSelect.appendChild(option);
            });

            lengthSelect.disabled = false;
        }
    } catch (error) {
        console.error('길이 목록 로드 실패:', error);
    }
}

// 계산 실행
document.getElementById('calculatorForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const specName = document.getElementById('spec').value;

    // 가격 정보가 없는 규격인지 확인
    if (!specsWithPrice.includes(specName)) {
        showModal();
        return;
    }

    const data = {
        spec_name: specName,
        length: parseFloat(document.getElementById('length').value),
        quantity: parseInt(document.getElementById('quantity').value),
        origin: document.getElementById('origin').value,
        material: document.getElementById('material').value
    };

    // UI 초기화
    document.getElementById('result').style.display = 'none';
    document.getElementById('error').style.display = 'none';
    document.getElementById('loading').style.display = 'block';

    try {
        const response = await fetch('ajax/calculate_rebar_price.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        document.getElementById('loading').style.display = 'none';

        if (result.success) {
            // 결과 표시
            document.getElementById('result-spec').textContent = result.spec_name;
            document.getElementById('result-length').textContent = result.length + 'm';
            document.getElementById('result-quantity').textContent = result.quantity + '번들';
            document.getElementById('result-piece-weight').textContent = result.weight.weight_per_piece.toFixed(2) + 'kg';
            document.getElementById('result-pieces').textContent = result.weight.pieces_per_bundle.toLocaleString() + '본';
            document.getElementById('result-bundle-weight').textContent = result.weight.weight_per_bundle.toLocaleString() + 'kg';
            document.getElementById('result-total-weight').textContent =
                result.weight.total_weight_kg.toLocaleString() + 'kg\n(' + result.weight.total_weight_ton.toFixed(3) + '톤)';
            document.getElementById('result-unit-price').textContent = result.pricing.unit_price.toLocaleString() + '원';
            document.getElementById('result-bundle-price').textContent = result.price_per_bundle.toLocaleString() + '원';
            document.getElementById('result-total-price').textContent = result.total_price.toLocaleString() + '원';
            document.getElementById('result-with-vat').textContent = Math.round(result.total_price * 1.1).toLocaleString() + '원';

            document.getElementById('result').style.display = 'block';
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('error').textContent = '계산 오류: ' + error.message;
        document.getElementById('error').style.display = 'block';
    }
});
</script>

<?php require_once 'tail.php'; ?>