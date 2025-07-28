<?php
require_once 'db.php';

echo "=== 철근 기준단가 실시간 연동 테스트 ===\n\n";

// 특정 철근 규격(D10)의 관리자 페이지 현재단가 확인
$spec_name = 'D10';
$stmt = $pdo->prepare("
    SELECT 
        rs.id,
        rs.spec_name,
        rs.unit_weight,
        COALESCE(rp.unit_price, 0) as current_price,
        rp.effective_date,
        rp.updated_at
    FROM rebar_specifications rs
    LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id 
        AND rp.is_active = TRUE
        AND rp.effective_date <= CURDATE()
        AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE())
    WHERE rs.spec_name = ? 
        AND rs.is_active = TRUE
");
$stmt->execute([$spec_name]);
$result = $stmt->fetch();

if ($result) {
    echo "【{$spec_name} 규격 정보】\n";
    echo "- 규격 ID: {$result['id']}\n";
    echo "- 단위중량: {$result['unit_weight']}kg/m\n";
    echo "- 현재단가(기준단가): " . number_format($result['current_price']) . "원/kg\n";
    echo "- 적용일자: {$result['effective_date']}\n";
    echo "- 최종수정: {$result['updated_at']}\n\n";
    
    // 재질별 최종가격 계산
    echo "【재질별 최종가격】\n";
    $stmt = $pdo->query("
        SELECT 
            material_name,
            additional_price,
            ({$result['current_price']} + additional_price) as final_price
        FROM rebar_materials 
        WHERE is_active = TRUE 
        ORDER BY display_order
    ");
    
    while ($material = $stmt->fetch()) {
        echo "- {$material['material_name']}: ";
        echo number_format($material['final_price']) . "원/kg ";
        echo "(기준 " . number_format($result['current_price']) . " + 재질 " . number_format($material['additional_price']) . ")\n";
    }
    
} else {
    echo "규격 정보를 찾을 수 없습니다.\n";
}

// 모든 철근 규격의 현재단가 확인
echo "\n【모든 철근 규격의 현재단가】\n";
$stmt = $pdo->query("
    SELECT 
        rs.spec_name,
        COALESCE(rp.unit_price, 0) as current_price,
        COUNT(DISTINCT rm.id) as material_count
    FROM rebar_specifications rs
    LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id 
        AND rp.is_active = TRUE
        AND rp.effective_date <= CURDATE()
        AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE())
    CROSS JOIN rebar_materials rm
    WHERE rs.is_active = TRUE AND rm.is_active = TRUE
    GROUP BY rs.id, rs.spec_name, rp.unit_price
    ORDER BY rs.display_order
");

echo "규격 | 현재단가(원/kg) | 재질수\n";
echo str_repeat("-", 40) . "\n";
while ($row = $stmt->fetch()) {
    printf("%-6s | %14s | %d개\n", 
        $row['spec_name'], 
        number_format($row['current_price']),
        $row['material_count']
    );
}
?>