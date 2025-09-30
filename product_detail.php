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
               pp.available_sizes as parent_available_sizes,
               pp.calculation_type as parent_calculation_type,
               pp.has_calculator as parent_has_calculator,
               CASE
                   WHEN p.has_calculator = 1 THEN 1
                   WHEN pp.has_calculator = 1 THEN 1
                   ELSE 0
               END as effective_has_calculator
        FROM products p
        JOIN product_categories pc ON p.category_code = pc.category_code
        LEFT JOIN products pp ON p.parent_product_id = pp.id
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // 철근 제품인 경우 길이별 본수 데이터 조회
    $length_pieces_data = [];
    if ($product && strpos($product['product_name'], '철근') === 0) {
        // 제품명에서 규격 추출 (예: "철근 D10" → "D10")
        $spec_name = trim(str_replace('철근', '', $product['product_name']));

        // 길이별 본수 데이터 조회 (NULL 값도 포함)
        $stmt = $pdo->prepare("SELECT length, IFNULL(pieces_per_length, 0) as pieces_per_length FROM rebar_length_data WHERE spec_name = ? ORDER BY length");
        $stmt->execute([$spec_name]);
        $length_pieces_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    if (!$product) {
        header('Location: products.php');
        exit;
    }

    // 계산기 데이터 준비 - effective_has_calculator 사용
    if ($product['effective_has_calculator']) {
        // 부모 제품의 데이터가 있으면 사용, 없으면 현재 제품의 데이터 사용
        $unit_weight_data = $product['parent_unit_weight_data'] ?? $product['unit_weight_data'];

        // 경량H형강의 경우 현재 제품의 재질 정보를 우선 사용
        if ($product['category_code'] === 'light-h-beam' && !empty($product['available_materials'])) {
            $available_materials = $product['available_materials'];
        } else {
            $available_materials = $product['parent_available_materials'] ?? $product['available_materials'];
        }

        $calculation_type = $product['parent_calculation_type'] ?? $product['calculation_type'];

        // JSON 파싱 - null 체크 추가
        $unit_weight_data = !empty($unit_weight_data) ? json_decode($unit_weight_data, true) : [];
        $available_materials = !empty($available_materials) ? json_decode($available_materials, true) : [];

        // 계산기 표시를 위해 has_calculator를 effective 값으로 설정
        $product['has_calculator'] = $product['effective_has_calculator'];
    }
    
    $pageTitle = $product['product_name'] . ' | 충남스틸';
    $additionalCSS = [];
    require_once 'head.php';
    ?>
    
    <style>
    /* Product Detail Page Styles - Clean Modern Design */
    .product-header-section {
        background: #FFFFFF;
        padding: 40px 0;
        text-align: center;
        position: relative;
        border-bottom: 1px solid #E5E5E5;
    }
    
    .product-header-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .product-category {
        color: #666;
        font-size: 14px;
        margin-bottom: 8px;
        font-weight: 400;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .product-title {
        font-size: 32px;
        font-weight: 700;
        color: #222;
        margin-bottom: 16px;
        line-height: 1.2;
    }
    
    .product-subtitle {
        font-size: 16px;
        color: #666;
        margin-bottom: 20px;
        line-height: 1.5;
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
        background: #FFFFFF;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
        font-weight: 400;
        color: #666;
        margin-bottom: 8px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .detail-value {
        font-size: 18px;
        color: #222;
        font-weight: 500;
    }
    
    .unit-weight-highlight {
        color: #1428A0;
        font-weight: 700;
        font-size: 28px;
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
    .origin-badges, .material-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .origin-badge {
        padding: 4px 12px;
        background: #e8f0ff;
        border-radius: 16px;
        font-size: 14px;
        color: #1428A0;
        border: 1px solid #c3d4f7;
    }

    .material-badge {
        padding: 4px 12px;
        background: #e8f8e8;
        border-radius: 16px;
        font-size: 14px;
        color: #2e7d2e;
        border: 1px solid #b8e6b8;
    }

    .unit-weight-display {
        margin-top: 5px;
        font-size: 13px;
        color: #1428A0;
        font-weight: 500;
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
        background: #F8F9FA;
        border-radius: 8px;
        padding: 30px;
        margin-top: 30px;
        border: 1px solid #E5E5E5;
    }
    
    .calculator-title {
        font-size: 18px;
        font-weight: 600;
        color: #222;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .calculator-icon {
        width: 24px;
        height: 24px;
        background: linear-gradient(135deg, #667EEA 0%, #764BA2 100%);
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
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
        font-weight: 500;
        color: #666;
        margin-bottom: 8px;
    }
    
    .calc-control {
        padding: 12px 16px;
        border: 1px solid #DDD;
        border-radius: 6px;
        font-size: 15px;
        transition: all 0.2s;
        background: #FFFFFF;
    }
    
    .calc-control:focus {
        outline: none;
        border-color: #1428A0;
        box-shadow: 0 0 0 3px rgba(20, 40, 160, 0.1);
    }
    
    .calc-result {
        background: #FFFFFF;
        border-radius: 8px;
        padding: 24px;
        margin-top: 24px;
        border: 1px solid #E5E5E5;
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
        font-size: 36px;
        font-weight: 700;
        color: #1428A0;
        margin-bottom: 20px;
        display: flex;
        align-items: baseline;
        gap: 8px;
    }

    .calc-result-unit {
        font-size: 20px;
        font-weight: 400;
        color: #666;
    }
    
    .calc-result-price {
        font-size: 22px;
        font-weight: 600;
        color: #28a745;
        margin: 20px 0;
        padding: 16px;
        background: #E8F5E9;
        border-radius: 6px;
        text-align: center;
        border: 1px solid #C8E6C9;
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

    /* 길이 제한 관련 스타일 */
    .length-error-message {
        color: #dc3545;
        font-size: 13px;
        margin-top: 5px;
        display: none;
    }

    .length-error-message.show {
        display: block;
        animation: shake 0.3s;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
        20%, 40%, 60%, 80% { transform: translateX(2px); }
    }

    .calc-control.error {
        border-color: #dc3545;
        background-color: #fff5f5;
    }

    .length-hint {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
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

    /* 철근 계산기도 일반 제품과 동일한 스타일 사용 */

    </style>
    
    <!-- Product Header Section -->
    <section class="product-header-section">
        <div class="product-header-content">
            <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
            <h1 class="product-title"><?php
                $display_name = $product['product_name'];
                // I형강은 제품명만 표시 (규격은 별도 표시)
                if ($product['category_code'] !== 'i-beam') {
                    $display_spec = $product['specification'] ?: $product['specifications'];
                    if ($display_spec) {
                        $display_name .= ' ' . $display_spec;
                    }
                }
                echo htmlspecialchars($display_name);
            ?></h1>
            <?php if ($product['category_code'] === 'i-beam' && !empty($product['specifications'])): ?>
            <div class="product-spec-badge" style="margin-top: 10px; font-size: 24px; color: #666;">
                규격: <?php echo htmlspecialchars($product['specifications']); ?>
            </div>
            <?php endif; ?>
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
                    <?php if ($product['specification'] || $product['specifications']): ?>
                    <div class="detail-item">
                        <div class="detail-label">규격</div>
                        <div class="detail-value"><?php echo htmlspecialchars($product['specification'] ?: $product['specifications']); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($product['specification_weight']): ?>
                    <div class="detail-item">
                        <div class="detail-label">단위중량</div>
                        <div class="detail-value unit-weight-highlight">
                            <?php echo number_format($product['specification_weight'], 3); ?> kg/<?php echo $product['calculation_type'] === 'sheet' ? '장' : 'm'; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($product['price'] && $product['price'] > 0): ?>
                    <?php
                    $min_price = isset($product['min_price']) && $product['min_price'] > 0
                        ? $product['min_price']
                        : $product['price'] * 0.90;
                    $max_price = isset($product['max_price']) && $product['max_price'] > 0
                        ? $product['max_price']
                        : $product['price'] * 1.10;
                    ?>
                    <div class="detail-item">
                        <div class="detail-label">가격 범위</div>
                        <div class="detail-value">
                            <span style="color: #2196F3; margin-right: 15px;">최저: <?php echo number_format($min_price); ?>원</span>
                            <span style="color: #FF5722;">최대: <?php echo number_format($max_price); ?>원</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    
                    
                    <?php if ($product['has_calculator']): ?>
                    <!-- 실시간 중량 계산기 섹션 -->
                    <?php
                    // 철근 제품 여부 확인 (category_code 또는 제품명으로)
                    $is_rebar_product = ($product['category_code'] === 'rebar' ||
                                         preg_match('/(철근|D\d+)/u', $product['product_name']));
                    if ($is_rebar_product):
                    ?>
                    <!-- 철근 계산기 (일반 제품과 동일한 스타일) -->
                    <div class="calculator-section">
                        <h3 class="calculator-title">
                            <span class="calculator-icon">📊</span>
                            실시간 중량 계산기
                        </h3>
                        <div class="inline-calculator">
                            <div class="calc-form-row">
                                <div class="calc-form-group">
                                    <label>길이 선택</label>
                                    <select id="calc-rebar-length" class="calc-control">
                                        <option value="">길이를 선택하세요</option>
                                        <?php
                                        // 6.0m부터 12.0m까지 0.1m 단위로 생성
                                        for ($length = 6.0; $length <= 12.0; $length += 0.1):
                                            $displayLength = number_format($length, 1);
                                        ?>
                                        <option value="<?php echo $displayLength; ?>" <?php echo $length == 6.0 ? 'selected' : ''; ?>>
                                            <?php echo $displayLength; ?>m
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div class="calc-form-group">
                                    <label>재질 선택</label>
                                    <select id="calc-rebar-material" class="calc-control">
                                        <?php
                                        // 데이터베이스의 available_materials 사용
                                        $rebar_materials = [];
                                        if (!empty($product['available_materials'])) {
                                            $rebar_materials = json_decode($product['available_materials'], true) ?: [];
                                        }

                                        // 기본값이 없으면 하드코딩된 값 사용
                                        if (empty($rebar_materials)) {
                                            $rebar_materials = ["SD400", "SD300", "SD400W", "SD400S", "SD500", "SD500W", "SD500S", "SD600", "SD600S"];
                                        }

                                        foreach ($rebar_materials as $index => $material):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($material); ?>"
                                                <?php echo ($index === 0) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($material); ?>
                                            <?php if ($material === 'SD400'): ?> (표준)<?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="calc-form-group">
                                    <label>원산지</label>
                                    <select id="calc-rebar-origin" class="calc-control">
                                        <?php
                                        // 데이터베이스의 available_origins 사용
                                        $rebar_origins = [];
                                        if (!empty($product['available_origins'])) {
                                            $rebar_origins = json_decode($product['available_origins'], true) ?: [];
                                        }

                                        // 기본값이 없으면 하드코딩된 값 사용
                                        if (empty($rebar_origins)) {
                                            $rebar_origins = ["국산", "수입산"];
                                        }

                                        foreach ($rebar_origins as $index => $origin):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($origin); ?>"
                                                <?php echo ($index === 0) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($origin); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="calc-form-group">
                                    <label>수량</label>
                                    <input type="number" id="calc-rebar-quantity" class="calc-control"
                                           min="1" placeholder="예: 1" value="1">
                                </div>
                            </div>


                            <!-- 실시간 계산 결과 -->
                            <div class="calc-result" id="rebarCalcResult" style="display: none;">
                                <div class="calc-result-header">계산 결과</div>
                                <div class="calc-result-value">
                                    <span id="rebarCalcResultValue">0</span>
                                    <span class="calc-result-unit">kg</span>
                                </div>
                                <div class="calc-result-price" id="rebarCalcResultPrice">견적금액: 0원</div>
                                <div class="calc-steps" id="rebarCalcSteps"></div>
                            </div>

                            <!-- 가격 문의 모달 -->
                            <div class="modal-overlay" id="rebarContactModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); z-index: 9999; justify-content: center; align-items: center;">
                                <div class="modal-content" style="background: white; border-radius: 16px; padding: 40px; max-width: 500px; width: 90%; text-align: center; animation: slideUp 0.3s ease; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);">
                                    <div style="font-size: 64px; margin-bottom: 20px;">📞</div>
                                    <h3 style="font-size: 24px; color: #333; margin-bottom: 15px;">문의 전화 주세요</h3>
                                    <p style="font-size: 16px; color: #666; margin-bottom: 30px; line-height: 1.6;">해당 제품의 가격 정보가 없습니다.<br>자세한 상담은 전화로 문의해 주세요.</p>
                                    <div style="font-size: 28px; font-weight: 700; color: #1428A0; margin: 20px 0 30px; letter-spacing: 1px;">010-9820-0495</div>
                                    <div style="display: flex; gap: 15px; justify-content: center;">
                                        <a href="tel:010-9820-0495" style="padding: 14px 32px; background: #1428A0; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; text-decoration: none; display: inline-block; flex: 1; max-width: 200px;">전화 걸기</a>
                                        <button onclick="closeRebarModal()" style="padding: 14px 32px; background: #e0e0e0; color: #666; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; flex: 1; max-width: 120px;">닫기</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- 일반 제품 계산기 -->
                    <div class="calculator-section">
                        <h3 class="calculator-title">
                            <span class="calculator-icon">📊</span>
                            실시간 중량 계산기
                        </h3>
                        <div class="inline-calculator">
                            <div class="calc-form-row">
                                <?php if ($product['category_code'] === 'i-beam'): ?>
                                <!-- I형강의 경우 규격이 고정되어 있으므로 숨김 필드로 처리 -->
                                <input type="hidden" id="calc-specification" value="<?php echo htmlspecialchars($product['specifications'] ?? ''); ?>">
                                <?php elseif ($product['category_code'] !== 'h-beam' && $product['category_code'] !== 'light-h-beam'): ?>
                                <!-- 기타 제품의 규격 선택 (H형강 및 경량H형강 제외) -->
                                <div class="calc-form-group">
                                    <label>규격 선택</label>
                                    <select id="calc-specification" class="calc-control">
                                        <option value="">규격을 선택하세요</option>
                                        <?php
                                        // available_sizes 사용 - null 체크 추가
                                        $sizes_data = $product['parent_available_sizes'] ?? $product['available_sizes'] ?? null;
                                        $available_sizes = $sizes_data ? json_decode($sizes_data, true) : [];
                                        $available_sizes = $available_sizes ?: [];
                                        foreach ($available_sizes as $size):
                                        ?>
                                        <option value="<?php echo htmlspecialchars($size); ?>">
                                            <?php echo htmlspecialchars($size); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="unit-weight-display" id="unitWeightDisplay" style="display: none;">
                                        단위중량: <span id="unitWeightValue">0</span> kg/m
                                    </div>
                                </div>
                                <?php else: ?>
                                <!-- H형강 및 경량H형강의 경우 규격이 고정되어 있으므로 숨김 필드로 처리 -->
                                <input type="hidden" id="calc-specification" value="<?php echo htmlspecialchars($product['specifications'] ?? $product['specification'] ?? ''); ?>">
                                <?php endif; ?>

                                <?php if (in_array($product['category_code'], ['h-beam', 'light-h-beam', 'i-beam']) && !empty($product['available_origins'])): ?>
                                <?php
                                $origins = json_decode($product['available_origins'], true) ?: [];
                                if (count($origins) > 0):
                                ?>
                                <div class="calc-form-group">
                                    <label>원산지 선택</label>
                                    <select id="calc-origin" class="calc-control">
                                        <?php if (count($origins) === 1): ?>
                                            <option value="<?php echo htmlspecialchars($origins[0]); ?>" selected>
                                                <?php echo htmlspecialchars($origins[0]); ?>
                                            </option>
                                        <?php else: ?>
                                            <?php foreach ($origins as $origin): ?>
                                            <option value="<?php echo htmlspecialchars($origin); ?>"
                                                    <?php echo ($origin === '국산') ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($origin); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>

                                <div class="calc-form-group">
                                    <label>재질 선택</label>
                                    <select id="calc-material" class="calc-control">
                                        <?php if (in_array($product['category_code'], ['h-beam', 'light-h-beam'])): ?>
                                            <?php
                                            // For H-beam and lightweight H-beam, show all available materials
                                            // First material in array is default
                                            ?>
                                            <?php foreach ($available_materials as $index => $material): ?>
                                            <option value="<?php echo htmlspecialchars($material); ?>"
                                                    <?php echo $index === 0 ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($material); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        <?php elseif (count($available_materials) === 1): ?>
                                            <option value="<?php echo htmlspecialchars($available_materials[0]); ?>" selected>
                                                <?php echo htmlspecialchars($available_materials[0]); ?>
                                            </option>
                                        <?php else: ?>
                                            <option value="">기본 재질</option>
                                            <?php foreach ($available_materials as $material): ?>
                                            <option value="<?php echo htmlspecialchars($material); ?>">
                                                <?php echo htmlspecialchars($material); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <?php if ($calculation_type === 'linear' || ($calculation_type === 'piece' && $product['category_code'] === 'h-beam')): ?>
                                <div class="calc-form-group">
                                    <label>길이 (미터)</label>
                                    <input type="number" id="calc-length" class="calc-control"
                                           min="<?php echo $product['min_length'] ?? 0.1; ?>"
                                           max="<?php echo $product['max_length'] ?? 100; ?>"
                                           step="0.1"
                                           placeholder="예: <?php echo $product['standard_length'] ?? 6; ?>"
                                           value="">
                                    <div class="length-error-message" id="calc-length-error"></div>
                                    <?php if ($calculation_type === 'piece' && $product['category_code'] === 'h-beam'): ?>
                                    <div class="length-hint">표준 길이: <?php echo $product['standard_length'] ?? 6; ?>m (사용자 지정 가능)</div>
                                    <?php elseif (!empty($product['min_length']) && !empty($product['max_length'])): ?>
                                    <div class="length-hint">입력 가능 범위: <?php echo $product['min_length']; ?>m ~ <?php echo $product['max_length']; ?>m</div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <div class="calc-form-group">
                                    <label>수량 (<?php
                                        if ($calculation_type === 'piece') {
                                            echo '개';
                                        } elseif ($calculation_type === 'linear') {
                                            echo '본';
                                        } else {
                                            echo '장';
                                        }
                                    ?>)</label>
                                    <input type="number" id="calc-quantity" class="calc-control"
                                           min="1" placeholder="예: 10" value="">
                                </div>
                            </div>
                            
                            <!-- 실시간 계산 결과 -->
                            <div class="calc-result" id="calcResult" style="display: none;">
                                <div class="calc-result-header">계산 결과</div>
                                <div class="calc-result-value">
                                    <span id="calcResultValue">0</span>
                                    <span class="calc-result-unit">kg</span>
                                </div>
                                <div class="calc-result-price" id="calcResultPrice">견적금액: 0원</div>
                                <div class="calc-steps" id="calcSteps"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <script>
                    // 계산기 데이터
                    const calculatorData = {
                        unitWeightData: <?php echo json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE); ?>,
                        specification: '<?php echo htmlspecialchars($product['specifications'] ?? ''); ?>',
                        calculationType: '<?php echo $calculation_type; ?>',
                        unitWeight: <?php
                        if ($product['category_code'] === 'light-h-beam' && empty($product['specification_weight'])) {
                            // 경량H형강의 경우 unitWeightData에서 해당 규격의 단위중량 가져오기
                            $spec = $product['specifications'] ?? '';
                            echo isset($unit_weight_data[$spec]) ? $unit_weight_data[$spec] : 0;
                        } else {
                            echo $product['specification_weight'] ?? 0;
                        }
                        ?>
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
                    <div class="info-value">032-564-0090</div>
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
                <?php if (!empty($product['available_origins'])): ?>
                <div class="info-item">
                    <div class="info-label">원산지</div>
                    <div class="info-value">
                        <?php
                        $origins = json_decode($product['available_origins'], true) ?: [];
                        if (count($origins) > 0) {
                            echo '<div class="origin-badges">';
                            foreach ($origins as $index => $origin) {
                                $isDefault = ($index === 0) ? ' style="font-weight: bold;"' : '';
                                echo '<span class="origin-badge"' . $isDefault . '>' . htmlspecialchars($origin) . '</span> ';
                            }
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($product['available_materials'])): ?>
                <div class="info-item">
                    <div class="info-label">재질</div>
                    <div class="info-value">
                        <?php
                        $materials = json_decode($product['available_materials'], true) ?: [];
                        if (count($materials) > 0) {
                            echo '<div class="material-badges">';
                            foreach ($materials as $index => $material) {
                                $isDefault = ($index === 0) ? ' style="font-weight: bold;"' : '';
                                echo '<span class="material-badge"' . $isDefault . '>' . htmlspecialchars($material) . '</span> ';
                            }
                            echo '</div>';
                        }
                        ?>
                    </div>
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
    <?php if ($is_rebar_product): ?>
    <!-- 철근 JavaScript -->
    <script>
    // 철근 데이터베이스에서 가져온 정보
    const rebarSpec = '<?php echo str_replace("철근 ", "", $product['product_name']); ?>';
    const unitWeight = <?php echo $product['specification_weight'] ?? 0; ?>; // kg/m

    // 원산지별 가격 데이터 (kg당 추가 비용)
    const originPriceData = <?php echo json_encode(json_decode($product['origin_price_data'] ?? '{}', true), JSON_UNESCAPED_UNICODE); ?>;

    // 길이별 본수 데이터
    const lengthPiecesData = <?php echo json_encode($length_pieces_data, JSON_UNESCAPED_UNICODE); ?>;

    // 모달 함수
    function showRebarModal() {
        const modal = document.getElementById('rebarContactModal');
        modal.style.display = 'flex';
    }

    function closeRebarModal() {
        const modal = document.getElementById('rebarContactModal');
        modal.style.display = 'none';
    }

    // 모달 오버레이 클릭 시 닫기
    document.getElementById('rebarContactModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeRebarModal();
        }
    });

    // 철근 가격/길이 데이터 확인 (비동기)
    let rebarAvailabilityCache = null;
    async function checkRebarPriceAvailability() {
        if (rebarAvailabilityCache) return rebarAvailabilityCache;

        try {
            const response = await fetch('/ajax/get_rebar_options.php?type=all');
            const data = await response.json();
            if (data.success) {
                rebarAvailabilityCache = data.data;
                return data.data;
            }
        } catch (error) {
            console.error('Error fetching rebar options:', error);
        }
        return null;
    }

    // 철근 중량 실시간 계산
    async function calculateRebarWeight() {
        const length = parseFloat(document.getElementById('calc-rebar-length').value) || 0;
        const material = document.getElementById('calc-rebar-material').value;
        const origin = document.getElementById('calc-rebar-origin').value;
        const quantity = parseInt(document.getElementById('calc-rebar-quantity').value) || 0;

        // 입력값 검증
        if (length <= 0 || quantity <= 0) {
            document.getElementById('rebarCalcResult').style.display = 'none';
            return;
        }

        // 가격 정보 확인 (비동기)
        const availability = await checkRebarPriceAvailability();
        if (availability) {
            const hasPrice = availability.specs.includes(rebarSpec);
            const lengthsForSpec = availability.lengths[rebarSpec] || [];
            const hasLength = lengthsForSpec.some(l =>
                Math.abs(parseFloat(l.length) - length) < 0.01
            );

            // 가격 정보나 길이 정보가 없으면 모달 표시
            if (!hasPrice || !hasLength) {
                document.getElementById('rebarCalcResult').style.display = 'none';
                showRebarModal();
                return;
            }
        }

        // 해당 길이의 본수 가져오기
        const lengthKey = length.toFixed(1); // 소수점 1자리로 키 생성
        const piecesPerLength = lengthPiecesData[lengthKey];

        // 본수 데이터가 없거나 0이거나 null인 경우 (엑셀 빈칸 처리)
        if (piecesPerLength === undefined || piecesPerLength === null || piecesPerLength === 0 || piecesPerLength === '') {
            console.log(`D32 길이 ${lengthKey}m: 본수 데이터 없음 - 모달 표시`);
            document.getElementById('rebarCalcResult').style.display = 'none';
            showRebarModal();
            return;
        }

        // 실제 본수 계산 (수량 × 길이당 본수)
        const actualPieces = quantity * piecesPerLength;

        // 중량 계산
        const weightPerPiece = unitWeight * length; // 1본 중량
        const totalWeight = weightPerPiece * actualPieces; // 총 중량

        // 계산 과정 생성
        let calculationSteps = [];
        calculationSteps.push(`선택 길이: ${length}m (길이당 ${piecesPerLength}본)`);
        calculationSteps.push(`단위중량: ${unitWeight.toFixed(3)} kg/m`);
        calculationSteps.push(`1본 중량: ${unitWeight.toFixed(3)} × ${length}m = ${weightPerPiece.toFixed(2)} kg`);
        calculationSteps.push(`총 본수: ${quantity}개 × ${piecesPerLength}본/개 = ${actualPieces.toLocaleString()}본`);
        calculationSteps.push(`총 중량: ${weightPerPiece.toFixed(2)} × ${actualPieces.toLocaleString()}본 = ${totalWeight.toFixed(1)} kg`);

        // 견적금액 계산 (kg당 1,000원 기준)
        const pricePerKg = 1000;
        const basePrice = totalWeight * pricePerKg;

        // 재질별 추가 단가
        const materialPrices = {
            'SD300': 30,
            'SD400': 0,
            'SD400W': 50,
            'SD400S': 50,
            'SD500': 40,
            'SD500W': 90,
            'SD500S': 90,
            'SD600': 80,
            'SD600S': 130
        };

        const materialPrice = materialPrices[material] || 0;

        // 원산지별 추가 단가 (데이터베이스에서 가져온 값 사용)
        const originPrice = parseFloat(originPriceData[origin]) || 0;

        let totalPrice = basePrice;

        if (materialPrice > 0) {
            const materialCost = totalWeight * materialPrice;
            totalPrice += materialCost;
            calculationSteps.push(`재질(${material}) 추가비용: ${totalWeight.toFixed(1)}kg × ${materialPrice}원/kg = ${materialCost.toLocaleString()}원`);
        }

        if (originPrice !== 0) {
            const originCost = totalWeight * originPrice;
            totalPrice += originCost;
            calculationSteps.push(`원산지(${origin}) ${originPrice > 0 ? '추가비용' : '할인'}: ${totalWeight.toFixed(1)}kg × ${Math.abs(originPrice)}원/kg = ${Math.abs(originCost).toLocaleString()}원`);
        }

        calculationSteps.push(`견적금액: ${totalPrice.toLocaleString()}원`);

        // 결과 표시
        document.getElementById('rebarCalcResultValue').textContent = totalWeight.toFixed(1);
        document.getElementById('rebarCalcResultPrice').textContent = '견적금액: ' + totalPrice.toLocaleString() + '원';

        // 계산 과정 표시
        const stepsHtml = calculationSteps.map(step =>
            `<div class="calc-step">${step}</div>`
        ).join('');
        document.getElementById('rebarCalcSteps').innerHTML = stepsHtml;

        // 결과 영역 표시
        document.getElementById('rebarCalcResult').style.display = 'block';
    }

    // 디바운스 함수
    let rebarCalculateTimer;
    function debouncedRebarCalculate() {
        clearTimeout(rebarCalculateTimer);
        rebarCalculateTimer = setTimeout(calculateRebarWeight, 300);
    }

    // 이벤트 리스너 등록
    document.addEventListener('DOMContentLoaded', function() {
        // 입력 필드 변경 시 자동 계산
        document.getElementById('calc-rebar-length').addEventListener('change', debouncedRebarCalculate);
        document.getElementById('calc-rebar-material').addEventListener('change', debouncedRebarCalculate);
        document.getElementById('calc-rebar-origin').addEventListener('change', debouncedRebarCalculate);
        document.getElementById('calc-rebar-quantity').addEventListener('input', debouncedRebarCalculate);
    });
    </script>

    <?php else: ?>
    <!-- 일반 제품 JavaScript -->
    <script>
    // 원산지 및 재질별 가격 데이터 추가
    const materialPriceData = <?php echo json_encode(json_decode($product['material_price_data'] ?? '{}', true), JSON_UNESCAPED_UNICODE); ?>;
    const originPriceData = <?php echo json_encode(json_decode($product['origin_price_data'] ?? '{}', true), JSON_UNESCAPED_UNICODE); ?>;
    const basePrice = <?php echo floatval($product['price'] ?? 1000); ?>; // 기준 단가 (원/kg)

    // 길이 제한값
    const minLength = <?php echo floatval($product['min_length'] ?? 0.1); ?>;
    const maxLength = <?php echo floatval($product['max_length'] ?? 100); ?>;

    // 길이 검증 함수
    function validateLength(value) {
        const lengthInput = document.getElementById('calc-length');
        const errorDiv = document.getElementById('calc-length-error');

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

    // 실시간 계산 기능
    function calculateWeight() {
        // H형강의 경우 specification이 hidden input에 있음
        const categoryCode = '<?php echo $product['category_code'] ?? ''; ?>';
        let specification = document.getElementById('calc-specification')?.value || '';
        if (categoryCode === 'h-beam' && !specification) {
            specification = '<?php echo $product['specification'] ?? ''; ?>';
        }
        const origin = document.getElementById('calc-origin')?.value || '';
        const material = document.getElementById('calc-material').value;
        const quantity = parseInt(document.getElementById('calc-quantity').value) || 0;
        const lengthInput = document.getElementById('calc-length');
        const length = lengthInput ? (parseFloat(lengthInput.value) || 0) : 0;
        
        // 입력값 검증
        if (quantity <= 0) {
            document.getElementById('calcResult').style.display = 'none';
            return;
        }

        if (calculatorData.calculationType === 'linear' || (calculatorData.calculationType === 'piece' && (categoryCode === 'h-beam' || categoryCode === 'light-h-beam'))) {
            if (length <= 0) {
                document.getElementById('calcResult').style.display = 'none';
                return;
            }

            // 길이 제한 검증
            if (!validateLength(length)) {
                document.getElementById('calcResult').style.display = 'none';
                return;
            }
        }
        
        // I형강과 H형강, 경량H형강이 아닌 경우에만 규격 체크
        if (categoryCode !== 'h-beam' && categoryCode !== 'i-beam' && categoryCode !== 'light-h-beam') {
            // 규격이 선택되지 않았으면 리턴
            if (!specification) {
                document.getElementById('calcResult').style.display = 'none';
                return;
            }
        }

        // 단위 중량 가져오기
        let unitWeight = 0;

        // H형강과 I형강, 경량H형강의 경우 specification_weight 사용
        if ((categoryCode === 'h-beam' || categoryCode === 'i-beam' || categoryCode === 'light-h-beam') && calculatorData.unitWeight > 0) {
            unitWeight = calculatorData.unitWeight;
        } else if (calculatorData.unitWeightData && specification) {
            // 일반 제품: 규격별 단중 데이터에서 가져오기
            unitWeight = calculatorData.unitWeightData[specification];

            // 만약 재질별로 구분되어 있다면
            if (typeof unitWeight === 'object' && material && unitWeight[material]) {
                unitWeight = unitWeight[material];
            } else if (typeof unitWeight === 'object') {
                // 첫 번째 재질의 단위중량 사용
                unitWeight = Object.values(unitWeight)[0] || 0;
            }
        }

        if (unitWeight <= 0) {
            document.getElementById('calcResult').style.display = 'none';
            return;
        }
        
        // 중량 계산
        let calculatedWeight = 0;
        let calculationSteps = [];

        if (calculatorData.calculationType === 'piece' && (categoryCode === 'h-beam' || categoryCode === 'light-h-beam')) {
            // H형강 및 경량H형강: 낱개 계산 (단위중량 × 길이 × 수량)
            const inputLength = parseFloat(document.getElementById('calc-length')?.value || 0);

            // 길이가 입력되지 않으면 계산하지 않음
            if (inputLength <= 0) {
                document.getElementById('calcResult').style.display = 'none';
                return;
            }

            const weightPerPiece = unitWeight * inputLength;
            calculatedWeight = weightPerPiece * quantity;

            calculationSteps = [
                `규격: ${calculatorData.specification || ''}`,
                `단위중량: ${unitWeight} kg/m`,
                `길이: ${inputLength}m`,
                `1개 중량: ${unitWeight} kg/m × ${inputLength}m = ${weightPerPiece.toFixed(1)} kg`,
                `총 중량: ${weightPerPiece.toFixed(1)} kg/개 × ${quantity}개 = ${calculatedWeight.toFixed(1)} kg`
            ];
        } else if (calculatorData.calculationType === 'linear') {
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
        
        // 가격 계산
        let pricePerKg = basePrice || 1000; // 기준 단가

        // 원산지별 추가 비용 적용
        if (origin && originPriceData[origin]) {
            const originAdditional = parseFloat(originPriceData[origin]) || 0;
            if (originAdditional !== 0) {
                pricePerKg += originAdditional;
                calculationSteps.push(`원산지(${origin}) 추가 비용: ${originAdditional > 0 ? '+' : ''}${originAdditional}원/kg`);
            }
        }

        // 재질별 추가 비용 적용
        if (material && materialPriceData[material]) {
            const materialAdditional = parseFloat(materialPriceData[material]) || 0;
            if (materialAdditional !== 0) {
                pricePerKg += materialAdditional;
                calculationSteps.push(`재질(${material}) 추가 비용: ${materialAdditional > 0 ? '+' : ''}${materialAdditional}원/kg`);
            }
        }

        const totalPrice = calculatedWeight * pricePerKg;

        // 계산 과정에 견적금액 추가
        calculationSteps.push(`최종 단가: ${pricePerKg.toLocaleString()}원/kg`);
        calculationSteps.push(`견적금액: ${calculatedWeight.toFixed(1)}kg × ${pricePerKg.toLocaleString()}원/kg = ${totalPrice.toLocaleString()}원`);
        
        // 결과 표시
        document.getElementById('calcResultValue').textContent = calculatedWeight.toFixed(1);
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
        // 규격 선택 이벤트 리스너 (H형강이 아닌 경우에만)
        const specificationInput = document.getElementById('calc-specification');
        if (specificationInput && specificationInput.tagName === 'SELECT') {
            specificationInput.addEventListener('change', function() {
                const selectedSpec = this.value;
                if (selectedSpec && calculatorData.unitWeightData[selectedSpec]) {
                    let unitWeight = calculatorData.unitWeightData[selectedSpec];
                    if (typeof unitWeight === 'object') {
                        unitWeight = Object.values(unitWeight)[0];
                    }
                    document.getElementById('unitWeightValue').textContent = unitWeight;
                    document.getElementById('unitWeightDisplay').style.display = 'block';
                } else {
                    document.getElementById('unitWeightDisplay').style.display = 'none';
                }
                debouncedCalculate();
            });
        }

        // 입력 필드에 이벤트 리스너 추가
        const originInput = document.getElementById('calc-origin');
        if (originInput) {
            originInput.addEventListener('change', debouncedCalculate);
        }

        document.getElementById('calc-material').addEventListener('change', debouncedCalculate);
        document.getElementById('calc-quantity').addEventListener('input', debouncedCalculate);

        const lengthInput = document.getElementById('calc-length');
        if (lengthInput) {
            lengthInput.addEventListener('input', function() {
                validateLength(this.value);
                debouncedCalculate();
            });

            // blur 이벤트로 최종 검증
            lengthInput.addEventListener('blur', function() {
                validateLength(this.value);
            });
        }
    });
    </script>
    <?php endif; ?>  <!-- 철근/일반 제품 구분 endif -->
    <?php endif; ?>  <!-- has_calculator endif -->

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
    
    /* Modal styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }

    .modal-overlay.show {
        display: flex;
        animation: fadeIn 0.3s ease;
    }

    .modal-content {
        background: white;
        border-radius: 16px;
        padding: 40px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        animation: slideUp 0.3s ease;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
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

    .modal-content h3 {
        font-size: 24px;
        color: #333;
        margin-bottom: 15px;
    }

    .modal-content p {
        font-size: 16px;
        color: #666;
        margin-bottom: 30px;
        line-height: 1.6;
    }

    .modal-phone {
        font-size: 28px;
        font-weight: 700;
        color: #1428A0;
        margin: 20px 0 30px;
        letter-spacing: 1px;
    }

    .modal-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
    }

    .btn-call, .btn-close {
        padding: 14px 32px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-call {
        background: #1428A0;
        color: white;
        flex: 1;
        max-width: 200px;
        text-decoration: none;
        display: inline-block;
    }

    .btn-call:hover {
        background: #0F1F7A;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
    }

    .btn-close {
        background: #e0e0e0;
        color: #666;
        flex: 1;
        max-width: 120px;
    }

    .btn-close:hover {
        background: #d0d0d0;
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

        .modal-content {
            padding: 30px 20px;
        }

        .modal-phone {
            font-size: 24px;
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

    <!-- Modal for contact -->
    <div class="modal-overlay" id="contactModal">
        <div class="modal-content">
            <div class="modal-icon">📞</div>
            <h3>문의 전화 주세요</h3>
            <p>해당 제품의 가격 정보가 없습니다.<br>자세한 상담은 전화로 문의해 주세요.</p>
            <div class="modal-phone">010-9820-0495</div>
            <div class="modal-buttons">
                <a href="tel:010-9820-0495" class="btn-call">전화 걸기</a>
                <button class="btn-close" onclick="closeModal()">닫기</button>
            </div>
        </div>
    </div>

    <script>
    // 단위중량 데이터
    const unitWeightData = <?php echo json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE); ?>;
    const calculationType = '<?php echo $product['calculation_type']; ?>';
    const categoryCode = '<?php echo $product['category_code']; ?>';
    const productName = '<?php echo $product['product_name']; ?>';

    // Modal functions
    function showModal() {
        document.getElementById('contactModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('contactModal').classList.remove('show');
    }

    // Close modal on overlay click
    document.getElementById('contactModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // 철근 제품 가격/길이 확인 함수
    async function checkRebarAvailability(specName, length) {
        try {
            const response = await fetch('/ajax/get_rebar_options.php?type=all');
            const data = await response.json();

            if (!data.success) return { hasPrice: false, hasLength: false };

            // 스펙에 가격 정보가 있는지 확인
            const hasPrice = data.data.specs.includes(specName);

            // 해당 스펙의 길이 데이터가 존재하는지 확인
            const lengthsForSpec = data.data.lengths[specName] || [];
            const hasLength = lengthsForSpec.some(l =>
                Math.abs(parseFloat(l.length) - parseFloat(length)) < 0.01
            );

            return { hasPrice, hasLength };
        } catch (error) {
            console.error('Error checking rebar availability:', error);
            return { hasPrice: false, hasLength: false };
        }
    }

    // 철근 가격 계산 함수
    async function calculateRebarPrice(specName, length, quantity, origin, material) {
        const response = await fetch('/ajax/calculate_rebar_price.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                spec_name: specName,
                length: length,
                quantity: quantity,
                origin: origin || '포항',
                material: material || 'SD400'
            })
        });
        return await response.json();
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

        const btn = document.getElementById('calculateBtn');
        btn.disabled = true;
        btn.textContent = '계산 중...';

        try {
            const specification = document.getElementById('specification').value;
            const material = document.getElementById('material').value;
            const length = parseFloat(document.getElementById('length')?.value || 0);
            const quantity = parseInt(document.getElementById('quantity').value);

            // 철근 제품인 경우 별도 처리
            if (categoryCode === 'rebar') {
                // 제품명에서 규격 추출 (예: "철근 D35" -> "D35")
                const specMatch = productName.match(/D\d+/);
                const rebarSpec = specMatch ? specMatch[0] : specification;

                // 철근 가격/길이 데이터 확인
                const availability = await checkRebarAvailability(rebarSpec, length);

                if (!availability.hasPrice || !availability.hasLength) {
                    // 가격 정보나 길이 정보가 없으면 모달 표시
                    showModal();
                    btn.disabled = false;
                    btn.textContent = '중량 계산하기';
                    return;
                }

                // 철근 가격 계산
                const result = await calculateRebarPrice(rebarSpec, length, quantity, '포항', material || 'SD400');

                if (result.success) {
                    const data = result.data;
                    document.getElementById('resultValue').textContent =
                        data.total_price.toLocaleString() + ' 원 (' + data.total_weight + ' kg)';

                    const stepsHtml = [
                        `규격: ${data.spec_name}`,
                        `길이: ${data.length}m × 수량: ${data.quantity}번들`,
                        `번들당 중량: ${data.weight_per_bundle}kg`,
                        `총 중량: ${data.total_weight}kg`,
                        `단가: ${data.unit_price.toLocaleString()}원/톤`,
                        `총 가격: ${data.total_price.toLocaleString()}원`
                    ].map(step => `<div class="step">${step}</div>`).join('');

                    document.getElementById('calculationSteps').innerHTML = stepsHtml;
                    document.getElementById('calculationResult').classList.add('show');
                    document.getElementById('calculationResult').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                } else {
                    // 에러 메시지에 "가격 정보가 없습니다" 또는 "전화 문의" 포함 시 모달 표시
                    if (result.error.includes('가격 정보') || result.error.includes('전화 문의') || result.error.includes('010-9820-0495')) {
                        showModal();
                    } else {
                        alert('계산 중 오류가 발생했습니다: ' + result.error);
                    }
                }
            } else {
                // 일반 제품 계산 (기존 로직)
                const formData = {
                    category: categoryCode,
                    specification: specification,
                    material: material,
                    length: length,
                    quantity: quantity
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