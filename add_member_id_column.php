<?php
require_once 'db.php';

try {
    // board_consignment 테이블의 컬럼 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM board_consignment LIKE 'member_id'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // member_id 컬럼 추가
        $sql = "ALTER TABLE board_consignment ADD COLUMN member_id INT DEFAULT NULL COMMENT '작성자 회원 ID' AFTER writer";
        $pdo->exec($sql);
        echo "board_consignment 테이블에 member_id 컬럼을 추가했습니다.<br>";
        
        // 인덱스 추가
        $sql = "ALTER TABLE board_consignment ADD INDEX idx_member_id (member_id)";
        $pdo->exec($sql);
        echo "member_id 인덱스를 추가했습니다.<br>";
    } else {
        echo "board_consignment 테이블에 이미 member_id 컬럼이 존재합니다.<br>";
    }
    
    echo "<br><a href='consignment.php'>중계판매 페이지로 돌아가기</a>";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>