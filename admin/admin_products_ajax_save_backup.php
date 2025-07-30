<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // ID 확인
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    // 데이터 수집
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
    
    // 유효성 검사
    $errors = [];
    if (!$category_code) $errors[] = "카테고리를 선택해주세요.";
    if (!$product_name) $errors[] = "제품명을 입력해주세요.";
    if (!$specifications) $errors[] = "규격을 입력해주세요.";
    
    if ($errors) {
        $response['message'] = implode(', ', $errors);
        echo json_encode($response);
        exit;
    }
    
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
                    delivery_info = ?, is_featured = ?, is_active = ?
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
                    delivery_info = ?, is_featured = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $category_code, $product_name, $product_code,
                $specifications, $description, $price,
                $unit, $min_order_qty, $stock_status,
                $base_length, $features, $dimensions, $weight,
                $material, $manufacturer, $origin,
                $delivery_info, $is_featured, $is_active,
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
        
        $response['success'] = true;
        $response['message'] = '제품이 성공적으로 수정되었습니다.';
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
        
        $newId = $pdo->lastInsertId();
        $response['success'] = true;
        $response['message'] = '제품이 성공적으로 등록되었습니다.';
        $response['newId'] = $newId;
    }
    
} catch (PDOException $e) {
    $response['message'] = '저장 중 오류가 발생했습니다: ' . $e->getMessage();
}

echo json_encode($response);
?>