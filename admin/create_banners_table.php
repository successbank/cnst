<?php
require_once '../db.php';

try {
    // SQL 파일 읽기
    $sql = file_get_contents('../sql/create_banners_table.sql');
    
    // SQL 실행
    $pdo->exec($sql);
    
    echo "배너 테이블이 성공적으로 생성되었습니다.\n";
    
    // 테이블 확인
    $stmt = $pdo->query("SHOW TABLES LIKE 'banners'");
    if ($stmt->fetch()) {
        echo "banners 테이블이 확인되었습니다.\n";
        
        // 컬럼 정보 출력
        $stmt = $pdo->query("DESCRIBE banners");
        echo "\n테이블 구조:\n";
        while ($row = $stmt->fetch()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>