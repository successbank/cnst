<?php
require_once 'db.php';
include 'head.php';

// 번들 데이터 샘플 조회
$stmt = $pdo->prepare("
    SELECT DISTINCT p_standard, p_material, p_unit_weight 
    FROM rebar_bundle_data 
    WHERE p_bd_count > 0 AND p_bd_weight > 0
    ORDER BY p_standard 
    LIMIT 20
");
$stmt->execute();
$standards = $stmt->fetchAll();

// HD16 8m 샘플 데이터
$stmt = $pdo->prepare("
    SELECT * FROM rebar_bundle_data 
    WHERE p_standard = 'HD16' AND p_unit_length = 8.0 AND p_material = 'SD400'
    LIMIT 1
");
$stmt->execute();
$sample_hd16 = $stmt->fetch();

// HD25 12m 샘플 데이터
$stmt = $pdo->prepare("
    SELECT * FROM rebar_bundle_data 
    WHERE p_standard = 'HD25' AND p_unit_length = 12.0 AND p_material = 'SD400'
    LIMIT 1
");
$stmt->execute();
$sample_hd25 = $stmt->fetch();
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

.bundle-info-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.bundle-info-table th,
.bundle-info-table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
}

.bundle-info-table th {
    background: #f5f5f5;
    font-weight: bold;
}

.bundle-info-table tr:hover {
    background: #f9f9f9;
}

.highlight-box {
    background: #ffeb3b;
    padding: 5px 10px;
    border-radius: 3px;
    font-weight: bold;
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
        <h1>철근 번들 계산식 안내</h1>
        <p>충남스틸 철근 번들 기준 중량 계산 공식</p>
    </div>

    <div class="note-box">
        <h4>📌 중요 변경사항</h4>
        <p>철근은 <strong>번들(Bundle) 단위</strong>로 판매됩니다. 각 규격과 길이별로 번들당 본수와 중량이 정해져 있습니다.</p>
    </div>

    <div class="formula-section">
        <h2 class="formula-title">1. 번들 기반 계산 공식</h2>
        
        <div class="formula-box">
            <div class="formula">
                <strong>총 중량(kg) = 번들 수량 × 번들당 중량(kg)</strong>
            </div>
            <div class="formula-desc">
                • 번들 수량: 주문하려는 번들의 개수<br>
                • 번들당 중량: 규격과 길이별로 정해진 고정값
            </div>
        </div>

        <div class="formula-box">
            <div class="formula">
                <strong>총 본수 = 번들 수량 × 번들당 본수</strong>
            </div>
            <div class="formula-desc">
                • 번들당 본수: 규격과 길이별로 정해진 고정값
            </div>
        </div>

        <div class="formula-box">
            <div class="formula">
                <strong>총 금액 = 총 중량 × (기준단가 + 재질추가단가 + 원산지추가단가)</strong>
            </div>
            <div class="formula-desc">
                • 기준단가: kg당 기본 가격<br>
                • 추가단가: 재질과 원산지에 따른 추가 비용
            </div>
        </div>
    </div>

    <div class="formula-section">
        <h2 class="formula-title">2. 번들 정보 예시</h2>
        
        <table class="bundle-info-table">
            <thead>
                <tr>
                    <th>규격</th>
                    <th>재질</th>
                    <th>길이(m)</th>
                    <th>단위중량(kg/m)</th>
                    <th>번들당 본수</th>
                    <th>번들당 중량(kg)</th>
                    <th>본당 중량(kg)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($sample_hd16): ?>
                <tr>
                    <td><strong><?php echo $sample_hd16['p_standard']; ?></strong></td>
                    <td><?php echo $sample_hd16['p_material']; ?></td>
                    <td><?php echo $sample_hd16['p_unit_length']; ?></td>
                    <td><?php echo $sample_hd16['p_unit_weight']; ?></td>
                    <td class="highlight-box"><?php echo $sample_hd16['p_bd_count']; ?></td>
                    <td class="highlight-box"><?php echo $sample_hd16['p_bd_weight']; ?></td>
                    <td><?php echo number_format($sample_hd16['p_unit_weight'] * $sample_hd16['p_unit_length'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($sample_hd25): ?>
                <tr>
                    <td><strong><?php echo $sample_hd25['p_standard']; ?></strong></td>
                    <td><?php echo $sample_hd25['p_material']; ?></td>
                    <td><?php echo $sample_hd25['p_unit_length']; ?></td>
                    <td><?php echo $sample_hd25['p_unit_weight']; ?></td>
                    <td class="highlight-box"><?php echo $sample_hd25['p_bd_count']; ?></td>
                    <td class="highlight-box"><?php echo $sample_hd25['p_bd_weight']; ?></td>
                    <td><?php echo number_format($sample_hd25['p_unit_weight'] * $sample_hd25['p_unit_length'], 2); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="formula-section">
        <h2 class="formula-title">3. 계산 예시</h2>
        
        <?php if ($sample_hd16): ?>
        <div class="example-section">
            <h3 class="example-title">예시 1: HD16 철근 8m, 3번들 주문 시</h3>
            
            <div class="calculation-step">
                <span class="step-number">1</span>
                <div class="step-content">
                    <strong>번들 정보 확인</strong><br>
                    HD16, 8m, SD400 재질<br>
                    - 번들당 본수: <?php echo $sample_hd16['p_bd_count']; ?>본<br>
                    - 번들당 중량: <?php echo $sample_hd16['p_bd_weight']; ?>kg
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">2</span>
                <div class="step-content">
                    <strong>총 중량 계산</strong><br>
                    총 중량 = 3번들 × <?php echo $sample_hd16['p_bd_weight']; ?>kg = <?php echo 3 * $sample_hd16['p_bd_weight']; ?>kg
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">3</span>
                <div class="step-content">
                    <strong>총 본수 계산</strong><br>
                    총 본수 = 3번들 × <?php echo $sample_hd16['p_bd_count']; ?>본 = <?php echo 3 * $sample_hd16['p_bd_count']; ?>본
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">4</span>
                <div class="step-content">
                    <strong>가격 계산 (예: 기준단가 700원/kg)</strong><br>
                    총 금액 = <?php echo 3 * $sample_hd16['p_bd_weight']; ?>kg × 700원 = <?php echo number_format(3 * $sample_hd16['p_bd_weight'] * 700); ?>원
                </div>
            </div>
            
            <div class="result-box">
                최종 결과: 총 <?php echo 3 * $sample_hd16['p_bd_weight']; ?>kg (<?php echo 3 * $sample_hd16['p_bd_count']; ?>본)
            </div>
        </div>
        <?php endif; ?>

        <?php if ($sample_hd25): ?>
        <div class="example-section">
            <h3 class="example-title">예시 2: HD25 철근 12m, 2번들 주문 시</h3>
            
            <div class="calculation-step">
                <span class="step-number">1</span>
                <div class="step-content">
                    <strong>번들 정보 확인</strong><br>
                    HD25, 12m, SD400 재질<br>
                    - 번들당 본수: <?php echo $sample_hd25['p_bd_count']; ?>본<br>
                    - 번들당 중량: <?php echo $sample_hd25['p_bd_weight']; ?>kg
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">2</span>
                <div class="step-content">
                    <strong>총 중량 계산</strong><br>
                    총 중량 = 2번들 × <?php echo $sample_hd25['p_bd_weight']; ?>kg = <?php echo 2 * $sample_hd25['p_bd_weight']; ?>kg
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">3</span>
                <div class="step-content">
                    <strong>총 본수 계산</strong><br>
                    총 본수 = 2번들 × <?php echo $sample_hd25['p_bd_count']; ?>본 = <?php echo 2 * $sample_hd25['p_bd_count']; ?>본
                </div>
            </div>
            
            <div class="calculation-step">
                <span class="step-number">4</span>
                <div class="step-content">
                    <strong>가격 계산 (기준단가 700원 + SD400S 재질 추가 50원)</strong><br>
                    총 금액 = <?php echo 2 * $sample_hd25['p_bd_weight']; ?>kg × 750원 = <?php echo number_format(2 * $sample_hd25['p_bd_weight'] * 750); ?>원
                </div>
            </div>
            
            <div class="result-box">
                최종 결과: 총 <?php echo 2 * $sample_hd25['p_bd_weight']; ?>kg (<?php echo 2 * $sample_hd25['p_bd_count']; ?>본)
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="formula-section">
        <h2 class="formula-title">4. 재질별 추가 단가</h2>
        
        <?php
        $stmt = $pdo->query("SELECT * FROM rebar_material_prices WHERE is_active = 1 ORDER BY material");
        $materials = $stmt->fetchAll();
        ?>
        
        <table class="bundle-info-table">
            <thead>
                <tr>
                    <th>재질</th>
                    <th>추가단가 (원/kg)</th>
                    <th>설명</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $material): ?>
                <tr>
                    <td><strong><?php echo $material['material']; ?></strong></td>
                    <td><?php echo $material['additional_price'] > 0 ? '+' : ''; ?><?php echo number_format($material['additional_price']); ?>원</td>
                    <td><?php echo htmlspecialchars($material['description']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="formula-section">
        <h2 class="formula-title">5. 원산지별 추가 단가</h2>
        
        <?php
        $stmt = $pdo->query("SELECT * FROM rebar_country_prices WHERE is_active = 1 ORDER BY country");
        $countries = $stmt->fetchAll();
        ?>
        
        <table class="bundle-info-table">
            <thead>
                <tr>
                    <th>원산지</th>
                    <th>추가단가 (원/kg)</th>
                    <th>설명</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($countries as $country): ?>
                <tr>
                    <td><strong><?php echo $country['country']; ?></strong></td>
                    <td><?php echo $country['additional_price'] > 0 ? '+' : ''; ?><?php echo number_format($country['additional_price']); ?>원</td>
                    <td><?php echo htmlspecialchars($country['description']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="note-box">
        <h4>📌 주의사항</h4>
        <ul>
            <li><strong>번들 단위 판매</strong>: 철근은 번들 단위로만 판매되며, 낱개 판매는 하지 않습니다.</li>
            <li><strong>길이별 번들 구성</strong>: 같은 규격이라도 길이에 따라 번들당 본수와 중량이 다릅니다.</li>
            <li><strong>실제 중량</strong>: 제조 공차로 인해 실제 중량은 표시된 값과 약간 차이가 있을 수 있습니다.</li>
            <li><strong>운송 고려사항</strong>: 번들 단위로 포장되어 있어 운송과 하역이 용이합니다.</li>
            <li><strong>최소 주문 단위</strong>: 1번들부터 주문 가능합니다.</li>
        </ul>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <a href="rebar_quote_bundle.php" class="calculator-link">번들 기반 철근 견적 계산기</a>
        <a href="rebar_products_list.php" class="calculator-link" style="margin-left: 10px;">철근 제품 목록 보기</a>
    </div>
</div>

<?php include 'tail.php'; ?>