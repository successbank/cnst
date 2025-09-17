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
    $base_length = (int)($_POST['base_length'] ?? 6);
    $features = trim($_POST['features'] ?? '');
    $dimensions = trim($_POST['dimensions'] ?? '');
    $weight = trim($_POST['weight'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $origin = trim($_POST['origin'] ?? '');
    $delivery_info = trim($_POST['delivery_info'] ?? '');
    
    // 원산지 선택 처리 - JSON 형식으로 받음
    $available_origins_json = $_POST['available_origins'] ?? '[]';
    $available_origins = json_decode($available_origins_json, true);
    if (!empty($available_origins)) {
        // 첫 번째 원산지를 기본값으로 설정
        $origin = $available_origins[0];
    }
    $available_origins_json = json_encode($available_origins, JSON_UNESCAPED_UNICODE);
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
    
    // 컬럼 존재 여부 확인
    $columns_check = [
        'base_length' => false,
        'min_price' => false,
        'max_price' => false,
        'detailed_description' => false,
        'key_features' => false,
        'technical_specs' => false,
        'applications' => false,
        'certifications' => false,
        'brochure_url' => false,
        'show_details' => false,
        'details_updated_at' => false
    ];
    
    foreach ($columns_check as $column => &$exists) {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE '$column'");
        $exists = $stmt->fetch() !== false;
    }
    
    if ($id > 0) {
        // 수정
        $sql = "UPDATE products SET 
                category_code = :category_code,
                product_name = :product_name,
                product_code = :product_code,
                specifications = :specifications,
                description = :description,
                price = :price,
                unit = :unit,
                min_order_qty = :min_order_qty,
                stock_status = :stock_status,
                features = :features,
                dimensions = :dimensions,
                weight = :weight,
                material = :material,
                manufacturer = :manufacturer,
                origin = :origin,
                available_origins = :available_origins,
                delivery_info = :delivery_info,
                is_featured = :is_featured,
                is_active = :is_active";
        
        $params = [
            ':category_code' => $category_code,
            ':product_name' => $product_name,
            ':product_code' => $product_code,
            ':specifications' => $specifications,
            ':description' => $description,
            ':price' => $price,
            ':unit' => $unit,
            ':min_order_qty' => $min_order_qty,
            ':stock_status' => $stock_status,
            ':features' => $features,
            ':dimensions' => $dimensions,
            ':weight' => $weight,
            ':material' => $material,
            ':manufacturer' => $manufacturer,
            ':origin' => $origin,
            ':available_origins' => $available_origins_json,
            ':delivery_info' => $delivery_info,
            ':is_featured' => $is_featured,
            ':is_active' => $is_active
        ];
        
        // 선택적 컬럼 추가
        if ($columns_check['base_length']) {
            $sql .= ", base_length = :base_length";
            $params[':base_length'] = $base_length;
        }
        if ($columns_check['min_price']) {
            $sql .= ", min_price = :min_price";
            $params[':min_price'] = $min_price;
        }
        if ($columns_check['max_price']) {
            $sql .= ", max_price = :max_price";
            $params[':max_price'] = $max_price;
        }
        if ($columns_check['detailed_description']) {
            $sql .= ", detailed_description = :detailed_description";
            $params[':detailed_description'] = $detailed_description;
        }
        if ($columns_check['key_features']) {
            $sql .= ", key_features = :key_features";
            $params[':key_features'] = $key_features;
        }
        if ($columns_check['technical_specs']) {
            $sql .= ", technical_specs = :technical_specs";
            $params[':technical_specs'] = $technical_specs;
        }
        if ($columns_check['applications']) {
            $sql .= ", applications = :applications";
            $params[':applications'] = $applications;
        }
        if ($columns_check['certifications']) {
            $sql .= ", certifications = :certifications";
            $params[':certifications'] = $certifications;
        }
        if ($columns_check['brochure_url']) {
            $sql .= ", brochure_url = :brochure_url";
            $params[':brochure_url'] = $brochure_url;
        }
        if ($columns_check['show_details']) {
            $sql .= ", show_details = :show_details";
            $params[':show_details'] = $show_details;
        }
        if ($columns_check['details_updated_at']) {
            $sql .= ", details_updated_at = NOW()";
        }
        
        $sql .= " WHERE id = :id";
        $params[':id'] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $response['success'] = true;
        $response['message'] = '제품이 성공적으로 수정되었습니다.';
    } else {
        // 신규 등록
        $columns = [
            'category_code', 'product_name', 'product_code',
            'specifications', 'description', 'price',
            'unit', 'min_order_qty', 'stock_status',
            'features', 'dimensions', 'weight',
            'material', 'manufacturer', 'origin', 'available_origins',
            'delivery_info', 'is_featured', 'is_active'
        ];
        
        $values = [
            ':category_code', ':product_name', ':product_code',
            ':specifications', ':description', ':price',
            ':unit', ':min_order_qty', ':stock_status',
            ':features', ':dimensions', ':weight',
            ':material', ':manufacturer', ':origin', ':available_origins',
            ':delivery_info', ':is_featured', ':is_active'
        ];
        
        $params = [
            ':category_code' => $category_code,
            ':product_name' => $product_name,
            ':product_code' => $product_code,
            ':specifications' => $specifications,
            ':description' => $description,
            ':price' => $price,
            ':unit' => $unit,
            ':min_order_qty' => $min_order_qty,
            ':stock_status' => $stock_status,
            ':features' => $features,
            ':dimensions' => $dimensions,
            ':weight' => $weight,
            ':material' => $material,
            ':manufacturer' => $manufacturer,
            ':origin' => $origin,
            ':available_origins' => $available_origins_json,
            ':delivery_info' => $delivery_info,
            ':is_featured' => $is_featured,
            ':is_active' => $is_active
        ];
        
        // 선택적 컬럼 추가
        if ($columns_check['base_length']) {
            $columns[] = 'base_length';
            $values[] = ':base_length';
            $params[':base_length'] = $base_length;
        }
        if ($columns_check['min_price']) {
            $columns[] = 'min_price';
            $values[] = ':min_price';
            $params[':min_price'] = $min_price;
        }
        if ($columns_check['max_price']) {
            $columns[] = 'max_price';
            $values[] = ':max_price';
            $params[':max_price'] = $max_price;
        }
        if ($columns_check['detailed_description']) {
            $columns[] = 'detailed_description';
            $values[] = ':detailed_description';
            $params[':detailed_description'] = $detailed_description;
        }
        if ($columns_check['key_features']) {
            $columns[] = 'key_features';
            $values[] = ':key_features';
            $params[':key_features'] = $key_features;
        }
        if ($columns_check['technical_specs']) {
            $columns[] = 'technical_specs';
            $values[] = ':technical_specs';
            $params[':technical_specs'] = $technical_specs;
        }
        if ($columns_check['applications']) {
            $columns[] = 'applications';
            $values[] = ':applications';
            $params[':applications'] = $applications;
        }
        if ($columns_check['certifications']) {
            $columns[] = 'certifications';
            $values[] = ':certifications';
            $params[':certifications'] = $certifications;
        }
        if ($columns_check['brochure_url']) {
            $columns[] = 'brochure_url';
            $values[] = ':brochure_url';
            $params[':brochure_url'] = $brochure_url;
        }
        if ($columns_check['show_details']) {
            $columns[] = 'show_details';
            $values[] = ':show_details';
            $params[':show_details'] = $show_details;
        }
        
        $sql = "INSERT INTO products (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
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