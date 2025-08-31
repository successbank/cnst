<?php
require_once 'db.php';
$currentPage = 'products';

// 파라미터 처리
$product_id = $_GET['id'] ?? 0;
$category_code = $_GET['category'] ?? '';
$selected_spec = $_GET['spec'] ?? '';

// ID가 있으면 상품 상세 페이지, category가 있으면 계산기 페이지
if ($product_id) {
    // 상품 상세 페이지
    session_start();
    
    // 관리자 권한 체크
    $is_admin = isset($_SESSION['admin_id']) && $_SESSION['admin_id'];
    
    // 조회수 증가
    $stmt = $pdo->prepare("UPDATE products SET view_count = COALESCE(view_count, 0) + 1 WHERE id = ?");
    $stmt->execute([$product_id]);
    
    // 제품 정보 가져오기
    $stmt = $pdo->prepare("
        SELECT p.*, pc.category_name,
               pp.unit_weight_data as parent_unit_weight_data,
               pp.available_materials as parent_available_materials,
               pp.calculation_type as parent_calculation_type
        FROM products p 
        JOIN product_categories pc ON p.category_code = pc.category_code 
        LEFT JOIN products pp ON p.parent_product_id = pp.id
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 계산기 데이터 준비
    if ($product['has_calculator']) {
        // 부모 제품의 데이터가 있으면 사용, 없으면 현재 제품의 데이터 사용
        $unit_weight_data = $product['parent_unit_weight_data'] ?? $product['unit_weight_data'];
        $available_materials = $product['parent_available_materials'] ?? $product['available_materials'];
        $calculation_type = $product['parent_calculation_type'] ?? $product['calculation_type'];
        
        // JSON 파싱
        $unit_weight_data = json_decode($unit_weight_data, true) ?? [];
        $available_materials = json_decode($available_materials, true) ?? [];
    }
    
    if (!$product) {
        header('Location: products.php');
        exit;
    }
    
    $pageTitle = $product['product_name'] . ' | 충남스틸';
    $additionalCSS = [];
    require_once 'head.php';
    ?>
    
    <style>
    .product-detail-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
    }
    
    .product-detail-header {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .product-category {
        color: #1428A0;
        font-size: 16px;
        margin-bottom: 10px;
    }
    
    .product-title {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin-bottom: 20px;
    }
    
    .product-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 30px;
    }
    
    .product-image-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .product-image-section img {
        max-width: 100%;
        max-height: 400px;
        object-fit: contain;
    }
    
    .no-image {
        font-size: 120px;
        color: #ddd;
    }
    
    .product-details-section {
        padding: 20px;
    }
    
    .detail-item {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .detail-item:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #666;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .detail-value {
        font-size: 18px;
        color: #333;
    }
    
    .unit-weight-highlight {
        color: #1428A0;
        font-weight: 700;
        font-size: 24px;
    }
    
    .calculator-link-btn {
        display: inline-block;
        padding: 15px 30px;
        background: #1428A0;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    
    .calculator-link-btn:hover {
        background: #0F1F7A;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
    }
    
    .stock-status {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .stock-status.in-stock {
        background: #d4edda;
        color: #155724;
    }
    
    .stock-status.out-of-stock {
        background: #f8d7da;
        color: #721c24;
    }
    
    .stock-status.on-order {
        background: #fff3cd;
        color: #856404;
    }
    
    .origin-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
    }
    
    .origin-badge {
        padding: 4px 12px;
        background: #e8f0ff;
        border-radius: 16px;
        font-size: 14px;
        color: #1428A0;
    }
    
    /* 실시간 계산기 스타일 */
    .calculator-section {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
    }
    
    .calculator-title {
        font-size: 20px;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
    }
    
    .calc-form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .calc-form-group {
        display: flex;
        flex-direction: column;
    }
    
    .calc-form-group label {
        font-size: 14px;
        font-weight: 600;
        color: #666;
        margin-bottom: 8px;
    }
    
    .calc-control {
        padding: 10px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s;
    }
    
    .calc-control:focus {
        outline: none;
        border-color: #1428A0;
    }
    
    .calc-result {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .calc-result-header {
        font-size: 16px;
        font-weight: 600;
        color: #666;
        margin-bottom: 10px;
    }
    
    .calc-result-value {
        font-size: 32px;
        font-weight: 700;
        color: #1428A0;
        margin-bottom: 15px;
    }
    
    .calc-result-price {
        font-size: 24px;
        font-weight: 600;
        color: #28a745;
        margin: 15px 0;
        padding: 15px;
        background: #f0fff4;
        border-radius: 8px;
        text-align: center;
    }
    
    .calc-steps {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
    }
    
    .calc-step {
        padding: 5px 0;
        border-bottom: 1px solid #eee;
    }
    
    .calc-step:last-child {
        border-bottom: none;
    }
    
    /* 제품 상세보기 섹션 스타일 */
    .product-detail-info-section {
        margin: 40px 0;
        background: #f8f9fa;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .section-header {
        background: #4a90e2;
        color: white;
        padding: 15px 20px;
        font-size: 18px;
        font-weight: 600;
    }
    
    .section-header h2 {
        margin: 0;
        font-size: 20px;
    }
    
    .detail-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0;
        background: white;
    }
    
    .info-item {
        display: grid;
        grid-template-columns: 140px 1fr;
        border-bottom: 1px solid #e0e0e0;
        border-right: 1px solid #e0e0e0;
    }
    
    .info-item:nth-child(2n) {
        border-right: none;
    }
    
    .info-item:nth-last-child(-n+2) {
        border-bottom: none;
    }
    
    .info-label {
        background: #f0f0f0;
        padding: 12px 15px;
        font-weight: 600;
        font-size: 14px;
        color: #333;
        border-right: 1px solid #e0e0e0;
    }
    
    .info-value {
        padding: 12px 15px;
        font-size: 14px;
        color: #666;
        background: white;
    }
    
    .info-value a {
        color: #4a90e2;
        text-decoration: none;
    }
    
    .info-value a:hover {
        text-decoration: underline;
    }
    
    /* 전체 너비 아이템 (제품 설명용) */
    .info-item.full-width {
        grid-column: 1 / -1;
        border-right: none;
    }
    
    .info-item.full-width:last-child {
        border-bottom: none;
    }
    
    /* 관리자 수정 버튼 스타일 */
    .admin-edit-btn {
        display: inline-block;
        background-color: #1428A0;
        color: white !important;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 5px;
        font-size: 14px;
        font-weight: 600;
        transition: background-color 0.3s ease;
        margin-top: 15px;
    }
    
    .admin-edit-btn:hover {
        background-color: #0F1F7A;
        color: white !important;
        text-decoration: none;
    }
    
    .product-header-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
    }
    
    .product-title-section {
        flex: 1;
    }
    
    @media (max-width: 768px) {
        .product-info-grid {
            grid-template-columns: 1fr;
        }
        
        .product-title {
            font-size: 24px;
        }
        
        .product-header-top {
            flex-direction: column;
        }
        
        .admin-actions {
            margin-top: 10px;
        }
        
        .admin-edit-btn {
            font-size: 13px;
            padding: 8px 16px;
        }
        
        .calc-form-row {
            grid-template-columns: 1fr;
        }
        
        .detail-info-grid {
            grid-template-columns: 1fr;
        }
        
        .info-item {
            border-right: none;
        }
        
        .info-item:nth-child(2n) {
            border-right: none;
        }
    }
    </style>
    
    <div class="product-detail-container">
        <div class="product-detail-header">
            <div class="product-header-top">
                <div class="product-title-section">
                    <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                    <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                </div>
                <?php if ($is_admin): ?>
                <div class="admin-actions">
                    <a href="/admin/admin_products_edit.php?id=<?php echo $product_id; ?>" 
                       class="admin-edit-btn">제품 수정</a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="product-info-grid">
                <div class="product-image-section">
                    <?php if ($product['main_image']): ?>
                        <img src="<?php echo htmlspecialchars($product['main_image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    <?php else: ?>
                        <?php
                        $icons = [
                            'rebar' => '🔩',
                            'h-beam' => '🏗️',
                            'steel-plate' => '📐',
                            'metal-lath' => '🔲',
                            'light-h-beam' => '🏢',
                            'i-beam' => '📍',
                            'angle' => '📏',
                            'channel' => '🔨',
                            'round-bar' => '⭕',
                            'flat-bar' => '➖',
                            'c-beam' => '🔧',
                            'deck-plate' => '🏗️',
                            'square-pipe' => '⬜',
                            'round-pipe' => '⚪',
                            'rail' => '🚂',
                            'sheet-pile' => '🔱',
                            'stainless' => '✨'
                        ];
                        ?>
                        <div class="no-image"><?php echo $icons[$product['category_code']] ?? '📦'; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="product-details-section">
                    <?php if ($product['specification']): ?>
                    <div class="detail-item">
                        <div class="detail-label">규격</div>
                        <div class="detail-value"><?php echo htmlspecialchars($product['specification']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($product['specification_weight']): ?>
                    <div class="detail-item">
                        <div class="detail-label">단위중량</div>
                        <div class="detail-value unit-weight-highlight">
                            <?php echo htmlspecialchars($product['specification_weight']); ?> kg/m
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['available_origins'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">원산지</div>
                        <div class="detail-value">
                            <div class="origin-badges">
                                <?php 
                                $origins = json_decode($product['available_origins'], true) ?: [];
                                foreach ($origins as $origin): 
                                ?>
                                    <span class="origin-badge"><?php echo htmlspecialchars($origin); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <div class="detail-label">재고 상태</div>
                        <div class="detail-value">
                            <?php
                            $stock_status_class = '';
                            $stock_status_text = '';
                            switch($product['stock_status']) {
                                case 'in_stock':
                                    $stock_status_class = 'in-stock';
                                    $stock_status_text = '재고 있음';
                                    break;
                                case 'out_of_stock':
                                    $stock_status_class = 'out-of-stock';
                                    $stock_status_text = '재고 없음';
                                    break;
                                case 'on_order':
                                    $stock_status_class = 'on-order';
                                    $stock_status_text = '주문 가능';
                                    break;
                                default:
                                    $stock_status_class = 'on-order';
                                    $stock_status_text = '문의 필요';
                            }
                            ?>
                            <span class="stock-status <?php echo $stock_status_class; ?>">
                                <?php echo $stock_status_text; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($product['has_calculator']): ?>
                    <!-- 실시간 중량 계산기 섹션 -->
                    <div class="calculator-section">
                        <h3 class="calculator-title">📊 실시간 중량 계산기</h3>
                        <div class="inline-calculator">
                            <div class="calc-form-row">
                                <div class="calc-form-group">
                                    <label>재질 선택</label>
                                    <select id="calc-material" class="calc-control">
                                        <option value="">기본 재질</option>
                                        <?php foreach ($available_materials as $material): ?>
                                        <option value="<?php echo htmlspecialchars($material); ?>">
                                            <?php echo htmlspecialchars($material); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if ($calculation_type === 'linear'): ?>
                                <div class="calc-form-group">
                                    <label>길이 (미터)</label>
                                    <input type="number" id="calc-length" class="calc-control" 
                                           min="0.1" step="0.1" placeholder="예: 6" value="">
                                </div>
                                <?php endif; ?>
                                
                                <div class="calc-form-group">
                                    <label>수량 (<?php echo $calculation_type === 'linear' ? '본' : '장'; ?>)</label>
                                    <input type="number" id="calc-quantity" class="calc-control" 
                                           min="1" placeholder="예: 10" value="">
                                </div>
                            </div>
                            
                            <!-- 실시간 계산 결과 -->
                            <div class="calc-result" id="calcResult" style="display: none;">
                                <div class="calc-result-header">계산 결과</div>
                                <div class="calc-result-value" id="calcResultValue">0 kg</div>
                                <div class="calc-result-price" id="calcResultPrice">견적금액: 0원</div>
                                <div class="calc-steps" id="calcSteps"></div>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                    // 계산기 데이터
                    const calculatorData = {
                        unitWeightData: <?php echo json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE); ?>,
                        specification: '<?php echo htmlspecialchars($product['specification']); ?>',
                        calculationType: '<?php echo $calculation_type; ?>',
                        unitWeight: <?php echo $product['specification_weight'] ?? 0; ?>
                    };
                    </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 제품 상세보기 섹션 -->
        <div class="product-detail-info-section">
            <div class="section-header">
                <h2>제품 상세보기</h2>
            </div>
            <div class="detail-info-grid">
                <div class="info-item">
                    <div class="info-label">주식회사 충남스틸</div>
                    <div class="info-value">구조용 강관 전문 공급업체</div>
                </div>
                <div class="info-item">
                    <div class="info-label">고객센터</div>
                    <div class="info-value">041-123-4567</div>
                </div>
                <div class="info-item">
                    <div class="info-label">영업시간</div>
                    <div class="info-value">평일 09:00 - 18:00</div>
                </div>
                <div class="info-item">
                    <div class="info-label">바로가기</div>
                    <div class="info-value">
                        <a href="/products_new.php?category=<?php echo htmlspecialchars($product['category_code']); ?>"><?php echo htmlspecialchars($product['category_name']); ?> 전체보기</a> | 
                        <a href="/contact.php">견적문의</a>
                    </div>
                </div>
                <?php if (!empty($product['features'])): ?>
                <div class="info-item">
                    <div class="info-label">제품 용도</div>
                    <div class="info-value"><?php echo htmlspecialchars($product['features']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['quality_cert'])): ?>
                <div class="info-item">
                    <div class="info-label">품질 인증</div>
                    <div class="info-value"><?php echo htmlspecialchars($product['quality_cert']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['material'])): ?>
                <div class="info-item">
                    <div class="info-label">재료</div>
                    <div class="info-value"><?php echo htmlspecialchars($product['material']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['manufacturer'])): ?>
                <div class="info-item">
                    <div class="info-label">제조사</div>
                    <div class="info-value"><?php echo htmlspecialchars($product['manufacturer']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['product_features'])): ?>
                <div class="info-item">
                    <div class="info-label">특징</div>
                    <div class="info-value"><?php echo htmlspecialchars($product['product_features']); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['delivery_info'])): ?>
                <div class="info-item">
                    <div class="info-label">배송 정보</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($product['delivery_info'])); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['description'])): ?>
                <div class="info-item full-width">
                    <div class="info-label">제품 설명</div>
                    <div class="info-value">
                        <?php
                        // 설명을 줄 단위로 분리
                        $lines = explode("\n", $product['description']);
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (empty($line)) continue;
                            
                            // 이미지 파일 확장자 패턴
                            if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $line)) {
                                // 이미지 경로인 경우
                                echo '<img src="' . htmlspecialchars($line) . '" alt="제품 이미지" style="max-width: 100%; height: auto; margin: 10px 0;">';
                            } else {
                                // 일반 텍스트인 경우
                                echo nl2br(htmlspecialchars($line)) . '<br>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($product['has_calculator']): ?>
    <script>
    // 실시간 계산 기능
    function calculateWeight() {
        const material = document.getElementById('calc-material').value;
        const quantity = parseInt(document.getElementById('calc-quantity').value) || 0;
        const lengthInput = document.getElementById('calc-length');
        const length = lengthInput ? (parseFloat(lengthInput.value) || 0) : 0;
        
        // 입력값 검증
        if (quantity <= 0) {
            document.getElementById('calcResult').style.display = 'none';
            return;
        }
        
        if (calculatorData.calculationType === 'linear' && length <= 0) {
            document.getElementById('calcResult').style.display = 'none';
            return;
        }
        
        // 단위 중량 가져오기
        let unitWeight = calculatorData.unitWeight;
        
        // 계산기 데이터에서 단위 중량 찾기
        if (calculatorData.unitWeightData && calculatorData.specification) {
            const specData = calculatorData.unitWeightData[calculatorData.specification];
            if (specData) {
                if (material && specData[material]) {
                    unitWeight = specData[material];
                } else {
                    // 첫 번째 재질의 단위중량 사용
                    unitWeight = Object.values(specData)[0] || unitWeight;
                }
            }
        }
        
        // 중량 계산
        let calculatedWeight = 0;
        let calculationSteps = [];
        
        if (calculatorData.calculationType === 'linear') {
            // 선형 제품: 단위중량 × 길이 × 수량
            const weightPerPiece = unitWeight * length;
            calculatedWeight = weightPerPiece * quantity;
            
            calculationSteps = [
                `단위중량: ${unitWeight} kg/m`,
                `1본 중량: ${unitWeight} × ${length}m = ${weightPerPiece.toFixed(2)} kg`,
                `총 중량: ${weightPerPiece.toFixed(2)} × ${quantity}본 = ${calculatedWeight.toFixed(1)} kg`
            ];
        } else {
            // 판재 제품: 단위중량(장) × 수량
            calculatedWeight = unitWeight * quantity;
            
            calculationSteps = [
                `단위중량(장): ${unitWeight} kg`,
                `총 중량: ${unitWeight} × ${quantity}장 = ${calculatedWeight.toFixed(1)} kg`
            ];
        }
        
        // 견적금액 계산 (kg당 1,000원)
        const pricePerKg = 1000;
        const totalPrice = calculatedWeight * pricePerKg;
        
        // 계산 과정에 견적금액 추가
        calculationSteps.push(`견적금액: ${calculatedWeight.toFixed(1)}kg × ${pricePerKg.toLocaleString()}원/kg = ${totalPrice.toLocaleString()}원`);
        
        // 결과 표시
        document.getElementById('calcResultValue').textContent = calculatedWeight.toFixed(1) + ' kg';
        document.getElementById('calcResultPrice').textContent = '견적금액: ' + totalPrice.toLocaleString() + '원';
        
        // 계산 과정 표시
        const stepsHtml = calculationSteps.map(step => 
            `<div class="calc-step">${step}</div>`
        ).join('');
        document.getElementById('calcSteps').innerHTML = stepsHtml;
        
        // 결과 영역 표시
        document.getElementById('calcResult').style.display = 'block';
    }
    
    // 디바운스 함수
    let calculateTimer;
    function debouncedCalculate() {
        clearTimeout(calculateTimer);
        calculateTimer = setTimeout(calculateWeight, 300);
    }
    
    // 이벤트 리스너 등록
    document.addEventListener('DOMContentLoaded', function() {
        // 입력 필드에 이벤트 리스너 추가
        document.getElementById('calc-material').addEventListener('change', debouncedCalculate);
        document.getElementById('calc-quantity').addEventListener('input', debouncedCalculate);
        
        const lengthInput = document.getElementById('calc-length');
        if (lengthInput) {
            lengthInput.addEventListener('input', debouncedCalculate);
        }
    });
    </script>
    <?php endif; ?>
    
    <?php
    require_once 'tail.php';
    
} else if ($category_code) {
    // 기존 계산기 페이지 코드
    
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
                    <input type="number" class="form-control" id="length" min="0.1" step="0.1" placeholder="예: 6" required>
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
        
        const btn = document.getElementById('calculateBtn');
        btn.disabled = true;
        btn.textContent = '계산 중...';
        
        try {
            const formData = {
                category: document.getElementById('categoryCode').value,
                specification: document.getElementById('specification').value,
                material: document.getElementById('material').value,
                length: parseFloat(document.getElementById('length')?.value || 0),
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
    
    // 페이지 로드 시 선택된 규격이 있으면 단위중량 표시
    window.addEventListener('DOMContentLoaded', function() {
        const specSelect = document.getElementById('specification');
        if (specSelect.value) {
            specSelect.dispatchEvent(new Event('change'));
        }
    });
    </script>
    
    <?php 
    require_once 'tail.php';
    
} else {
    // 파라미터가 없으면 제품 목록으로 리다이렉트
    header('Location: products.php');
    exit;
}
?>