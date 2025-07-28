<?php
require_once 'db.php';

echo "=== 철근 규격별 기본 단가 확인 ===\n\n";

// 규격별 기본 단가 조회
$stmt = $pdo->query("
    SELECT 
        rs.spec_name,
        rs.diameter,
        rs.unit_weight,
        rp.unit_price AS base_price,
        rp.effective_date
    FROM rebar_specifications rs
    LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id 
        AND rp.is_active = TRUE 
        AND rp.effective_date <= CURDATE()
        AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE())
    WHERE rs.is_active = TRUE
    ORDER BY rs.display_order
");
$specs = $stmt->fetchAll();

echo "규격\t직경\t단위중량\t기본단가\t적용일\n";
echo "----------------------------------------------------\n";
foreach ($specs as $spec) {
    echo "{$spec['spec_name']}\t";
    echo "{$spec['diameter']}mm\t";
    echo "{$spec['unit_weight']}kg/m\t";
    echo ($spec['base_price'] ? number_format($spec['base_price']) . "원/kg" : "미설정") . "\t";
    echo ($spec['effective_date'] ?: "-") . "\n";
}

echo "\n=== D10 재질별 최종 가격 예시 ===\n";
// D10의 재질별 가격 표시
$stmt = $pdo->query("
    SELECT 
        rm.material_code,
        rm.material_name,
        rm.additional_price,
        COALESCE(rp.unit_price, 0) AS base_price,
        (COALESCE(rp.unit_price, 0) + rm.additional_price) AS total_price
    FROM rebar_materials rm
    LEFT JOIN (
        SELECT rp.* 
        FROM rebar_prices rp
        JOIN rebar_specifications rs ON rs.id = rp.spec_id
        WHERE rs.spec_name = 'D10' 
        AND rp.is_active = TRUE 
        AND rp.effective_date <= CURDATE()
        AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE())
    ) rp ON 1=1
    WHERE rm.is_active = TRUE
    ORDER BY rm.display_order
");
$prices = $stmt->fetchAll();

echo "\n재질\t추가단가\t기본단가\t최종가격\n";
echo "----------------------------------------------------\n";
foreach ($prices as $price) {
    echo "{$price['material_code']}\t";
    echo number_format($price['additional_price']) . "원\t";
    echo number_format($price['base_price']) . "원\t";
    echo number_format($price['total_price']) . "원/kg\n";
}
?>