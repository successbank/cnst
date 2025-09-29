<?php
/**
 * 계산식 관리 페이지
 * 관리자가 카테고리별 계산식을 관리할 수 있습니다
 */

session_start();
require_once '../db.php';
require_once 'admin_check.php';

// 카테고리 목록 가져오기
$stmt = $pdo->query("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY display_order");
$categories = $stmt->fetchAll();

$pageTitle = '계산식 관리';

require_once 'admin_head.php';
?>

<style>
.formula-manager {
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.category-tabs {
    display: flex;
    gap: 0;
    background: white;
    border-radius: 8px 8px 0 0;
    overflow-x: auto;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: -1px;
}

.category-tab {
    padding: 15px 25px;
    background: #f8f9fa;
    color: #666;
    cursor: pointer;
    font-weight: 500;
    border-right: 1px solid #dee2e6;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.category-tab:hover {
    background: #e9ecef;
    color: #333;
}

.category-tab.active {
    background: white;
    color: #007bff;
    border-bottom: 3px solid #007bff;
}

.formula-content {
    background: white;
    padding: 30px;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    min-height: 400px;
}

.formula-section {
    display: none;
    animation: fadeIn 0.3s ease;
}

.formula-section.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.formula-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
    background: #f8f9fa;
}

.formula-card h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 20px;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #80bdff;
    box-shadow: 0 0 0 3px rgba(0,123,255,.25);
}

.formula-expression {
    font-family: 'Courier New', monospace;
    background: white;
    min-height: 80px;
    resize: vertical;
}

.parameter-list {
    background: white;
    border-radius: 6px;
    padding: 15px;
}

.parameter-item {
    display: grid;
    grid-template-columns: 2fr 2fr 1fr 1fr 100px;
    gap: 10px;
    margin-bottom: 10px;
    align-items: end;
}

.parameter-item input {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-sm {
    padding: 5px 12px;
    font-size: 13px;
}

.btn-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.formula-test {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 6px;
    padding: 20px;
    margin-top: 20px;
}

.formula-test h4 {
    margin: 0 0 15px 0;
    color: #856404;
}

.test-inputs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.test-result {
    background: white;
    padding: 15px;
    border-radius: 4px;
    margin-top: 15px;
    font-weight: 600;
    color: #28a745;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.alert {
    padding: 15px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.formula-history {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #e9ecef;
}

.history-item {
    padding: 12px;
    background: white;
    border-radius: 4px;
    margin-bottom: 10px;
    font-size: 14px;
}

.history-item .date {
    color: #6c757d;
    font-weight: 600;
}

.constants-info {
    background: #e7f3ff;
    border: 1px solid #b3d7ff;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
}

.constants-info h4 {
    margin: 0 0 10px 0;
    color: #004085;
}

.constants-info code {
    background: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 13px;
    color: #d63384;
}
</style>

<div class="formula-manager">
    <div class="page-header">
        <h1>📐 계산식 관리</h1>
        <p>카테고리별 제품 중량 계산식을 관리합니다. 각 카테고리의 기본 계산식을 설정하거나 제품별로 커스터마이징할 수 있습니다.</p>
    </div>

    <!-- 카테고리 탭 -->
    <div class="category-tabs">
        <?php foreach ($categories as $index => $category): ?>
            <div class="category-tab <?php echo $index === 0 ? 'active' : ''; ?>"
                 data-category="<?php echo htmlspecialchars($category['category_code']); ?>"
                 onclick="switchCategory('<?php echo htmlspecialchars($category['category_code']); ?>')">
                <?php echo htmlspecialchars($category['category_name']); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 계산식 콘텐츠 -->
    <div class="formula-content">
        <div id="loading" class="loading">
            계산식을 불러오는 중...
        </div>

        <!-- 각 카테고리별 섹션 (동적 생성) -->
        <?php foreach ($categories as $index => $category): ?>
            <div class="formula-section <?php echo $index === 0 ? 'active' : ''; ?>"
                 id="section-<?php echo htmlspecialchars($category['category_code']); ?>"
                 data-category="<?php echo htmlspecialchars($category['category_code']); ?>">
                <!-- AJAX로 로드됨 -->
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
let currentCategory = '<?php echo htmlspecialchars($categories[0]['category_code'] ?? ''); ?>';

// 페이지 로드 시 첫 번째 카테고리 로드
document.addEventListener('DOMContentLoaded', function() {
    if (currentCategory) {
        loadCategoryFormula(currentCategory);
    }
});

// 카테고리 전환
function switchCategory(categoryCode) {
    // 탭 활성화
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');

    // 섹션 활성화
    document.querySelectorAll('.formula-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById('section-' + categoryCode).classList.add('active');

    currentCategory = categoryCode;

    // 데이터가 없으면 로드
    const section = document.getElementById('section-' + categoryCode);
    if (!section.hasAttribute('data-loaded')) {
        loadCategoryFormula(categoryCode);
    }
}

// 카테고리 계산식 로드
function loadCategoryFormula(categoryCode) {
    const section = document.getElementById('section-' + categoryCode);
    section.innerHTML = '<div class="loading">계산식을 불러오는 중...</div>';

    fetch(`ajax/get_calculation_formulas.php?category_code=${categoryCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderFormulaUI(section, categoryCode, data.data);
                section.setAttribute('data-loaded', 'true');
            } else {
                section.innerHTML = `<div class="alert alert-error">오류: ${data.error}</div>`;
            }
        })
        .catch(error => {
            section.innerHTML = `<div class="alert alert-error">계산식 로드 실패: ${error.message}</div>`;
        });
}

// 계산식 UI 렌더링
function renderFormulaUI(container, categoryCode, data) {
    const formula = data.category_formula;

    let html = '';

    // 사용 가능한 상수 정보
    html += `
        <div class="constants-info">
            <h4>사용 가능한 상수</h4>
            <p>
                <code>STEEL_DENSITY</code> = 7850 (철 밀도) |
                <code>STEEL_FACTOR_1</code> = 0.00617 (철근 계산) |
                <code>STEEL_FACTOR_2</code> = 0.00785 (철판/평철) |
                <code>STEEL_FACTOR_3</code> = 0.02466 (원형파이프) |
                <code>PI</code> = 3.14159
            </p>
        </div>
    `;

    if (formula) {
        // 기존 계산식 수정 UI
        html += renderFormulaEditUI(formula, categoryCode);
    } else {
        // 새 계산식 생성 UI
        html += renderFormulaCreateUI(categoryCode);
    }

    container.innerHTML = html;
}

// 계산식 편집 UI
function renderFormulaEditUI(formula, categoryCode) {
    const formulaData = JSON.parse(formula.formula_expression);
    const params = formula.parameters || [];

    return `
        <div class="formula-card">
            <h3>카테고리 기본 계산식</h3>

            <div class="form-group">
                <label>계산식 이름</label>
                <input type="text" class="form-control" id="formula-name"
                       value="${escapeHtml(formula.formula_name)}">
            </div>

            <div class="form-group">
                <label>설명</label>
                <textarea class="form-control" id="formula-desc" rows="2">${escapeHtml(formula.description || '')}</textarea>
            </div>

            <div class="form-group">
                <label>계산식 수식</label>
                <textarea class="form-control formula-expression" id="formula-expr" rows="3">${escapeHtml(formulaData.expression)}</textarea>
                <small style="color: #6c757d; margin-top: 5px; display: block;">
                    예: (diameter * diameter) * STEEL_FACTOR_1 * length * quantity
                </small>
            </div>

            <div class="form-group">
                <label>파라미터 설정</label>
                <div class="parameter-list" id="param-list">
                    ${params.map((p, i) => `
                        <div class="parameter-item">
                            <input type="text" placeholder="파라미터 이름" value="${escapeHtml(p.parameter_name)}">
                            <input type="text" placeholder="라벨" value="${escapeHtml(p.parameter_label)}">
                            <input type="text" placeholder="기본값" value="${escapeHtml(p.default_value || '')}">
                            <input type="text" placeholder="단위" value="${escapeHtml(p.unit || '')}">
                            <button class="btn btn-danger btn-sm" onclick="removeParam(this)">삭제</button>
                        </div>
                    `).join('')}
                </div>
                <button class="btn btn-secondary btn-sm" onclick="addParam()">+ 파라미터 추가</button>
            </div>

            <div class="btn-group">
                <button class="btn btn-primary" onclick="saveFormula(${formula.id}, '${categoryCode}')">
                    💾 저장
                </button>
                <button class="btn btn-success" onclick="testFormula()">
                    🧪 테스트
                </button>
            </div>

            <div class="formula-test" id="test-section" style="display: none;">
                <h4>계산식 테스트</h4>
                <div class="test-inputs" id="test-inputs"></div>
                <button class="btn btn-success btn-sm" onclick="runTest()">계산 실행</button>
                <div class="test-result" id="test-result" style="display: none;"></div>
            </div>
        </div>
    `;
}

// 계산식 생성 UI
function renderFormulaCreateUI(categoryCode) {
    return `
        <div class="alert alert-error">
            이 카테고리에 계산식이 없습니다.
        </div>
        <div class="formula-card">
            <h3>새 계산식 만들기</h3>
            <p>데이터베이스 스키마에 기본 계산식이 설정되어 있어야 합니다.</p>
            <button class="btn btn-primary" onclick="location.reload()">새로고침</button>
        </div>
    `;
}

// 파라미터 추가
function addParam() {
    const list = document.getElementById('param-list');
    const div = document.createElement('div');
    div.className = 'parameter-item';
    div.innerHTML = `
        <input type="text" placeholder="파라미터 이름 (예: length)">
        <input type="text" placeholder="라벨 (예: 길이)">
        <input type="text" placeholder="기본값">
        <input type="text" placeholder="단위 (예: m)">
        <button class="btn btn-danger btn-sm" onclick="removeParam(this)">삭제</button>
    `;
    list.appendChild(div);
}

// 파라미터 제거
function removeParam(btn) {
    btn.parentElement.remove();
}

// 계산식 저장
function saveFormula(formulaId, categoryCode) {
    const name = document.getElementById('formula-name').value;
    const desc = document.getElementById('formula-desc').value;
    const expr = document.getElementById('formula-expr').value;

    // 파라미터 수집
    const params = [];
    document.querySelectorAll('#param-list .parameter-item').forEach(item => {
        const inputs = item.querySelectorAll('input');
        params.push({
            parameter_name: inputs[0].value,
            parameter_label: inputs[1].value,
            default_value: inputs[2].value || null,
            unit: inputs[3].value || null,
            parameter_type: 'number',
            is_required: 1
        });
    });

    const data = {
        id: formulaId,
        formula_name: name,
        category_code: categoryCode,
        description: desc,
        formula_expression: {
            type: 'expression',
            expression: expr,
            rounding: {
                final: { decimals: 2, method: 'round' }
            }
        },
        parameters: params
    };

    fetch('ajax/save_calculation_formula.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert('계산식이 저장되었습니다.');
            loadCategoryFormula(categoryCode);
        } else {
            alert('저장 실패: ' + result.error);
        }
    })
    .catch(error => {
        alert('오류 발생: ' + error.message);
    });
}

// 계산식 테스트
function testFormula() {
    const testSection = document.getElementById('test-section');
    const testInputs = document.getElementById('test-inputs');

    testSection.style.display = 'block';

    // 파라미터에서 입력 필드 생성
    testInputs.innerHTML = '';
    document.querySelectorAll('#param-list .parameter-item').forEach(item => {
        const inputs = item.querySelectorAll('input');
        const paramName = inputs[0].value;
        const paramLabel = inputs[1].value;
        const defaultVal = inputs[2].value;

        const div = document.createElement('div');
        div.className = 'form-group';
        div.innerHTML = `
            <label>${paramLabel}</label>
            <input type="number" class="form-control" data-param="${paramName}" value="${defaultVal || ''}" step="any">
        `;
        testInputs.appendChild(div);
    });
}

// 테스트 실행
function runTest() {
    const expr = document.getElementById('formula-expr').value;
    const testData = {};

    document.querySelectorAll('#test-inputs input').forEach(input => {
        testData[input.getAttribute('data-param')] = parseFloat(input.value) || 0;
    });

    fetch('ajax/test_calculation_formula.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            formula_expression: {
                type: 'expression',
                expression: expr,
                rounding: { final: { decimals: 2, method: 'round' } }
            },
            test_data: testData
        })
    })
    .then(response => response.json())
    .then(result => {
        const resultDiv = document.getElementById('test-result');
        if (result.success) {
            resultDiv.textContent = `✓ 계산 결과: ${result.result}`;
            resultDiv.style.display = 'block';
        } else {
            resultDiv.textContent = `✗ 오류: ${result.error}`;
            resultDiv.style.color = '#dc3545';
            resultDiv.style.display = 'block';
        }
    })
    .catch(error => {
        alert('테스트 실패: ' + error.message);
    });
}

// HTML 이스케이프
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once 'admin_tail.php'; ?>