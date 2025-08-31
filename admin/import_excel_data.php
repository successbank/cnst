<?php
require_once '../db.php';
require_once '../vendor/autoload.php'; // PhpSpreadsheet 사용 시

use PhpOffice\PhpSpreadsheet\IOFactory;

// 카테고리 매핑
$category_mapping = [
    '평철' => 'flat-bar',
    '환봉' => 'round-bar',
    '철판' => 'steel-plate',
    'ㄱ형강' => 'angle',
    'ㄷ형강' => 'channel',
    'C형강' => 'c-beam',
    'BS파이프' => 'bs-pipe',
    'KS파이프' => 'ks-pipe',
    '강관파일' => 'steel-pipe-pile',
    '구조관' => 'structural-pipe',
    '데크플레이트' => 'deck-plate',
    '레일' => 'rail',
    '복공판' => 'temporary-deck',
    '부등변ㄱ형강' => 'unequal-angle',
    '사각파이프' => 'square-pipe',
    '쉬트파일' => 'sheet-pile',
    '압력배관' => 'pressure-pipe',
    '전선관' => 'conduit-pipe',
    '단관비계' => 'scaffold-pipe'
];

// 계산 유형 결정
$linear_products = ['flat-bar', 'round-bar', 'angle', 'channel', 'c-beam', 'unequal-angle'];
$sheet_products = ['steel-plate', 'deck-plate', 'temporary-deck'];

function importExcelData($filepath, $category_code, $pdo) {
    global $linear_products, $sheet_products;
    
    try {
        $spreadsheet = IOFactory::load($filepath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // 헤더 확인
        $header = $rows[0];
        if (!isset($header[1]) || $header[1] !== '품명') {
            throw new Exception("잘못된 Excel 형식입니다.");
        }
        
        $specifications = [];
        $materials = [];
        $unit_weight_data = [];
        
        // 데이터 파싱
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty($row[2])) continue; // 규격이 없으면 스킵
            
            $product_name = $row[1];
            $specification = $row[2];
            $unit_weight = floatval($row[3]);
            $material = $row[5] ?? 'SS400'; // 기본 재질
            
            // 규격별 단위중량 저장
            if (!isset($unit_weight_data[$specification])) {
                $unit_weight_data[$specification] = [];
            }
            $unit_weight_data[$specification][$material] = $unit_weight;
            
            // 규격과 재질 목록 수집
            if (!in_array($specification, $specifications)) {
                $specifications[] = $specification;
            }
            if (!in_array($material, $materials)) {
                $materials[] = $material;
            }
        }
        
        // 계산 유형 결정
        $calculation_type = in_array($category_code, $linear_products) ? 'linear' : 
                           (in_array($category_code, $sheet_products) ? 'sheet' : 'linear');
        
        // 제품 업데이트 또는 생성
        $stmt = $pdo->prepare("
            INSERT INTO products (
                category_code, 
                product_name, 
                calculation_type,
                unit_weight_data,
                available_materials,
                available_sizes,
                has_calculator,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, 1, 1)
            ON DUPLICATE KEY UPDATE
                calculation_type = VALUES(calculation_type),
                unit_weight_data = VALUES(unit_weight_data),
                available_materials = VALUES(available_materials),
                available_sizes = VALUES(available_sizes),
                has_calculator = 1
        ");
        
        $stmt->execute([
            $category_code,
            $product_name ?? $category_code,
            $calculation_type,
            json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
            json_encode($materials, JSON_UNESCAPED_UNICODE),
            json_encode($specifications, JSON_UNESCAPED_UNICODE)
        ]);
        
        return [
            'success' => true,
            'specifications' => count($specifications),
            'materials' => count($materials),
            'product_name' => $product_name
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// CLI 실행 또는 웹 실행
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === '1')) {
    $excel_dir = '../114/product/';
    $results = [];
    
    foreach ($category_mapping as $korean_name => $category_code) {
        $excel_file = $excel_dir . $korean_name . '.xlsx';
        if (file_exists($excel_file)) {
            echo "처리 중: $excel_file\n";
            $result = importExcelData($excel_file, $category_code, $pdo);
            $results[$korean_name] = $result;
            
            if ($result['success']) {
                echo "✓ $korean_name 완료: {$result['specifications']}개 규격, {$result['materials']}개 재질\n";
            } else {
                echo "✗ $korean_name 실패: {$result['error']}\n";
            }
        }
    }
    
    echo "\n임포트 완료!\n";
}
?>