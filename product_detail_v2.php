<?php
/**
 * 제품 상세페이지 - 신규 견적요청(quote) 방식
 *
 * product_detail.php(얇은 라우터)가 모드='quote' 이고 ?id=N 일 때 require 하는 화면이다.
 * 단독 실행용 파일이 아니다.
 *
 * 기존 product_detail_calc.php 의 ?id= 분기와 화면 구조(헤더/이미지/상세정보)는 동일하게 유지하고,
 * 실시간 계산기 영역만 '견적요청 폼'으로 교체한다.
 *
 * 설계 원칙 (dev_docs/PRD_product_quote_v2.md 8.1)
 *  - 금액은 어떤 형태로도 표시하지 않는다. 금액 산출은 기존 calc 모드의 역할이다.
 *  - has_calculator 여부와 무관하게 모든 제품에서 견적요청이 가능하다.
 *  - 담기 요청은 /ajax/cart_add.php 로 보내며, 제품명·규격은 서버에서 다시 채운다.
 *  - 담기 스크립트는 tail.php 이후에 출력하고, CSRF 토큰은 meta 태그에서 직접 읽어 헤더로 넣는다.
 *    (tail.php 의 fetch 인터셉터가 페이지 최하단에서 등록되기 때문)
 */

// 라우터가 이미 db.php 를 로드했지만, 안전을 위해 $pdo 가 없으면 직접 로드한다.
if (!isset($pdo) || !($pdo instanceof PDO)) {
    require_once __DIR__ . '/db.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = 'products';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: products.php');
    exit;
}

// 관리자 권한 체크 (기존 화면과 동일한 기준)
$is_admin = isset($_SESSION['admin_id']) && $_SESSION['admin_id'];

// 조회수 증가
$stmt = $pdo->prepare("UPDATE products SET view_count = COALESCE(view_count, 0) + 1 WHERE id = ?");
$stmt->execute([$product_id]);

// 제품 정보 조회 (부모 제품의 재질/계산타입 상속 정보 포함)
$stmt = $pdo->prepare("
    SELECT p.*, pc.category_name,
           pp.available_materials AS parent_available_materials,
           pp.available_origins   AS parent_available_origins,
           pp.calculation_type    AS parent_calculation_type
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

/* ------------------------------------------------------------------
 * 견적요청 폼에 쓸 선택지 준비
 * ------------------------------------------------------------------ */

// 원산지 목록: 자식 제품이 자체 목록을 가지면 그것을 쓰고, 비어 있으면 부모 목록을 상속한다.
// (ajax/cart_add.php 의 서버측 검증과 같은 우선순위를 유지해야 담기가 거부되지 않는다)
$qr_origins = json_decode($product['available_origins'] ?? '[]', true);
if (!is_array($qr_origins)) {
    $qr_origins = [];
}
if (empty($qr_origins) && !empty($product['parent_available_origins'])) {
    $qr_parent_origins = json_decode($product['parent_available_origins'], true);
    if (is_array($qr_parent_origins)) {
        $qr_origins = $qr_parent_origins;
    }
}

// 재질 목록: 부모 제품이 있으면 부모 재질을 상속한다.
// 단 경량H형강(light-h-beam) 자식 제품은 자체 재질을 우선 사용한다.
$qr_materials_json = $product['parent_available_materials'] ?? $product['available_materials'];
if ($product['category_code'] === 'light-h-beam' && !empty($product['available_materials'])) {
    $qr_materials_json = $product['available_materials'];
}
$qr_materials = json_decode($qr_materials_json ?? '[]', true);
if (!is_array($qr_materials)) {
    $qr_materials = [];
}

// 재질 기본 선택 여부: 경량H형강·I형강은 기본값 없이 '선택하세요'
$qr_material_no_default = in_array($product['category_code'], ['light-h-beam', 'i-beam'], true);

// 계산 타입(수량 단위 기본값 판단용)
// 계산 타입(길이 UI 분기 및 수량 단위 판정의 기준. 부모 제품이 있으면 부모 값을 상속한다)
$qr_calculation_type = $product['parent_calculation_type'] ?? $product['calculation_type'];

// 길이 목록
$qr_lengths = [];          // 드롭다운으로 노출할 길이 목록
$qr_length_free_input = false;  // true 면 숫자 입력으로 폴백
$qr_standard_length = !empty($product['standard_length']) ? floatval($product['standard_length']) : 0;
$qr_length_help = '';

if ($product['category_code'] === 'rebar') {
    // 철근: rebar_length_data 의 길이 목록 사용 (기존 계산기와 동일 기준)
    $spec_name = str_replace('철근 ', '', $product['product_name']);
    try {
        $stmt = $pdo->prepare("
            SELECT length
            FROM rebar_length_data
            WHERE spec_name = ? AND length BETWEEN 6 AND 12
            ORDER BY length
        ");
        $stmt->execute([$spec_name]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $qr_lengths[] = floatval($row['length']);
        }
    } catch (PDOException $e) {
        error_log('product_detail_v2 철근 길이 조회 실패: ' . $e->getMessage());
        $qr_lengths = [];
    }
    if (!empty($qr_lengths)) {
        $qr_length_help = '선택 가능 범위: '
            . number_format(min($qr_lengths), 1) . 'm ~ '
            . number_format(max($qr_lengths), 1) . 'm';
    }
}

if (empty($qr_lengths)) {
    $min_len = !empty($product['min_length']) ? floatval($product['min_length']) : 0;
    $max_len = !empty($product['max_length']) ? floatval($product['max_length']) : 0;

    if ($min_len > 0 && $max_len >= $min_len) {
        // 최소~최대 길이를 0.1m 단위로 생성
        for ($i = (int)round($min_len * 10); $i <= (int)round($max_len * 10); $i++) {
            $qr_lengths[] = $i / 10;
        }
        $qr_length_help = '선택 가능 범위: ' . number_format($min_len, 1) . 'm ~ '
            . number_format($max_len, 1) . 'm (0.1m 단위)';
    } else {
        // 범위 정보가 없으면 직접 입력으로 폴백
        $qr_length_free_input = true;
        $qr_length_help = '길이를 직접 입력하세요 (미터 단위)';
    }
}

// 수량 단위: 기존 자동계산 화면이 제품마다 보여주던 단위를 그대로 사용한다.
// (판정 규칙은 includes/product_unit.php 에 원본 근거와 함께 정리되어 있다)
require_once __DIR__ . '/includes/product_unit.php';
$qr_unit = productQuantityUnit($product);

$pageTitle = $product['product_name'] . ' | 충남스틸';
$additionalCSS = [];
require_once __DIR__ . '/head.php';
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

/* 견적요청 폼 (기존 계산기 영역과 동일한 외형을 유지한다) */
.calculator-section {
    background: var(--bg-light);
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
}

.calculator-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 6px;
}

.calculator-desc {
    font-size: 13px;
    color: #777;
    margin-bottom: 18px;
    line-height: 1.5;
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
    width: 100%;
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

/* 길이/수량 + 단위를 한 칸 안에 나란히 배치 */
.calc-unit-fixed {
    display: inline-flex;
    align-items: center;
    padding: 0 12px;
    color: #666;
    font-size: 14px;
    white-space: nowrap;
}
.calc-input-pair {
    display: grid;
    grid-template-columns: 1fr 90px;
    gap: 8px;
}

.qr-note-group {
    margin-bottom: 15px;
}

.qr-note-group textarea.calc-control {
    resize: vertical;
    min-height: 72px;
    font-family: inherit;
}

.qr-submit-btn {
    width: 100%;
    padding: 15px 20px;
    background: var(--primary-blue);
    color: var(--white);
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.qr-submit-btn:hover:not(:disabled) {
    background: var(--secondary-blue);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
}

.qr-submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.qr-message {
    margin-top: 15px;
    padding: 15px;
    border-radius: 8px;
    font-size: 14px;
    line-height: 1.6;
    display: none;
}

.qr-message.is-visible {
    display: block;
}

.qr-message.is-success {
    background: #EAF4EA;
    border: 1px solid #BEDDBE;
    color: #256B29;
}

.qr-message.is-error {
    background: #FDECEA;
    border: 1px solid #F5C2BD;
    color: #A32218;
}

.qr-message-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.qr-link-btn {
    display: inline-block;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid var(--primary-blue);
    background: var(--primary-blue);
    color: var(--white);
}

.qr-link-btn.is-ghost {
    background: var(--white);
    color: var(--primary-blue);
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
        <div class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></div>
        <h1 class="product-title"><?php echo htmlspecialchars($product['product_name'] ?? ''); ?></h1>
        <p class="product-subtitle">충남스틸이 공급하는 고품질 <?php echo htmlspecialchars($product['category_name'] ?? ''); ?> 제품입니다</p>
    </div>
    <?php if ($is_admin): ?>
    <div class="admin-actions">
        <a href="/admin/admin_products_edit.php?id=<?php echo (int)$product_id; ?>"
           class="admin-edit-btn">제품 수정</a>
    </div>
    <?php endif; ?>
</section>

<!-- Product Detail Container -->
<div class="product-detail-container">
    <div class="product-info-section">
        <div class="product-info-grid">
            <div class="product-image-section">
                <?php if (!empty($product['main_image'])): ?>
                    <img src="<?php echo htmlspecialchars($product['main_image']); ?>"
                         alt="<?php echo htmlspecialchars($product['product_name'] ?? ''); ?>">
                <?php elseif ($product['category_code'] === 'rebar'): ?>
                    <img src="img/철근.jpg" alt="철근">
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
                <?php if (!empty($product['specification'])): ?>
                <div class="detail-item">
                    <div class="detail-label">규격</div>
                    <div class="detail-value"><?php echo htmlspecialchars($product['specification']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($product['specification_weight'])): ?>
                <div class="detail-item">
                    <div class="detail-label">단위중량</div>
                    <div class="detail-value unit-weight-highlight">
                        <?php echo htmlspecialchars($product['specification_weight']); ?> kg/m
                    </div>
                </div>
                <?php endif; ?>

                <!-- 견적요청 영역 (금액은 표시하지 않는다) -->
                <div class="calculator-section">
                    <div class="calculator-title">견적요청</div>
                    <div class="calculator-desc">
                        원산지·재질·길이·수량을 선택해 장바구니에 담고 견적을 요청하세요.<br>
                        담당자가 확인 후 개별 견적으로 회신해 드립니다.
                    </div>

                    <div class="calc-form-row">
                        <div class="calc-form-group">
                            <label for="qr-origin">원산지</label>
                            <select id="qr-origin" class="calc-control">
                                <?php if (empty($qr_origins)): ?>
                                    <option value="" selected>선택 안 함</option>
                                <?php else: ?>
                                    <?php foreach ($qr_origins as $index => $origin): ?>
                                    <option value="<?php echo htmlspecialchars($origin); ?>" <?php echo $index === 0 ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($origin); ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="calc-form-group">
                            <label for="qr-material">재질</label>
                            <select id="qr-material" class="calc-control">
                                <?php if (empty($qr_materials)): ?>
                                    <option value="" selected>선택 안 함</option>
                                <?php else: ?>
                                    <?php if ($qr_material_no_default): ?>
                                        <option value="" selected>선택하세요</option>
                                    <?php endif; ?>
                                    <?php foreach ($qr_materials as $index => $material): ?>
                                    <option value="<?php echo htmlspecialchars($material); ?>"
                                            <?php echo (!$qr_material_no_default && $index === 0) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($material); ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="calc-form-row">
                        <div class="calc-form-group">
                            <label for="qr-length">길이</label>
                            <div class="calc-input-pair">
                                <?php if ($qr_length_free_input): ?>
                                    <input type="number" id="qr-length" class="calc-control"
                                           min="0" step="0.01" value="" placeholder="길이를 입력하세요">
                                <?php else: ?>
                                    <select id="qr-length" class="calc-control">
                                        <?php if ($qr_standard_length <= 0): ?>
                                            <option value="" selected>선택하세요</option>
                                        <?php endif; ?>
                                        <?php foreach ($qr_lengths as $length_value): ?>
                                            <option value="<?php echo htmlspecialchars(number_format($length_value, 1, '.', '')); ?>"
                                                    <?php echo ($qr_standard_length > 0 && abs($length_value - $qr_standard_length) < 0.0001) ? 'selected' : ''; ?>>
                                                <?php echo number_format($length_value, 1); ?>m
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <?php if ($qr_length_free_input): ?>
                                    <select id="qr-length-unit" class="calc-control">
                                        <option value="M" selected>M</option>
                                        <option value="mm">mm</option>
                                    </select>
                                <?php else: ?>
                                    <?php /* 드롭다운 선택지가 모두 미터 단위이므로 단위를 M 으로 고정한다.
                                             단위를 바꿀 수 있게 두면 '6.0m' 를 고르고 mm 를 선택해
                                             6mm 로 저장되는 모순이 생긴다. */ ?>
                                    <input type="hidden" id="qr-length-unit" value="M">
                                    <span class="calc-unit-fixed">M</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($qr_length_help !== ''): ?>
                            <div class="input-help"><?php echo htmlspecialchars($qr_length_help); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="calc-form-group">
                            <label for="qr-quantity"><?php echo htmlspecialchars($qr_unit['label']); ?> (<?php echo htmlspecialchars($qr_unit['display']); ?>)</label>
                            <div class="calc-input-pair">
                                <input type="number" id="qr-quantity" class="calc-control"
                                       min="<?php echo $qr_unit['decimal'] ? '0.001' : '1'; ?>"
                                       step="<?php echo $qr_unit['decimal'] ? '0.001' : '1'; ?>" value="1">
                                <?php /* 단위는 제품 속성이므로 사용자가 바꿀 수 없다. 기존 화면도 고정 라벨이었다.
                                         서버(ajax/cart_add.php)도 같은 규칙으로 다시 판정하므로 위조되지 않는다. */ ?>
                                <input type="hidden" id="qr-quantity-unit" value="<?php echo htmlspecialchars($qr_unit['value']); ?>">
                                <span class="calc-unit-fixed"><?php echo htmlspecialchars($qr_unit['display']); ?></span>
                            </div>
                            <div class="input-help"><?php echo htmlspecialchars($qr_unit['help']); ?></div>
                        </div>
                    </div>

                    <div class="calc-form-group qr-note-group">
                        <label for="qr-note">요청사항 (선택)</label>
                        <textarea id="qr-note" class="calc-control" rows="3" maxlength="500"
                                  placeholder="절단·도착지·납기 등 요청사항을 적어주세요. (최대 500자)"></textarea>
                    </div>

                    <button type="button" id="qr-submit" class="qr-submit-btn"
                            data-product-id="<?php echo (int)$product_id; ?>">
                        견적요청 (장바구니 담기)
                    </button>

                    <div class="qr-message" id="qr-message" role="status" aria-live="polite"></div>
                </div>
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
                    <a href="/products_new.php?category=<?php echo htmlspecialchars($product['category_code'] ?? ''); ?>"><?php echo htmlspecialchars($product['category_name'] ?? ''); ?> 전체보기</a> |
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

<?php
require_once __DIR__ . '/tail.php';
?>

<script>
/**
 * 견적요청(장바구니 담기)
 *
 * tail.php 의 fetch 인터셉터는 페이지 최하단에서 등록되므로 의존하지 않고,
 * head.php 가 출력한 meta[name="csrf-token"] 값을 직접 읽어 X-CSRF-TOKEN 헤더로 보낸다.
 */
(function () {
    var submitBtn = document.getElementById('qr-submit');
    if (!submitBtn) {
        return;
    }

    var messageBox = document.getElementById('qr-message');
    var productId = submitBtn.getAttribute('data-product-id');
    var categoryCode = <?php echo json_encode($product['category_code'] ?? '', JSON_UNESCAPED_UNICODE); ?>;
    var BUTTON_LABEL = '견적요청 (장바구니 담기)';

    function fieldValue(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function showError(message) {
        if (!messageBox) {
            return;
        }
        messageBox.className = 'qr-message is-visible is-error';
        messageBox.innerHTML = escapeHtml(message);
    }

    function showSuccess(message) {
        if (!messageBox) {
            return;
        }
        var listUrl = '/quote_cart.php';
        var browseUrl = categoryCode
            ? '/products_new.php?category=' + encodeURIComponent(categoryCode)
            : '/products_new.php';

        messageBox.className = 'qr-message is-visible is-success';
        messageBox.innerHTML =
            '<div>' + escapeHtml(message) + '</div>' +
            '<div class="qr-message-actions">' +
            '<a class="qr-link-btn" href="' + listUrl + '">장바구니 보기</a>' +
            '<a class="qr-link-btn is-ghost" href="' + browseUrl + '">계속 둘러보기</a>' +
            '</div>';
    }

    // 이 제품의 수량 단위 (서버가 다시 판정하므로 화면 표시·검증 용도)
    var QUANTITY_DISPLAY = <?php echo json_encode($qr_unit['display'], JSON_UNESCAPED_UNICODE); ?>;
    var QUANTITY_DECIMAL = <?php echo $qr_unit['decimal'] ? 'true' : 'false'; ?>;

    function updateCartBadge(count) {
        if (count === undefined || count === null) {
            return;
        }
        var badge = document.querySelector('.cart-count');
        if (!badge) {
            return;
        }
        badge.textContent = count;
        badge.style.display = Number(count) > 0 ? 'block' : 'none';
    }

    submitBtn.addEventListener('click', function () {
        var quantity = parseFloat(fieldValue('qr-quantity'));
        if (!(quantity > 0)) {
            showError('수량을 1 이상으로 입력해 주세요.');
            return;
        }
        // 본/장 단위는 정수만 허용한다 (기존 자동계산 화면과 동일)
        if (!QUANTITY_DECIMAL && quantity % 1 !== 0) {
            showError('수량은 ' + QUANTITY_DISPLAY + ' 단위로 정수만 입력할 수 있습니다.');
            return;
        }

        var params = new URLSearchParams();
        params.append('product_id', productId);
        params.append('origin', fieldValue('qr-origin'));
        params.append('material', fieldValue('qr-material'));
        params.append('length_value', fieldValue('qr-length'));
        params.append('length_unit', fieldValue('qr-length-unit'));
        params.append('quantity', String(quantity));
        params.append('quantity_unit', fieldValue('qr-quantity-unit'));
        params.append('note', fieldValue('qr-note'));

        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.content : '';

        submitBtn.disabled = true;
        submitBtn.textContent = '담는 중...';

        fetch('/ajax/cart_add.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params.toString()
        })
        .then(function (response) {
            return response.json().catch(function () {
                return { success: false, message: '서버 응답을 처리할 수 없습니다. 잠시 후 다시 시도해 주세요.' };
            });
        })
        .then(function (data) {
            if (data && data.success) {
                updateCartBadge(data.cart_count);
                showSuccess(data.message || '장바구니에 담았습니다.');
            } else {
                showError((data && data.message) ? data.message : '장바구니에 담지 못했습니다.');
            }
        })
        .catch(function () {
            showError('네트워크 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.');
        })
        .then(function () {
            submitBtn.disabled = false;
            submitBtn.textContent = BUTTON_LABEL;
        });
    });
})();
</script>
