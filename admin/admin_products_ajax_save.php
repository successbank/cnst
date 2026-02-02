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
    $specification = trim($_POST['specification'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? (float)$_POST['price'] : null;
    $min_price = isset($_POST['min_price']) && $_POST['min_price'] !== '' ? (float)$_POST['min_price'] : null;
    $max_price = isset($_POST['max_price']) && $_POST['max_price'] !== '' ? (float)$_POST['max_price'] : null;

    // 철근 수동 가격 (번들 기준)
    $rebar_manual_min_price = isset($_POST['rebar_manual_min_price']) && $_POST['rebar_manual_min_price'] !== '' ? (int)$_POST['rebar_manual_min_price'] : null;
    $rebar_manual_max_price = isset($_POST['rebar_manual_max_price']) && $_POST['rebar_manual_max_price'] !== '' ? (int)$_POST['rebar_manual_max_price'] : null;

    $unit = trim($_POST['unit'] ?? '');
    $min_order_qty = (int)($_POST['min_order_qty'] ?? 1);
    $stock_status = $_POST['stock_status'] ?? 'in_stock';
    $base_length = (int)($_POST['base_length'] ?? 6);

    // 길이 제한 값들
    $min_length = isset($_POST['min_length']) && $_POST['min_length'] !== '' ? (float)$_POST['min_length'] : null;
    $max_length = isset($_POST['max_length']) && $_POST['max_length'] !== '' ? (float)$_POST['max_length'] : null;
    $standard_length = isset($_POST['standard_length']) && $_POST['standard_length'] !== '' ? (float)$_POST['standard_length'] : null;

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
    
    // 원산지별 가격 데이터 처리
    $origin_prices = $_POST['origin_prices'] ?? [];
    $origin_price_data = json_encode($origin_prices, JSON_UNESCAPED_UNICODE);
    
    // 재질 선택 처리 - JSON 형식으로 받음
    $available_materials_json = $_POST['available_materials'] ?? '[]';
    $available_materials = json_decode($available_materials_json, true);
    if (!empty($available_materials)) {
        // 첫 번째 재질을 기본값으로 설정
        $material = $available_materials[0];
    }
    $available_materials_json = json_encode($available_materials, JSON_UNESCAPED_UNICODE);
    
    // 재질별 가격 데이터 처리
    $material_prices = $_POST['material_prices'] ?? [];
    $material_price_data = json_encode($material_prices, JSON_UNESCAPED_UNICODE);
    
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

    // 계산 관련 필드
    $calculation_type = $_POST['calculation_type'] ?? 'linear';
    $has_calculator = isset($_POST['has_calculator']) ? 1 : 0;
    $unit_weight_data_raw = $_POST['unit_weight_data'] ?? '';
    $unit_weight_data = $unit_weight_data_raw !== '' ? $unit_weight_data_raw : null;

    // 메인페이지 노출 필드
    $show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;
    $homepage_display_order = isset($_POST['homepage_display_order']) ? (int)$_POST['homepage_display_order'] : 0;
    
    // 유효성 검사
    $errors = [];
    if (!$category_code) $errors[] = "카테고리를 선택해주세요.";
    if (!$product_name) $errors[] = "제품명을 입력해주세요.";
    
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
        'min_length' => false,
        'max_length' => false,
        'standard_length' => false,
        'specification' => false,
        'detailed_description' => false,
        'key_features' => false,
        'technical_specs' => false,
        'applications' => false,
        'certifications' => false,
        'brochure_url' => false,
        'show_details' => false,
        'details_updated_at' => false,
        'origin_price_data' => false,
        'material_price_data' => false,
        'available_materials' => false,
        'quality_cert' => false,
        'product_features' => false,
        'show_on_homepage' => false,
        'homepage_display_order' => false,
        'rebar_manual_min_price' => false,
        'rebar_manual_max_price' => false,
        'calculation_type' => false,
        'has_calculator' => false,
        'unit_weight_data' => false
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

        // specification 필드는 선택적으로 처리 (컬럼이 있는 경우에만)
        if ($columns_check['specification']) {
            $sql .= ", specification = :specification";
            $params[':specification'] = $specification;
        }
        
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
        if ($columns_check['origin_price_data']) {
            $sql .= ", origin_price_data = :origin_price_data";
            $params[':origin_price_data'] = $origin_price_data;
        }
        if ($columns_check['material_price_data']) {
            $sql .= ", material_price_data = :material_price_data";
            $params[':material_price_data'] = $material_price_data;
        }
        if ($columns_check['available_materials']) {
            $sql .= ", available_materials = :available_materials";
            $params[':available_materials'] = $available_materials_json;
        }
        if ($columns_check['min_length']) {
            $sql .= ", min_length = :min_length";
            $params[':min_length'] = $min_length;
        }
        if ($columns_check['max_length']) {
            $sql .= ", max_length = :max_length";
            $params[':max_length'] = $max_length;
        }
        if ($columns_check['standard_length']) {
            $sql .= ", standard_length = :standard_length";
            $params[':standard_length'] = $standard_length;
        }
        if ($columns_check['quality_cert']) {
            $sql .= ", quality_cert = :quality_cert";
            $params[':quality_cert'] = $quality_cert;
        }
        if ($columns_check['product_features']) {
            $sql .= ", product_features = :product_features";
            $params[':product_features'] = $product_features;
        }
        if ($columns_check['show_on_homepage']) {
            $sql .= ", show_on_homepage = :show_on_homepage";
            $params[':show_on_homepage'] = $show_on_homepage;
        }
        if ($columns_check['homepage_display_order']) {
            $sql .= ", homepage_display_order = :homepage_display_order";
            $params[':homepage_display_order'] = $homepage_display_order;
        }
        if ($columns_check['rebar_manual_min_price']) {
            $sql .= ", rebar_manual_min_price = :rebar_manual_min_price";
            $params[':rebar_manual_min_price'] = $rebar_manual_min_price;
        }
        if ($columns_check['rebar_manual_max_price']) {
            $sql .= ", rebar_manual_max_price = :rebar_manual_max_price";
            $params[':rebar_manual_max_price'] = $rebar_manual_max_price;
        }
        if ($columns_check['calculation_type']) {
            $sql .= ", calculation_type = :calculation_type";
            $params[':calculation_type'] = $calculation_type;
        }
        if ($columns_check['has_calculator']) {
            $sql .= ", has_calculator = :has_calculator";
            $params[':has_calculator'] = $has_calculator;
        }
        if ($columns_check['unit_weight_data']) {
            $sql .= ", unit_weight_data = :unit_weight_data";
            $params[':unit_weight_data'] = $unit_weight_data;
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
        if ($columns_check['origin_price_data']) {
            $columns[] = 'origin_price_data';
            $values[] = ':origin_price_data';
            $params[':origin_price_data'] = $origin_price_data;
        }
        if ($columns_check['material_price_data']) {
            $columns[] = 'material_price_data';
            $values[] = ':material_price_data';
            $params[':material_price_data'] = $material_price_data;
        }
        if ($columns_check['available_materials']) {
            $columns[] = 'available_materials';
            $values[] = ':available_materials';
            $params[':available_materials'] = $available_materials_json;
        }
        if ($columns_check['min_length']) {
            $columns[] = 'min_length';
            $values[] = ':min_length';
            $params[':min_length'] = $min_length;
        }
        if ($columns_check['max_length']) {
            $columns[] = 'max_length';
            $values[] = ':max_length';
            $params[':max_length'] = $max_length;
        }
        if ($columns_check['standard_length']) {
            $columns[] = 'standard_length';
            $values[] = ':standard_length';
            $params[':standard_length'] = $standard_length;
        }
        if ($columns_check['specification']) {
            $columns[] = 'specification';
            $values[] = ':specification';
            $params[':specification'] = $specification;
        }
        if ($columns_check['quality_cert']) {
            $columns[] = 'quality_cert';
            $values[] = ':quality_cert';
            $params[':quality_cert'] = $quality_cert;
        }
        if ($columns_check['product_features']) {
            $columns[] = 'product_features';
            $values[] = ':product_features';
            $params[':product_features'] = $product_features;
        }
        if ($columns_check['show_on_homepage']) {
            $columns[] = 'show_on_homepage';
            $values[] = ':show_on_homepage';
            $params[':show_on_homepage'] = $show_on_homepage;
        }
        if ($columns_check['homepage_display_order']) {
            $columns[] = 'homepage_display_order';
            $values[] = ':homepage_display_order';
            $params[':homepage_display_order'] = $homepage_display_order;
        }
        if ($columns_check['rebar_manual_min_price']) {
            $columns[] = 'rebar_manual_min_price';
            $values[] = ':rebar_manual_min_price';
            $params[':rebar_manual_min_price'] = $rebar_manual_min_price;
        }
        if ($columns_check['rebar_manual_max_price']) {
            $columns[] = 'rebar_manual_max_price';
            $values[] = ':rebar_manual_max_price';
            $params[':rebar_manual_max_price'] = $rebar_manual_max_price;
        }
        if ($columns_check['calculation_type']) {
            $columns[] = 'calculation_type';
            $values[] = ':calculation_type';
            $params[':calculation_type'] = $calculation_type;
        }
        if ($columns_check['has_calculator']) {
            $columns[] = 'has_calculator';
            $values[] = ':has_calculator';
            $params[':has_calculator'] = $has_calculator;
        }
        if ($columns_check['unit_weight_data']) {
            $columns[] = 'unit_weight_data';
            $values[] = ':unit_weight_data';
            $params[':unit_weight_data'] = $unit_weight_data;
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