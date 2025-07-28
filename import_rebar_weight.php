<?php
// 데이터베이스 연결
$host = 'localhost';
$dbname = 'project1_db';
$username = 'root';
$password = 'rootpassword';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}

// Python 스크립트로 엑셀 데이터 파싱
$command = "cd /home/successbank/projects/docker/project1/html/114 && python3 -c \"
import pandas as pd
import json

df = pd.read_excel('철근.xlsx')

specs = {
    'D10': ('D10 / 0.56', 'Unnamed: 2', 'Unnamed: 3'),
    'D13': ('D13 / 0.995', 'Unnamed: 5', 'Unnamed: 6'),
    'D16': ('D16 / 1.56', 'Unnamed: 8', 'Unnamed: 9'),
    'D19': ('D19 / 2.25', 'Unnamed: 11', 'Unnamed: 12'),
    'D22': ('D22 / 3.04', 'Unnamed: 14', 'Unnamed: 15'),
    'D25': ('D25 / 3.98', 'Unnamed: 17', 'Unnamed: 18'),
    'D29': ('D29 / 5.04', 'Unnamed: 20', 'Unnamed: 21'),
    'D32': ('D32 / 6.23', 'Unnamed: 23', 'Unnamed: 24'),
    'D35': ('D35 / 7.51', 'Unnamed: 26', 'Unnamed: 27'),
    'D38': ('D38 / 8.95', 'Unnamed: 29', 'Unnamed: 30'),
    'D41': ('D41 / 10.5', 'Unnamed: 32', 'Unnamed: 33'),
    'D51': ('D51 / 15.9', 'Unnamed: 35', 'Unnamed: 36')
}

result = []
for spec_name, (weight_col, pieces_col, total_col) in specs.items():
    for idx in range(1, len(df)):
        length = df.iloc[idx]['길이']
        total_weight = df.iloc[idx][total_col]
        
        if pd.notna(length) and pd.notna(total_weight):
            result.append({
                'spec_name': spec_name,
                'length': float(length),
                'total_weight': float(total_weight)
            })

print(json.dumps(result))
\"";

$output = shell_exec($command);
$data = json_decode($output, true);

if (!$data) {
    die("엑셀 데이터 파싱 실패");
}

$update_count = 0;

foreach ($data as $item) {
    // 규격 ID 가져오기
    $stmt = $pdo->prepare("SELECT id FROM rebar_specifications WHERE spec_name = ?");
    $stmt->execute([$item['spec_name']]);
    $spec = $stmt->fetch();
    
    if (!$spec) {
        echo "{$item['spec_name']} 규격을 찾을 수 없습니다.\n";
        continue;
    }
    
    // total_weight 업데이트
    $stmt = $pdo->prepare("
        UPDATE rebar_length_info 
        SET total_weight = ? 
        WHERE spec_id = ? AND length = ?
    ");
    $stmt->execute([$item['total_weight'], $spec['id'], $item['length']]);
    
    if ($stmt->rowCount() > 0) {
        $update_count++;
        echo "{$item['spec_name']} {$item['length']}m: {$item['total_weight']}kg 업데이트\n";
    }
}

echo "\n총 {$update_count}개 레코드 업데이트 완료\n";
?>