<?php
require_once 'db.php';

try {
    // board_quote 테이블에 member_id 컬럼 추가
    $sql = "ALTER TABLE board_quote ADD COLUMN IF NOT EXISTS member_id INT DEFAULT NULL";
    $pdo->exec($sql);
    echo "board_quote 테이블에 member_id 컬럼 추가 완료<br>";
    
    // board_consignment 테이블에 member_id 컬럼 추가
    $sql = "ALTER TABLE board_consignment ADD COLUMN IF NOT EXISTS member_id INT DEFAULT NULL";
    $pdo->exec($sql);
    echo "board_consignment 테이블에 member_id 컬럼 추가 완료<br>";
    
    // 기존 데이터 업데이트 - writer 기반으로 member_id 매칭
    // board_quote 업데이트
    $sql = "UPDATE board_quote bq 
            INNER JOIN members m ON bq.writer = m.user_id 
            SET bq.member_id = m.id 
            WHERE bq.member_id IS NULL";
    $pdo->exec($sql);
    echo "board_quote 기존 데이터 member_id 업데이트 완료<br>";
    
    // board_consignment 업데이트
    $sql = "UPDATE board_consignment bc 
            INNER JOIN members m ON bc.writer = m.user_id 
            SET bc.member_id = m.id 
            WHERE bc.member_id IS NULL";
    $pdo->exec($sql);
    echo "board_consignment 기존 데이터 member_id 업데이트 완료<br>";
    
    echo "<br>모든 작업이 완료되었습니다.";
    
} catch (PDOException $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>