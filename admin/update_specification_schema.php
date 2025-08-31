<?php
require_once '../db.php';

try {
    echo "규격별 표시 스키마 업데이트 시작...\n\n";
    
    // SQL 파일 읽기
    $sql = file_get_contents('../sql/update_specification_display.sql');
    
    // SQL을 개별 명령으로 분리
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ 실행 완료: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
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
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>