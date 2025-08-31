<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

// 수정 모드인 경우 기존 데이터 가져오기
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header("Location: admin_products.php");
        exit;
    }
    
    // min_price, max_price 컬럼이 없는 경우를 대비
    if (!isset($product['min_price'])) {
        $product['min_price'] = null;
    }
    if (!isset($product['max_price'])) {
        $product['max_price'] = null;
    }
}

// 카테고리 목록 가져오기
$stmt = $pdo->query("SELECT * FROM product_categories ORDER BY display_order");
$categories = $stmt->fetchAll();

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_code = $_POST['category_code'] ?? '';
    $product_name = trim($_POST['product_name'] ?? '');
    $product_code = trim($_POST['product_code'] ?? '');
    $product_code = $product_code === '' ? null : $product_code;
    $specifications = trim($_POST['specifications'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null;
    $min_price = isset($_POST['min_price']) && $_POST['min_price'] !== '' ? (float)$_POST['min_price'] : null;
    $max_price = isset($_POST['max_price']) && $_POST['max_price'] !== '' ? (float)$_POST['max_price'] : null;
    $unit = trim($_POST['unit'] ?? '');
    $min_order_qty = (int)($_POST['min_order_qty'] ?? 1);
    $stock_status = $_POST['stock_status'] ?? 'in_stock';
    // base_length 컬럼이 존재하는지 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'base_length'");
    $has_base_length = $stmt->fetch() !== false;
    $base_length = (int)($_POST['base_length'] ?? 6);
    
    // min_price, max_price 컬럼이 존재하는지 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'min_price'");
    $has_price_range = $stmt->fetch() !== false;
    $features = trim($_POST['features'] ?? '');
    $dimensions = trim($_POST['dimensions'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $origin = trim($_POST['origin'] ?? '');
    $delivery_info = trim($_POST['delivery_info'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // 새로운 상세내용 필드들
    $detailed_description = trim($_POST['detailed_description'] ?? '');
    $key_features = trim($_POST['key_features'] ?? '');
    $technical_specs = trim($_POST['technical_specs'] ?? '');
    $applications = trim($_POST['applications'] ?? '');
    $certifications = trim($_POST['certifications'] ?? '');
    $brochure_url = trim($_POST['brochure_url'] ?? '');
    $show_details = isset($_POST['show_details']) ? 1 : 0;
    
    // 제품 상세보기용 필드들
    $quality_cert = trim($_POST['quality_cert'] ?? '');
    $product_features = trim($_POST['product_features'] ?? '');
    
    // 유효성 검사
    $errors = [];
    if (!$category_code) $errors[] = "카테고리를 선택해주세요.";
    if (!$product_name) $errors[] = "제품명을 입력해주세요.";
    if (!$specifications) $errors[] = "규격을 입력해주세요.";
    
    if (!$errors) {
        try {
            if ($id > 0) {
                // 수정
                if ($has_base_length && $has_price_range) {
                    $stmt = $pdo->prepare("
                        UPDATE products SET 
                            category_code = ?, product_name = ?, product_code = ?,
                            specifications = ?, description = ?, price = ?,
                            min_price = ?, max_price = ?,
                            unit = ?, min_order_qty = ?, stock_status = ?,
                            base_length = ?, features = ?, dimensions = ?, weight = ?,
                            material = ?, manufacturer = ?, origin = ?,
                            delivery_info = ?, is_featured = ?, is_active = ?,
                            detailed_description = ?, key_features = ?, technical_specs = ?,
                            applications = ?, certifications = ?, brochure_url = ?,
                            show_details = ?, quality_cert = ?, product_features = ?, 
                            details_updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $min_price, $max_price,
                        $unit, $min_order_qty, $stock_status,
                        $base_length, $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $delivery_info, $is_featured, $is_active,
                        $detailed_description, $key_features, $technical_specs,
                        $applications, $certifications, $brochure_url,
                        $show_details, $quality_cert, $product_features,
                        $id
                    ]);
                } else if ($has_base_length) {
                    $stmt = $pdo->prepare("
                        UPDATE products SET 
                            category_code = ?, product_name = ?, product_code = ?,
                            specifications = ?, description = ?, price = ?,
                            unit = ?, min_order_qty = ?, stock_status = ?,
                            base_length = ?, features = ?, dimensions = ?, weight = ?,
                            material = ?, manufacturer = ?, origin = ?,
                            delivery_info = ?, is_featured = ?, is_active = ?,
                            detailed_description = ?, key_features = ?, technical_specs = ?,
                            applications = ?, certifications = ?, brochure_url = ?,
                            show_details = ?, quality_cert = ?, product_features = ?, 
                            details_updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $unit, $min_order_qty, $stock_status,
                        $base_length, $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $delivery_info, $is_featured, $is_active,
                        $detailed_description, $key_features, $technical_specs,
                        $applications, $certifications, $brochure_url,
                        $show_details, $quality_cert, $product_features,
                        $id
                    ]);
                } else if ($has_price_range) {
                    $stmt = $pdo->prepare("
                        UPDATE products SET 
                            category_code = ?, product_name = ?, product_code = ?,
                            specifications = ?, description = ?, price = ?,
                            min_price = ?, max_price = ?,
                            unit = ?, min_order_qty = ?, stock_status = ?,
                            features = ?, dimensions = ?, weight = ?,
                            material = ?, manufacturer = ?, origin = ?,
                            delivery_info = ?, is_featured = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $min_price, $max_price,
                        $unit, $min_order_qty, $stock_status,
                        $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $delivery_info, $is_featured, $is_active,
                        $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE products SET 
                            category_code = ?, product_name = ?, product_code = ?,
                            specifications = ?, description = ?, price = ?,
                            unit = ?, min_order_qty = ?, stock_status = ?,
                            features = ?, dimensions = ?, weight = ?,
                            material = ?, manufacturer = ?, origin = ?,
                            delivery_info = ?, is_featured = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $unit, $min_order_qty, $stock_status,
                        $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $delivery_info, $is_featured, $is_active,
                        $id
                    ]);
                }
            } else {
                // 신규 등록
                if ($has_base_length && $has_price_range) {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            category_code, product_name, product_code,
                            specifications, description, price,
                            min_price, max_price,
                            unit, min_order_qty, stock_status,
                            base_length, features, dimensions, weight,
                            material, manufacturer, origin,
                            delivery_info, is_featured, is_active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $min_price, $max_price,
                        $unit, $min_order_qty, $stock_status,
                        $base_length, $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $delivery_info, $is_featured, $is_active
                    ]);
                } else if ($has_base_length) {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            category_code, product_name, product_code,
                            specifications, description, price,
                            unit, min_order_qty, stock_status,
                            base_length, features, dimensions, weight,
                            material, manufacturer, origin,
                            delivery_info, is_featured, is_active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $unit, $min_order_qty, $stock_status,
                        $base_length, $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $delivery_info, $is_featured, $is_active
                    ]);
                } else if ($has_price_range) {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            category_code, product_name, product_code,
                            specifications, description, price,
                            min_price, max_price,
                            unit, min_order_qty, stock_status,
                            features, dimensions, weight,
                            material, manufacturer, origin,
                            delivery_info, is_featured, is_active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $min_price, $max_price,
                        $unit, $min_order_qty, $stock_status,
                        $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $delivery_info, $is_featured, $is_active
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            category_code, product_name, product_code,
                            specifications, description, price,
                            unit, min_order_qty, stock_status,
                            features, dimensions, weight,
                            material, manufacturer, origin,
                            delivery_info, is_featured, is_active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $unit, $min_order_qty, $stock_status,
                        $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $delivery_info, $is_featured, $is_active
                    ]);
                }
            }
            
            header("Location: admin_products.php?message=saved");
            exit;
        } catch (PDOException $e) {
            $errors[] = "저장 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

include 'admin_head.php';
?>

<style>
.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.form-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

.btn-back {
    padding: 10px 20px;
    background: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-back:hover {
    background: #5a6268;
}

.form-container {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.error-messages {
    background: #f8d7da;
    color: #721c24;
    padding: 12px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
}

.error-messages ul {
    margin: 0;
    padding-left: 20px;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.required {
    color: #dc3545;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
}

.checkbox-group input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #eee;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: white;
    color: #333;
    border: 2px solid #ddd;
}

.btn-secondary:hover {
    background: #f8f9fa;
}

.help-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}
</style>

<div class="form-header">
    <h2><?php echo $id > 0 ? '제품 수정' : '새 제품 추가'; ?></h2>
    <a href="admin_products.php" class="btn-back">목록으로</a>
</div>

<?php if (!empty($errors)): ?>
<div class="error-messages">
    <ul>
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="" class="form-container">
    <?php if ($id > 0): ?>
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <?php endif; ?>
    <!-- 기본 정보 -->
    <div class="form-section">
        <h3 class="section-title">기본 정보</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="category_code">카테고리 <span class="required">*</span></label>
                <select id="category_code" name="category_code" required>
                    <option value="">카테고리 선택</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_code']; ?>"
                                <?php echo ($product['category_code'] ?? '') == $category['category_code'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="product_code">제품코드</label>
                <input type="text" id="product_code" name="product_code" 
                       value="<?php echo htmlspecialchars($product['product_code'] ?? ''); ?>"
                       placeholder="예: STL-H-001">
                <div class="help-text">비워두면 자동으로 생성됩니다</div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="product_name">제품명 <span class="required">*</span></label>
            <input type="text" id="product_name" name="product_name" 
                   value="<?php echo htmlspecialchars($product['product_name'] ?? ''); ?>"
                   placeholder="예: H형강 200×200" required>
        </div>
        
        <div class="form-group">
            <label for="specifications">규격 <span class="required">*</span></label>
            <input type="text" id="specifications" name="specifications" 
                   value="<?php echo htmlspecialchars($product['specifications'] ?? ''); ?>"
                   placeholder="예: 200×200×8×12" required>
        </div>
        
        <div class="form-group">
            <label for="description">제품 설명</label>
            <textarea id="description" name="description" rows="4"
                      placeholder="제품에 대한 상세한 설명을 입력하세요"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
        </div>
    </div>
    
    <!-- 판매 정보 -->
    <div class="form-section">
        <h3 class="section-title">판매 정보</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="price">기준단가</label>
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <input type="number" id="price" name="price" step="0.01"
                           value="<?php echo $product['price'] ?? ''; ?>"
                           placeholder="0.00"
                           onchange="calculatePriceRange()"
                           style="flex: 1;">
                    <?php if (($product['category_code'] ?? '') === 'rebar' || !$id): ?>
                    <button type="button" 
                            id="applyRebarPriceBtn"
                            onclick="checkAndApplyRebarPrice()"
                            style="padding: 10px 16px; background: #17a2b8; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; white-space: nowrap; display: none;"
                            title="철근 기본 단가 적용">
                        단가 적용
                    </button>
                    <?php endif; ?>
                </div>
                <div class="help-text">
                    견적 문의 제품은 비워두세요<br>
                    <small style="color: #007bff;">계산식: 단위중량(kg/m) × 길이(m) × 수량(본) × 기준단가(원/TON)</small>
                    <?php if (($product['category_code'] ?? '') === 'rebar' || !$id): ?>
                    <br><small style="color: #17a2b8;">※ 철근 제품은 철근 자재 관리에서 설정한 기본 단가가 자동 적용됩니다.</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="min_price">최저단가</label>
                <input type="number" id="min_price" name="min_price" step="0.01"
                       value="<?php echo isset($product['min_price']) ? $product['min_price'] : ''; ?>">
                <div class="help-text">
                    비워두면 기준단가의 90%로 자동 계산<br>
                    <small style="color: #007bff;">※ 최저단가 × 기준길이(m) × 단위중량(kg/m) ÷ 1000 = 최저금액</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="max_price">최대단가</label>
                <input type="number" id="max_price" name="max_price" step="0.01"
                       value="<?php echo isset($product['max_price']) ? $product['max_price'] : ''; ?>">
                <div class="help-text">
                    비워두면 기준단가의 110%로 자동 계산<br>
                    <small style="color: #007bff;">※ 최대단가 × 기준길이(m) × 단위중량(kg/m) ÷ 1000 = 최대금액</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="unit">판매단위</label>
                <input type="text" id="unit" name="unit" 
                       value="<?php echo htmlspecialchars($product['unit'] ?? ''); ?>"
                       placeholder="예: TON, EA, M">
            </div>
            
            <div class="form-group">
                <label for="min_order_qty">최소주문수량</label>
                <input type="number" id="min_order_qty" name="min_order_qty" min="1"
                       value="<?php echo $product['min_order_qty'] ?? 1; ?>">
            </div>
            
            <div class="form-group">
                <label for="stock_status">재고상태</label>
                <select id="stock_status" name="stock_status">
                    <option value="in_stock" <?php echo ($product['stock_status'] ?? 'in_stock') == 'in_stock' ? 'selected' : ''; ?>>재고 있음</option>
                    <option value="out_of_stock" <?php echo ($product['stock_status'] ?? '') == 'out_of_stock' ? 'selected' : ''; ?>>재고 없음</option>
                    <option value="on_order" <?php echo ($product['stock_status'] ?? '') == 'on_order' ? 'selected' : ''; ?>>주문 가능</option>
                </select>
            </div>
            
            <?php 
            // base_length 컬럼이 존재하는지 확인
            $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'base_length'");
            if ($stmt->fetch()): 
            ?>
            <div class="form-group">
                <label for="base_length">기준길이(m)</label>
                <select id="base_length" name="base_length">
                    <?php for ($i = 6; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($product['base_length'] ?? 6) == $i ? 'selected' : ''; ?>>
                            <?php echo $i; ?>m
                        </option>
                    <?php endfor; ?>
                </select>
                <div class="help-text">제품 판매 시 기본으로 선택되는 길이입니다</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 상세 정보 -->
    <div class="form-section">
        <h3 class="section-title">상세 정보</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="dimensions">치수</label>
                <input type="text" id="dimensions" name="dimensions" 
                       value="<?php echo htmlspecialchars($product['dimensions'] ?? ''); ?>"
                       placeholder="예: 200×200×8×12×12000">
            </div>
            
            <div class="form-group">
                <label for="weight">중량</label>
                <input type="text" id="weight" name="weight" 
                       value="<?php echo htmlspecialchars($product['weight'] ?? ''); ?>"
                       placeholder="예: 49.9kg/m">
            </div>
            
            <div class="form-group">
                <label for="material">재질</label>
                <select id="material" name="material" style="width: 100%;">
                    <option value="">재질 선택</option>
                    <optgroup label="일반 구조용강">
                        <option value="SS400" <?php echo ($product['material'] ?? '') == 'SS400' ? 'selected' : ''; ?>>SS400</option>
                        <option value="SM490" <?php echo ($product['material'] ?? '') == 'SM490' ? 'selected' : ''; ?>>SM490</option>
                        <option value="SM490A" <?php echo ($product['material'] ?? '') == 'SM490A' ? 'selected' : ''; ?>>SM490A</option>
                        <option value="SM490B" <?php echo ($product['material'] ?? '') == 'SM490B' ? 'selected' : ''; ?>>SM490B</option>
                    </optgroup>
                    <optgroup label="고장력강">
                        <option value="SM520" <?php echo ($product['material'] ?? '') == 'SM520' ? 'selected' : ''; ?>>SM520</option>
                        <option value="SM570" <?php echo ($product['material'] ?? '') == 'SM570' ? 'selected' : ''; ?>>SM570</option>
                    </optgroup>
                    <optgroup label="스테인리스강">
                        <option value="SUS304" <?php echo ($product['material'] ?? '') == 'SUS304' ? 'selected' : ''; ?>>SUS304</option>
                        <option value="SUS316" <?php echo ($product['material'] ?? '') == 'SUS316' ? 'selected' : ''; ?>>SUS316</option>
                        <option value="SUS430" <?php echo ($product['material'] ?? '') == 'SUS430' ? 'selected' : ''; ?>>SUS430</option>
                    </optgroup>
                    <optgroup label="기타">
                        <option value="S45C" <?php echo ($product['material'] ?? '') == 'S45C' ? 'selected' : ''; ?>>S45C (탄소강)</option>
                        <option value="SCM440" <?php echo ($product['material'] ?? '') == 'SCM440' ? 'selected' : ''; ?>>SCM440 (합금강)</option>
                        <option value="기타" <?php echo ($product['material'] ?? '') == '기타' ? 'selected' : ''; ?>>기타</option>
                    </optgroup>
                </select>
                <div class="help-text">주요 재질: SS400(일반구조용), SM490(고강도구조용)</div>
                <input type="text" id="material_custom" name="material_custom" 
                       placeholder="목록에 없는 재질 직접 입력" 
                       style="width: 100%; margin-top: 5px; display: none;"
                       value="">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="manufacturer">제조사</label>
                <input type="text" id="manufacturer" name="manufacturer" 
                       value="<?php echo htmlspecialchars($product['manufacturer'] ?? ''); ?>"
                       placeholder="예: 포스코, 현대제철">
            </div>
            
            <div class="form-group">
                <label for="origin">원산지</label>
                <input type="text" id="origin" name="origin" 
                       value="<?php echo htmlspecialchars($product['origin'] ?? ''); ?>"
                       placeholder="예: 대한민국">
            </div>
        </div>
        
        <div class="form-group">
            <label for="features">제품 특징</label>
            <textarea id="features" name="features" rows="4"
                      placeholder="각 특징을 줄바꿈으로 구분하여 입력하세요"><?php echo htmlspecialchars($product['features'] ?? ''); ?></textarea>
            <div class="help-text">각 특징을 새 줄로 구분하면 목록으로 표시됩니다</div>
        </div>
        
        <div class="form-group">
            <label for="delivery_info">배송 정보</label>
            <textarea id="delivery_info" name="delivery_info" rows="3"
                      placeholder="배송 관련 안내사항을 입력하세요"><?php echo htmlspecialchars($product['delivery_info'] ?? ''); ?></textarea>
        </div>
    </div>
    
    <!-- 제품 상세보기 정보 (사각파이프용) -->
    <div class="form-section">
        <h3 class="section-title">제품 상세보기 정보 (사각파이프 전용)</h3>
        
        <div class="form-group">
            <label for="quality_cert">품질 인증</label>
            <input type="text" id="quality_cert" name="quality_cert" 
                   value="<?php echo htmlspecialchars($product['quality_cert'] ?? ''); ?>"
                   placeholder="예: KS D 3568 규격 인증">
            <div class="help-text">제품 상세보기 섹션에 표시될 품질 인증 정보</div>
        </div>
        
        <div class="form-group">
            <label for="product_features">제품 특징</label>
            <textarea id="product_features" name="product_features" rows="3"
                      placeholder="예: 고강도, 경량화, 우수한 내구성"><?php echo htmlspecialchars($product['product_features'] ?? ''); ?></textarea>
            <div class="help-text">제품 상세보기 섹션에 표시될 특징 (쉼표로 구분)</div>
        </div>
    </div>
    
    <!-- 상세 내용 -->
    <div class="form-section">
        <h3 class="section-title">상세 내용</h3>
        
        <div class="form-group">
            <label for="detailed_description">상세 설명</label>
            <textarea id="detailed_description" name="detailed_description" rows="6"
                      placeholder="제품에 대한 자세한 설명을 입력하세요"><?php echo htmlspecialchars($product['detailed_description'] ?? ''); ?></textarea>
            <div class="help-text">제품 상세 페이지에 표시될 긴 설명입니다</div>
        </div>
        
        <div class="form-group">
            <label for="key_features">주요 특징</label>
            <textarea id="key_features" name="key_features" rows="4"
                      placeholder="• 고강도 구조용강으로 제작&#10;• 우수한 내구성과 안정성&#10;• 다양한 규격 제공"><?php echo htmlspecialchars($product['key_features'] ?? ''); ?></textarea>
            <div class="help-text">각 특징을 • 기호나 줄바꿈으로 구분하세요</div>
        </div>
        
        <div class="form-group">
            <label for="technical_specs">기술 사양</label>
            <textarea id="technical_specs" name="technical_specs" rows="4"
                      placeholder="단면계수: XXX cm³&#10;단면2차모멘트: XXX cm⁴&#10;회전반경: XXX cm"><?php echo htmlspecialchars($product['technical_specs'] ?? ''); ?></textarea>
            <div class="help-text">상세한 기술적 사양을 입력하세요</div>
        </div>
        
        <div class="form-group">
            <label for="applications">사용 용도</label>
            <textarea id="applications" name="applications" rows="4"
                      placeholder="• 건축물의 기둥 및 보&#10;• 교량 구조물&#10;• 산업 시설 골조"><?php echo htmlspecialchars($product['applications'] ?? ''); ?></textarea>
            <div class="help-text">제품의 주요 사용처나 용도를 입력하세요</div>
        </div>
        
        <div class="form-group">
            <label for="certifications">관련 규격/인증</label>
            <input type="text" id="certifications" name="certifications" 
                   value="<?php echo htmlspecialchars($product['certifications'] ?? ''); ?>"
                   placeholder="예: KS D 3503, JIS G 3192, ISO 9001">
            <div class="help-text">관련 규격이나 인증을 쉼표로 구분하여 입력하세요</div>
        </div>
        
        <div class="form-group">
            <label for="brochure_url">브로셔/카탈로그 URL</label>
            <input type="url" id="brochure_url" name="brochure_url" 
                   value="<?php echo htmlspecialchars($product['brochure_url'] ?? ''); ?>"
                   placeholder="https://example.com/brochure.pdf">
            <div class="help-text">제품 브로셔나 카탈로그 다운로드 링크</div>
        </div>
    </div>
    
    <!-- 표시 설정 -->
    <div class="form-section">
        <h3 class="section-title">표시 설정</h3>
        
        <div class="checkbox-group">
            <input type="checkbox" id="is_featured" name="is_featured" value="1"
                   <?php echo ($product['is_featured'] ?? 0) ? 'checked' : ''; ?>>
            <label for="is_featured">추천 제품으로 표시</label>
        </div>
        
        <div class="checkbox-group">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   <?php echo ($product['is_active'] ?? 1) ? 'checked' : ''; ?>>
            <label for="is_active">제품 활성화</label>
        </div>
        
        <div class="checkbox-group">
            <input type="checkbox" id="show_details" name="show_details" value="1"
                   <?php echo ($product['show_details'] ?? 1) ? 'checked' : ''; ?>>
            <label for="show_details">상세내용 표시 (체크 해제 시 기본 정보만 표시)</label>
        </div>
    </div>
    
    <div class="form-actions">
        <a href="admin_products_integrated.php?tab=products" class="btn btn-secondary">목록보기</a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
            <?php echo $id > 0 ? '수정하기' : '등록하기'; ?>
        </button>
        <a href="admin_products.php" class="btn btn-secondary">취소</a>
    </div>
</form>

<!-- 메시지 표시 영역 -->
<div id="messageBox" style="display: none; margin-top: 20px; padding: 15px; border-radius: 6px;"></div>

<script>
// 재질 선택 변경 시 처리
document.getElementById('material').addEventListener('change', function() {
    const customInput = document.getElementById('material_custom');
    if (this.value === '기타') {
        customInput.style.display = 'block';
        customInput.required = true;
    } else {
        customInput.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
});

// 페이지 로드 시 기타 선택 여부 확인
window.addEventListener('DOMContentLoaded', function() {
    const material = document.getElementById('material');
    const customInput = document.getElementById('material_custom');
    
    <?php if (($product['material'] ?? '') && !in_array($product['material'], ['SS400', 'SM490', 'SM490A', 'SM490B', 'SM520', 'SM570', 'SUS304', 'SUS316', 'SUS430', 'S45C', 'SCM440'])): ?>
    // 목록에 없는 재질인 경우
    material.value = '기타';
    customInput.style.display = 'block';
    customInput.value = '<?php echo htmlspecialchars($product['material'] ?? ''); ?>';
    <?php endif; ?>
});

// 기준단가 변경 시 최저/최대단가 자동 계산
function calculatePriceRange() {
    const priceInput = document.getElementById('price');
    const minPriceInput = document.getElementById('min_price');
    const maxPriceInput = document.getElementById('max_price');
    
    if (priceInput.value && !minPriceInput.value) {
        const basePrice = parseFloat(priceInput.value);
        minPriceInput.placeholder = (basePrice * 0.90).toFixed(2);
    }
    
    if (priceInput.value && !maxPriceInput.value) {
        const basePrice = parseFloat(priceInput.value);
        maxPriceInput.placeholder = (basePrice * 1.10).toFixed(2);
    }
}

// 페이지 로드 시 초기 계산
calculatePriceRange();

// 철근 단가 자동 적용 기능
function checkAndApplyRebarPrice() {
    const categoryCode = document.getElementById('category_code').value;
    const specifications = document.getElementById('specifications').value;
    const priceInput = document.getElementById('price');
    
    // 철근 카테고리이고 규격이 입력된 경우
    if (categoryCode === 'rebar' && specifications && specifications.match(/D\d+/)) {
        fetch(`ajax/get_rebar_price.php?category_code=${categoryCode}&specifications=${encodeURIComponent(specifications)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    // 기준단가 자동 적용
                    priceInput.value = data.data.base_price;
                    
                    // 최저/최대단가 재계산
                    calculatePriceRange();
                    
                    // 메시지 표시
                    const messageBox = document.getElementById('messageBox');
                    messageBox.style.display = 'block';
                    messageBox.style.background = '#d1ecf1';
                    messageBox.style.color = '#0c5460';
                    messageBox.innerHTML = '✓ ' + data.data.message;
                    
                    // 3초 후 메시지 숨기기
                    setTimeout(() => {
                        messageBox.style.display = 'none';
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error fetching rebar price:', error);
            });
    }
}

// 단가 적용 버튼 표시/숨김 처리
function toggleApplyPriceButton() {
    const categoryCode = document.getElementById('category_code').value;
    const applyBtn = document.getElementById('applyRebarPriceBtn');
    
    if (applyBtn) {
        if (categoryCode === 'rebar') {
            applyBtn.style.display = 'block';
        } else {
            applyBtn.style.display = 'none';
        }
    }
}

// 카테고리 변경 시 철근 단가 확인
document.getElementById('category_code').addEventListener('change', function() {
    toggleApplyPriceButton();
    checkAndApplyRebarPrice();
});

// 규격 입력 시 철근 단가 확인
document.getElementById('specifications').addEventListener('blur', function() {
    checkAndApplyRebarPrice();
});

// 페이지 로드 시 버튼 상태 설정
window.addEventListener('DOMContentLoaded', function() {
    toggleApplyPriceButton();
});

// 페이지 로드 시 철근 제품인 경우 단가 확인
<?php if ($id > 0 && ($product['category_code'] ?? '') === 'rebar' && !$product['price']): ?>
window.addEventListener('DOMContentLoaded', function() {
    checkAndApplyRebarPrice();
});
<?php endif; ?>

// 폼 제출 시 처리
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault(); // 기본 폼 제출 방지
    
    const material = document.getElementById('material');
    const customInput = document.getElementById('material_custom');
    
    // 기타 재질 처리
    if (material.value === '기타' && customInput.value.trim()) {
        // 기타 선택 시 직접 입력한 값을 material 필드에 설정
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'material';
        hiddenInput.value = customInput.value.trim();
        this.appendChild(hiddenInput);
        
        // 원래 select는 비활성화
        material.disabled = true;
    }
    
    // AJAX로 폼 제출
    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitBtn');
    const messageBox = document.getElementById('messageBox');
    
    
    // 버튼 비활성화
    submitBtn.disabled = true;
    submitBtn.textContent = '처리 중...';
    
    fetch('admin_products_ajax_save.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // 메시지 표시
        messageBox.style.display = 'block';
        if (data.success) {
            messageBox.style.background = '#d4edda';
            messageBox.style.color = '#155724';
            messageBox.innerHTML = '✓ ' + data.message;
            
            // 신규 등록인 경우 ID 업데이트
            if (data.newId) {
                // URL 변경 (페이지 새로고침 없이)
                window.history.replaceState({}, '', 'admin_products_edit.php?id=' + data.newId);
                // 폼에 ID 추가
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = data.newId;
                this.appendChild(idInput);
            }
        } else {
            messageBox.style.background = '#f8d7da';
            messageBox.style.color = '#721c24';
            messageBox.innerHTML = '✗ ' + data.message;
        }
        
        // 3초 후 메시지 숨기기
        setTimeout(() => {
            messageBox.style.display = 'none';
        }, 3000);
        
        // 버튼 복원
        submitBtn.disabled = false;
        submitBtn.textContent = data.newId ? '수정하기' : submitBtn.textContent;
        
        // 재질 select 복원
        if (material.disabled) {
            material.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageBox.style.display = 'block';
        messageBox.style.background = '#f8d7da';
        messageBox.style.color = '#721c24';
        messageBox.innerHTML = '✗ 처리 중 오류가 발생했습니다.';
        
        submitBtn.disabled = false;
        submitBtn.textContent = '<?php echo $id > 0 ? '수정하기' : '등록하기'; ?>';
    });
});
</script>

<?php include 'admin_tail.php'; ?>