<?php
require_once 'admin_check.php';
require_once '../db.php';

try {
    // link_url이 잘못된 값인 경우 NULL로 업데이트
    $stmt = $pdo->prepare("UPDATE layer_popups SET link_url = NULL WHERE link_url LIKE '%Deprecated%' OR link_url LIKE '%htmlspecialchars%'");
    $stmt->execute();
    $affected = $stmt->rowCount();

    echo "<h2 style='color: green;'>link_url 필드 정리 완료</h2>";
    echo "<p>수정된 레코드: {$affected}개</p>";

    // 현재 팝업 데이터 확인
    $stmt = $pdo->query("SELECT id, title, link_url FROM layer_popups");
    $popups = $stmt->fetchAll();

    echo "<h3>현재 팝업 데이터:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>제목</th><th>링크 URL</th></tr>";
    foreach ($popups as $popup) {
        echo "<tr>";
        echo "<td>{$popup['id']}</td>";
        echo "<td>" . htmlspecialchars($popup['title'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($popup['link_url'] ?? '(없음)') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p><a href='admin_layer_popups.php'>팝업 관리로 이동</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>오류</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
