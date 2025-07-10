<?php
require_once 'admin_check.php';
require_once '../db.php';

echo "<h2>Database Connection Test</h2>";

try {
    // 기본 연결 테스트
    echo "PDO 연결 테스트: ";
    if($pdo) {
        echo "성공<br>";
    } else {
        echo "실패<br>";
    }
    
    // 테이블 존재 확인
    $stmt = $pdo->query("SHOW TABLES LIKE 'board_quote'");
    $result = $stmt->fetch();
    echo "board_quote 테이블 존재: " . ($result ? "예" : "아니오") . "<br>";
    
    // 전체 데이터 수
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM board_quote");
    $total = $stmt->fetch();
    echo "전체 견적문의 수: " . $total['total'] . "<br>";
    
    // 테이블 구조 확인
    echo "<h3>board_quote 테이블 구조:</h3>";
    $stmt = $pdo->query("DESCRIBE board_quote");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 최신 5개 데이터
    $stmt = $pdo->query("SELECT id, title, writer, company, is_answered, admin_reply, created_at FROM board_quote ORDER BY id DESC LIMIT 5");
    $quotes = $stmt->fetchAll();
    
    echo "<h3>최신 견적문의 5개:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>제목</th><th>작성자</th><th>회사</th><th>답변상태</th><th>답변내용</th><th>작성일</th></tr>";
    
    foreach($quotes as $quote) {
        echo "<tr>";
        echo "<td>" . $quote['id'] . "</td>";
        echo "<td>" . htmlspecialchars($quote['title']) . "</td>";
        echo "<td>" . htmlspecialchars($quote['writer']) . "</td>";
        echo "<td>" . htmlspecialchars($quote['company'] ?? '') . "</td>";
        echo "<td>" . ($quote['is_answered'] ? '답변완료' : '대기중') . "</td>";
        echo "<td>" . htmlspecialchars($quote['admin_reply'] ?? '') . "</td>";
        echo "<td>" . $quote['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "오류 발생: " . $e->getMessage() . "<br>";
    echo "오류 코드: " . $e->getCode() . "<br>";
}
?>