<?php
// 데이터베이스 규격 동기화 스크립트
require_once 'db.php';

echo "<h2>제품 규격 동기화 스크립트</h2>";

// 1. specification 컬럼이 비어있는 제품들 찾기
$stmt = $pdo->query("
    SELECT id, product_name, specifications, specification
    FROM products
    WHERE (specification IS NULL OR specification = '')
    AND specifications IS NOT NULL
    AND specifications != ''
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>규격 동기화가 필요한 제품: " . count($products) . "개</p>";

foreach ($products as $product) {
    // specifications -> specification으로 복사
    $updateStmt = $pdo->prepare("
        UPDATE products
        SET specification = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$product['specifications'], $product['id']]);

    echo "✅ ID {$product['id']}: {$product['product_name']} - 규격 동기화 완료<br>";
}

// 2. 제품명에서 규격 추출하기 (선택사항)
echo "<hr><h3>제품명에서 규격 추출</h3>";

$stmt = $pdo->query("
    SELECT id, product_name, specification
    FROM products
    WHERE id = 164
");

$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product) {
    echo "<p>제품 ID 164 처리 중...</p>";

    $productName = $product['product_name'];

    // 규격 패턴 매칭 (숫자*숫자 또는 숫자x숫자 패턴)
    if (preg_match('/([0-9]+[\*xX×][0-9]+[\*xX×]?[0-9]*[\*xX×]?[0-9]*)/', $productName, $matches)) {
        $specification = $matches[1];

        // 제품명에서 규격 제거
        $cleanProductName = trim(preg_replace('/\s*' . preg_quote($specification, '/') . '\s*/', ' ', $productName));

        echo "원본 제품명: {$productName}<br>";
        echo "추출된 규격: {$specification}<br>";
        echo "정리된 제품명: {$cleanProductName}<br>";

        // 데이터베이스 업데이트
        $updateStmt = $pdo->prepare("
            UPDATE products
            SET product_name = ?,
                specification = ?,
                specifications = ?
            WHERE id = ?
        ");

        $updateStmt->execute([
            $cleanProductName,
            $specification,
            $specification,
            164
        ]);

        echo "<p style='color: green;'>✅ 업데이트 완료!</p>";
    }
}

// 3. 전체 제품 규격 현황 확인
echo "<hr><h3>전체 제품 규격 현황</h3>";

$stmt = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN specification IS NOT NULL AND specification != '' THEN 1 ELSE 0 END) as with_specification,
        SUM(CASE WHEN specifications IS NOT NULL AND specifications != '' THEN 1 ELSE 0 END) as with_specifications
    FROM products
");

$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>전체 제품</th><th>specification 있음</th><th>specifications 있음</th></tr>";
echo "<tr>";
echo "<td>{$stats['total']}</td>";
echo "<td>{$stats['with_specification']}</td>";
echo "<td>{$stats['with_specifications']}</td>";
echo "</tr>";
echo "</table>";

echo "<br><a href='/admin/admin_products.php'>관리자 제품 목록으로 돌아가기</a>";
?>