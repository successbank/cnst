<?php
require_once 'db.php';

// 실행 시간 제한 해제
set_time_limit(0);

try {
    echo "=== 철근 번들 데이터 임포트 시작 ===\n\n";
    
    // 엑셀 파일 경로
    $excel_file = '114/9/철근.xlsx';
    
    if (!file_exists($excel_file)) {
        throw new Exception("엑셀 파일을 찾을 수 없습니다: " . $excel_file);
    }
    
    // JSON 파일에서 데이터 읽기
    $json_file = 'rebar_data.json';
    
    if (!file_exists($json_file)) {
        // Python 스크립트 실행하여 JSON 생성
        $command = 'python3 import_rebar_excel.py 2>&1';
        $output = shell_exec($command);
        echo "Python 출력: " . $output . "\n";
        
        if (!file_exists($json_file)) {
            throw new Exception("JSON 파일 생성 실패");
        }
    }
    
    $json_content = file_get_contents($json_file);
    if (!$json_content) {
        throw new Exception("JSON 파일 읽기 실패");
    }
    
    // JSON 파싱 시도
    $data = json_decode($json_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON 파싱 오류: " . json_last_error_msg());
    }
    
    if (!$data) {
        throw new Exception("JSON 데이터가 비어있습니다");
    }
    
    echo "총 " . count($data) . "개의 데이터를 읽었습니다.\n\n";
    
    // 기존 데이터 삭제 (옵션)
    $pdo->exec("TRUNCATE TABLE rebar_bundle_data");
    echo "기존 데이터를 삭제했습니다.\n";
    
    // 데이터 삽입 준비
    $stmt = $pdo->prepare("
        INSERT INTO rebar_bundle_data (
            p_code, it_code, p_name, p_standard, p_material, 
            p_unit_weight, p_unit_length, p_weight, p_bd_count, p_bd_weight,
            p_low_cost, p_high_cost, p_low_price, p_high_price,
            p_country, p_maker, p_class, p_remark
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $success_count = 0;
    $error_count = 0;
    $standards = [];
    
    foreach ($data as $index => $row) {
        try {
            // 데이터 정제
            $p_bd_count = is_numeric($row['p_bd_count']) ? intval($row['p_bd_count']) : 0;
            $p_bd_weight = is_numeric($row['p_bd_weight']) ? intval($row['p_bd_weight']) : 0;
            
            $stmt->execute([
                $row['p_code'],
                $row['it_code'],
                $row['p_name'],
                $row['p_standard'],
                $row['p_material'],
                $row['p_unit_weight'],
                $row['p_unit_lengh'], // 원본 컬럼명 그대로 사용
                $row['p_weight'] ?? 0,
                $p_bd_count,
                $p_bd_weight,
                $row['p_low_cost'],
                $row['p_high_cost'],
                $row['p_low_price'],
                $row['p_high_price'],
                $row['p_country'],
                $row['p_maker'],
                $row['p_class'],
                $row['p_remark']
            ]);
            
            $success_count++;
            
            // 규격 수집
            if (!in_array($row['p_standard'], $standards)) {
                $standards[] = $row['p_standard'];
            }
            
            // 진행상황 표시
            if ($success_count % 100 == 0) {
                echo "처리중... " . $success_count . "/" . count($data) . "\r";
            }
            
        } catch (PDOException $e) {
            $error_count++;
            if ($error_count <= 5) { // 처음 5개 에러만 표시
                echo "\n행 " . ($index + 1) . " 삽입 실패: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n\n=== 임포트 완료 ===\n";
    echo "성공: " . $success_count . "건\n";
    echo "실패: " . $error_count . "건\n";
    echo "총 규격 수: " . count($standards) . "종\n";
    echo "규격 목록: " . implode(', ', array_slice($standards, 0, 10)) . "...\n";
    
    // 데이터 확인
    $stmt = $pdo->query("SELECT p_standard, p_material, COUNT(*) as cnt FROM rebar_bundle_data GROUP BY p_standard, p_material ORDER BY p_standard");
    $summary = $stmt->fetchAll();
    
    echo "\n=== 규격별 데이터 수 ===\n";
    foreach (array_slice($summary, 0, 10) as $row) {
        echo $row['p_standard'] . " (" . $row['p_material'] . "): " . $row['cnt'] . "건\n";
    }
    echo "...\n";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>