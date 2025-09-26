<?php
require_once '../db.php';

try {
    echo "<h2>카테고리 테이블 마이그레이션 실행</h2>";
    echo "<pre>";

    // 1. parent_id 컬럼 추가
    echo "1. parent_id 컬럼 확인/추가 중...\n";
    try {
        $result = $pdo->query("SHOW COLUMNS FROM product_categories LIKE 'parent_id'");
        if ($result->rowCount() == 0) {
            $pdo->exec("ALTER TABLE product_categories ADD COLUMN parent_id INT DEFAULT NULL");
            echo "   ✓ parent_id 컬럼 추가 완료\n";
        } else {
            echo "   - parent_id 컬럼이 이미 존재합니다\n";
        }
    } catch (PDOException $e) {
        echo "   ! 오류: " . $e->getMessage() . "\n";
    }

    // 2. level 컬럼 추가
    echo "2. level 컬럼 확인/추가 중...\n";
    try {
        $result = $pdo->query("SHOW COLUMNS FROM product_categories LIKE 'level'");
        if ($result->rowCount() == 0) {
            $pdo->exec("ALTER TABLE product_categories ADD COLUMN level INT DEFAULT 0");
            echo "   ✓ level 컬럼 추가 완료\n";
        } else {
            echo "   - level 컬럼이 이미 존재합니다\n";
        }
    } catch (PDOException $e) {
        echo "   ! 오류: " . $e->getMessage() . "\n";
    }

    // 3. path 컬럼 추가
    echo "3. path 컬럼 확인/추가 중...\n";
    try {
        $result = $pdo->query("SHOW COLUMNS FROM product_categories LIKE 'path'");
        if ($result->rowCount() == 0) {
            $pdo->exec("ALTER TABLE product_categories ADD COLUMN path VARCHAR(255) DEFAULT NULL");
            echo "   ✓ path 컬럼 추가 완료\n";
        } else {
            echo "   - path 컬럼이 이미 존재합니다\n";
        }
    } catch (PDOException $e) {
        echo "   ! 오류: " . $e->getMessage() . "\n";
    }

    // 4. 기존 카테고리들의 path 업데이트
    echo "4. 기존 카테고리들의 path 업데이트 중...\n";
    try {
        $stmt = $pdo->query("SELECT id, category_code FROM product_categories WHERE path IS NULL");
        $categories = $stmt->fetchAll();

        foreach ($categories as $category) {
            $pdo->exec("UPDATE product_categories SET path = '{$category['category_code']}' WHERE id = {$category['id']}");
        }
        echo "   ✓ " . count($categories) . "개 카테고리 path 업데이트 완료\n";
    } catch (PDOException $e) {
        echo "   ! 오류: " . $e->getMessage() . "\n";
    }

    // 5. 테이블 구조 확인
    echo "\n5. 최종 테이블 구조:\n";
    $result = $pdo->query("SHOW COLUMNS FROM product_categories");
    $columns = $result->fetchAll();
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']})\n";
    }

    echo "\n✅ 마이그레이션 완료!\n";
    echo "\n<a href='admin_product_categories_tree.php'>트리뷰로 이동</a>\n";
    echo "</pre>";

} catch (Exception $e) {
    echo "<pre style='color: red;'>";
    echo "❌ 오류 발생: " . $e->getMessage() . "\n";
    echo "</pre>";
}
?>