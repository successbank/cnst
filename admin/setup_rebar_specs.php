<?php
require_once '../db.php';

echo "=== 철근 규격 데이터 설정 ===\n\n";

try {
    // 기존 데이터 확인
    $result = $pdo->query("SELECT COUNT(*) FROM rebar_specifications");
    $count = $result->fetchColumn();
    echo "현재 rebar_specifications 테이블에 {$count}개의 데이터가 있습니다.\n";
    
    // 철근 규격 데이터 추가
    $specs = [
        ['D10', 9.53, 0.560],
        ['D13', 12.7, 0.995],
        ['D16', 15.9, 1.560],
        ['D19', 19.1, 2.250],
        ['D22', 22.2, 3.040],
        ['D25', 25.4, 3.980],
        ['D29', 28.6, 5.040],
        ['D32', 31.8, 6.230],
        ['D35', 34.9, 7.510],
        ['D38', 38.1, 8.950],
        ['D41', 41.3, 10.500],
        ['D51', 50.8, 15.900]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO rebar_specifications (spec_name, diameter, unit_weight, description, display_order, is_active)
        VALUES (?, ?, ?, ?, ?, TRUE)
        ON DUPLICATE KEY UPDATE 
            diameter = VALUES(diameter),
            unit_weight = VALUES(unit_weight),
            is_active = TRUE
    ");
    
    $added = 0;
    foreach ($specs as $index => $spec) {
        $description = "이형철근 {$spec[0]}, 직경: {$spec[1]}mm, 단위중량: {$spec[2]}kg/m";
        $stmt->execute([$spec[0], $spec[1], $spec[2], $description, ($index + 1) * 10]);
        $added++;
    }
    
    echo "\n{$added}개의 철근 규격 데이터를 추가/업데이트했습니다.\n";
    
    // 기준 가격 추가 (rebar_prices 테이블)
    echo "\n=== 기준 가격 설정 ===\n";
    
    // 모든 규격에 대해 기본 가격 설정 (1000원/kg)
    $stmt = $pdo->prepare("
        INSERT INTO rebar_prices (spec_id, unit_price, effective_date, is_active)
        SELECT id, 1000, CURDATE(), TRUE
        FROM rebar_specifications
        WHERE NOT EXISTS (
            SELECT 1 FROM rebar_prices 
            WHERE spec_id = rebar_specifications.id 
            AND is_active = TRUE
        )
    ");
    $stmt->execute();
    $priceCount = $stmt->rowCount();
    echo "{$priceCount}개의 기준 가격을 추가했습니다.\n";
    
    // 결과 확인
    echo "\n=== 설정 완료 결과 ===\n";
    $result = $pdo->query("
        SELECT rs.*, rp.unit_price 
        FROM rebar_specifications rs
        LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id AND rp.is_active = TRUE
        ORDER BY rs.display_order
    ");
    
    echo "규격\t직경(mm)\t단위중량(kg/m)\t기준단가(원/kg)\n";
    echo str_repeat("-", 60) . "\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['spec_name']}\t{$row['diameter']}\t\t{$row['unit_weight']}\t\t";
        echo ($row['unit_price'] ?? '-') . "\n";
    }
    
    echo "\n설정이 완료되었습니다!\n";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>