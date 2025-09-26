<?php
// 세션 없이 직접 테스트
require_once '../db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>카테고리 트리 직접 테스트</h2>";
echo "<pre>";

// 1. 컬럼 확인
echo "1. 데이터베이스 컬럼 확인:\n";
$columns = $pdo->query("SHOW COLUMNS FROM product_categories")->fetchAll();
$hasParentId = false;
$hasLevel = false;
$hasPath = false;

foreach ($columns as $col) {
    echo "   - {$col['Field']} ({$col['Type']})\n";
    if ($col['Field'] == 'parent_id') $hasParentId = true;
    if ($col['Field'] == 'level') $hasLevel = true;
    if ($col['Field'] == 'path') $hasPath = true;
}

echo "\n필수 컬럼 상태:\n";
echo "   - parent_id: " . ($hasParentId ? "✅ 있음" : "❌ 없음") . "\n";
echo "   - level: " . ($hasLevel ? "✅ 있음" : "❌ 없음") . "\n";
echo "   - path: " . ($hasPath ? "✅ 있음" : "❌ 없음") . "\n";

// 2. 카테고리 데이터
echo "\n2. 카테고리 데이터 (총 " . $pdo->query("SELECT COUNT(*) FROM product_categories")->fetchColumn() . "개):\n";

try {
    $stmt = $pdo->query("
        SELECT
            pc.id,
            pc.category_code,
            pc.category_name,
            pc.parent_id,
            pc.level,
            pc.display_order,
            pc.is_active,
            COUNT(p.id) as product_count
        FROM product_categories pc
        LEFT JOIN products p ON pc.category_code = p.category_code
        GROUP BY pc.id
        ORDER BY IFNULL(pc.parent_id, 0), pc.display_order, pc.id
        LIMIT 20
    ");

    $categories = $stmt->fetchAll();

    foreach ($categories as $cat) {
        $indent = str_repeat('  ', $cat['level'] ?? 0);
        echo sprintf("%s[ID:%d] %s (code: %s, parent: %s, order: %d, products: %d)\n",
            $indent,
            $cat['id'],
            $cat['category_name'],
            $cat['category_code'],
            $cat['parent_id'] ?? 'NULL',
            $cat['display_order'],
            $cat['product_count']
        );
    }
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}

// 3. 트리 구조 빌드 테스트
echo "\n3. 트리 구조 빌드 테스트:\n";

function buildTree($categories, $parentId = 0) {
    $tree = [];

    foreach ($categories as $category) {
        $catParentId = $category['parent_id'] === null ? 0 : intval($category['parent_id']);

        if ($catParentId === $parentId) {
            $node = [
                'id' => $category['id'],
                'name' => $category['category_name'],
                'children' => buildTree($categories, intval($category['id']))
            ];
            $tree[] = $node;
        }
    }

    return $tree;
}

try {
    $allCategories = $pdo->query("
        SELECT * FROM product_categories
        ORDER BY IFNULL(parent_id, 0), display_order
    ")->fetchAll();

    $tree = buildTree($allCategories);

    function printTree($nodes, $level = 0) {
        foreach ($nodes as $node) {
            echo str_repeat('  ', $level) . "- " . $node['name'] . "\n";
            if (!empty($node['children'])) {
                printTree($node['children'], $level + 1);
            }
        }
    }

    printTree($tree);

} catch (Exception $e) {
    echo "트리 빌드 오류: " . $e->getMessage() . "\n";
}

// 4. AJAX 엔드포인트 테스트
echo "\n4. AJAX 엔드포인트 링크:\n";
echo "   - <a href='ajax/get_categories_tree.php' target='_blank'>get_categories_tree.php</a> (세션 필요)\n";
echo "   - <a href='test_tree.php'>test_tree.php</a>\n";
echo "   - <a href='admin_product_categories_tree.php'>트리뷰 페이지</a>\n";
echo "   - <a href='admin_product_categories_tree_v2.php'>트리뷰 V2 (개선버전)</a>\n";
echo "   - <a href='run_migration.php'>마이그레이션 실행</a>\n";

echo "</pre>";

// 5. JavaScript 테스트
?>

<h3>JavaScript 콘솔 테스트</h3>
<button onclick="testAjax()">AJAX 테스트</button>
<div id="result"></div>

<script>
function testAjax() {
    fetch('ajax/get_categories_tree.php')
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Response text:', text);
            document.getElementById('result').innerHTML = '<pre>' + text + '</pre>';

            try {
                const data = JSON.parse(text);
                if (data.success) {
                    console.log('카테고리 수:', data.categories ? data.categories.length : 0);
                } else {
                    console.log('오류:', data.message);
                }
            } catch (e) {
                console.log('JSON 파싱 오류:', e.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            document.getElementById('result').innerHTML = 'Error: ' + error.message;
        });
}
</script>