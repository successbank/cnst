<?php
require_once '../../db.php';

try {
    echo "<h2>카테고리 테이블에 트리 구조 컬럼 추가</h2>";
    echo "<pre>";

    // 1. parent_id 컬럼 추가
    echo "1. parent_id 컬럼 추가 중...\n";
    try {
        $pdo->exec("ALTER TABLE product_categories ADD COLUMN parent_id INT DEFAULT NULL");
        echo "   ✓ parent_id 컬럼 추가 완료\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   - parent_id 컬럼이 이미 존재합니다\n";
        } else {
            throw $e;
        }
    }

    // 2. level 컬럼 추가
    echo "2. level 컬럼 추가 중...\n";
    try {
        $pdo->exec("ALTER TABLE product_categories ADD COLUMN level INT DEFAULT 0");
        echo "   ✓ level 컬럼 추가 완료\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   - level 컬럼이 이미 존재합니다\n";
        } else {
            throw $e;
        }
    }

    // 3. path 컬럼 추가
    echo "3. path 컬럼 추가 중...\n";
    try {
        $pdo->exec("ALTER TABLE product_categories ADD COLUMN path VARCHAR(255) DEFAULT NULL");
        echo "   ✓ path 컬럼 추가 완료\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "   - path 컬럼이 이미 존재합니다\n";
        } else {
            throw $e;
        }
    }

    // 4. 외래키 제약 추가
    echo "4. 외래키 제약 추가 중...\n";
    try {
        // 먼저 기존 외래키가 있는지 확인
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'product_categories'
            AND COLUMN_NAME = 'parent_id'
            AND REFERENCED_TABLE_NAME = 'product_categories'
        ");

        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                ALTER TABLE product_categories
                ADD CONSTRAINT fk_category_parent
                FOREIGN KEY (parent_id) REFERENCES product_categories(id)
                ON DELETE SET NULL
            ");
            echo "   ✓ 외래키 제약 추가 완료\n";
        } else {
            echo "   - 외래키가 이미 존재합니다\n";
        }
    } catch (PDOException $e) {
        echo "   ! 외래키 추가 중 오류: " . $e->getMessage() . "\n";
    }

    // 5. 기본 카테고리 그룹 생성 (선택사항)
    echo "\n5. 기본 카테고리 그룹 설정...\n";

    // 철강재 그룹
    $steel_group = [
        'code' => 'steel-materials',
        'name' => '철강재',
        'children' => ['h-beam', 'i-beam', 'angle', 'channel', 'rebar']
    ];

    // 철판류 그룹
    $plate_group = [
        'code' => 'plate-materials',
        'name' => '철판류',
        'children' => ['steel-plate', 'steel-plates']
    ];

    // 파이프류 그룹
    $pipe_group = [
        'code' => 'pipe-materials',
        'name' => '파이프류',
        'children' => ['square-pipe', 'round-pipe']
    ];

    // 그룹 생성 (선택적 - 주석 처리됨)
    // createCategoryGroup($pdo, $steel_group);
    // createCategoryGroup($pdo, $plate_group);
    // createCategoryGroup($pdo, $pipe_group);

    echo "\n✅ 트리 구조 컬럼 추가가 완료되었습니다.\n";
    echo "\n참고: 카테고리 그룹화는 관리자 페이지에서 수동으로 설정할 수 있습니다.\n";
    echo "</pre>";

} catch (Exception $e) {
    echo "<pre style='color: red;'>";
    echo "❌ 오류 발생: " . $e->getMessage() . "\n";
    echo "</pre>";
}

function createCategoryGroup($pdo, $group) {
    // 부모 카테고리가 없으면 생성
    $stmt = $pdo->prepare("SELECT id FROM product_categories WHERE category_code = ?");
    $stmt->execute([$group['code']]);
    $parent = $stmt->fetch();

    if (!$parent) {
        $stmt = $pdo->prepare("
            INSERT INTO product_categories (category_code, category_name, parent_id, level, is_active, display_order)
            VALUES (?, ?, NULL, 0, 1, 0)
        ");
        $stmt->execute([$group['code'], $group['name']]);
        $parent_id = $pdo->lastInsertId();
        echo "   ✓ {$group['name']} 그룹 생성\n";
    } else {
        $parent_id = $parent['id'];
        echo "   - {$group['name']} 그룹이 이미 존재\n";
    }

    // 자식 카테고리들을 그룹에 연결
    foreach ($group['children'] as $child_code) {
        $stmt = $pdo->prepare("
            UPDATE product_categories
            SET parent_id = ?, level = 1
            WHERE category_code = ? AND parent_id IS NULL
        ");
        $stmt->execute([$parent_id, $child_code]);
    }
}
?>