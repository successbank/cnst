<?php
// 직접 UPDATE 테스트
session_start();
require_once 'admin_check.php';
require_once '../db.php';

// 파라미터 설정
$category_id = 5; // light-h-beam의 ID (실제 ID로 변경)
$custom_url = '/test-custom-url.php';
$url_target = '_blank';

echo "<h3>카테고리 URL 업데이트 테스트</h3>";

try {
    // 먼저 카테고리 확인
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE category_code = 'light-h-beam'");
    $stmt->execute();
    $category = $stmt->fetch();

    if ($category) {
        $category_id = $category['id'];

        echo "<p>찾은 카테고리: ID={$category_id}, Name={$category['category_name']}</p>";
        echo "<p>현재 custom_url: " . ($category['custom_url'] ?: '(없음)') . "</p>";

        // UPDATE 실행
        $stmt = $pdo->prepare("
            UPDATE product_categories
            SET custom_url = ?, url_target = ?
            WHERE id = ?
        ");

        $result = $stmt->execute([$custom_url, $url_target, $category_id]);

        if ($result) {
            echo "<p style='color:green'>✅ UPDATE 성공!</p>";

            // 결과 확인
            $stmt = $pdo->prepare("SELECT custom_url, url_target FROM product_categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $updated = $stmt->fetch();

            echo "<p>업데이트된 값:</p>";
            echo "<ul>";
            echo "<li>custom_url: {$updated['custom_url']}</li>";
            echo "<li>url_target: {$updated['url_target']}</li>";
            echo "</ul>";
        } else {
            echo "<p style='color:red'>UPDATE 실패</p>";
        }

    } else {
        echo "<p>light-h-beam 카테고리를 찾을 수 없습니다.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>SQL 오류: " . $e->getMessage() . "</p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
}

echo "<hr>";
echo "<a href='admin_product_categories_tree_v2.php'>카테고리 관리 페이지로 돌아가기</a>";
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
</style>