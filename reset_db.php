<?php
// 데이터베이스 리셋 스크립트
require_once 'db.php';

echo "<h1>데이터베이스 리셋</h1>";

try {
    // 기존 테이블 삭제
    $tables = ['board_notice', 'board_quote', 'board_news'];
    
    foreach ($tables as $table) {
        $db->exec("DROP TABLE IF EXISTS $table");
        echo "$table 테이블 삭제 완료<br>";
    }
    
    echo "<br>모든 테이블이 삭제되었습니다.<br>";
    echo "<a href='init_db.php'>데이터베이스 재초기화하기</a>";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>