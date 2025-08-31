<?php
require_once '../db.php';

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

function parseExcelWithPython($filepath) {
    $python_script = <<<'PYTHON'
import sys
import pandas as pd
import json

try:
    df = pd.read_excel(sys.argv[1])
    # NaN 값을 None으로 변환
    df = df.where(pd.notnull(df), None)
    result = df.to_dict('records')
    print(json.dumps(result, ensure_ascii=False))
except Exception as e:
    print(json.dumps({"error": str(e)}))
PYTHON;

    $temp_script = tempnam(sys_get_temp_dir(), 'excel_reader');
    file_put_contents($temp_script, $python_script);
    
    $output = shell_exec("python3 $temp_script " . escapeshellarg($filepath) . " 2>&1");
    unlink($temp_script);
    
    return json_decode($output, true);
}

function importExcelData($filepath, $korean_name, $category_code, $pdo) {
    global $linear_products, $sheet_products;
    
    try {
        echo "처리 중: $filepath\n";
        
        // Python으로 Excel 읽기
        $data = parseExcelWithPython($filepath);
        
        if (isset($data['error'])) {
            throw new Exception($data['error']);
        }
        
        $specifications = [];
        $materials = [];
        $unit_weight_data = [];
        $product_name = '';
        
        // 데이터 파싱
        foreach ($data as $row) {
            if (empty($row['규격'])) continue;
            
            $product_name = $row['품명'] ?? $korean_name;
            $specification = $row['규격'];
            $unit_weight = floatval($row['단위중량(kg)'] ?? $row['단위중량(장)'] ?? 0);
            $material = $row['재질'] ?? 'SS400';
            
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
        
        // 기존 제품 확인
        $check_stmt = $pdo->prepare("SELECT id FROM products WHERE category_code = ? LIMIT 1");
        $check_stmt->execute([$category_code]);
        $existing_id = $check_stmt->fetchColumn();
        
        if ($existing_id) {
            // 업데이트
            $stmt = $pdo->prepare("
                UPDATE products SET
                    product_name = ?,
                    calculation_type = ?,
                    unit_weight_data = ?,
                    available_materials = ?,
                    available_sizes = ?,
                    has_calculator = 1
                WHERE id = ?
            ");
            
            $stmt->execute([
                $product_name,
                $calculation_type,
                json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
                json_encode($materials, JSON_UNESCAPED_UNICODE),
                json_encode($specifications, JSON_UNESCAPED_UNICODE),
                $existing_id
            ]);
        } else {
            // 신규 생성
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
            ");
            
            $stmt->execute([
                $category_code,
                $product_name,
                $calculation_type,
                json_encode($unit_weight_data, JSON_UNESCAPED_UNICODE),
                json_encode($materials, JSON_UNESCAPED_UNICODE),
                json_encode($specifications, JSON_UNESCAPED_UNICODE)
            ]);
        }
        
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

// 실행
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === '1')) {
    $excel_dir = '../114/product/';
    $results = [];
    
    echo "<pre>\n";
    echo "Excel 데이터 임포트 시작...\n\n";
    
    foreach ($category_mapping as $korean_name => $category_code) {
        $excel_file = $excel_dir . $korean_name . '.xlsx';
        if (file_exists($excel_file)) {
            $result = importExcelData($excel_file, $korean_name, $category_code, $pdo);
            $results[$korean_name] = $result;
            
            if ($result['success']) {
                echo "✓ $korean_name 완료: {$result['specifications']}개 규격, {$result['materials']}개 재질\n";
            } else {
                echo "✗ $korean_name 실패: {$result['error']}\n";
            }
        } else {
            echo "- $korean_name: 파일 없음 ($excel_file)\n";
        }
    }
    
    echo "\n임포트 완료!\n";
    echo "</pre>\n";
    
    if (php_sapi_name() !== 'cli') {
        echo "<a href='../products.php'>제품 목록 페이지로 이동</a>";
    }
}
?>