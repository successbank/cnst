<?php
require_once 'db.php';
require_once 'includes/rebar_unit_weights.php';
include 'head.php';
?>

<style>
.calc-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

.calc-header {
    text-align: center;
    margin-bottom: 40px;
}

.calc-header h1 {
    font-size: 2.5em;
    color: #333;
    margin-bottom: 10px;
}

.formula-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.formula-title {
    font-size: 1.8em;
    color: #0066cc;
    margin-bottom: 20px;
    border-bottom: 2px solid #0066cc;
    padding-bottom: 10px;
}

.formula-box {
    background: #f8f9fa;
    padding: 20px;
    border-left: 4px solid #0066cc;
    margin: 20px 0;
    font-family: 'Courier New', monospace;
}

.formula {
    font-size: 1.2em;
    color: #333;
    margin: 10px 0;
}

.formula-desc {
    color: #666;
    margin-top: 10px;
    font-family: inherit;
}

.example-section {
    background: #e3f2fd;
    padding: 25px;
    border-radius: 8px;
    margin: 20px 0;
}

.example-title {
    font-size: 1.3em;
    color: #1565c0;
    margin-bottom: 15px;
    font-weight: bold;
}

.calculation-step {
    background: white;
    padding: 15px;
    margin: 10px 0;
    border-radius: 5px;
    border: 1px solid #ddd;
}

.step-number {
    display: inline-block;
    background: #0066cc;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    text-align: center;
    line-height: 30px;
    margin-right: 10px;
    font-weight: bold;
}

.step-content {
    display: inline-block;
    vertical-align: top;
    width: calc(100% - 50px);
}

.result-box {
    background: #4caf50;
    color: white;
    padding: 20px;
    border-radius: 5px;
    text-align: center;
    font-size: 1.2em;
    font-weight: bold;
    margin-top: 20px;
}

.note-box {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
}

.note-box h4 {
    color: #856404;
    margin-bottom: 10px;
}

.formula-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.formula-table th,
.formula-table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
}

.formula-table th {
    background: #f5f5f5;
    font-weight: bold;
}

.formula-table tr:hover {
    background: #f9f9f9;
}

.calculator-link {
    display: inline-block;
    background: #0066cc;
    color: white;
    padding: 12px 30px;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 20px;
    transition: background 0.3s;
}

.calculator-link:hover {
    background: #0052a3;
    color: white;
    text-decoration: none;
}
</style>

<div class="calc-container">
    <div class="calc-header">
        <h1>철근 계산식 안내</h1>
        <p>충남스틸 철근 중량 계산 공식 및 예시</p>
    </div>

    <div class="formula-section">
        <h2 class="formula-title">1. 기본 계산 공식</h2>
        
        <div class="formula-box">
            <div class="formula">
                <strong>본당 중량(kg) = 단위중량(kg/m) × 길이(m)</strong>
            </div>
            <div class="formula-desc">
                • 단위중량: 철근 1m당 무게 (규격별로 고정값)
            </div>
        </div>

        <div class="formula-box">
            <div class="formula">
                <strong>총 중량(kg) = 본당 중량(kg) × 본수</strong>
            </div>
            <div class="formula-desc">
                • 본수: 주문하려는 철근의 개수
            </div>
        </div>

        <div class="formula-box">
            <div class="formula">
                <strong>톤 수량(ton) = 총 중량(kg) ÷ 1,000</strong>
            </div>
            <div class="formula-desc">
                • 1톤 = 1,000kg
            </div>
        </div>
    </div>

    <div class="formula-section">
        <h2 class="formula-title">2. 규격별 단위중량</h2>
        
        <table class="formula-table">
            <thead>
                <tr>
                    <th>규격</th>
                    <th>공칭직경(mm)</th>
                    <th>단위중량(kg/m)</th>
                    <th>비고</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $diameters = [
                    'D10' => 9.53,
                    'D13' => 12.7,
                    'D16' => 15.9,
                    'D19' => 19.1,
                    'D22' => 22.2,
                    'D25' => 25.4,
                    'D29' => 28.6,
                    'D32' => 31.8,
                    'D35' => 34.9,
                    'D38' => 38.1,
                    'D41' => 41.3,
                    'D51' => 50.8
                ];
                
                foreach ($rebar_unit_weights as $spec => $weight) {
                    $diameter = $diameters[$spec];
                    $note = '';
                    if (in_array($spec, ['D10', 'D13', 'D16'])) {
                        $note = '주로 보조철근용';
                    } elseif (in_array($spec, ['D19', 'D22', 'D25'])) {
                        $note = '주철근용';
                    } else {
                        $note = '대형 구조물용';
                    }
                ?>
                <tr>
                    <td><strong><?php echo $spec; ?></strong></td>
                    <td><?php echo $diameter; ?></td>
                    <td><?php echo $weight; ?></td>
                    <td><?php echo $note; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="formula-section">
        <h2 class="formula-title">3. 계산 예시</h2>
        
        <div class="example-section">
            <h3 class="example-title">예시 1: D16 철근 8m, 100본 주문 시</h3>
            
            <div class="calculation-step">
                <span class="step-number">1</span>
                <div class="step-content">
                    <strong>단위중량 확인</strong><br>
                    D16 철근의 단위중량 = 1.56 kg/m
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">2</span>
                <div class="step-content">
                    <strong>본당 중량 계산</strong><br>
                    본당 중량 = 1.56 kg/m × 8m = 12.48 kg
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">3</span>
                <div class="step-content">
                    <strong>총 중량 계산</strong><br>
                    총 중량 = 12.48 kg × 100본 = 1,248 kg
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">4</span>
                <div class="step-content">
                    <strong>톤 수량 환산</strong><br>
                    톤 수량 = 1,248 kg ÷ 1,000 = 1.248 톤
                </div>
            </div>
            
            <div class="result-box">
                최종 결과: 1.248 톤
            </div>
        </div>

        <div class="example-section">
            <h3 class="example-title">예시 2: D25 철근 12m, 50본 주문 시</h3>
            
            <div class="calculation-step">
                <span class="step-number">1</span>
                <div class="step-content">
                    <strong>단위중량 확인</strong><br>
                    D25 철근의 단위중량 = 3.98 kg/m
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">2</span>
                <div class="step-content">
                    <strong>본당 중량 계산</strong><br>
                    본당 중량 = 3.98 kg/m × 12m = 47.76 kg
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">3</span>
                <div class="step-content">
                    <strong>총 중량 계산</strong><br>
                    총 중량 = 47.76 kg × 50본 = 2,388 kg
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">4</span>
                <div class="step-content">
                    <strong>톤 수량 환산</strong><br>
                    톤 수량 = 2,388 kg ÷ 1,000 = 2.388 톤
                </div>
            </div>
            
            <div class="result-box">
                최종 결과: 2.388 톤
            </div>
        </div>
    </div>

    <div class="formula-section">
        <h2 class="formula-title">4. 톤당 본수 계산</h2>
        
        <div class="formula-box">
            <div class="formula">
                <strong>톤당 본수 = 1,000 ÷ (단위중량 × 길이)</strong>
            </div>
            <div class="formula-desc">
                • 1톤에 들어가는 철근의 본수를 계산
            </div>
        </div>

        <div class="example-section">
            <h3 class="example-title">D16 철근 8m의 톤당 본수</h3>
            
            <div class="calculation-step">
                <div class="step-content">
                    톤당 본수 = 1,000 ÷ (1.56 × 8) = 1,000 ÷ 12.48 = 80.13본<br>
                    → 약 80본/톤
                </div>
            </div>
        </div>
    </div>

    <div class="note-box">
        <h4>📌 주의사항</h4>
        <ul>
            <li>단위중량은 KS D 3504 규격에 따른 이론값입니다.</li>
            <li>실제 중량은 제조 공차에 따라 약간의 차이가 있을 수 있습니다.</li>
            <li>견적 시에는 여유분을 고려하여 주문하시기 바랍니다.</li>
            <li>운송 및 하역 시 손실을 고려하여 3~5% 여유분을 추천합니다.</li>
        </ul>
    </div>

    <div class="formula-section">
        <h2 class="formula-title">5. 가격 계산식</h2>
        
        <div class="formula-box">
            <div class="formula">
                <strong>기본 금액 = 총 중량(kg) × 단가(원/kg)</strong>
            </div>
            <div class="formula-desc">
                • 재질별, 원산지별 추가 비용이 있을 수 있습니다.
            </div>
        </div>

        <div class="formula-box">
            <div class="formula">
                <strong>최종 금액 = 기본 금액 + 재질 추가비용 + 원산지 추가비용</strong>
            </div>
            <div class="formula-desc">
                • 추가비용 = 총 중량(kg) × 추가단가(원/kg)
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <a href="rebar_quote.php" class="calculator-link">철근 견적 계산기 사용하기</a>
        <a href="rebar_products_list.php" class="calculator-link" style="margin-left: 10px;">철근 제품 목록 보기</a>
    </div>
</div>

<?php include 'tail.php'; ?>