<?php
require_once 'db.php';

// 실행 시간 제한 해제
set_time_limit(0);

try {
    echo "=== 철근 번들 데이터 직접 임포트 시작 ===\n\n";
    
    // Python으로 직접 데이터 처리
    $python_code = <<<'PYTHON'
import pandas as pd
import sys

try:
    # Read Excel file
    df = pd.read_excel('114/9/철근.xlsx', sheet_name=0)
    
    # Process each row
    for index, row in df.iterrows():
        # Convert values to appropriate types
        # Handle '문의' or other non-numeric values
        try:
            p_bd_count = int(row['p_bd_count']) if pd.notnull(row['p_bd_count']) and str(row['p_bd_count']).isdigit() else 0
        except:
            p_bd_count = 0
            
        try:
            p_bd_weight = int(row['p_bd_weight']) if pd.notnull(row['p_bd_weight']) and str(row['p_bd_weight']).isdigit() else 0
        except:
            p_bd_weight = 0
        
        # Create SQL-safe values
        values = [
            str(row['p_code']) if pd.notnull(row['p_code']) else '',
            int(row['it_code']) if pd.notnull(row['it_code']) else 0,
            str(row['p_name']) if pd.notnull(row['p_name']) else '',
            str(row['p_standard']) if pd.notnull(row['p_standard']) else '',
            str(row['p_material']) if pd.notnull(row['p_material']) else '',
            float(row['p_unit_weight']) if pd.notnull(row['p_unit_weight']) else 0,
            float(row['p_unit_lengh']) if pd.notnull(row['p_unit_lengh']) else 0,
            int(row['p_weight']) if pd.notnull(row['p_weight']) else 0,
            p_bd_count,
            p_bd_weight,
            float(row['p_low_cost']) if pd.notnull(row['p_low_cost']) else 0,
            float(row['p_high_cost']) if pd.notnull(row['p_high_cost']) else 0,
            int(row['p_low_price']) if pd.notnull(row['p_low_price']) else 0,
            int(row['p_high_price']) if pd.notnull(row['p_high_price']) else 0,
            str(row['p_country']) if pd.notnull(row['p_country']) else '',
            str(row['p_maker']) if pd.notnull(row['p_maker']) else '',
            str(row['p_class']) if pd.notnull(row['p_class']) else '',
            str(row['p_remark']) if pd.notnull(row['p_remark']) else ''
        ]
        
        # Output as pipe-separated values (safer than tab)
        print('|'.join(map(str, values)))
        
        # Show progress every 100 rows
        if (index + 1) % 100 == 0:
            sys.stderr.write(f'Processed {index + 1} rows\r')
    
    sys.stderr.write(f'\nTotal rows processed: {len(df)}\n')
    
except Exception as e:
    sys.stderr.write(f'Error: {str(e)}\n')
    sys.exit(1)
PYTHON;
    
    // Python 스크립트를 파일로 저장
    file_put_contents('temp_import.py', $python_code);
    
    // Python 실행 및 데이터 받기
    $command = 'python3 temp_import.py 2>import_progress.log';
    $handle = popen($command, 'r');
    
    if (!$handle) {
        throw new Exception("Python 스크립트 실행 실패");
    }
    
    // 기존 데이터 삭제
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
    $line_count = 0;
    
    // 데이터 읽기 및 삽입
    while (($line = fgets($handle)) !== false) {
        $line_count++;
        $values = explode("|", trim($line));
        
        if (count($values) != 18) {
            $error_count++;
            continue;
        }
        
        try {
            $stmt->execute($values);
            $success_count++;
            
            if ($success_count % 100 == 0) {
                echo "처리중... " . $success_count . " 건 완료\r";
            }
        } catch (PDOException $e) {
            $error_count++;
            if ($error_count <= 5) {
                echo "\n라인 " . $line_count . " 삽입 실패: " . $e->getMessage() . "\n";
            }
        }
    }
    
    pclose($handle);
    
    // 진행 상황 로그 출력
    if (file_exists('import_progress.log')) {
        echo "\n" . file_get_contents('import_progress.log');
    }
    
    echo "\n\n=== 임포트 완료 ===\n";
    echo "성공: " . $success_count . "건\n";
    echo "실패: " . $error_count . "건\n";
    
    // 데이터 확인
    $stmt = $pdo->query("
        SELECT p_standard, p_material, COUNT(*) as cnt, 
               MIN(p_unit_length) as min_len, MAX(p_unit_length) as max_len
        FROM rebar_bundle_data 
        GROUP BY p_standard, p_material 
        ORDER BY p_standard, p_material
        LIMIT 20
    ");
    $summary = $stmt->fetchAll();
    
    echo "\n=== 규격별 데이터 요약 ===\n";
    foreach ($summary as $row) {
        echo $row['p_standard'] . " (" . $row['p_material'] . "): " . 
             $row['cnt'] . "건, 길이 " . $row['min_len'] . "~" . $row['max_len'] . "m\n";
    }
    
    // 샘플 데이터 확인
    echo "\n=== 샘플 데이터 (HD16, 8m) ===\n";
    $stmt = $pdo->prepare("
        SELECT * FROM rebar_bundle_data 
        WHERE p_standard = 'HD16' AND p_unit_length = 8.0 
        LIMIT 1
    ");
    $stmt->execute();
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        echo "규격: " . $sample['p_standard'] . "\n";
        echo "재질: " . $sample['p_material'] . "\n";
        echo "길이: " . $sample['p_unit_length'] . "m\n";
        echo "번들당 본수: " . $sample['p_bd_count'] . "본\n";
        echo "번들당 중량: " . $sample['p_bd_weight'] . "kg\n";
    }
    
    // 임시 파일 삭제
    unlink('temp_import.py');
    unlink('import_progress.log');
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>