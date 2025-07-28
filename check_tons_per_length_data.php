<?php
require_once 'db.php';

echo "=== 사용자 제공 데이터와 기존 DB 데이터 비교 ===\n\n";

// 사용자가 제공한 데이터
$user_data = [
    'D10' => [
        '6.0' => 1008,
        '6.1' => 1005,
        '7.1' => 955,
        '7.2' => 967
    ],
    'D13' => [
        '6.1' => 971,
        '6.2' => 987
    ],
    'D25' => [
        '8.0' => 1019,
        '8.1' => 1032
    ]
];

foreach ($user_data as $spec_name => $lengths) {
    echo "【{$spec_name} 규격】\n";
    
    // 규격 정보 조회
    $stmt = $pdo->prepare("
        SELECT id, unit_weight 
        FROM rebar_specifications 
        WHERE spec_name = ?
    ");
    $stmt->execute([$spec_name]);
    $spec = $stmt->fetch();
    
    if (!$spec) {
        echo "  ❌ 규격을 찾을 수 없습니다.\n\n";
        continue;
    }
    
    echo "  단위중량: {$spec['unit_weight']}kg/m\n\n";
    
    foreach ($lengths as $length => $user_tons_value) {
        // DB에서 해당 길이의 데이터 조회
        $stmt = $pdo->prepare("
            SELECT 
                length,
                pieces_per_ton,
                weight_per_piece,
                total_weight
            FROM rebar_length_info
            WHERE spec_id = ? AND length = ?
        ");
        $stmt->execute([$spec['id'], $length]);
        $db_data = $stmt->fetch();
        
        echo "  길이 {$length}m:\n";
        echo "    - 사용자 제공값: {$user_tons_value}\n";
        
        if ($db_data) {
            echo "    - DB 톤당 본수: {$db_data['pieces_per_ton']}본\n";
            echo "    - DB 본당 중량: {$db_data['weight_per_piece']}kg\n";
            
            // 본당 중량으로 톤당 본수 계산
            $calculated_pieces = round(1000 / $db_data['weight_per_piece']);
            echo "    - 계산된 톤당 본수: {$calculated_pieces}본\n";
            
            // 사용자 값과 비교
            if ($user_tons_value == $db_data['pieces_per_ton']) {
                echo "    ✅ 일치함\n";
            } else {
                echo "    ❌ 불일치 (차이: " . abs($user_tons_value - $db_data['pieces_per_ton']) . "본)\n";
            }
        } else {
            echo "    ❌ DB에 데이터 없음\n";
            
            // 계산해서 확인
            $weight_per_piece = $spec['unit_weight'] * $length;
            $calculated_pieces = round(1000 / $weight_per_piece);
            echo "    - 계산된 본당 중량: {$weight_per_piece}kg\n";
            echo "    - 계산된 톤당 본수: {$calculated_pieces}본\n";
        }
        echo "\n";
    }
    echo str_repeat("-", 50) . "\n\n";
}

// 기존 데이터 전체 확인
echo "=== DB의 전체 톤당 본수 데이터 ===\n\n";

$stmt = $pdo->query("
    SELECT 
        rs.spec_name,
        rs.unit_weight,
        rl.length,
        rl.pieces_per_ton,
        rl.weight_per_piece,
        ROUND(1000 / rl.weight_per_piece) as calculated_pieces_per_ton
    FROM rebar_specifications rs
    JOIN rebar_length_info rl ON rs.id = rl.spec_id
    WHERE rs.spec_name IN ('D10', 'D13', 'D25')
    ORDER BY rs.spec_name, rl.length
");

$current_spec = '';
foreach ($stmt->fetchAll() as $row) {
    if ($current_spec != $row['spec_name']) {
        if ($current_spec != '') echo "\n";
        echo "【{$row['spec_name']}】 (단위중량: {$row['unit_weight']}kg/m)\n";
        $current_spec = $row['spec_name'];
    }
    
    $match = '';
    if (isset($user_data[$row['spec_name']][$row['length']])) {
        $user_value = $user_data[$row['spec_name']][$row['length']];
        if ($user_value == $row['pieces_per_ton']) {
            $match = ' ✅';
        } else {
            $match = ' ❌ (사용자: ' . $user_value . ')';
        }
    }
    
    echo "  {$row['length']}m: {$row['pieces_per_ton']}본 (본당 {$row['weight_per_piece']}kg){$match}\n";
}
?>