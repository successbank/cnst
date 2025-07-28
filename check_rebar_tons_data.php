<?php
require_once 'db.php';

echo "=== 철근 규격별 톤당 본수 데이터 확인 ===\n\n";

// 모든 철근 규격 조회
$stmt = $pdo->query("
    SELECT id, spec_name, diameter, unit_weight 
    FROM rebar_specifications 
    WHERE is_active = TRUE 
    ORDER BY display_order
");
$specs = $stmt->fetchAll();

foreach ($specs as $spec) {
    echo "【{$spec['spec_name']}】\n";
    echo "- 직경: {$spec['diameter']}mm\n";
    echo "- 단위중량: {$spec['unit_weight']}kg/m\n\n";
    
    // 각 규격의 길이별 톤당 본수 조회
    $stmt = $pdo->prepare("
        SELECT length, pieces_per_ton, weight_per_piece 
        FROM rebar_length_info 
        WHERE spec_id = ? 
        ORDER BY length
        LIMIT 10
    ");
    $stmt->execute([$spec['id']]);
    $lengths = $stmt->fetchAll();
    
    echo "길이별 톤당 본수:\n";
    foreach ($lengths as $length) {
        echo "  - {$length['length']}m: 톤당 {$length['pieces_per_ton']}본 (본당 {$length['weight_per_piece']}kg)\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// 요약 정보
echo "=== 요약 정보 ===\n";
$stmt = $pdo->query("
    SELECT 
        rs.spec_name,
        MIN(rl.pieces_per_ton) as min_pieces,
        MAX(rl.pieces_per_ton) as max_pieces,
        COUNT(DISTINCT rl.length) as length_count
    FROM rebar_specifications rs
    JOIN rebar_length_info rl ON rs.id = rl.spec_id
    WHERE rs.is_active = TRUE
    GROUP BY rs.spec_name
    ORDER BY rs.display_order
");

echo "규격 | 최소 톤당본수 | 최대 톤당본수 | 길이 개수\n";
echo str_repeat("-", 50) . "\n";
foreach ($stmt->fetchAll() as $row) {
    printf("%-6s | %13s | %13s | %9s\n", 
        $row['spec_name'], 
        $row['min_pieces'] . '본', 
        $row['max_pieces'] . '본',
        $row['length_count'] . '개'
    );
}
?>