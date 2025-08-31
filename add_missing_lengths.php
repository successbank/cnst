<?php
// 데이터베이스 연결
$host = 'project5_mysql';
$dbname = 'project5_db';
$username = 'root';
$password = 'rootpassword';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage());
}

// Python 스크립트로 엑셀 데이터 파싱
$command = "cd /var/www/html/114 && python3 -c \"
import pandas as pd
import json

df = pd.read_excel('철근.xlsx')

# 모든 길이 목록 수집
all_lengths = []
for idx in range(1, len(df)):
    length = df.iloc[idx]['길이']
    if pd.notna(length) and 'RED FONT' not in str(length):
        all_lengths.append(float(length))

# 중복 제거 후 정렬
unique_lengths = sorted(list(set(all_lengths)))
print(json.dumps(unique_lengths))
\"";

$output = shell_exec($command);
$all_lengths = json_decode($output, true);

if (!$all_lengths) {
    die("길이 데이터 파싱 실패");
}

// D35, D38, D41, D51에 대해 누락된 길이 추가
$specs = ['D35', 'D38', 'D41', 'D51'];

foreach ($specs as $spec_name) {
    // 규격 ID 가져오기
    $stmt = $pdo->prepare("SELECT id, unit_weight FROM rebar_specifications WHERE spec_name = ?");
    $stmt->execute([$spec_name]);
    $spec = $stmt->fetch();
    
    if (!$spec) {
        echo "{$spec_name} 규격을 찾을 수 없습니다.\n";
        continue;
    }
    
    $spec_id = $spec['id'];
    $unit_weight = $spec['unit_weight'];
    
    // 현재 존재하는 길이 가져오기
    $stmt = $pdo->prepare("SELECT length FROM rebar_length_info WHERE spec_id = ?");
    $stmt->execute([$spec_id]);
    $existing_lengths = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $added = 0;
    foreach ($all_lengths as $length) {
        // 이미 존재하는 길이는 스킵
        if (in_array($length, $existing_lengths)) {
            continue;
        }
        
        // 본당 중량 계산
        $weight_per_piece = $unit_weight * $length;
        
        // 새 길이 추가 (본수와 중량은 NULL로)
        $stmt = $pdo->prepare("
            INSERT INTO rebar_length_info (spec_id, length, weight_per_piece, pieces_per_ton, total_weight)
            VALUES (?, ?, ?, NULL, NULL)
        ");
        $stmt->execute([$spec_id, $length, $weight_per_piece]);
        $added++;
    }
    
    echo "{$spec_name}: {$added}개 길이 추가\n";
}

echo "\n완료\n";
?>