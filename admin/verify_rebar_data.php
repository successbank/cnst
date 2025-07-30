<?php
require_once '../db.php';

echo "=== 철근 데이터 검증 ===\n\n";

// 1. 전체 데이터 개수 확인
$count = $pdo->query("SELECT COUNT(*) FROM rebar_length_data")->fetchColumn();
echo "전체 데이터 개수: {$count}개\n\n";

// 2. 단위중량 계산 검증 (본중 = 단위중량 × 길이)
echo "=== 단위중량 계산 검증 (오차 0.1% 이상만 표시) ===\n";
$sql = "SELECT spec_name, length, unit_weight, piece_weight,
               ROUND(unit_weight * length, 2) as calculated_weight,
               ROUND(ABS(piece_weight - (unit_weight * length)) / piece_weight * 100, 2) as error_pct
        FROM rebar_length_data
        WHERE ABS(piece_weight - (unit_weight * length)) / piece_weight > 0.001
        LIMIT 10";

$result = $pdo->query($sql);
$errorCount = 0;
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "규격: {$row['spec_name']}, 길이: {$row['length']}m, ";
    echo "본중: {$row['piece_weight']}kg, 계산값: {$row['calculated_weight']}kg, ";
    echo "오차: {$row['error_pct']}%\n";
    $errorCount++;
}
if ($errorCount == 0) {
    echo "모든 데이터의 본중 계산이 정확합니다.\n";
}

// 3. 톤당 본수 검증
echo "\n=== 톤당 본수 검증 ===\n";
$sql = "SELECT spec_name, length, piece_weight, pieces_per_ton,
               ROUND(1000 / piece_weight) as calculated_pieces
        FROM rebar_length_data
        WHERE spec_name = 'D10' AND length IN (6.0, 8.0, 10.0, 12.0)
        ORDER BY length";

$result = $pdo->query($sql);
echo "규격\t길이\t본중\t톤당본수\t계산값\n";
echo str_repeat("-", 50) . "\n";
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['spec_name']}\t{$row['length']}m\t{$row['piece_weight']}kg\t";
    echo "{$row['pieces_per_ton']}본\t{$row['calculated_pieces']}본\n";
}

// 4. 각 규격별 데이터 존재 확인
echo "\n=== 각 규격별 데이터 수 ===\n";
$sql = "SELECT spec_name, unit_weight, COUNT(*) as count
        FROM rebar_length_data
        GROUP BY spec_name, unit_weight
        ORDER BY spec_name";

$result = $pdo->query($sql);
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['spec_name']} ({$row['unit_weight']}kg/m): {$row['count']}개\n";
}

echo "\n임포트된 데이터가 검증되었습니다.\n";
?>