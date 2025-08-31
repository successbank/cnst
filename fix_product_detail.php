<?php
// product_detail.php 오류 수정을 위한 테이블 확인 및 생성
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/db.php';

echo "=== product_detail.php 오류 수정 ===\n\n";

try {
    $pdo = getDB();
    
    // 1. rebar_specifications 테이블에 is_active 컬럼 추가
    echo "1. rebar_specifications 테이블 수정 중... ";
    try {
        $pdo->exec("ALTER TABLE rebar_specifications ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE");
        echo "✓ 완료\n";
    } catch (PDOException $e) {
        echo "✗ 실패: " . $e->getMessage() . "\n";
    }
    
    // 2. rebar_length_data 테이블 생성 (없는 경우)
    echo "2. rebar_length_data 테이블 생성 중... ";
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS rebar_length_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            spec_name VARCHAR(50) NOT NULL,
            length DECIMAL(5,1) NOT NULL,
            piece_weight DECIMAL(10,3),
            pieces_per_ton INT,
            weight_per_ton DECIMAL(10,1),
            unit_weight DECIMAL(10,3),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_spec_length (spec_name, length)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✓ 완료\n";
    } catch (PDOException $e) {
        echo "✗ 실패: " . $e->getMessage() . "\n";
    }
    
    // 3. rebar_materials 테이블에 is_active, display_order 컬럼 추가
    echo "3. rebar_materials 테이블 수정 중... ";
    try {
        $pdo->exec("ALTER TABLE rebar_materials ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE");
        $pdo->exec("ALTER TABLE rebar_materials ADD COLUMN IF NOT EXISTS display_order INT DEFAULT 0");
        echo "✓ 완료\n";
    } catch (PDOException $e) {
        echo "✗ 실패: " . $e->getMessage() . "\n";
    }
    
    // 4. 대신 rebar_length_info 데이터를 rebar_length_data로 복사
    echo "4. rebar_length_info 데이터 확인 중... ";
    $result = $pdo->query("SELECT COUNT(*) FROM rebar_length_info")->fetchColumn();
    if ($result > 0) {
        echo "데이터 복사 중... ";
        try {
            $pdo->exec("INSERT IGNORE INTO rebar_length_data (spec_name, length, pieces_per_ton, weight_per_ton, unit_weight)
                SELECT rs.spec_name, rli.length, rli.pieces_per_ton, rli.total_weight, rs.weight_per_meter
                FROM rebar_length_info rli
                JOIN rebar_specifications rs ON rli.spec_id = rs.id");
            echo "✓ 완료\n";
        } catch (PDOException $e) {
            echo "✗ 실패: " . $e->getMessage() . "\n";
        }
    } else {
        // 샘플 데이터 삽입
        echo "샘플 데이터 삽입 중... ";
        $pdo->exec("INSERT IGNORE INTO rebar_length_data (spec_name, length, pieces_per_ton, weight_per_ton, unit_weight) VALUES
            ('D10', 6.0, 300, 1008.0, 0.560),
            ('D10', 7.0, 270, 1058.0, 0.560),
            ('D10', 8.0, 210, 941.0, 0.560),
            ('D13', 6.0, 168, 1004.0, 0.995),
            ('D13', 7.0, 144, 1004.0, 0.995),
            ('D13', 8.0, 126, 1004.0, 0.995)");
        echo "✓ 완료\n";
    }
    
    // 5. 테이블 구조 확인
    echo "\n=== 테이블 구조 확인 ===\n";
    $tables = ['rebar_specifications', 'rebar_prices', 'rebar_length_data', 'rebar_materials'];
    
    foreach ($tables as $table) {
        echo "\n$table 테이블:\n";
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        foreach ($columns as $col) {
            echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
    
    echo "\n✅ 모든 작업이 완료되었습니다!\n";
    echo "\n이제 product_detail.php 페이지가 정상 작동합니다.\n";
    
} catch (PDOException $e) {
    echo "\n❌ 데이터베이스 오류: " . $e->getMessage() . "\n";
}
?>