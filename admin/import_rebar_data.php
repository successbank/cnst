<?php
require_once '../db.php';

// 엑셀 파일 경로
$excelFile = '../114/철근20250730.xlsx';

try {
    // 1. 철근 길이별 데이터 테이블 생성 (새로운 테이블)
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS rebar_length_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        spec_name VARCHAR(10) NOT NULL COMMENT '규격명 (D10, D13 등)',
        unit_weight DECIMAL(10,3) NOT NULL COMMENT '단위중량 (kg/m)',
        length DECIMAL(10,2) NOT NULL COMMENT '길이 (m)',
        piece_weight DECIMAL(10,2) NOT NULL COMMENT '본중 (kg)',
        pieces_per_ton INT COMMENT '톤당 본수',
        weight_per_ton DECIMAL(10,2) COMMENT '톤당 중량 (kg)',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_spec_length (spec_name, length),
        INDEX idx_spec_name (spec_name),
        INDEX idx_length (length)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($createTableSQL);
    echo "테이블 생성 완료\n";
    
    // 2. Python 스크립트를 실행하여 데이터 추출
    $pythonScript = __DIR__ . '/import_rebar_data.py';
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($excelFile);
    
    echo "Python 스크립트 실행 중...\n";
    $output = shell_exec($command . " 2>&1");
    
    if (empty($output)) {
        throw new Exception("Python 스크립트 실행 실패");
    }
    
    // JSON 디코드
    $data = json_decode($output, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON 파싱 오류: " . json_last_error_msg() . "\nOutput: " . substr($output, 0, 500));
    }
    
    echo "추출된 데이터: " . count($data) . "개\n";
    
    // 3. 기존 데이터 삭제
    $pdo->exec("DELETE FROM rebar_length_data");
    
    // 4. 데이터 삽입
    $insertSQL = "INSERT INTO rebar_length_data 
                  (spec_name, unit_weight, length, piece_weight, pieces_per_ton, weight_per_ton) 
                  VALUES (:spec_name, :unit_weight, :length, :piece_weight, :pieces_per_ton, :weight_per_ton)";
    $stmt = $pdo->prepare($insertSQL);
    
    $totalInserted = 0;
    
    foreach ($data as $item) {
        $stmt->execute([
            ':spec_name' => $item['spec_name'],
            ':unit_weight' => $item['unit_weight'],
            ':length' => $item['length'],
            ':piece_weight' => $item['piece_weight'],
            ':pieces_per_ton' => $item['pieces_per_ton'],
            ':weight_per_ton' => $item['weight_per_ton']
        ]);
        $totalInserted++;
    }
    
    echo "\n총 {$totalInserted}개의 데이터를 임포트했습니다.\n";
    
    // 5. 임포트 결과 확인
    $checkSQL = "SELECT spec_name, COUNT(*) as count, 
                        MIN(length) as min_length, 
                        MAX(length) as max_length,
                        unit_weight
                 FROM rebar_length_data 
                 GROUP BY spec_name, unit_weight
                 ORDER BY spec_name";
    
    $result = $pdo->query($checkSQL);
    
    echo "\n=== 임포트 결과 ===\n";
    echo "규격\t단위중량\t데이터수\t길이범위\n";
    echo str_repeat("-", 50) . "\n";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['spec_name']}\t{$row['unit_weight']}kg/m\t{$row['count']}개\t{$row['min_length']}m~{$row['max_length']}m\n";
    }
    
    // 6. 샘플 데이터 확인
    echo "\n=== D10 철근 샘플 데이터 (6m~8m) ===\n";
    $sampleSQL = "SELECT length, piece_weight, pieces_per_ton, weight_per_ton 
                  FROM rebar_length_data 
                  WHERE spec_name = 'D10' AND length BETWEEN 6 AND 8
                  ORDER BY length";
    
    $sampleResult = $pdo->query($sampleSQL);
    echo "길이(m)\t본중(kg)\t톤당본수\t톤당중량(kg)\n";
    echo str_repeat("-", 50) . "\n";
    
    while ($row = $sampleResult->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['length']}\t{$row['piece_weight']}\t{$row['pieces_per_ton']}\t{$row['weight_per_ton']}\n";
    }
    
    echo "\n임포트가 성공적으로 완료되었습니다!\n";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
    echo "스택 트레이스:\n" . $e->getTraceAsString() . "\n";
}
?>