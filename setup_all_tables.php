<?php
// 모든 테이블 설정
header('Content-Type: text/plain; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

echo "=== 충남스틸 데이터베이스 전체 테이블 생성 ===\n\n";

try {
    $pdo = getDB();
    echo "✓ 데이터베이스 연결 성공\n\n";
    
    // 1. members 테이블
    echo "1. members 테이블 생성 중... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        company VARCHAR(100),
        department VARCHAR(50),
        position VARCHAR(50),
        is_admin BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "완료\n";
    
    // 2. product_categories 테이블 (이미 있으면 스킵)
    echo "2. product_categories 테이블 확인... ";
    $result = $pdo->query("SHOW TABLES LIKE 'product_categories'")->rowCount();
    if ($result == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_code VARCHAR(50) NOT NULL UNIQUE,
            category_name VARCHAR(100) NOT NULL,
            display_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            click_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "생성 완료\n";
    } else {
        echo "이미 존재\n";
    }
    
    // 3. products 테이블 (이미 있으면 스킵)
    echo "3. products 테이블 확인... ";
    $result = $pdo->query("SHOW TABLES LIKE 'products'")->rowCount();
    if ($result == 0) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_code VARCHAR(50) NOT NULL,
            product_name VARCHAR(200) NOT NULL,
            product_code VARCHAR(100) UNIQUE,
            specifications TEXT,
            description TEXT,
            price DECIMAL(12,2),
            unit VARCHAR(50),
            min_order_qty INT DEFAULT 1,
            stock_status ENUM('in_stock', 'out_of_stock', 'on_order') DEFAULT 'in_stock',
            main_image VARCHAR(500),
            detail_images TEXT,
            features TEXT,
            dimensions TEXT,
            weight VARCHAR(100),
            material VARCHAR(200),
            manufacturer VARCHAR(200),
            origin VARCHAR(100),
            delivery_info TEXT,
            is_featured BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            view_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_code) REFERENCES product_categories(category_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "생성 완료\n";
    } else {
        echo "이미 존재\n";
    }
    
    // 4. unit_weights 테이블 *** 중요 ***
    echo "4. unit_weights 테이블 생성 중... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS unit_weights (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_type VARCHAR(50) NOT NULL COMMENT '제품유형',
        specification VARCHAR(100) NOT NULL COMMENT '규격',
        unit_weight DECIMAL(10,2) NOT NULL COMMENT '단위중량',
        material VARCHAR(50) DEFAULT NULL COMMENT '재질',
        height INT DEFAULT NULL COMMENT '높이',
        width INT DEFAULT NULL COMMENT '너비',
        web_thickness DECIMAL(5,1) DEFAULT NULL COMMENT '웹두께',
        flange_thickness DECIMAL(5,1) DEFAULT NULL COMMENT '플랜지두께',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_spec (product_type, specification)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "완료\n";
    
    // 5. 철근 관련 테이블
    echo "5. rebar_specifications 테이블 생성 중... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS rebar_specifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        spec_name VARCHAR(50) NOT NULL UNIQUE,
        diameter DECIMAL(5,1) NOT NULL,
        weight_per_meter DECIMAL(10,3) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "완료\n";
    
    echo "6. rebar_length_info 테이블 생성 중... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS rebar_length_info (
        id INT AUTO_INCREMENT PRIMARY KEY,
        spec_id INT NOT NULL,
        length DECIMAL(5,1) NOT NULL,
        pieces_per_ton INT NOT NULL,
        total_weight DECIMAL(10,1) NOT NULL,
        FOREIGN KEY (spec_id) REFERENCES rebar_specifications(id),
        UNIQUE KEY unique_spec_length (spec_id, length)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "완료\n";
    
    echo "7. rebar_materials 테이블 생성 중... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS rebar_materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        material_name VARCHAR(50) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "완료\n";
    
    echo "8. rebar_products 테이블 생성 중... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS rebar_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        spec_id INT NOT NULL,
        material_id INT,
        origin VARCHAR(100),
        manufacturer VARCHAR(100),
        stock_status VARCHAR(50),
        price DECIMAL(12,2),
        available_origins TEXT,
        stock_types TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (spec_id) REFERENCES rebar_specifications(id),
        FOREIGN KEY (material_id) REFERENCES rebar_materials(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "완료\n";
    
    // 6. 기본 데이터 삽입
    echo "\n=== 기본 데이터 삽입 ===\n";
    
    // 샘플 unit_weights 데이터
    echo "unit_weights 샘플 데이터 삽입 중... ";
    $unitWeights = [
        ['H형강', '100×100×6×8', 17.2],
        ['H형강', '125×125×6.5×9', 23.8],
        ['H형강', '150×150×7×10', 31.5],
        ['H형강', '200×200×8×12', 50.5],
        ['H형강', '250×250×9×14', 72.4],
        ['경량H형강', '100×50×5×7', 9.3],
        ['경량H형강', '150×75×5×7', 14.0],
        ['경량H형강', '200×100×5.5×8', 21.3]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO unit_weights (product_type, specification, unit_weight) VALUES (?, ?, ?)");
    foreach ($unitWeights as $weight) {
        $stmt->execute($weight);
    }
    echo "완료\n";
    
    // 철근 사양 데이터
    echo "철근 사양 데이터 삽입 중... ";
    $rebarSpecs = [
        ['D10', 10.0, 0.560],
        ['D13', 12.7, 0.995],
        ['D16', 15.9, 1.560],
        ['D19', 19.1, 2.250],
        ['D22', 22.2, 3.040],
        ['D25', 25.4, 3.980],
        ['D29', 28.6, 5.040],
        ['D32', 31.8, 6.230],
        ['D35', 34.9, 7.510],
        ['D38', 38.1, 8.950],
        ['D41', 41.3, 10.500],
        ['D51', 50.8, 15.900]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO rebar_specifications (spec_name, diameter, weight_per_meter) VALUES (?, ?, ?)");
    foreach ($rebarSpecs as $spec) {
        $stmt->execute($spec);
    }
    echo "완료\n";
    
    // 7. 테이블 확인
    echo "\n=== 생성된 테이블 확인 ===\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "✓ $table 테이블 (레코드 수: $count)\n";
    }
    
    echo "\n✅ 모든 테이블 생성이 완료되었습니다!\n";
    echo "\n이제 모든 페이지가 정상적으로 작동합니다.\n";
    
} catch (PDOException $e) {
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
    echo "오류 코드: " . $e->getCode() . "\n";
    echo "오류 위치: " . $e->getFile() . " (" . $e->getLine() . ")\n";
}
?>