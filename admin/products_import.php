<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$message = '';
$errors = [];
$results = [
    'updated' => 0,
    'created' => 0,
    'errors' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $uploadedFile = $_FILES['excel_file'];
    
    // 파일 검증
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "파일 업로드 실패";
    } else {
        // 파일 확장자 확인
        $fileExt = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExt, ['csv', 'txt'])) {
            $errors[] = "CSV 파일만 업로드 가능합니다.";
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
        
        // 데이터 처리
        $lineNumber = 2; // 헤더 다음 줄부터
        while (($data = fgetcsv($handle)) !== FALSE) {
            try {
                // 데이터 매핑
                $id = $data[0] ?? null;
                $category_code = $data[1] ?? '';
                $product_name = $data[3] ?? '';
                $product_code = $data[4] ?? '';
                $specifications = $data[5] ?? '';
                $description = $data[6] ?? '';
                $price = $data[7] ?: null;
                $unit = $data[8] ?? 'TON';
                $min_order_qty = $data[9] ?: 1;
                $stock_status = $data[10] ?? 'in_stock';
                $origin = $data[11] ?? '';
                $manufacturer = $data[12] ?? '';
                $dimensions = $data[13] ?? '';
                $weight = $data[14] ?? '';
                $material = $data[15] ?? '';
                $features = $data[17] ?? '';
                $delivery_info = $data[18] ?? '';
                $is_featured = ($data[19] ?? 'N') === 'Y' ? 1 : 0;
                $is_active = ($data[20] ?? 'Y') === 'Y' ? 1 : 0;
                
                // 필수 필드 검증
                if (!$category_code || !$product_name || !$specifications) {
                    $results['errors'][] = "라인 $lineNumber: 필수 항목 누락 (카테고리코드, 제품명, 규격)";
                    $lineNumber++;
                    continue;
                }
                
                if ($id && is_numeric($id)) {
                    // 기존 제품 업데이트
                    $stmt = $pdo->prepare("
                        UPDATE products SET 
                            category_code = ?, product_name = ?, product_code = ?,
                            specifications = ?, description = ?, price = ?,
                            unit = ?, min_order_qty = ?, stock_status = ?,
                            origin = ?, manufacturer = ?, dimensions = ?,
                            weight = ?, material = ?, features = ?,
                            delivery_info = ?, is_featured = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $unit, $min_order_qty, $stock_status,
                        $origin, $manufacturer, $dimensions,
                        $weight, $material, $features,
                        $delivery_info, $is_featured, $is_active,
                        $id
                    ]);
                    $results['updated']++;
                } else {
                    // 새 제품 생성
                    $stmt = $pdo->prepare("
                        INSERT INTO products (
                            category_code, product_name, product_code,
                            specifications, description, price,
                            unit, min_order_qty, stock_status,
                            origin, manufacturer, dimensions,
                            weight, material, features,
                            delivery_info, is_featured, is_active,
                            created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $category_code, $product_name, $product_code,
                        $specifications, $description, $price,
                        $unit, $min_order_qty, $stock_status,
                        $origin, $manufacturer, $dimensions,
                        $weight, $material, $features,
                        $delivery_info, $is_featured, $is_active
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
        $message .= "신규 생성 " . $results['created'] . "건";
        if (!empty($results['errors'])) {
            $message .= ", 오류 " . count($results['errors']) . "건";
        }
        
        // 성공적으로 처리되고 오류가 없으면 제품 목록으로 리다이렉트
        if (empty($results['errors']) && ($results['updated'] > 0 || $results['created'] > 0)) {
            $_SESSION['import_message'] = $message;
            header("Location: admin_products_integrated.php?tab=products&imported=1");
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
            <div class="file-input-wrapper">
                <input type="file" name="excel_file" id="excelFile" accept=".csv" required>
                <label for="excelFile" class="file-input-label">
                    CSV 파일 선택
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
document.getElementById('excelFile').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    if (fileName) {
        document.getElementById('fileSelected').textContent = '선택된 파일: ' + fileName;
        document.getElementById('fileSelected').style.display = 'block';
        document.getElementById('uploadBtn').disabled = false;
    } else {
        document.getElementById('fileSelected').style.display = 'none';
        document.getElementById('uploadBtn').disabled = true;
    }
});

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('uploadBtn');
    btn.textContent = '처리 중...';
    btn.disabled = true;
});
</script>

<?php include 'admin_tail.php'; ?>