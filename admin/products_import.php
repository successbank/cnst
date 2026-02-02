<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

/**
 * 쉼표로 구분된 문자열을 JSON 배열로 변환
 * 예: "SS275,SS400" → ["SS275","SS400"]
 */
function commaSeparatedToJsonArray($str) {
    if (empty(trim($str))) return null;
    $arr = array_map('trim', explode(',', $str));
    $arr = array_filter($arr, function($v) { return $v !== ''; });
    return !empty($arr) ? json_encode(array_values($arr), JSON_UNESCAPED_UNICODE) : null;
}

/**
 * 세미콜론 형식을 JSON 객체로 변환
 * 예: "SS400:100;SM490A:200" → {"SS400":"100","SM490A":"200"}
 */
function semicolonFormatToJsonObject($str) {
    if (empty(trim($str))) return null;
    $obj = [];
    $pairs = explode(';', $str);
    foreach ($pairs as $pair) {
        $pair = trim($pair);
        if (empty($pair)) continue;
        $parts = explode(':', $pair, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if (!empty($key)) {
                $obj[$key] = $value;
            }
        }
    }
    return !empty($obj) ? json_encode($obj, JSON_UNESCAPED_UNICODE) : null;
}

$message = '';
$errors = [];
$results = [
    'updated' => 0,
    'created' => 0,
    'deleted' => 0,
    'errors' => []
];

// 삭제 건수 제한
$MAX_DELETE_COUNT = 50;

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 리턴 카테고리 (업로드 후 돌아갈 카테고리)
$return_category = $_GET['category'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $uploadedFile = $_FILES['excel_file'];

    // CSRF 토큰 검증
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "보안 토큰이 유효하지 않습니다. 페이지를 새로고침 후 다시 시도하세요.";
    }

    // 리턴 카테고리 저장
    $return_category = $_POST['return_category'] ?? '';

    // 파일 검증
    if (empty($errors) && $uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "파일 업로드 실패";
    } elseif (empty($errors)) {
        // 파일 확장자 확인
        $fileExt = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, ['csv', 'txt'])) {
            $errors[] = "CSV 파일만 업로드 가능합니다.";
        }

        // MIME 타입 검증 (파일 내용 기반)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($uploadedFile['tmp_name']);
        $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        if (!in_array($mimeType, $allowedMimes)) {
            $errors[] = "허용되지 않은 파일 형식입니다. (감지된 타입: $mimeType)";
        }

        // 파일 크기 제한 (10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($uploadedFile['size'] > $maxSize) {
            $errors[] = "파일 크기가 너무 큽니다. (최대 10MB)";
        }
    }
    
    if (empty($errors)) {
        // CSV 파일 읽기
        $handle = fopen($uploadedFile['tmp_name'], 'r');
        
        // BOM 제거
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        
        // 헤더 읽기
        $headers = fgetcsv($handle);
        
        // 컬럼 수 확인 (하위 호환성: 23개, 35개 또는 36개 컬럼 지원)
        $columnCount = count($headers);
        $isExtendedFormat = ($columnCount >= 35);
        $hasDeleteColumn = ($columnCount >= 36);

        // 삭제 대상 수집 (건수 제한 검증용)
        $deleteTargets = [];

        // 데이터 처리
        $lineNumber = 2; // 헤더 다음 줄부터
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                // 기본 데이터 매핑 (공통)
                $id = $data[0] ?? null;
                $category_code = trim($data[1] ?? '');
                $product_name = trim($data[3] ?? '');
                $product_code = trim($data[4] ?? '');
                $product_code = ($product_code !== '') ? $product_code : null;  // 빈 값 → NULL (UNIQUE 제약 대응)
                $specifications = trim($data[5] ?? '');
                $description = trim($data[6] ?? '');
                $description = str_replace(['{{NEWLINE}}', '{{BR}}'], ["\n", "\n"], $description);  // 개행 복원
                $price = $data[7] !== '' ? $data[7] : null;

                if ($isExtendedFormat) {
                    // 35개 컬럼 형식
                    $price_unit = trim($data[8] ?? 'kg');
                    $unit = trim($data[9] ?? 'TON');
                    $min_order_qty = $data[10] !== '' ? (int)$data[10] : 1;
                    $stock_status = trim($data[11] ?? 'in_stock');
                    $origin = trim($data[12] ?? '');
                    $manufacturer = trim($data[13] ?? '');
                    $dimensions = trim($data[14] ?? '');
                    $weight = trim($data[15] ?? '');
                    $material = trim($data[16] ?? '');
                    $specification_weight = $data[17] !== '' ? (float)$data[17] : null;
                    $calculation_type = trim($data[18] ?? 'linear');
                    $has_calculator = strtoupper(trim($data[19] ?? 'N')) === 'Y' ? 1 : 0;
                    $available_materials = commaSeparatedToJsonArray($data[20] ?? '');
                    $available_origins = commaSeparatedToJsonArray($data[21] ?? '');
                    $material_price_data = semicolonFormatToJsonObject($data[22] ?? '');
                    $origin_price_data = semicolonFormatToJsonObject($data[23] ?? '');
                    $features = trim($data[24] ?? '');
                    $delivery_info = trim($data[25] ?? '');
                    $is_featured = strtoupper(trim($data[26] ?? 'N')) === 'Y' ? 1 : 0;
                    $is_active = strtoupper(trim($data[27] ?? 'Y')) === 'Y' ? 1 : 0;
                    $base_length = $data[28] !== '' ? (float)$data[28] : 6;
                    $min_length = $data[29] !== '' ? (float)$data[29] : null;
                    $max_length = $data[30] !== '' ? (float)$data[30] : null;
                    $standard_length = trim($data[31] ?? '');
                    $standard_length = ($standard_length !== '' && is_numeric($standard_length)) ? (float)$standard_length : null;  // 빈 값 → NULL (decimal 대응)
                    $show_on_homepage = strtoupper(trim($data[32] ?? 'Y')) === 'Y' ? 1 : 0;
                } else {
                    // 23개 컬럼 형식 (하위 호환성)
                    $price_unit = 'kg';
                    $unit = trim($data[8] ?? 'TON');
                    $min_order_qty = $data[9] !== '' ? (int)$data[9] : 1;
                    $stock_status = trim($data[10] ?? 'in_stock');
                    $origin = trim($data[11] ?? '');
                    $manufacturer = trim($data[12] ?? '');
                    $dimensions = trim($data[13] ?? '');
                    $weight = trim($data[14] ?? '');
                    $material = trim($data[15] ?? '');
                    $specification_weight = null;
                    $calculation_type = 'linear';
                    $has_calculator = 0;
                    $available_materials = null;
                    $available_origins = null;
                    $material_price_data = null;
                    $origin_price_data = null;
                    $features = trim($data[17] ?? '');
                    $delivery_info = trim($data[18] ?? '');
                    $is_featured = strtoupper(trim($data[19] ?? 'N')) === 'Y' ? 1 : 0;
                    $is_active = strtoupper(trim($data[20] ?? 'Y')) === 'Y' ? 1 : 0;
                    $base_length = 6;
                    $min_length = null;
                    $max_length = null;
                    $standard_length = '';
                    $show_on_homepage = 1;
                }

                // 삭제 플래그 확인 (36번째 컬럼)
                $deleteFlag = $hasDeleteColumn ? strtoupper(trim($data[35] ?? '')) : '';
                $isDeleteRequest = ($deleteFlag === 'Y');

                // 삭제 요청 처리
                if ($isDeleteRequest) {
                    if (!$id || !is_numeric($id)) {
                        $results['errors'][] = "라인 $lineNumber: 삭제하려면 유효한 ID가 필요합니다";
                        $lineNumber++;
                        continue;
                    }

                    // 삭제 건수 제한 확인
                    if ($results['deleted'] >= $MAX_DELETE_COUNT) {
                        $results['errors'][] = "라인 $lineNumber: 한 번에 최대 {$MAX_DELETE_COUNT}건까지만 삭제 가능합니다 (ID: $id)";
                        $lineNumber++;
                        continue;
                    }

                    // 제품 존재 여부 확인
                    $stmt_exist = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                    $stmt_exist->execute([$id]);
                    $existingProduct = $stmt_exist->fetch(PDO::FETCH_ASSOC);

                    if (!$existingProduct) {
                        $results['errors'][] = "라인 $lineNumber: 존재하지 않는 제품 ID ($id)";
                        $lineNumber++;
                        continue;
                    }

                    // 삭제 로그 기록
                    $stmt_log = $pdo->prepare("
                        INSERT INTO product_delete_logs
                        (product_id, product_name, category_code, specifications, deleted_data, deleted_by, deleted_via)
                        VALUES (?, ?, ?, ?, ?, ?, 'csv_import')
                    ");
                    $stmt_log->execute([
                        $existingProduct['id'],
                        $existingProduct['product_name'],
                        $existingProduct['category_code'],
                        $existingProduct['specifications'],
                        json_encode($existingProduct, JSON_UNESCAPED_UNICODE),
                        $_SESSION['admin_name'] ?? 'unknown'
                    ]);

                    // 제품 삭제
                    $stmt_delete = $pdo->prepare("DELETE FROM products WHERE id = ?");
                    $stmt_delete->execute([$id]);
                    $results['deleted']++;
                    $lineNumber++;
                    continue;
                }

                // 필수 필드 검증 (삭제가 아닌 경우)
                if (!$category_code || !$product_name || !$specifications) {
                    $results['errors'][] = "라인 $lineNumber: 필수 항목 누락 (카테고리코드, 제품명, 규격)";
                    $lineNumber++;
                    continue;
                }

                // 가격 단위 검증
                if (!in_array($price_unit, ['kg', 'piece'])) {
                    $price_unit = 'kg';
                }

                // 계산기 유형 검증
                if (!in_array($calculation_type, ['linear', 'sheet', 'piece'])) {
                    $calculation_type = 'linear';
                }

                // 카테고리 존재 여부 확인
                $stmt_check = $pdo->prepare("SELECT category_code FROM product_categories WHERE category_code = ?");
                $stmt_check->execute([$category_code]);
                if (!$stmt_check->fetch()) {
                    $results['errors'][] = "라인 $lineNumber: 존재하지 않는 카테고리코드 ($category_code)";
                    $lineNumber++;
                    continue;
                }

                if ($id && is_numeric($id)) {
                    // ID 존재 확인
                    $stmt_exist = $pdo->prepare("SELECT id FROM products WHERE id = ?");
                    $stmt_exist->execute([$id]);
                    $idExists = $stmt_exist->fetch();

                    if ($idExists) {
                        // 기존 제품 업데이트
                        $stmt = $pdo->prepare("
                            UPDATE products SET
                                category_code = ?, product_name = ?, product_code = ?,
                                specifications = ?, description = ?, price = ?,
                                price_unit = ?, unit = ?, min_order_qty = ?, stock_status = ?,
                                origin = ?, manufacturer = ?, dimensions = ?,
                                weight = ?, material = ?, specification_weight = ?,
                                calculation_type = ?, has_calculator = ?,
                                available_materials = ?, available_origins = ?,
                                material_price_data = ?, origin_price_data = ?,
                                features = ?, delivery_info = ?, is_featured = ?, is_active = ?,
                                base_length = ?, min_length = ?, max_length = ?,
                                standard_length = ?, show_on_homepage = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $category_code, $product_name, $product_code,
                            $specifications, $description, $price,
                            $price_unit, $unit, $min_order_qty, $stock_status,
                            $origin, $manufacturer, $dimensions,
                            $weight, $material, $specification_weight,
                            $calculation_type, $has_calculator,
                            $available_materials, $available_origins,
                            $material_price_data, $origin_price_data,
                            $features, $delivery_info, $is_featured, $is_active,
                            $base_length, $min_length, $max_length,
                            $standard_length, $show_on_homepage,
                            $id
                        ]);
                        $results['updated']++;
                    } else {
                        // DB에 없는 ID → INSERT로 전환 (미리보기와 동일한 동작)
                        $stmt = $pdo->prepare("
                            INSERT INTO products (
                                category_code, product_name, product_code,
                                specifications, description, price,
                                price_unit, unit, min_order_qty, stock_status,
                                origin, manufacturer, dimensions,
                                weight, material, specification_weight,
                                calculation_type, has_calculator,
                                available_materials, available_origins,
                                material_price_data, origin_price_data,
                                features, delivery_info, is_featured, is_active,
                                base_length, min_length, max_length,
                                standard_length, show_on_homepage,
                                created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $category_code, $product_name, $product_code,
                            $specifications, $description, $price,
                            $price_unit, $unit, $min_order_qty, $stock_status,
                            $origin, $manufacturer, $dimensions,
                            $weight, $material, $specification_weight,
                            $calculation_type, $has_calculator,
                            $available_materials, $available_origins,
                            $material_price_data, $origin_price_data,
                            $features, $delivery_info, $is_featured, $is_active,
                            $base_length, $min_length, $max_length,
                            $standard_length, $show_on_homepage
                        ]);
                        $results['created']++;
                    }
                } else {
                    // 새 제품 생성
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            category_code, product_name, product_code,
                            specifications, description, price,
                            price_unit, unit, min_order_qty, stock_status,
                            origin, manufacturer, dimensions,
                            weight, material, specification_weight,
                            calculation_type, has_calculator,
                            available_materials, available_origins,
                            material_price_data, origin_price_data,
                            features, delivery_info, is_featured, is_active,
                            base_length, min_length, max_length,
                            standard_length, show_on_homepage,
                            created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $price_unit, $unit, $min_order_qty, $stock_status,
                        $origin, $manufacturer, $dimensions,
                        $weight, $material, $specification_weight,
                        $calculation_type, $has_calculator,
                        $available_materials, $available_origins,
                        $material_price_data, $origin_price_data,
                        $features, $delivery_info, $is_featured, $is_active,
                        $base_length, $min_length, $max_length,
                        $standard_length, $show_on_homepage
                    ]);
                    $results['created']++;
                }

            } catch (PDOException $e) {
                $results['errors'][] = "라인 $lineNumber: " . $e->getMessage();
            }

            $lineNumber++;
        }
        
        fclose($handle);
        
        // 결과 메시지 생성
        $message = "처리 완료: ";
        $message .= "업데이트 " . $results['updated'] . "건, ";
        $message .= "신규 생성 " . $results['created'] . "건, ";
        $message .= "삭제 " . $results['deleted'] . "건";
        if (!empty($results['errors'])) {
            $message .= ", 오류 " . count($results['errors']) . "건";
        }

        // 성공적으로 처리되고 오류가 없으면 제품 목록으로 리다이렉트 (카테고리 컨텍스트 유지)
        if (empty($results['errors']) && ($results['updated'] > 0 || $results['created'] > 0 || $results['deleted'] > 0)) {
            $_SESSION['import_message'] = $message;
            $redirect_url = "admin_products_integrated.php?tab=products&imported=1";
            if ($return_category) {
                $redirect_url .= "&category=" . urlencode($return_category);
            }
            header("Location: $redirect_url");
            exit;
        }
    }
}

$pageTitle = '제품 데이터 업로드';
include 'admin_head.php';
?>

<style>
.upload-container {
    max-width: 800px;
    margin: 0 auto;
}

.upload-box {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.upload-form {
    text-align: center;
}

.file-input-wrapper {
    position: relative;
    display: inline-block;
    cursor: pointer;
    margin: 20px 0;
}

.file-input-wrapper input[type="file"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-input-label {
    display: inline-block;
    padding: 15px 30px;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    font-size: 16px;
    color: #495057;
    transition: all 0.3s ease;
}

.file-input-wrapper:hover .file-input-label {
    background: #e9ecef;
    border-color: #adb5bd;
}

.file-input-wrapper.drag-over .file-input-label {
    background: #e3f2fd;
    border-color: #2196f3;
    border-style: solid;
}

.file-selected {
    margin: 15px 0;
    color: #28a745;
    font-weight: 600;
}

.upload-btn {
    padding: 12px 40px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 20px;
}

.upload-btn:hover {
    background: #0056b3;
}

.upload-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.result-box {
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.result-box.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.result-box.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.error-list {
    margin-top: 10px;
    font-size: 14px;
    max-height: 200px;
    overflow-y: auto;
}

.instructions {
    background: #e3f2fd;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.instructions h3 {
    color: #1976d2;
    margin-bottom: 15px;
}

.instructions ol {
    margin-left: 20px;
    color: #555;
}

.instructions li {
    margin-bottom: 8px;
}

.template-download {
    text-align: center;
    margin: 20px 0;
}

.template-download a {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}

.template-download a:hover {
    text-decoration: underline;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
    color: #6c757d;
    text-decoration: none;
}

.back-link:hover {
    color: #495057;
}
</style>

<div class="upload-container">
    <a href="admin_products_integrated.php?tab=products" class="back-link">← 제품 관리로 돌아가기</a>
    
    <h2>제품 데이터 일괄 업로드</h2>
    
    <div class="instructions">
        <h3>업로드 방법</h3>
        <ol>
            <li>제품 목록에서 CSV 파일을 다운로드합니다.</li>
            <li>Excel에서 파일을 열어 데이터를 수정합니다.</li>
            <li>수정 완료 후 CSV 형식으로 저장합니다.</li>
            <li>저장한 파일을 아래에서 선택하여 업로드합니다.</li>
        </ol>
        <h4 style="margin-top:15px; color:#1976d2;">컬럼 안내 (36개)</h4>
        <div style="font-size:13px; color:#555; line-height:1.6;">
            <p><strong>필수:</strong> 카테고리코드, 제품명, 규격</p>
            <p><strong>신규 필드:</strong></p>
            <ul style="margin-left:20px;">
                <li><strong>가격단위</strong>: kg 또는 piece</li>
                <li><strong>단위중량</strong>: kg/m 단위 (specification_weight)</li>
                <li><strong>계산기유형</strong>: linear, sheet, piece</li>
                <li><strong>계산기보유</strong>: Y/N</li>
                <li><strong>사용가능재질</strong>: 쉼표 구분 (예: SS275,SS400,SM490A)</li>
                <li><strong>사용가능원산지</strong>: 쉼표 구분 (예: 국산,중국산)</li>
                <li><strong>재질별추가가격</strong>: 재질:가격;... (예: SS400:0;SM490A:100)</li>
                <li><strong>원산지별추가가격</strong>: 원산지:가격;... (예: 국산:0;중국산:-50)</li>
                <li><strong>기본길이/최소길이/최대길이/표준길이</strong>: 길이 설정</li>
                <li><strong>홈페이지표시</strong>: Y/N</li>
            </ul>
        </div>
        <div style="margin-top:15px; padding:12px; background:#fff3cd; border:1px solid #ffc107; border-radius:6px;">
            <h4 style="color:#856404; margin-bottom:8px;">⚠️ 제품 삭제 기능</h4>
            <div style="font-size:13px; color:#856404; line-height:1.6;">
                <p><strong>삭제(Y)</strong> 컬럼(36번째)에 <strong>Y</strong>를 입력하면 해당 제품이 삭제됩니다.</p>
                <ul style="margin-left:20px; margin-top:8px;">
                    <li>삭제하려면 반드시 <strong>ID</strong>가 있어야 합니다</li>
                    <li>한 번에 최대 <strong>50건</strong>까지 삭제 가능</li>
                    <li>삭제된 데이터는 <strong>삭제 로그</strong>에 백업됩니다</li>
                    <li>빈 값이면 삭제되지 않음 (수정/추가만 처리)</li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="result-box <?php echo empty($errors) && empty($results['errors']) ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
        <?php if (!empty($results['errors'])): ?>
        <div class="error-list">
            <?php foreach ($results['errors'] as $error): ?>
                <div>• <?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
    <div class="result-box error">
        <?php foreach ($errors as $error): ?>
            <div><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="upload-box">
        <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="return_category" value="<?php echo htmlspecialchars($return_category); ?>">
            <div class="file-input-wrapper">
                <input type="file" name="excel_file" id="excelFile" accept=".csv" required>
                <label for="excelFile" class="file-input-label">
                    📄 CSV 파일 선택 또는 여기로 드래그
                </label>
            </div>
            <div class="file-selected" id="fileSelected" style="display: none;"></div>
            <button type="submit" class="upload-btn" id="uploadBtn" disabled>업로드</button>
        </form>
    </div>
    
    <div class="template-download">
        <p>현재 제품 데이터를 다운로드하려면 <a href="admin_products_integrated.php?tab=products">제품 관리 페이지</a>에서 다운로드 버튼을 클릭하세요.</p>
    </div>
</div>

<script>
const fileInput = document.getElementById('excelFile');
const fileWrapper = document.querySelector('.file-input-wrapper');
const fileSelected = document.getElementById('fileSelected');
const uploadBtn = document.getElementById('uploadBtn');

// 파일 선택 핸들러
function handleFileSelect(file) {
    if (file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['csv', 'txt'].includes(ext)) {
            alert('CSV 파일만 업로드 가능합니다.');
            return;
        }
        fileSelected.textContent = '선택된 파일: ' + file.name + ' (' + formatFileSize(file.size) + ')';
        fileSelected.style.display = 'block';
        uploadBtn.disabled = false;
    }
}

// 파일 크기 포맷
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// 파일 입력 변경
fileInput.addEventListener('change', function(e) {
    handleFileSelect(e.target.files[0]);
});

// 드래그앤드롭 지원
fileWrapper.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.add('drag-over');
});

fileWrapper.addEventListener('dragleave', function(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.remove('drag-over');
});

fileWrapper.addEventListener('drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
    this.classList.remove('drag-over');

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        handleFileSelect(files[0]);
    }
});

// 폼 제출
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    uploadBtn.textContent = '처리 중...';
    uploadBtn.disabled = true;
});
</script>

<?php include 'admin_tail.php'; ?>