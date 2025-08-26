<?php
// 데이터베이스 복원 스크립트
require_once 'db.php';

try {
    $pdo = getDB();
    echo "데이터베이스 연결 성공\n";
    
    // SQL 파일 목록
    $sqlFiles = [
        'sql/create_members_table.sql',
        'sql/create_site_settings.sql',
        'sql/create_unit_weights_table.sql',
        'sql/create_products_table.sql',
        'sql/create_rebar_tables.sql',
        'sql/create_login_tables.sql',
        'sql/create_member_addresses_table.sql',
        'sql/create_kakao_log_table.sql',
        'sql/create_dashboard_tables.sql',
        'sql/insert_sample_data.sql',
        'sql/insert_rebar_products.sql',
        'sql/insert_rebar_materials.sql'
    ];
    
    foreach ($sqlFiles as $file) {
        $filePath = __DIR__ . '/' . $file;
        if (file_exists($filePath)) {
            echo "\n실행 중: $file\n";
            $sql = file_get_contents($filePath);
            
            // 여러 SQL 문을 분리하여 실행
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                        echo ".";
                    } catch (PDOException $e) {
                        // 이미 존재하는 테이블 오류는 무시
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            echo "\n오류: " . $e->getMessage() . "\n";
                        }
                    }
                }
            }
            echo " 완료\n";
        } else {
            echo "파일 없음: $file\n";
        }
    }
    
    // 테이블 목록 확인
    echo "\n생성된 테이블 목록:\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
} catch (PDOException $e) {
    echo "데이터베이스 오류: " . $e->getMessage() . "\n";
}
?>