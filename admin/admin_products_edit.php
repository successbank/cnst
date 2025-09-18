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
    $specification = trim($_POST['specification'] ?? '');
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
    $delivery_info = trim($_POST['delivery_info'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;
    $homepage_display_order = isset($_POST['homepage_display_order']) ? (int)$_POST['homepage_display_order'] : 0;
    
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
    
    // 원산지 선택 처리 - JSON 형식으로 받음
    $available_origins_json = $_POST['available_origins'] ?? '[]';
    $available_origins = json_decode($available_origins_json, true);
    $available_origins_json = json_encode($available_origins, JSON_UNESCAPED_UNICODE);
    
    // 재질 선택 처리 - JSON 형식으로 받음
    $available_materials_json = $_POST['available_materials'] ?? '[]';
    $available_materials = json_decode($available_materials_json, true);
    $available_materials_json = json_encode($available_materials, JSON_UNESCAPED_UNICODE);
    
    // 재질별 가격 처리
    $material_prices = $_POST['material_prices'] ?? [];
    $material_price_data = json_encode($material_prices, JSON_UNESCAPED_UNICODE);
    
    // 유효성 검사
    $errors = [];
    if (!$category_code) $errors[] = "카테고리를 선택해주세요.";
    if (!$product_name) $errors[] = "제품명을 입력해주세요.";
    if (!$specifications) $errors[] = "규격을 입력해주세요.";
    
    if (!$errors) {
        try {
            if ($id > 0) {
                // 디버그: 어떤 조건이 실행되는지 확인
                echo "<div style='background: #ffeaa7; padding: 10px; margin: 10px; border-radius: 5px;'>";
                echo "디버그 정보:<br>";
                echo "has_base_length: " . ($has_base_length ? 'true' : 'false') . "<br>";
                echo "has_price_range: " . ($has_price_range ? 'true' : 'false') . "<br>";
                echo "price 값: " . ($price ?? 'null') . "<br>";
                echo "</div>";
                
                // 수정
                if ($has_base_length && $has_price_range) {
                    $stmt = $pdo->prepare("
                        UPDATE products SET
                            category_code = ?, product_name = ?, product_code = ?,
                            specifications = ?, specification = ?, description = ?, price = ?,
                            min_price = ?, max_price = ?,
                            unit = ?, min_order_qty = ?, stock_status = ?,
                            base_length = ?, features = ?,
                            available_origins = ?, available_materials = ?, material_price_data = ?,
                            delivery_info = ?, is_featured = ?, is_active = ?,
                            show_on_homepage = ?, homepage_display_order = ?,
                            detailed_description = ?, key_features = ?, technical_specs = ?,
                            applications = ?, certifications = ?, brochure_url = ?,
                            show_details = ?, quality_cert = ?, product_features = ?,
                            details_updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $specification, $description, $price,
                        $min_price, $max_price,
                        $unit, $min_order_qty, $stock_status,
                        $base_length, $features,
                        $available_origins_json, $available_materials_json, $material_price_data,
                        $delivery_info, $is_featured, $is_active,
                        $show_on_homepage, $homepage_display_order,
                        $detailed_description, $key_features, $technical_specs,
                        $applications, $certifications, $brochure_url,
                        $show_details, $quality_cert, $product_features,
                        $id
                    ]);
                } else if ($has_base_length) {
                    $stmt = $pdo->prepare("
                        UPDATE products SET
                            category_code = ?, product_name = ?, product_code = ?,
                            specifications = ?, specification = ?, description = ?, price = ?,
                            unit = ?, min_order_qty = ?, stock_status = ?,
                            base_length = ?, features = ?,
                            available_origins = ?, available_materials = ?, delivery_info = ?, is_featured = ?, is_active = ?,
                            show_on_homepage = ?, homepage_display_order = ?,
                            detailed_description = ?, key_features = ?, technical_specs = ?,
                            applications = ?, certifications = ?, brochure_url = ?,
                            show_details = ?, quality_cert = ?, product_features = ?,
                            details_updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $specification, $description, $price,
                        $unit, $min_order_qty, $stock_status,
                        $base_length, $features,
                        $available_origins_json, $available_materials_json, $delivery_info, $is_featured, $is_active,
                        $show_on_homepage, $homepage_display_order,
                        $detailed_description, $key_features, $technical_specs,
                        $applications, $certifications, $brochure_url,
                        $show_details, $quality_cert, $product_features,
                        $id
                    ]);
                } else if ($has_price_range) {
                    $stmt = $pdo->prepare("
                        UPDATE products SET
                            category_code = ?, product_name = ?, product_code = ?,
                            specifications = ?, specification = ?, description = ?, price = ?,
                            min_price = ?, max_price = ?,
                            unit = ?, min_order_qty = ?, stock_status = ?,
                            features = ?,
                            available_origins = ?, available_materials = ?, material_price_data = ?, delivery_info = ?, is_featured = ?, is_active = ?,
                            show_on_homepage = ?, homepage_display_order = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $specification, $description, $price,
                        $min_price, $max_price,
                        $unit, $min_order_qty, $stock_status,
                        $features,
                        $available_origins_json, $available_materials_json, $material_price_data, $delivery_info, $is_featured, $is_active,
                        $show_on_homepage, $homepage_display_order,
                        $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE products SET
                            category_code = ?, product_name = ?, product_code = ?,
                            specifications = ?, specification = ?, description = ?, price = ?,
                            unit = ?, min_order_qty = ?, stock_status = ?,
                            features = ?,
                            available_origins = ?, available_materials = ?, material_price_data = ?, delivery_info = ?, is_featured = ?, is_active = ?,
                            show_on_homepage = ?, homepage_display_order = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $specification, $description, $price,
                        $unit, $min_order_qty, $stock_status,
                        $features,
                        $available_origins_json, $available_materials_json, $material_price_data, $delivery_info, $is_featured, $is_active,
                        $show_on_homepage, $homepage_display_order,
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
                            available_origins, delivery_info, is_featured, is_active,
                            show_on_homepage, homepage_display_order
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $specification, $description, $price,
                        $min_price, $max_price,
                        $unit, $min_order_qty, $stock_status,
                        $base_length, $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $available_origins_json, $delivery_info, $is_featured, $is_active,
                        $show_on_homepage, $homepage_display_order
                    ]);
                } else if ($has_base_length) {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            category_code, product_name, product_code,
                            specifications, description, price,
                            unit, min_order_qty, stock_status,
                            base_length, features, dimensions, weight,
                            material, manufacturer, origin,
                            available_origins, delivery_info, is_featured, is_active,
                            show_on_homepage, homepage_display_order
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $specification, $description, $price,
                        $unit, $min_order_qty, $stock_status,
                        $base_length, $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $available_origins_json, $delivery_info, $is_featured, $is_active,
                        $show_on_homepage, $homepage_display_order
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
                            available_origins, delivery_info, is_featured, is_active,
                            show_on_homepage, homepage_display_order
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $specification, $description, $price,
                        $min_price, $max_price,
                        $unit, $min_order_qty, $stock_status,
                        $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $available_origins_json, $delivery_info, $is_featured, $is_active,
                        $show_on_homepage, $homepage_display_order
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            category_code, product_name, product_code,
                            specifications, description, price,
                            unit, min_order_qty, stock_status,
                            features, dimensions, weight,
                            material, manufacturer, origin,
                            available_origins, delivery_info, is_featured, is_active,
                            show_on_homepage, homepage_display_order
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $specification, $description, $price,
                        $unit, $min_order_qty, $stock_status,
                        $features, $dimensions, $weight,
                        $material, $manufacturer, $origin,
                        $available_origins_json, $delivery_info, $is_featured, $is_active,
                        $show_on_homepage, $homepage_display_order
                    ]);
                }
            }
            
            // 디버깅을 위해 임시로 리다이렉션 비활성화
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px;'>";
            echo "✅ 제품이 성공적으로 업데이트되었습니다.";
            echo "<br>업데이트된 가격: " . number_format($price ?? 0, 0) . "원";
            echo "<br><a href='admin_products.php'>제품 목록으로 돌아가기</a> | ";
            echo "<a href='admin_products_edit.php?id={$id}'>계속 편집하기</a>";
            echo "</div>";
            // header("Location: admin_products.php?message=saved");
            // exit;
        } catch (PDOException $e) {
            $errors[] = "저장 중 오류가 발생했습니다: " . $e->getMessage();
            // 디버그 정보 추가
            error_log("Product update error: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
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

.header-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
}

.btn-view {
    padding: 10px 20px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-view:hover {
    background: #0056b3;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
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

/* 이미지 드래그 앤 드롭 스타일 */
.image-drop-zone {
    margin-top: 15px;
    border: 2px dashed #007bff;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    background: #f8f9fa;
    cursor: pointer;
    transition: all 0.3s ease;
}

.image-drop-zone:hover {
    background: #e9ecef;
    border-color: #0056b3;
}

.image-drop-zone.drag-over {
    background: #e3f2fd;
    border-color: #2196f3;
    border-style: solid;
}

.drop-zone-content {
    pointer-events: none;
}

.upload-icon {
    font-size: 48px;
    display: block;
    margin-bottom: 10px;
}

.upload-info {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

/* 업로드 진행 상태 */
.upload-progress {
    margin-top: 15px;
}

.progress-bar {
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #007bff;
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    margin-top: 10px;
    font-size: 14px;
    color: #666;
}

/* 이미지 미리보기 스타일 */
.image-preview-container {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}

.image-preview-container h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.image-preview-list {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}

.image-preview-item {
    position: relative;
    width: 120px;
    height: 120px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    cursor: move;
    transition: all 0.3s ease;
}

.image-preview-item.dragging {
    opacity: 0.5;
    transform: scale(0.95);
}

.image-preview-item.drag-over {
    border-color: #007bff;
    background: #e3f2fd;
}

.image-preview-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-preview-item .delete-btn {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 24px;
    height: 24px;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    line-height: 1;
    transition: all 0.2s ease;
}

.image-preview-item .delete-btn:hover {
    background: #dc3545;
    transform: scale(1.1);
}

.preview-help-text {
    font-size: 12px;
    color: #6c757d;
    line-height: 1.5;
}

/* 원산지 드래그 앤 드롭 스타일 */
.origin-container {
    margin-top: 10px;
}

.origin-sortable {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    min-height: 45px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #f8f9fa;
}

.origin-item {
    background: #007bff;
    color: white;
    padding: 8px 16px;
    border-radius: 24px;
    cursor: move;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.origin-item.ghost {
    opacity: 0.5;
}

.origin-item-remove {
    cursor: pointer;
    margin-left: 4px;
    font-weight: bold;
    font-size: 18px;
    line-height: 1;
}

.origin-item-remove:hover {
    color: #ffcdd2;
}

.origin-available {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.origin-add {
    background: #e9ecef;
    color: #495057;
    padding: 6px 14px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s ease;
}

.origin-add:hover {
    background: #dee2e6;
    transform: translateY(-1px);
}

/* 원산지별 가격 스타일 */
#origin-price-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 10px;
}

.origin-price-item {
    display: grid;
    grid-template-columns: 150px 200px 1fr;
    gap: 15px;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.origin-price-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.origin-price-item label {
    font-weight: 600;
    color: #333;
    margin: 0;
}

.origin-price-input {
    max-width: 200px;
}

.origin-price-item .help-text {
    font-size: 12px;
    color: #666;
    margin: 0;
}

/* 재질별 가격 스타일 (원산지와 동일) */
#material-price-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-top: 10px;
}

.material-price-item {
    display: grid;
    grid-template-columns: 150px 200px 1fr;
    gap: 15px;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.material-price-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.material-price-item label {
    font-weight: 600;
    color: #333;
    margin: 0;
}

.material-price-input {
    max-width: 200px;
}

.material-price-item .help-text {
    font-size: 12px;
    color: #666;
    margin: 0;
}

/* 재질 드래그 앤 드롭 스타일 (원산지와 동일) */
.material-container {
    margin-top: 10px;
}

.material-sortable {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    min-height: 45px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #f8f9fa;
}

.material-item {
    background: #28a745;
    color: white;
    padding: 8px 16px;
    border-radius: 24px;
    cursor: move;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.material-item.ghost {
    opacity: 0.5;
}

.material-item-remove {
    cursor: pointer;
    margin-left: 4px;
    font-weight: bold;
    font-size: 18px;
    line-height: 1;
}

.material-item-remove:hover {
    color: #ffcdd2;
}

.material-available {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.material-add {
    background: #e9ecef;
    color: #495057;
    padding: 6px 14px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s ease;
}

.material-add:hover {
    background: #dee2e6;
    transform: translateY(-1px);
}

/* 수동 입력 버튼 스타일 */
.material-custom-input button:hover {
    background: #138496 !important;
}

.material-custom-input button:active {
    transform: translateY(1px);
}
</style>

<div class="form-header">
    <h2><?php echo $id > 0 ? '제품 수정' : '새 제품 추가'; ?></h2>
    <div class="header-buttons">
        <?php if ($id > 0): ?>
        <a href="../product_detail.php?id=<?php echo $id; ?>" target="_blank" class="btn-view">제품보기</a>
        <?php endif; ?>
        <a href="admin_products.php" class="btn-back">목록으로</a>
    </div>
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
                <select id="category_code" name="category_code" required onchange="updateMaterialOptions(event)">
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
            <label for="specifications">규격(관리용)</label>
            <input type="text" id="specifications" name="specifications"
                   value="<?php echo htmlspecialchars($product['specifications'] ?? ''); ?>"
                   placeholder="예: 200×200×8×12">
        </div>

        <div class="form-group">
            <label for="specification">규격(표시용) <span class="required">*</span></label>
            <input type="text" id="specification" name="specification"
                   value="<?php echo htmlspecialchars($product['specification'] ?? ''); ?>"
                   placeholder="예: 200×200×8×12" required>
            <div class="help-text">제품 상세 페이지에 표시될 규격입니다</div>
        </div>
        
        <div class="form-group">
            <label for="description">제품 설명</label>
            <textarea id="description" name="description" rows="4"
                      placeholder="제품에 대한 상세한 설명을 입력하세요"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            
            <!-- 이미지 업로드 영역 -->
            <div id="imageDropZone" class="image-drop-zone">
                <div class="drop-zone-content">
                    <i class="upload-icon">📷</i>
                    <p>이미지를 드래그 & 드롭하거나 클릭하여 업로드</p>
                    <p class="upload-info">JPG, PNG, GIF, WEBP (최대 5MB)</p>
                </div>
                <input type="file" id="imageInput" accept="image/*" style="display: none;" multiple>
            </div>
            
            <!-- 업로드 진행 상태 -->
            <div id="uploadProgress" class="upload-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <p class="progress-text">업로드 중...</p>
            </div>
            
            <!-- 이미지 미리보기 영역 -->
            <div id="imagePreviewContainer" class="image-preview-container" style="display: none;">
                <h4>업로드된 이미지 (드래그하여 순서 변경 가능)</h4>
                <div id="imagePreviewList" class="image-preview-list"></div>
                <div class="preview-help-text">
                    • 이미지를 드래그하여 순서를 변경할 수 있습니다<br>
                    • X 버튼을 클릭하여 이미지를 삭제할 수 있습니다
                </div>
            </div>
        </div>
    </div>
    
    <!-- 판매 정보 -->
    <div class="form-section">
        <h3 class="section-title">판매 정보</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="price">기준단가</label>
                <input type="number" id="price" name="price" step="0.01"
                       value="<?php echo $product['price'] ?? ''; ?>"
                       placeholder="0.00"
                       onchange="calculatePriceRange()"
                       class="form-control">
                <div class="help-text">
                    견적 문의 제품은 비워두세요<br>
                    <small style="color: #007bff;">계산식: 단위중량(kg/m) × 길이(m) × 수량(본) × 기준단가(원/TON)</small>
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
        
        <div class="form-group">
            <label>판매 가능 원산지</label>
            <div class="origin-container">
                <div class="origin-sortable" id="origin-sortable">
                    <?php
                    // 가능한 원산지 목록
                    $origin_options = [
                        "국산", "중국산", "일본산", "베트남산", 
                        "바레인산", "수입산", "장기재고", "중고"
                    ];
                    
                    // 현재 선택된 원산지 (JSON 디코드)
                    $selected_origins = [];
                    if (!empty($product['available_origins'])) {
                        $selected_origins = json_decode($product['available_origins'], true) ?: [];
                    }
                    
                    // 선택된 원산지 표시
                    foreach ($selected_origins as $origin):
                    ?>
                    <div class="origin-item" data-origin="<?php echo htmlspecialchars($origin); ?>">
                        <?php echo htmlspecialchars($origin); ?>
                        <span class="origin-item-remove" onclick="removeOrigin('<?php echo htmlspecialchars($origin); ?>')">×</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="origin-available">
                    <?php
                    // 선택되지 않은 원산지 표시
                    foreach ($origin_options as $origin):
                        if (!in_array($origin, $selected_origins)):
                    ?>
                    <span class="origin-add" onclick="addOrigin('<?php echo htmlspecialchars($origin); ?>')"><?php echo htmlspecialchars($origin); ?> +</span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                
                <input type="hidden" name="available_origins" id="available_origins_input" 
                       value='<?php echo json_encode($selected_origins, JSON_UNESCAPED_UNICODE); ?>'>
            </div>
            <div class="help-text" style="margin-top: 10px;">
                원산지를 드래그하여 순서를 변경할 수 있습니다. 첫 번째 원산지가 기본값으로 표시됩니다.
            </div>
        </div>
        
        <div class="form-group">
            <label>원산지별 추가 비용 (kg당)</label>
            <div id="origin-price-container">
                <?php
                // 원산지별 가격 데이터 파싱
                $origin_prices = [];
                if (!empty($product['origin_price_data'])) {
                    $origin_prices = json_decode($product['origin_price_data'], true) ?: [];
                }
                
                // 선택된 원산지에 대해 가격 입력 필드 표시
                foreach ($selected_origins as $origin):
                    $price = $origin_prices[$origin] ?? 0;
                ?>
                <div class="origin-price-item" data-origin="<?php echo htmlspecialchars($origin); ?>">
                    <label><?php echo htmlspecialchars($origin); ?></label>
                    <input type="number" 
                           name="origin_prices[<?php echo htmlspecialchars($origin); ?>]" 
                           value="<?php echo $price; ?>" 
                           step="10"
                           placeholder="0"
                           class="form-control origin-price-input">
                    <span class="help-text">원/kg (기본가격 대비 추가비용, 마이너스 가능)</span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="help-text" style="margin-top: 10px;">
                각 원산지별로 kg당 추가 비용을 입력하세요. 
                예: 국산 0원, 중국산 -50원(할인), 일본산 +200원(할증)
            </div>
        </div>
        
        <div class="form-group">
            <label>판매 가능 재질</label>
            <div class="material-container">
                <div class="material-sortable" id="material-sortable">
                    <?php
                    // 카테고리별 재질 목록
                    $material_options = [];
                    if (isset($product['category_code'])) {
                        if ($product['category_code'] === 'rebar') {
                            // 철근 재질
                            $material_options = ["SD300", "SD400", "SD400S", "SD500", "SD600", "SD600S"];
                        } else {
                            // 기타 철강재 재질
                            $material_options = [
                                "SS400", "SM490", "SM490A", "SM490B", "SM520", "SM570",
                                "SUS304", "SUS316", "SUS430", "S45C", "SCM440", 
                                "SPHC", "SS400/A36", "SM45C"
                            ];
                        }
                    } else {
                        // 기본 재질 목록
                        $material_options = [
                            "SS400", "SM490", "SM490A", "SM490B", "SM520", "SM570",
                            "SUS304", "SUS316", "SUS430", "S45C", "SCM440", 
                            "SPHC", "SS400/A36", "SM45C"
                        ];
                    }
                    
                    // 현재 선택된 재질 (JSON 디코드)
                    $selected_materials = [];
                    if (!empty($product['available_materials'])) {
                        $selected_materials = json_decode($product['available_materials'], true) ?: [];
                    }
                    
                    // 선택된 재질 표시
                    foreach ($selected_materials as $material):
                    ?>
                    <div class="material-item" data-material="<?php echo htmlspecialchars($material); ?>">
                        <?php echo htmlspecialchars($material); ?>
                        <span class="material-item-remove" onclick="removeMaterial('<?php echo htmlspecialchars($material); ?>')">×</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="material-available">
                    <?php
                    // 선택되지 않은 재질 표시
                    foreach ($material_options as $material):
                        if (!in_array($material, $selected_materials)):
                    ?>
                    <span class="material-add" onclick="addMaterial('<?php echo htmlspecialchars($material); ?>')"><?php echo htmlspecialchars($material); ?> +</span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                
                <!-- 수동 입력 영역 -->
                <div class="material-custom-input" style="margin-top: 15px;">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" 
                               id="custom_material_input" 
                               placeholder="직접 재질 입력 (예: SD700)" 
                               style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;">
                        <button type="button" 
                                onclick="addCustomMaterial()" 
                                style="padding: 8px 20px; background: #17a2b8; color: white; border: none; border-radius: 6px; cursor: pointer;">
                            추가
                        </button>
                    </div>
                </div>
                
                <input type="hidden" name="available_materials" id="available_materials_input" 
                       value='<?php echo json_encode($selected_materials, JSON_UNESCAPED_UNICODE); ?>'>
            </div>
            <div class="help-text" style="margin-top: 10px;">
                재질을 드래그하여 순서를 변경할 수 있습니다. 첫 번째 재질이 기본값으로 표시됩니다.<br>
                목록에 없는 재질은 직접 입력하여 추가할 수 있습니다.
            </div>
        </div>
        
        <!-- 재질별 추가 비용 -->
        <div class="form-group">
            <label>재질별 추가 비용 (kg당)</label>
            <div id="material-price-container">
                <?php
                // 재질별 가격 데이터 파싱
                $material_prices = [];
                if (!empty($product['material_price_data'])) {
                    $material_prices = json_decode($product['material_price_data'], true) ?: [];
                }
                
                // 선택된 재질에 대해 가격 입력 필드 표시
                foreach ($selected_materials as $material):
                    $price = $material_prices[$material] ?? 0;
                ?>
                <div class="material-price-item" data-material="<?php echo htmlspecialchars($material); ?>">
                    <label><?php echo htmlspecialchars($material); ?></label>
                    <input type="number" 
                           name="material_prices[<?php echo htmlspecialchars($material); ?>]" 
                           value="<?php echo $price; ?>" 
                           step="10"
                           placeholder="0"
                           class="form-control material-price-input">
                    <span class="help-text">원/kg (기본가격 대비 추가비용, 마이너스 가능)</span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="help-text" style="margin-top: 10px;">
                각 재질별로 kg당 추가 비용을 입력하세요. 
                예: SS400 0원(기준), SM490 +100원, SUS304 +500원(스테인리스)
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
    
    <!-- 제품 상세보기 정보 -->
    <div class="form-section">
        <h3 class="section-title">제품 상세보기 정보</h3>
        
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
        
        <div class="checkbox-group">
            <input type="checkbox" id="show_on_homepage" name="show_on_homepage" value="1"
                   <?php echo ($product['show_on_homepage'] ?? 0) ? 'checked' : ''; ?>>
            <label for="show_on_homepage">메인페이지 노출</label>
        </div>
        
        <div class="form-group">
            <label for="homepage_display_order">메인페이지 노출 순서</label>
            <input type="number" id="homepage_display_order" name="homepage_display_order" 
                   value="<?php echo htmlspecialchars($product['homepage_display_order'] ?? 0); ?>"
                   min="0" placeholder="숫자가 작을수록 먼저 표시됩니다">
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
// 재질 선택 처리 코드 제거 (material 필드 삭제로 인해 불필요)

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

// 카테고리 변경 시 처리 (단가 적용 버튼 제거됨)
// document.getElementById('category_code').addEventListener('change', function() {
//     toggleApplyPriceButton();
//     checkAndApplyRebarPrice();
// });

// 규격 입력 시 처리 (단가 적용 버튼 제거됨)
// document.getElementById('specifications').addEventListener('blur', function() {
//     checkAndApplyRebarPrice();
// });

// 페이지 로드 시 처리 (단가 적용 버튼 제거됨)
// window.addEventListener('DOMContentLoaded', function() {
//     toggleApplyPriceButton();
// });

// 페이지 로드 시 철근 제품인 경우 단가 확인
<?php if ($id > 0 && ($product['category_code'] ?? '') === 'rebar' && !$product['price']): ?>
window.addEventListener('DOMContentLoaded', function() {
    checkAndApplyRebarPrice();
});
<?php endif; ?>

// 폼 제출 시 처리
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault(); // 기본 폼 제출 방지

    // 원산지 순서 최종 업데이트
    updateOriginOrder();

    // 재질 순서 최종 업데이트
    updateMaterialOrder();
    
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
        submitBtn.textContent = '<?php echo $id > 0 ? '수정하기' : '등록하기'; ?>';
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

// 이미지 드래그 앤 드롭 기능
const dropZone = document.getElementById('imageDropZone');
const imageInput = document.getElementById('imageInput');
const descriptionTextarea = document.getElementById('description');
const uploadProgress = document.getElementById('uploadProgress');
const progressFill = document.querySelector('.progress-fill');
const progressText = document.querySelector('.progress-text');
const imagePreviewContainer = document.getElementById('imagePreviewContainer');
const imagePreviewList = document.getElementById('imagePreviewList');

// 이미지 경로 배열
let imagePaths = [];

// 페이지 로드 시 기존 이미지 경로 파싱
window.addEventListener('DOMContentLoaded', () => {
    parseExistingImages();
});

// 클릭하여 파일 선택
dropZone.addEventListener('click', () => {
    imageInput.click();
});

// 파일 선택 시
imageInput.addEventListener('change', (e) => {
    handleFiles(e.target.files);
});

// 드래그 오버
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('drag-over');
});

// 드래그 떠남
dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('drag-over');
});

// 드롭
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    handleFiles(e.dataTransfer.files);
});

// 파일 처리
function handleFiles(files) {
    const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'));
    
    if (imageFiles.length === 0) {
        alert('이미지 파일만 업로드 가능합니다.');
        return;
    }
    
    // 순차적으로 업로드
    uploadImages(imageFiles);
}

// 이미지 업로드
async function uploadImages(files) {
    let uploadedCount = 0;
    const totalFiles = files.length;
    
    // 진행 상태 표시
    uploadProgress.style.display = 'block';
    progressFill.style.width = '0%';
    progressText.textContent = `업로드 중... (0/${totalFiles})`;
    
    for (const file of files) {
        try {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('type', 'products');
            
            const response = await fetch('upload_image.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // 이미지 경로 배열에 추가
                imagePaths.push(result.url);
                
                // 미리보기 업데이트
                updateImagePreview();
                
                // textarea 업데이트
                updateDescriptionTextarea();
                
                uploadedCount++;
                
                // 진행률 업데이트
                const progress = (uploadedCount / totalFiles) * 100;
                progressFill.style.width = progress + '%';
                progressText.textContent = `업로드 중... (${uploadedCount}/${totalFiles})`;
            } else {
                alert(`업로드 실패: ${file.name} - ${result.message || '알 수 없는 오류'}`);
            }
        } catch (error) {
            console.error('Upload error:', error);
            alert(`업로드 중 오류 발생: ${file.name}`);
        }
    }
    
    // 완료 메시지
    if (uploadedCount > 0) {
        progressText.textContent = `✓ ${uploadedCount}개 이미지 업로드 완료`;
        progressFill.style.background = '#28a745';
        
        // 3초 후 숨기기
        setTimeout(() => {
            uploadProgress.style.display = 'none';
            progressFill.style.background = '#007bff';
        }, 3000);
    } else {
        uploadProgress.style.display = 'none';
    }
    
    // 입력 초기화
    imageInput.value = '';
}

// 기존 이미지 경로 파싱
function parseExistingImages() {
    const text = descriptionTextarea.value;
    if (!text) return;
    
    // 줄바꿈으로 분리하고 이미지 확장자를 가진 경로만 필터링
    const lines = text.split('\n');
    const imageExtensions = /\.(jpg|jpeg|png|gif|webp)$/i;
    
    imagePaths = lines.filter(line => {
        const trimmedLine = line.trim();
        return trimmedLine && imageExtensions.test(trimmedLine);
    });
    
    // 이미지가 있으면 미리보기 표시
    if (imagePaths.length > 0) {
        updateImagePreview();
    }
    
    // 나머지 텍스트는 유지
    const nonImageLines = lines.filter(line => {
        const trimmedLine = line.trim();
        return trimmedLine && !imageExtensions.test(trimmedLine);
    });
    
    // textarea에 이미지가 아닌 텍스트만 표시
    const nonImageText = nonImageLines.join('\n');
    if (nonImageText !== text) {
        descriptionTextarea.value = nonImageText;
        updateDescriptionTextarea();
    }
}

// 이미지 미리보기 업데이트
function updateImagePreview() {
    if (imagePaths.length === 0) {
        imagePreviewContainer.style.display = 'none';
        return;
    }
    
    imagePreviewContainer.style.display = 'block';
    imagePreviewList.innerHTML = '';
    
    imagePaths.forEach((path, index) => {
        const item = document.createElement('div');
        item.className = 'image-preview-item';
        item.draggable = true;
        item.dataset.index = index;
        
        const img = document.createElement('img');
        img.src = path;
        img.alt = `이미지 ${index + 1}`;
        
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'delete-btn';
        deleteBtn.innerHTML = '×';
        deleteBtn.onclick = () => removeImage(index);
        
        item.appendChild(img);
        item.appendChild(deleteBtn);
        
        // 드래그 이벤트 추가
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
        item.addEventListener('dragover', handleDragOver);
        item.addEventListener('drop', handleDrop);
        item.addEventListener('dragenter', handleDragEnter);
        item.addEventListener('dragleave', handleDragLeave);
        
        imagePreviewList.appendChild(item);
    });
}

// 이미지 제거
function removeImage(index) {
    imagePaths.splice(index, 1);
    updateImagePreview();
    updateDescriptionTextarea();
}

// textarea 업데이트
function updateDescriptionTextarea() {
    const text = descriptionTextarea.value;
    const lines = text.split('\n');
    const imageExtensions = /\.(jpg|jpeg|png|gif|webp)$/i;
    
    // 이미지가 아닌 텍스트만 추출
    const nonImageLines = lines.filter(line => {
        const trimmedLine = line.trim();
        return trimmedLine && !imageExtensions.test(trimmedLine);
    });
    
    // 텍스트와 이미지 경로 합치기
    let finalText = nonImageLines.join('\n');
    if (imagePaths.length > 0) {
        if (finalText) {
            finalText += '\n';
        }
        finalText += imagePaths.join('\n');
    }
    
    descriptionTextarea.value = finalText;
}

// 드래그 앤 드롭 핸들러
let draggedElement = null;
let draggedIndex = null;

function handleDragStart(e) {
    draggedElement = this;
    draggedIndex = parseInt(this.dataset.index);
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    
    // 모든 drag-over 클래스 제거
    document.querySelectorAll('.image-preview-item').forEach(item => {
        item.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
}

function handleDragEnter(e) {
    if (this !== draggedElement) {
        this.classList.add('drag-over');
    }
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    
    if (draggedElement !== this) {
        const dropIndex = parseInt(this.dataset.index);
        
        // 배열에서 아이템 이동
        const draggedPath = imagePaths[draggedIndex];
        imagePaths.splice(draggedIndex, 1);
        
        if (draggedIndex < dropIndex) {
            imagePaths.splice(dropIndex - 1, 0, draggedPath);
        } else {
            imagePaths.splice(dropIndex, 0, draggedPath);
        }
        
        // 미리보기와 textarea 업데이트
        updateImagePreview();
        updateDescriptionTextarea();
    }
    
    return false;
}

// SortableJS 초기화
document.addEventListener('DOMContentLoaded', function() {
    // 페이지 로드 시 카테고리에 맞는 재질 옵션 업데이트
    <?php if ($id > 0 && !empty($product['category_code'])): ?>
    setTimeout(function() {
        updateMaterialOptions();
    }, 100);
    <?php endif; ?>
    
    // 원산지 Sortable
    const originEl = document.getElementById('origin-sortable');
    if (originEl) {
        new Sortable(originEl, {
            animation: 150,
            ghostClass: 'ghost',
            onEnd: function(evt) {
                updateOriginOrder();
            }
        });
    }
    
    // 재질 Sortable
    const materialEl = document.getElementById('material-sortable');
    if (materialEl) {
        new Sortable(materialEl, {
            animation: 150,
            ghostClass: 'ghost',
            onEnd: function(evt) {
                updateMaterialOrder();
            }
        });
    }
});

// 원산지 순서 업데이트
function updateOriginOrder() {
    const container = document.getElementById('origin-sortable');
    const origins = Array.from(container.querySelectorAll('.origin-item')).map(item => 
        item.getAttribute('data-origin')
    );
    document.getElementById('available_origins_input').value = JSON.stringify(origins);
}

// 원산지 추가
function addOrigin(origin) {
    const container = document.getElementById('origin-sortable');
    const div = document.createElement('div');
    div.className = 'origin-item';
    div.setAttribute('data-origin', origin);
    div.innerHTML = `${origin}<span class="origin-item-remove" onclick="removeOrigin('${origin}')">×</span>`;
    container.appendChild(div);
    
    // 추가 버튼 숨기기
    event.target.remove();
    
    // 가격 입력 필드 추가
    addOriginPriceField(origin);
    
    updateOriginOrder();
}

// 원산지 제거
function removeOrigin(origin) {
    const container = document.getElementById('origin-sortable');
    const item = container.querySelector(`[data-origin="${origin}"]`);
    
    if (item) {
        item.remove();
        
        // 추가 가능한 목록에 다시 추가
        const availableContainer = document.querySelector('.origin-available');
        const span = document.createElement('span');
        span.className = 'origin-add';
        span.setAttribute('onclick', `addOrigin('${origin}')`);
        span.innerHTML = `${origin} +`;
        availableContainer.appendChild(span);
        
        // 가격 입력 필드 제거
        removeOriginPriceField(origin);
        
        updateOriginOrder();
    }
}

// 원산지별 가격 필드 추가
function addOriginPriceField(origin) {
    const container = document.getElementById('origin-price-container');
    const div = document.createElement('div');
    div.className = 'origin-price-item';
    div.setAttribute('data-origin', origin);
    div.innerHTML = `
        <label>${origin}</label>
        <input type="number" 
               name="origin_prices[${origin}]" 
               value="0" 
               step="10"
               placeholder="0"
               class="form-control origin-price-input">
        <span class="help-text">원/kg (기본가격 대비 추가비용, 마이너스 가능)</span>
    `;
    container.appendChild(div);
}

// 원산지별 가격 필드 제거
function removeOriginPriceField(origin) {
    const container = document.getElementById('origin-price-container');
    const item = container.querySelector(`[data-origin="${origin}"]`);
    if (item) {
        item.remove();
    }
}

// 재질 관리 함수들
// 재질 순서 업데이트
function updateMaterialOrder() {
    const container = document.getElementById('material-sortable');
    const materials = Array.from(container.querySelectorAll('.material-item')).map(item => 
        item.getAttribute('data-material')
    );
    document.getElementById('available_materials_input').value = JSON.stringify(materials);
}

// 카테고리 변경 시 재질 옵션 업데이트
function updateMaterialOptions(event) {
    const categoryCode = document.getElementById('category_code').value;
    const materialSortable = document.getElementById('material-sortable');
    const materialAvailable = document.querySelector('.material-available');
    
    // 재질 목록 정의
    let materialOptions = [];
    if (categoryCode === 'rebar') {
        // 철근 재질
        materialOptions = ["SD300", "SD400", "SD400S", "SD500", "SD600", "SD600S"];
    } else {
        // 기타 철강재 재질
        materialOptions = [
            "SS400", "SM490", "SM490A", "SM490B", "SM520", "SM570",
            "SUS304", "SUS316", "SUS430", "S45C", "SCM440", 
            "SPHC", "SS400/A36", "SM45C"
        ];
    }
    
    // 현재 선택된 재질 가져오기
    const selectedMaterials = Array.from(materialSortable.querySelectorAll('.material-item')).map(item => 
        item.getAttribute('data-material')
    );
    
    // 선택된 재질 중 새 옵션에 없는 것은 유지 (커스텀 재질 보호)
    // 단, 카테고리 변경 시에만 제거
    const isInitialLoad = !event || event.type !== 'change';
    if (!isInitialLoad) {
        materialSortable.querySelectorAll('.material-item').forEach(item => {
            const material = item.getAttribute('data-material');
            if (!materialOptions.includes(material)) {
                item.remove();
            }
        });
    }
    
    // 사용 가능한 재질 목록 재구성
    materialAvailable.innerHTML = '';
    
    // 현재 선택된 재질 다시 가져오기 (커스텀 재질 포함)
    const currentSelected = Array.from(materialSortable.querySelectorAll('.material-item')).map(item => 
        item.getAttribute('data-material')
    );
    
    // 카테고리별 기본 재질 중 선택되지 않은 것만 표시
    materialOptions.forEach(material => {
        if (!currentSelected.includes(material)) {
            const span = document.createElement('span');
            span.className = 'material-add';
            span.setAttribute('onclick', `addMaterial('${material}')`);
            span.innerHTML = `${material} +`;
            materialAvailable.appendChild(span);
        }
    });
    
    // 재질별 가격 필드는 제거하지 않음 (커스텀 재질 보호)
    // 새로 추가된 재질의 가격 필드만 동적으로 추가됨
    
    updateMaterialOrder();
}

// 재질 추가
function addMaterial(material) {
    const container = document.getElementById('material-sortable');
    const div = document.createElement('div');
    div.className = 'material-item';
    div.setAttribute('data-material', material);
    div.innerHTML = `${material}<span class="material-item-remove" onclick="removeMaterial('${material}')">×</span>`;
    container.appendChild(div);
    
    // 추가 버튼 숨기기
    event.target.remove();
    
    // 가격 입력 필드 추가
    addMaterialPriceField(material);
    
    updateMaterialOrder();
}

// 사용자 정의 재질 추가
function addCustomMaterial() {
    const input = document.getElementById('custom_material_input');
    const material = input.value.trim().toUpperCase(); // 대문자로 변환
    
    if (!material) {
        alert('재질명을 입력해주세요.');
        return;
    }
    
    // 이미 존재하는 재질인지 확인
    const existingMaterials = Array.from(document.querySelectorAll('#material-sortable .material-item')).map(item => 
        item.getAttribute('data-material')
    );
    
    if (existingMaterials.includes(material)) {
        alert('이미 추가된 재질입니다.');
        return;
    }
    
    // 재질 추가
    const container = document.getElementById('material-sortable');
    const div = document.createElement('div');
    div.className = 'material-item';
    div.setAttribute('data-material', material);
    div.innerHTML = `${material}<span class="material-item-remove" onclick="removeMaterial('${material}')">×</span>`;
    container.appendChild(div);
    
    // 가격 입력 필드 추가
    addMaterialPriceField(material);
    
    // 입력 필드 초기화
    input.value = '';
    
    updateMaterialOrder();
}

// Enter 키로 재질 추가
document.addEventListener('DOMContentLoaded', function() {
    const customInput = document.getElementById('custom_material_input');
    if (customInput) {
        customInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCustomMaterial();
            }
        });
    }
});

// 재질 제거
function removeMaterial(material) {
    const container = document.getElementById('material-sortable');
    const item = container.querySelector(`[data-material="${material}"]`);
    
    if (item) {
        item.remove();
        
        // 추가 가능한 목록에 다시 추가
        const availableContainer = document.querySelector('.material-available');
        const span = document.createElement('span');
        span.className = 'material-add';
        span.setAttribute('onclick', `addMaterial('${material}')`);
        span.innerHTML = `${material} +`;
        availableContainer.appendChild(span);
        
        // 가격 입력 필드 제거
        removeMaterialPriceField(material);
        
        updateMaterialOrder();
    }
}

// 재질별 가격 필드 추가
function addMaterialPriceField(material) {
    const container = document.getElementById('material-price-container');
    const div = document.createElement('div');
    div.className = 'material-price-item';
    div.setAttribute('data-material', material);
    div.innerHTML = `
        <label>${material}</label>
        <input type="number" 
               name="material_prices[${material}]" 
               value="0" 
               step="10"
               placeholder="0"
               class="form-control material-price-input">
        <span class="help-text">원/kg (기본가격 대비 추가비용, 마이너스 가능)</span>
    `;
    container.appendChild(div);
}

// 재질별 가격 필드 제거
function removeMaterialPriceField(material) {
    const container = document.getElementById('material-price-container');
    const item = container.querySelector(`[data-material="${material}"]`);
    if (item) {
        item.remove();
    }
}

// 제품명에서 규격 자동 추출
function extractSpecificationFromProductName() {
    const productNameInput = document.getElementById('product_name');
    const specificationInput = document.getElementById('specification');
    const specificationsInput = document.getElementById('specifications');

    if (!productNameInput || !specificationInput) return;

    productNameInput.addEventListener('input', function() {
        const productName = this.value;

        // 규격 패턴 매칭 (숫자*숫자 또는 숫자x숫자 패턴)
        const specPattern = /([0-9]+[\*xX×][0-9]+(?:[\*xX×][0-9]+)*(?:[\*xX×][0-9]+)*)/g;
        const matches = productName.match(specPattern);

        if (matches && matches.length > 0) {
            const specification = matches[0];

            // 규격 필드가 비어있을 때만 자동 입력
            if (!specificationInput.value || specificationInput.value === '') {
                specificationInput.value = specification;
                // specifications 필드도 동일하게 업데이트
                if (specificationsInput && !specificationsInput.value) {
                    specificationsInput.value = specification;
                }
            }
        }
    });

    // 표시용 규격 변경 시 관리용 규격도 동기화
    specificationInput.addEventListener('input', function() {
        if (specificationsInput && !specificationsInput.value) {
            specificationsInput.value = this.value;
        }
    });
}

// DOM이 로드되면 실행
document.addEventListener('DOMContentLoaded', function() {
    extractSpecificationFromProductName();
});
</script>

<!-- SortableJS for drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<?php include 'admin_tail.php'; ?>