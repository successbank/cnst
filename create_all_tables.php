<?php
// 모든 테이블 생성 스크립트
require_once 'db.php';

try {
    $pdo = getDB();
    echo "데이터베이스 연결 성공\n\n";
    
    // 1. product_categories 테이블 생성
    echo "1. product_categories 테이블 생성 중...";
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_code VARCHAR(50) NOT NULL UNIQUE,
        category_name VARCHAR(100) NOT NULL,
        display_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        click_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo " 완료\n";
    
    // 2. products 테이블 생성
    echo "2. products 테이블 생성 중...";
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
    )");
    echo " 완료\n";
    
    // 3. 카테고리 데이터 삽입
    echo "3. 카테고리 데이터 삽입 중...";
    $pdo->exec("INSERT IGNORE INTO product_categories (category_code, category_name, display_order) VALUES
        ('rebar', '철근', 1),
        ('h-beam', 'H형강', 2),
        ('steel-plate', '철강(강판)', 3),
        ('metal-lath', '메탈라스', 4),
        ('light-h-beam', '경량H형강', 5),
        ('i-beam', 'I형강', 6),
        ('angle', 'ㄱ형강(앵글)', 7),
        ('channel', 'ㄷ형강(찬넬)', 8),
        ('round-bar', '환봉', 9),
        ('flat-bar', '평철', 10),
        ('c-beam', 'C형강', 11),
        ('deck-plate', '테크플레이트', 12),
        ('square-pipe', '사각파이프', 13),
        ('round-pipe', '원형파이프', 14),
        ('rail', '레일', 15),
        ('sheet-pile', '강널말뚝', 16),
        ('stainless', '스테인레스', 17)");
    echo " 완료\n";
    
    // 4. 샘플 제품 데이터 삽입
    echo "4. 샘플 제품 데이터 삽입 중...";
    $pdo->exec("INSERT IGNORE INTO products (category_code, product_name, specifications, description, unit) VALUES
        ('rebar', '철근(특판) D10', 'D10 × 8m', '콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.', 'TON'),
        ('rebar', '철근(특판) D13', 'D13 × 8m', '콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.', 'TON'),
        ('h-beam', 'H형강 100×100', '100×100×6×8', '건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.', 'TON'),
        ('h-beam', 'H형강 200×200', '200×200×8×12', '건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.', 'TON')");
    echo " 완료\n";
    
    // 5. unit_weights 테이블 생성
    echo "5. unit_weights 테이블 생성 중...";
    $pdo->exec("CREATE TABLE IF NOT EXISTS unit_weights (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_type VARCHAR(50) NOT NULL,
        specification VARCHAR(100) NOT NULL,
        unit_weight DECIMAL(10,2) NOT NULL,
        material VARCHAR(50) DEFAULT NULL,
        height INT DEFAULT NULL,
        width INT DEFAULT NULL,
        web_thickness DECIMAL(5,1) DEFAULT NULL,
        flange_thickness DECIMAL(5,1) DEFAULT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_spec (product_type, specification)
    )");
    echo " 완료\n";
    
    // 6. 철근 관련 테이블 생성
    echo "6. 철근 관련 테이블 생성 중...";
    $pdo->exec("CREATE TABLE IF NOT EXISTS rebar_specifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        spec_name VARCHAR(50) NOT NULL UNIQUE,
        diameter DECIMAL(5,1) NOT NULL,
        weight_per_meter DECIMAL(10,3) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS rebar_length_info (
        id INT AUTO_INCREMENT PRIMARY KEY,
        spec_id INT NOT NULL,
        length DECIMAL(5,1) NOT NULL,
        pieces_per_ton INT NOT NULL,
        total_weight DECIMAL(10,1) NOT NULL,
        FOREIGN KEY (spec_id) REFERENCES rebar_specifications(id),
        UNIQUE KEY unique_spec_length (spec_id, length)
    )");
    echo " 완료\n";
    
    // 7. 생성된 테이블 확인
    echo "\n=== 생성된 테이블 목록 ===\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "- $table (레코드 수: $count)\n";
    }
    
    echo "\n테이블 생성 완료!\n";
    
} catch (PDOException $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>