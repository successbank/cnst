<?php
// 테스트 스크립트 - 카테고리 URL 업데이트 확인
require_once '../db.php';

try {
    // 경량H형강 카테고리 확인
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE category_code = 'light-h-beam'");
    $stmt->execute();
    $category = $stmt->fetch();

    if ($category) {
        echo "<h3>경량H형강 카테고리 정보:</h3>";
        echo "<pre>";
        echo "ID: " . $category['id'] . "\n";
        echo "카테고리명: " . $category['category_name'] . "\n";
        echo "커스텀 URL: " . ($category['custom_url'] ?: '(없음)') . "\n";
        echo "URL 타겟: " . ($category['url_target'] ?: '_self') . "\n";
        echo "</pre>";

        // 테스트 업데이트
        if (isset($_GET['update'])) {
            $test_url = '/test-page.php';
            $stmt = $pdo->prepare("UPDATE product_categories SET custom_url = ?, url_target = ? WHERE id = ?");
            $stmt->execute([$test_url, '_blank', $category['id']]);
            echo "<p style='color: green;'>✅ 테스트 URL 업데이트 완료!</p>";
            echo "<p>새로고침하여 확인하세요.</p>";
        } else {
            echo "<p><a href='?update=1'>테스트 URL 업데이트 실행</a></p>";
        }
    } else {
        echo "경량H형강 카테고리를 찾을 수 없습니다.";
    }

    // 모든 카테고리의 URL 정보 표시
    echo "<h3>모든 카테고리 URL 정보:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>코드</th><th>이름</th><th>커스텀 URL</th><th>타겟</th></tr>";

    $stmt = $pdo->query("SELECT id, category_code, category_name, custom_url, url_target FROM product_categories ORDER BY display_order");
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['category_code'] . "</td>";
        echo "<td>" . $row['category_name'] . "</td>";
        echo "<td>" . ($row['custom_url'] ?: '-') . "</td>";
        echo "<td>" . ($row['url_target'] ?: '_self') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "오류: " . $e->getMessage();
}
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { border-collapse: collapse; margin-top: 10px; }
    th { background: #f0f0f0; }
    td, th { padding: 8px; text-align: left; }
</style>