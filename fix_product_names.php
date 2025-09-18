<?php
// 제품명 중복 규격 제거 스크립트
require_once 'db.php';

echo "<h2>제품명 규격 중복 제거 스크립트</h2>";

// 1. 제품명에 규격이 포함되어 있고 specification도 있는 제품 찾기
$stmt = $pdo->query("
    SELECT id, product_name, specification, specifications, category_code
    FROM products
    WHERE specification IS NOT NULL
    OR specifications IS NOT NULL
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>검사할 제품: " . count($products) . "개</p>";

$updated = 0;
$skipped = 0;

foreach ($products as $product) {
    $spec = $product['specification'] ?: $product['specifications'];

    if (!$spec) {
        $skipped++;
        continue;
    }

    // 제품명에서 규격 부분 제거
    $originalName = $product['product_name'];

    // 규격이 제품명에 포함되어 있는지 확인
    if (strpos($originalName, $spec) !== false) {
        // 제품명에서 규격 제거
        $cleanName = trim(str_replace($spec, '', $originalName));

        // 빈 공백 정리
        $cleanName = preg_replace('/\s+/', ' ', $cleanName);

        if ($cleanName !== $originalName) {
            // 업데이트
            $updateStmt = $pdo->prepare("
                UPDATE products
                SET product_name = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$cleanName, $product['id']]);

            echo "✅ ID {$product['id']}: '{$originalName}' → '{$cleanName}'<br>";
            $updated++;
        } else {
            $skipped++;
        }
    } else {
        // 규격 패턴으로 매칭 시도
        if (preg_match('/\s+(\d+[\*xX×]\d+(?:[\*xX×]\d+)*(?:[\*xX×]\d+)*)/', $originalName, $matches)) {
            $foundSpec = $matches[1];

            // 제품명에서 찾은 규격 제거
            $cleanName = trim(str_replace($foundSpec, '', $originalName));
            $cleanName = preg_replace('/\s+/', ' ', $cleanName);

            // specification 컬럼이 비어있다면 채우기
            if (!$product['specification'] && !$product['specifications']) {
                $updateStmt = $pdo->prepare("
                    UPDATE products
                    SET product_name = ?,
                        specification = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$cleanName, $foundSpec, $product['id']]);
                echo "✅ ID {$product['id']}: '{$originalName}' → '{$cleanName}' (규격: {$foundSpec})<br>";
            } else {
                $updateStmt = $pdo->prepare("
                    UPDATE products
                    SET product_name = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$cleanName, $product['id']]);
                echo "✅ ID {$product['id']}: '{$originalName}' → '{$cleanName}'<br>";
            }
            $updated++;
        } else {
            $skipped++;
        }
    }
}

echo "<hr>";
echo "<p><strong>처리 결과:</strong></p>";
echo "<ul>";
echo "<li>업데이트됨: {$updated}개</li>";
echo "<li>건너뜀: {$skipped}개</li>";
echo "</ul>";

// 2. 특정 카테고리별 제품명 표준화
echo "<hr><h3>카테고리별 제품명 표준화</h3>";

$categories = [
    'h-beam' => 'H형강',
    'i-beam' => 'I형강',
    'angle' => '앵글',
    'channel' => '채널',
    'flat-bar' => '평철',
    'round-bar' => '환봉',
    'square-pipe' => '각파이프',
    'round-pipe' => '원형파이프'
];

foreach ($categories as $code => $name) {
    $stmt = $pdo->prepare("
        UPDATE products
        SET product_name = ?
        WHERE category_code = ?
        AND product_name LIKE ?
        AND (specification IS NOT NULL OR specifications IS NOT NULL)
    ");

    $stmt->execute([$name, $code, $name . '%']);
    $affected = $stmt->rowCount();

    if ($affected > 0) {
        echo "✅ {$name} 카테고리: {$affected}개 표준화<br>";
    }
}

echo "<br><a href='/admin/admin_products.php'>관리자 제품 목록으로 돌아가기</a>";
?>