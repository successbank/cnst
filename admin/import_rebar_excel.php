<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$pageTitle = '철근 제품 엑셀 임포트';

// 추가 스타일 정의
$additionalStyles = '
.import-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 30px;
}

.import-form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.form-group input[type="file"] {
    width: 100%;
    padding: 10px;
    border: 2px dashed #E5E5E7;
    border-radius: 8px;
    background: #F8F9FA;
}

.form-help {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
}

.sample-format {
    background: #F8F9FA;
    padding: 20px;
    border-radius: 8px;
    margin-top: 30px;
}

.sample-format h3 {
    font-size: 16px;
    margin-bottom: 15px;
}

.sample-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.sample-table th,
.sample-table td {
    border: 1px solid #E5E5E7;
    padding: 8px;
    text-align: left;
}

.sample-table th {
    background: #F0F0F2;
    font-weight: 600;
}

.btn-import {
    background: #1A237E;
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
}

.btn-import:hover {
    background: #283593;
}
';

require_once 'admin_head.php';

// 파일 업로드 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $uploadResult = handleExcelUpload($_FILES['excel_file']);
    if ($uploadResult['success']) {
        echo '<div class="msg success">' . $uploadResult['message'] . '</div>';
    } else {
        echo '<div class="msg error">' . $uploadResult['message'] . '</div>';
    }
}

function handleExcelUpload($file) {
    global $pdo;
    
    // 파일 검증
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '파일 업로드 실패'];
    }
    
    $allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => '엑셀 파일만 업로드 가능합니다.'];
    }
    
    // 임시 파일 처리
    $tempFile = $file['tmp_name'];
    
    // CSV로 변환하여 읽기 (간단한 처리를 위해)
    // 실제로는 PhpSpreadsheet 등을 사용하는 것이 좋습니다
    
    return ['success' => true, 'message' => '엑셀 파일 처리 기능은 별도 라이브러리 설치가 필요합니다.'];
}
?>

<div class="page-header">
    <h1>철근 제품 엑셀 임포트</h1>
    <p>엑셀 파일을 업로드하여 철근 제품을 일괄 등록할 수 있습니다.</p>
</div>

<div class="import-section">
    <form method="POST" enctype="multipart/form-data" class="import-form">
        <div class="form-group">
            <label for="excel_file">엑셀 파일 선택</label>
            <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
            <div class="form-help">*.xlsx, *.xls 파일만 업로드 가능합니다.</div>
        </div>
        
        <button type="submit" class="btn-import">엑셀 파일 임포트</button>
    </form>
    
    <div class="sample-format">
        <h3>엑셀 파일 형식 예시</h3>
        <p style="margin-bottom: 15px;">아래와 같은 형식으로 엑셀 파일을 준비해주세요:</p>
        
        <table class="sample-table">
            <thead>
                <tr>
                    <th>제품명</th>
                    <th>규격(직경)</th>
                    <th>단위중량(kg/m)</th>
                    <th>설명</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>D10</td>
                    <td>9.53mm</td>
                    <td>0.56</td>
                    <td>건축용 이형철근 D10 (SD400)</td>
                </tr>
                <tr>
                    <td>D13</td>
                    <td>12.7mm</td>
                    <td>0.995</td>
                    <td>건축용 이형철근 D13 (SD400)</td>
                </tr>
                <tr>
                    <td>D16</td>
                    <td>15.9mm</td>
                    <td>1.56</td>
                    <td>건축용 이형철근 D16 (SD400)</td>
                </tr>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; padding: 15px; background: #FFF3E0; border-radius: 8px;">
            <strong>참고사항:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li>D10/0.56 - D10: 철근 직경 표시, 0.56: 단위중량(kg/m)</li>
                <li>모든 철근은 SD400 규격으로 등록됩니다</li>
                <li>단위는 TON으로 고정됩니다</li>
            </ul>
        </div>
    </div>
</div>

<div class="import-section">
    <h2>수동 입력 방식</h2>
    <p>엑셀 파일 없이 직접 철근 제품을 등록하려면 아래 SQL을 실행하세요:</p>
    
    <pre style="background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto;">
-- 철근 카테고리가 없는 경우 먼저 생성
INSERT INTO product_categories (name, display_name, description, icon, display_order, is_active) 
VALUES ('rebar', '철근', '건축용 철근', 'fas fa-bars', 11, 1);

-- 철근 제품 추가
SET @category_id = (SELECT id FROM product_categories WHERE name = 'rebar');

INSERT INTO products (category_id, product_name, korean_name, specifications, unit, description, is_active) VALUES
(@category_id, 'D10', '이형철근 D10', '직경: 9.53mm, 단위중량: 0.56kg/m', 'TON', '건축용 이형철근 D10 (SD400)', 1),
(@category_id, 'D13', '이형철근 D13', '직경: 12.7mm, 단위중량: 0.995kg/m', 'TON', '건축용 이형철근 D13 (SD400)', 1),
(@category_id, 'D16', '이형철근 D16', '직경: 15.9mm, 단위중량: 1.56kg/m', 'TON', '건축용 이형철근 D16 (SD400)', 1);
    </pre>
</div>

<?php require_once 'admin_tail.php'; ?>