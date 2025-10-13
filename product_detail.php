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

    // 제품이 없으면 즉시 리다이렉트
    if (!$product) {
        header('Location: products.php');
        exit;
    }

    // 계산기 데이터 준비
    if ($product['has_calculator']) {
        // 부모 제품의 데이터가 있으면 사용, 없으면 현재 제품의 데이터 사용
        $unit_weight_data = $product['parent_unit_weight_data'] ?? $product['unit_weight_data'];
        $available_materials = $product['parent_available_materials'] ?? $product['available_materials'];
        $calculation_type = $product['parent_calculation_type'] ?? $product['calculation_type'];

        // JSON 파싱
        $unit_weight_data = json_decode($unit_weight_data, true) ?? [];
        $available_materials = json_decode($available_materials, true) ?? [];

        // 철근 제품인 경우 길이별 본수 데이터 조회
        $pieces_per_length_data = [];
        $available_lengths = [];
        $spec_name = '';
        if ($product['category_code'] === 'rebar') {
            // 제품명에서 규격 추출 (예: "철근 D10" → "D10")
            $spec_name = str_replace('철근 ', '', $product['product_name']);

            $stmt = $pdo->prepare("
                SELECT length, pieces_per_length
                FROM rebar_length_data
                WHERE spec_name = ?
                AND length BETWEEN 6 AND 12
                ORDER BY length
            ");
            $stmt->execute([$spec_name]);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // 길이를 문자열 키로 변환 (JavaScript에서 사용하기 위해)
                $length_key = number_format($row['length'], 1, '.', '');
                $pieces_per_length_data[$length_key] = $row['pieces_per_length'];

                // 사용 가능한 길이 목록 저장
                $available_lengths[] = $row['length'];
            }
        }
    }

    $pageTitle = $product['product_name'] . ' | 충남스틸';
    $additionalCSS = [];
    require_once 'head.php';
    ?>
    
    <style>
    /* Product Detail Page Styles - Using Samsung Style Variables */
    .product-header-section {
        background: linear-gradient(135deg, #E8F0FE 0%, #F8F9FA 100%);
        padding: 60px 0;
        text-align: center;
        position: relative;
    }
    
    .product-header-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .product-category {
        color: var(--primary-blue);
        font-size: 18px;
        margin-bottom: 12px;
        font-weight: 600;
    }
    
    .product-title {
        font-size: 36px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 12px;
    }
    
    .product-subtitle {
        font-size: 18px;
        color: var(--text-secondary);
        margin-bottom: 20px;
    }
    
    .admin-actions {
        position: absolute;
        top: 20px;
        right: 20px;
    }
    
    .product-detail-container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
    }
    
    .product-info-section {
        background: var(--white);
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .product-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    
    .product-image-section {
        background: var(--bg-light);
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
        color: var(--border-color);
    }
    
    .product-details-section {
        padding: 20px;
    }
    
    .detail-item {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }
    
    .detail-item:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .detail-value {
        font-size: 18px;
        color: var(--text-primary);
    }
    
    .unit-weight-highlight {
        color: var(--primary-blue);
        font-weight: 700;
        font-size: 24px;
    }
    
    .calculator-link-btn {
        display: inline-block;
        padding: 15px 30px;
        background: var(--primary-blue);
        color: var(--white);
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    
    .calculator-link-btn:hover {
        background: var(--secondary-blue);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
    }
    .origin-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 8px;
    }
    
    .origin-badge {
        padding: 4px 12px;
        background: var(--light-blue);
        border-radius: 16px;
        font-size: 14px;
        color: var(--primary-blue);
    }
    
    .origin-select {
        width: 100%;
        max-width: 250px;
        padding: 10px 12px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        font-size: 15px;
        background-color: var(--white);
        cursor: pointer;
        transition: border-color 0.3s ease;
    }
    
    .origin-select:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 3px rgba(20, 40, 160, 0.1);
    }
    
    .origin-select:hover {
        border-color: var(--primary-blue);
    }
    
    /* 실시간 계산기 스타일 */
    .calculator-section {
        background: var(--bg-light);
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
    }

    /* 가격 범위 박스 */
    .price-range-box {
        background: #f8f8f8;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 20px;
    }

    .price-range-title {
        font-size: 14px;
        color: #666;
        margin-bottom: 5px;
    }

    .price-range-values {
        font-size: 16px;
        font-weight: 600;
    }

    .price-min {
        color: #1E90FF;
        margin-right: 20px;
    }

    .price-max {
        color: #FF6347;
    }

    .calculator-title {
        font-size: 20px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 20px;
    }

    .calc-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }

    .calc-form-group {
        display: flex;
        flex-direction: column;
    }

    .calc-form-group label {
        font-size: 14px;
        font-weight: 500;
        color: #666;
        margin-bottom: 6px;
    }

    .calc-control {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.3s;
        background: white;
    }

    .calc-control:focus {
        outline: none;
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 2px rgba(20, 40, 160, 0.1);
    }

    .input-help {
        margin-top: 5px;
        font-size: 12px;
        color: #999;
    }
    
    .calc-result {
        background: var(--white);
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
        color: var(--text-secondary);
        margin-bottom: 10px;
    }
    
    .calc-result-value {
        font-size: 32px;
        font-weight: 700;
        color: var(--primary-blue);
        margin-bottom: 15px;
    }
    
    .calc-result-price {
        font-size: 24px;
        font-weight: 600;
        color: var(--success-color);
        margin: 15px 0;
        padding: 15px;
        background: var(--success-light-bg);
        border-radius: 8px;
        text-align: center;
    }
    
    .calc-steps {
        font-size: 14px;
        color: var(--text-secondary);
        line-height: 1.6;
    }
    
    .calc-step {
        padding: 5px 0;
        border-bottom: 1px solid var(--border-color);
    }
    
    .calc-step:last-child {
        border-bottom: none;
    }
    
    /* 제품 상세보기 섹션 스타일 */
    .product-detail-info-section {
        margin: 40px 0;
        background: var(--bg-light);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .section-header {
        background: var(--primary-blue);
        color: var(--white);
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
        background: var(--white);
    }
    
    .info-item {
        display: grid;
        grid-template-columns: 140px 1fr;
        border-bottom: 1px solid var(--border-color);
        border-right: 1px solid var(--border-color);
    }
    
    .info-item:nth-child(2n) {
        border-right: none;
    }
    
    .info-item:nth-last-child(-n+2) {
        border-bottom: none;
    }
    
    .info-label {
        background: var(--bg-light);
        padding: 12px 15px;
        font-weight: 600;
        font-size: 14px;
        color: var(--text-primary);
        border-right: 1px solid var(--border-color);
    }
    
    .info-value {
        padding: 12px 15px;
        font-size: 14px;
        color: var(--text-secondary);
        background: var(--white);
    }
    
    .info-value a {
        color: var(--primary-blue);
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
        background-color: var(--white);
        color: var(--primary-blue) !important;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 28px;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        border: 2px solid var(--primary-blue);
    }
    
    .admin-edit-btn:hover {
        background-color: var(--primary-blue);
        color: var(--white) !important;
        text-decoration: none;
        border-color: var(--primary-blue);
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
    
    <!-- Product Header Section -->
    <section class="product-header-section">
        <div class="product-header-content">
            <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
            <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>
            <p class="product-subtitle">충남스틸이 공급하는 고품질 <?php echo htmlspecialchars($product['category_name']); ?> 제품입니다</p>
        </div>
        <?php if ($is_admin): ?>
        <div class="admin-actions">
            <a href="/admin/admin_products_edit.php?id=<?php echo $product_id; ?>" 
               class="admin-edit-btn">제품 수정</a>
        </div>
        <?php endif; ?>
    </section>
    
    <!-- Product Detail Container -->
    <div class="product-detail-container">
        <div class="product-info-section">
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
                        <div class="detail-value"><?php echo htmlspecialchars($product['specification'] ?? ''); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($product['specification_weight']): ?>
                    <div class="detail-item">
                        <div class="detail-label">단위중량</div>
                        <div class="detail-value unit-weight-highlight">
                            <?php echo htmlspecialchars($product['specification_weight'] ?? ''); ?> kg/m
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($product['has_calculator']): ?>
                    <!-- 실시간 중량 계산기 섹션 -->
                    <div class="calculator-section">
                        <!-- 가격 범위 표시 -->
                        <div class="price-range-box">
                            <div class="price-range-title">가격 범위</div>
                            <div class="price-range-values">
                                <?php
                                $display_min_price = $product['min_price'] ?? ($product['price'] * 0.9);
                                $display_max_price = $product['max_price'] ?? ($product['price'] * 1.1);
                                ?>
                                <span class="price-min">최저: <?php echo number_format($display_min_price); ?>원</span>
                                <span class="price-max">최대: <?php echo number_format($display_max_price); ?>원</span>
                            </div>
                        </div>

                        <h3 class="calculator-title">📊 실시간 중량 계산기</h3>
                        <div class="inline-calculator">
                            <!-- 원산지와 재질 선택 (첫 번째 줄) -->
                            <div class="calc-form-row">
                                <div class="calc-form-group">
                                    <label>원산지</label>
                                    <select id="calc-origin" class="calc-control">
                                        <?php
                                        $origins = json_decode($product['available_origins'] ?? '[]', true) ?: [];
                                        if (empty($origins)) {
                                            // 기본값이 없으면 "선택하세요" 표시
                                            echo '<option value="" selected>선택하세요</option>';
                                        } else {
                                            // DB의 첫 번째 원산지를 기본값으로 사용
                                            foreach ($origins as $index => $origin):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($origin); ?>" <?php echo $index === 0 ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($origin); ?>
                                        </option>
                                        <?php
                                            endforeach;
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="calc-form-group">
                                    <label>재질 선택</label>
                                    <select id="calc-material" class="calc-control">
                                        <?php
                                        // Set default material for unequal-angle products
                                        $default_material = ($product['category_code'] === 'unequal-angle') ? 'SS400' : 'SS400';
                                        ?>
                                        <?php foreach ($available_materials as $material): ?>
                                        <option value="<?php echo htmlspecialchars($material); ?>"
                                                <?php echo ($material === $default_material) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($material); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <?php if ($calculation_type === 'linear'): ?>
                            <!-- 길이와 수량 입력 (두 번째 줄) -->
                            <div class="calc-form-row">
                                <div class="calc-form-group">
                                    <label>길이 (미터)</label>
                                    <?php if ($product['category_code'] === 'rebar' && !empty($available_lengths)): ?>
                                        <!-- 철근 제품: DB 기반 드롭다운 선택 -->
                                        <select id="calc-length" class="calc-control">
                                            <option value="0" selected>선택하세요</option>
                                            <?php foreach ($available_lengths as $length): ?>
                                                <option value="<?php echo $length; ?>">
                                                    <?php echo number_format($length, 1); ?>m
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="input-help">선택 가능 범위: 6.0m ~ 12.0m</div>
                                    <?php elseif ($product['category_code'] === 'unequal-angle'): ?>
                                        <!-- 부등변ㄱ형강: 6m-12m 드롭다운 선택 (0.1m 단위) -->
                                        <select id="calc-length" class="calc-control">
                                            <option value="0" selected>선택하세요</option>
                                            <?php
                                            // 6.0m부터 12.0m까지 0.1m 단위로 생성
                                            for ($i = 60; $i <= 120; $i++):
                                                $length_value = $i / 10;
                                            ?>
                                                <option value="<?php echo $length_value; ?>">
                                                    <?php echo number_format($length_value, 1); ?>m
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <div class="input-help">선택 가능 범위: 6.0m ~ 12.0m (0.1m 단위)</div>
                                    <?php elseif ($product['category_code'] === 'h-beam' || $product['category_code'] === 'light-h-beam' || $product['category_code'] === 'i-beam' || $product['category_code'] === 'angle' || $product['category_code'] === 'channel' || $product['category_code'] === 'flat-bar' || $product['category_code'] === 'round-bar' || $product['category_code'] === 'c-beam' || $product['category_code'] === 'rail' || $product['category_code'] === 'square-pipe' || $product['category_code'] === 'bs-pipe' || $product['category_code'] === 'ks-pipe' || $product['category_code'] === 'conduit' || $product['category_code'] === 'structural-pipe' || $product['category_code'] === 'steel-pipe-pile' || $product['category_code'] === 'deck-plate' || $product['category_code'] === 'sheet-pile' || $product['category_code'] === 'scaffold-pipe' || $product['category_code'] === 'pressure-pipe'): ?>
                                        <!-- H형강/경량H형강/I형강/ㄱ형강/ㄷ형강/평철/환봉/C형강/레일/사각파이프/BS파이프/KS파이프/전선관/구조관/강관파일/데크플레이트/쉬트파일/단관비계/압력배관: 6m-12m 드롭다운 선택 (0.1m 단위) -->
                                        <select id="calc-length" class="calc-control">
                                            <option value="0" selected>선택하세요</option>
                                            <?php
                                            // 6.0m부터 12.0m까지 0.1m 단위로 생성
                                            for ($i = 60; $i <= 120; $i++):
                                                $length_value = $i / 10;
                                            ?>
                                                <option value="<?php echo $length_value; ?>">
                                                    <?php echo number_format($length_value, 1); ?>m
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <div class="input-help">선택 가능 범위: 6.0m ~ 12.0m (0.1m 단위)</div>
                                    <?php else: ?>
                                        <!-- 기타 제품: 수동 입력 -->
                                        <input type="number" id="calc-length" class="calc-control"
                                               min="0" step="0.01" value="0" placeholder="길이를 입력하세요">
                                        <div class="input-help">길이를 입력하세요 (미터 단위)</div>
                                    <?php endif; ?>
                                </div>

                                <div class="calc-form-group">
                                    <label>수량 (본)</label>
                                    <input type="number" id="calc-quantity" class="calc-control"
                                           min="1" value="1">
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="calc-form-group">
                                <label>수량 (장)</label>
                                <input type="number" id="calc-quantity" class="calc-control"
                                       min="1" value="1">
                            </div>
                            <?php endif; ?>

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
                        specification: '<?php echo htmlspecialchars($product['specification'] ?? ''); ?>',
                        calculationType: '<?php echo $calculation_type ?? ''; ?>',
                        unitWeight: <?php echo $product['specification_weight'] ?? 0; ?>,
                        piecesPerLengthData: <?php echo json_encode($pieces_per_length_data); ?>,
                        specName: '<?php echo htmlspecialchars($spec_name ?? ''); ?>',
                        categoryCode: '<?php echo htmlspecialchars($product['category_code'] ?? ''); ?>',
                        // 가격 데이터 추가
                        price: <?php echo floatval($product['price'] ?? 1000); ?>,
                        minPrice: <?php echo floatval($product['min_price'] ?? 0); ?>,
                        maxPrice: <?php echo floatval($product['max_price'] ?? 0); ?>
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
                    <div class="info-value">032-564-1616</div>
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
                    <div class="info-value"><?php echo htmlspecialchars($product['features'] ?? ''); ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['quality_cert'])): ?>
                <div class="info-item">
                    <div class="info-label">품질 인증</div>
                    <div class="info-value"><?php echo htmlspecialchars($product['quality_cert'] ?? ''); ?></div>
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
                `1본 중량: ${unitWeight} × ${length}m = ${weightPerPiece.toFixed(2)} kg`
            ];

            // 철근 제품인 경우 본수 계산 추가
            if (calculatorData.categoryCode === 'rebar' && calculatorData.piecesPerLengthData) {
                // 길이를 문자열 키로 변환 (소수점 1자리)
                const lengthKey = length.toFixed(1);
                const piecesPerLength = calculatorData.piecesPerLengthData[lengthKey] || 0;

                if (piecesPerLength > 0) {
                    const totalPieces = piecesPerLength * quantity;
                    calculationSteps.push(
                        `톤당 본수: ${piecesPerLength}본 (${length}m 기준)`,
                        `총 본수: ${piecesPerLength}본 × ${quantity}톤 = ${totalPieces}본`
                    );
                } else {
                    calculationSteps.push(`본수: ${quantity}본`);
                }
            } else {
                calculationSteps.push(`본수: ${quantity}본`);
            }

            calculationSteps.push(
                `총 중량: ${weightPerPiece.toFixed(2)} × ${quantity}본 = ${calculatedWeight.toFixed(1)} kg`
            );
        } else {
            // 판재 제품: 단위중량(장) × 수량
            calculatedWeight = unitWeight * quantity;
            
            calculationSteps = [
                `단위중량(장): ${unitWeight} kg`,
                `총 중량: ${unitWeight} × ${quantity}장 = ${calculatedWeight.toFixed(1)} kg`
            ];
        }
        
        // 견적금액 계산 (DB의 기준단가 사용)
        const pricePerKg = calculatorData.price || 1000;
        const totalPrice = calculatedWeight * pricePerKg;

        // 계산 과정에 견적금액 추가
        calculationSteps.push(`견적금액: ${calculatedWeight.toFixed(1)}kg × ${pricePerKg.toLocaleString()}원/kg = ${totalPrice.toLocaleString()}원`);
        
        // 결과 표시
        let resultText = calculatedWeight.toFixed(1) + ' kg';
        if (calculatorData.calculationType === 'linear') {
            resultText += ' (' + quantity + '본)';
        } else {
            resultText += ' (' + quantity + '장)';
        }
        document.getElementById('calcResultValue').textContent = resultText;
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
            // Check if it's a select or input element
            if (lengthInput.tagName === 'SELECT') {
                lengthInput.addEventListener('change', debouncedCalculate);
                // 초기 계산은 수행하지 않음 (기본값이 0이므로)
            } else {
                lengthInput.addEventListener('input', debouncedCalculate);
            }
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
    
    .price-range-section {
        text-align: left;
        margin-bottom: 20px;
        padding: 15px 20px;
        background: #f8f8f8;
        border-radius: 8px;
    }

    .price-range-section h3 {
        font-size: 16px;
        color: #666;
        margin-bottom: 8px;
        font-weight: normal;
    }

    .price-range-section p {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    .product-header {
        text-align: left;
        margin-bottom: 30px;
        padding: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

    .help-text {
        margin-top: 5px;
        font-size: 13px;
        color: #999;
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
        <!-- 가격 범위 표시 -->
        <div class="price-range-section">
            <h3>가격 범위</h3>
            <p>
                <?php
                $display_min_price = $product['min_price'] ?? ($product['price'] * 0.9);
                $display_max_price = $product['max_price'] ?? ($product['price'] * 1.1);
                ?>
                <span style="color: #1E90FF;">최저: <?php echo number_format($display_min_price); ?>원</span>&nbsp;&nbsp;&nbsp;
                <span style="color: #FF6347;">최대: <?php echo number_format($display_max_price); ?>원</span>
            </p>
        </div>

        <div class="product-header">
            <h1>📊 실시간 중량 계산기</h1>
        </div>
    
        <form class="calculator-form" id="calculatorForm">
            <input type="hidden" id="categoryCode" value="<?php echo htmlspecialchars($category_code); ?>">
            <input type="hidden" id="calculationType" value="<?php echo htmlspecialchars($product['calculation_type']); ?>">

            <!-- 원산지와 재질 선택 -->
            <div class="form-row">
                <div class="form-group">
                    <label for="origin">원산지</label>
                    <select class="form-control" id="origin">
                        <?php
                        $available_origins = json_decode($product['available_origins'] ?? '[]', true) ?: [];
                        if (empty($available_origins)) {
                            // 기본값이 없으면 "선택하세요" 표시
                            echo '<option value="" selected>선택하세요</option>';
                        } else {
                            // DB의 첫 번째 원산지를 기본값으로 사용
                            foreach ($available_origins as $index => $origin):
                        ?>
                            <option value="<?php echo htmlspecialchars($origin); ?>" <?php echo $index === 0 ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($origin); ?>
                            </option>
                        <?php
                            endforeach;
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="material">재질 선택</label>
                    <select class="form-control" id="material">
                        <option value="SS400" selected>SS400</option>
                        <?php foreach ($available_materials as $material): ?>
                            <option value="<?php echo htmlspecialchars($material); ?>">
                                <?php echo htmlspecialchars($material); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($product['calculation_type'] === 'linear'): ?>
            <!-- 길이와 수량 입력 -->
            <div class="form-row">
                <div class="form-group">
                    <label for="length">길이 (미터)</label>
                    <input type="number" class="form-control" id="length"
                           min="10" max="10" step="0.01"
                           placeholder="10" value="10" required>
                    <div class="help-text">입력 가능 범위: 10.00m ~ 10.00m</div>
                </div>

                <div class="form-group">
                    <label for="quantity">수량 (본)</label>
                    <input type="number" class="form-control" id="quantity"
                           min="1" placeholder="1" value="1" required>
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