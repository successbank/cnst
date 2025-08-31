<?php
// 데이터베이스 테이블 설정
header('Content-Type: text/plain; charset=utf-8');

// 오류 표시
error_reporting(E_ALL);
ini_set('display_errors', 1);

// db.php 파일 포함
require_once __DIR__ . '/db.php';

echo "=== 데이터베이스 테이블 생성 시작 ===\n\n";

try {
    $pdo = getDB();
    echo "✓ 데이터베이스 연결 성공\n\n";
    
    // 1. product_categories 테이블
    echo "1. product_categories 테이블 생성 중... ";
    $sql = "CREATE TABLE IF NOT EXISTS product_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_code VARCHAR(50) NOT NULL UNIQUE,
        category_name VARCHAR(100) NOT NULL,
        display_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        click_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "완료\n";
    
    // 2. products 테이블
    echo "2. products 테이블 생성 중... ";
    $sql = "CREATE TABLE IF NOT EXISTS products (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "완료\n";
    
    // 3. 카테고리 데이터 삽입
    echo "3. 카테고리 데이터 삽입 중... ";
    $categories = [
        ['rebar', '철근', 1],
        ['h-beam', 'H형강', 2],
        ['steel-plate', '철강(강판)', 3],
        ['metal-lath', '메탈라스', 4],
        ['light-h-beam', '경량H형강', 5],
        ['i-beam', 'I형강', 6],
        ['angle', 'ㄱ형강(앵글)', 7],
        ['channel', 'ㄷ형강(찬넬)', 8],
        ['round-bar', '환봉', 9],
        ['flat-bar', '평철', 10],
        ['c-beam', 'C형강', 11],
        ['deck-plate', '테크플레이트', 12],
        ['square-pipe', '사각파이프', 13],
        ['round-pipe', '원형파이프', 14],
        ['rail', '레일', 15],
        ['sheet-pile', '강널말뚝', 16],
        ['stainless', '스테인레스', 17]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO product_categories (category_code, category_name, display_order) VALUES (?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    echo "완료\n";
    
    // 4. 샘플 제품 데이터 삽입
    echo "4. 샘플 제품 데이터 삽입 중... ";
    $products = [
        ['rebar', '철근(특판) D10', 'D10 × 8m', '콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.', 'TON'],
        ['rebar', '철근(특판) D13', 'D13 × 8m', '콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.', 'TON'],
        ['h-beam', 'H형강 100×100', '100×100×6×8', '건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.', 'TON'],
        ['h-beam', 'H형강 200×200', '200×200×8×12', '건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.', 'TON'],
        ['steel-plate', '일반 강판 6T', '6T × 1524 × 3048', '일반 구조용 및 용접 구조용으로 사용되는 열간 압연 강판입니다.', 'TON']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO products (category_code, product_name, specifications, description, unit) VALUES (?, ?, ?, ?, ?)");
    foreach ($products as $prod) {
        $stmt->execute($prod);
    }
    echo "완료\n";
    
    // 5. 테이블 확인
    echo "\n=== 생성된 테이블 확인 ===\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "✓ $table 테이블 (레코드 수: $count)\n";
    }
    
    echo "\n✅ 모든 작업이 완료되었습니다!\n";
    echo "\n이제 products.php 페이지에 접속할 수 있습니다.\n";
    
} catch (PDOException $e) {
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
    echo "오류 코드: " . $e->getCode() . "\n";
}
?>