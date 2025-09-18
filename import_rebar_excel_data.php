<?php
require_once 'includes/SimpleXLSX.php';
require_once 'db.php';

use Shuchkin\SimpleXLSX;

// 엑셀 파일 경로
$excel_file = __DIR__ . '/114/철근20250730.xlsx';

try {
    echo "철근 엑셀 데이터 가져오기 시작...\n";
    
    // 엑셀 파일 읽기
    if (!file_exists($excel_file)) {
        throw new Exception("엑셀 파일을 찾을 수 없습니다: $excel_file");
    }
    
    $xlsx = SimpleXLSX::parse($excel_file);
    if (!$xlsx) {
        throw new Exception("엑셀 파일을 읽을 수 없습니다: " . SimpleXLSX::parseError());
    }
    
    // 첫 번째 시트 가져오기
    $rows = $xlsx->rows();
    
    // 철근 규격별 데이터 구조 생성
    $rebar_data = [];
    
    // 헤더 행에서 철근 규격 추출 (D10, D13, D16 등)
    $header = $rows[0];
    $specs = [];
    
    for ($i = 1; $i < count($header); $i += 3) {
        if (preg_match('/D(\d+)\s*\/\s*([\d.]+)/', $header[$i], $matches)) {
            $spec = 'D' . $matches[1];
            $weight_per_meter = floatval($matches[2]);
            $specs[$i] = [
                'spec' => $spec,
                'weight_per_meter' => $weight_per_meter,
                'col_index' => $i
            ];
            $rebar_data[$spec] = [
                'weight_per_meter' => $weight_per_meter,
                'length_data' => []
            ];
        }
    }
    
    echo "발견된 철근 규격: " . implode(', ', array_keys($rebar_data)) . "\n";
    
    // 데이터 행 파싱 (row 1부터 시작, row 0은 헤더)
    for ($row_idx = 1; $row_idx < count($rows); $row_idx++) {
        $row = $rows[$row_idx];
        
        // 길이 값 추출
        $length = isset($row[0]) ? floatval($row[0]) : null;
        if (!$length || $length <= 0) continue;
        
        // 각 규격별로 데이터 추출
        foreach ($specs as $col_idx => $spec_info) {
            $spec = $spec_info['spec'];
            
            // 본중 (본당 중량)
            $weight_per_piece = isset($row[$col_idx]) ? floatval($row[$col_idx]) : null;
            
            // 톤당 본수
            $pieces_per_ton = isset($row[$col_idx + 1]) ? floatval($row[$col_idx + 1]) : null;
            
            // 톤당 중량 (옵션)
            $weight_per_ton = isset($row[$col_idx + 2]) ? floatval($row[$col_idx + 2]) : null;
            
            if ($weight_per_piece && $pieces_per_ton) {
                $length_str = strval($length);
                $rebar_data[$spec]['length_data'][$length_str] = [
                    'length' => $length,
                    'weight_per_piece' => $weight_per_piece,
                    'pieces_per_ton' => $pieces_per_ton,
                    'weight_per_ton' => $weight_per_ton
                ];
            }
        }
    }
    
    // 데이터베이스 업데이트
    $pdo = getDB();
    $pdo->beginTransaction();
    
    $updated_count = 0;
    
    foreach ($rebar_data as $spec => $data) {
        // 해당 철근 제품 찾기
        $sql = "SELECT id, product_name FROM products 
                WHERE category_code = 'rebar' 
                AND (product_name LIKE :spec1 OR product_name LIKE :spec2)
                LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':spec1' => "%철근 $spec%",
            ':spec2' => "%철근%$spec%"
        ]);
        
        $product = $stmt->fetch();
        
        if ($product) {
            // JSON 데이터 준비
            $length_data_json = json_encode($data['length_data'], JSON_UNESCAPED_UNICODE);
            
            // 톤당 본수 데이터만 추출
            $pieces_data = [];
            foreach ($data['length_data'] as $length => $item) {
                $pieces_data[strval($length)] = $item['pieces_per_ton'];
            }
            $pieces_per_ton_json = json_encode($pieces_data, JSON_UNESCAPED_UNICODE);
            
            // 제품 업데이트
            $update_sql = "UPDATE products SET 
                          length_data = :length_data,
                          weight_per_meter = :weight_per_meter,
                          pieces_per_ton = :pieces_per_ton,
                          updated_at = NOW()
                          WHERE id = :id";
            
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                ':length_data' => $length_data_json,
                ':weight_per_meter' => $data['weight_per_meter'],
                ':pieces_per_ton' => $pieces_per_ton_json,
                ':id' => $product['id']
            ]);
            
            echo "업데이트됨: {$product['product_name']} (ID: {$product['id']})\n";
            echo "  - 미터당 중량: {$data['weight_per_meter']}kg\n";
            echo "  - 길이별 데이터: " . count($data['length_data']) . "개\n";
            
            $updated_count++;
        } else {
            echo "경고: '$spec' 철근 제품을 찾을 수 없습니다.\n";
        }
    }
    
    $pdo->commit();
    echo "\n총 {$updated_count}개의 철근 제품이 업데이트되었습니다.\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "오류 발생: " . $e->getMessage() . "\n";
    exit(1);
}

echo "완료!\n";
?>