<?php
session_start();
require_once 'admin_check.php';
require_once '../db.php';

echo "<h2>트리 구조 테스트</h2>";
echo "<pre>";

// 1. 컬럼 확인
echo "1. 컬럼 존재 확인:\n";
$columns = $pdo->query("SHOW COLUMNS FROM product_categories")->fetchAll();
foreach ($columns as $col) {
    echo "   - {$col['Field']} ({$col['Type']})\n";
}

// 2. 카테고리 데이터 확인
echo "\n2. 카테고리 데이터:\n";
$stmt = $pdo->query("
    SELECT
        pc.*,
        COUNT(DISTINCT p.id) as product_count
    FROM product_categories pc
    LEFT JOIN products p ON pc.category_code = p.category_code AND p.is_active = 1
    GROUP BY pc.id
    ORDER BY IFNULL(pc.parent_id, 0), pc.display_order, pc.id
");
$categories = $stmt->fetchAll();

foreach ($categories as $cat) {
    echo sprintf("   [%d] %s (parent: %s, level: %s, products: %d)\n",
        $cat['id'],
        $cat['category_name'],
        $cat['parent_id'] ?? 'NULL',
        $cat['level'] ?? '0',
        $cat['product_count']
    );
}

// 3. AJAX 엔드포인트 직접 테스트
echo "\n3. AJAX 엔드포인트 테스트:\n";
echo "   URL: <a href='ajax/get_categories_tree.php' target='_blank'>ajax/get_categories_tree.php</a>\n";

// 4. 트리뷰 페이지 링크
echo "\n4. 트리뷰 페이지:\n";
echo "   <a href='admin_product_categories_tree.php'>트리뷰 페이지로 이동</a>\n";

echo "</pre>";
?>