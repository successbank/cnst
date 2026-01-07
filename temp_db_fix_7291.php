<?php
// 임시 DB 수정 스크립트 - 실행 후 즉시 삭제
require_once 'db.php';

try {
    $pdo = getDB();

    // 먼저 컬럼이 있는지 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM board_news LIKE 'source_url'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "source_url 컬럼이 이미 존재합니다.";
    } else {
        // 컬럼 추가
        $pdo->exec("ALTER TABLE board_news ADD COLUMN source_url VARCHAR(500) DEFAULT NULL AFTER source");
        echo "source_url 컬럼이 성공적으로 추가되었습니다!";
    }

    // 현재 테이블 구조 출력
    echo "<br><br>현재 board_news 테이블 구조:<br>";
    $stmt = $pdo->query("DESCRIBE board_news");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "<br>";
    }

} catch (PDOException $e) {
    echo "오류: " . $e->getMessage();
}
?>
