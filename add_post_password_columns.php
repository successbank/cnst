<?php
require_once 'db.php';

try {
    // board_consignment 테이블에 컬럼 추가
    $sqls = [
        "ALTER TABLE board_consignment ADD COLUMN post_password VARCHAR(255) DEFAULT NULL COMMENT '게시글 비밀번호'",
        "ALTER TABLE board_consignment ADD COLUMN member_id INT DEFAULT NULL COMMENT '작성자 회원 ID'",
        "ALTER TABLE board_quote ADD COLUMN post_password VARCHAR(255) DEFAULT NULL COMMENT '게시글 비밀번호'",
        "ALTER TABLE board_quote ADD COLUMN member_id INT DEFAULT NULL COMMENT '작성자 회원 ID'",
        "ALTER TABLE board_notice ADD COLUMN post_password VARCHAR(255) DEFAULT NULL COMMENT '게시글 비밀번호'",
        "ALTER TABLE board_notice ADD COLUMN member_id INT DEFAULT NULL COMMENT '작성자 회원 ID'",
        "ALTER TABLE board_news ADD COLUMN post_password VARCHAR(255) DEFAULT NULL COMMENT '게시글 비밀번호'",
        "ALTER TABLE board_news ADD COLUMN member_id INT DEFAULT NULL COMMENT '작성자 회원 ID'"
    ];
    
    foreach ($sqls as $sql) {
        try {
            $pdo->exec($sql);
            echo "실행 성공: " . substr($sql, 0, 50) . "...<br>\n";
        } catch (PDOException $e) {
            // 이미 컬럼이 존재하는 경우 무시
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "실행 실패: " . $sql . "<br>\n";
                echo "오류: " . $e->getMessage() . "<br>\n";
            } else {
                echo "이미 존재: " . substr($sql, 0, 50) . "...<br>\n";
            }
        }
    }
    
    echo "<br>컬럼 추가 작업이 완료되었습니다.";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>