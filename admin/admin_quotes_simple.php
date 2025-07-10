<?php
require_once 'admin_check.php';
require_once '../db.php';

echo "<h1>견적문의 관리 (간단 버전)</h1>";

try {
    $stmt = $pdo->query("SELECT * FROM board_quote ORDER BY id DESC LIMIT 10");
    $quotes = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>제목</th><th>작성자</th><th>회사</th><th>상태</th><th>작성일</th></tr>";
    
    foreach($quotes as $quote) {
        echo "<tr>";
        echo "<td>" . $quote['id'] . "</td>";
        echo "<td>" . htmlspecialchars($quote['title']) . "</td>";
        echo "<td>" . htmlspecialchars($quote['writer']) . "</td>";
        echo "<td>" . htmlspecialchars($quote['company'] ?? '') . "</td>";
        echo "<td>" . ($quote['is_answered'] ? '답변완료' : '대기중') . "</td>";
        echo "<td>" . $quote['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "오류: " . $e->getMessage();
}
?>