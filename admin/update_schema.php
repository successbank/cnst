<?php
require_once '../db.php';

try {
    echo "데이터베이스 스키마 업데이트 시작...\n\n";
    
    // SQL 파일 읽기
    $sql = file_get_contents('../sql/update_products_calculation.sql');
    
    // SQL을 개별 명령으로 분리
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ 실행 완료: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // 이미 존재하는 컬럼이나 테이블에 대한 오류는 무시
                if (strpos($e->getMessage(), 'Duplicate column name') !== false ||
                    strpos($e->getMessage(), 'already exists') !== false) {
                    echo "ℹ 이미 존재: " . substr($statement, 0, 50) . "...\n";
                } else {
                    echo "✗ 오류: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n스키마 업데이트 완료!\n";
    
    // has_calculator 필드가 있는지 확인
    $check = $pdo->query("SHOW COLUMNS FROM products LIKE 'has_calculator'");
    if ($check->rowCount() > 0) {
        echo "✓ has_calculator 컬럼이 성공적으로 추가되었습니다.\n";
    } else {
        echo "✗ has_calculator 컬럼 추가 실패\n";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}

// 웹에서 실행한 경우
if (php_sapi_name() !== 'cli') {
    echo "<pre>";
    echo "\n<a href='import_excel_data.php?run=1'>Excel 데이터 임포트 실행하기</a>";
    echo "</pre>";
}
?>