<?php
include 'db.php';

try {
    // 제품 카테고리 테이블
    $sql1 = "CREATE TABLE IF NOT EXISTS product_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_code VARCHAR(50) NOT NULL UNIQUE,
        category_name VARCHAR(100) NOT NULL,
        display_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql1);
    echo "product_categories 테이블 생성 완료<br>";

    // 제품 테이블
    $sql2 = "CREATE TABLE IF NOT EXISTS products (
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
    )";
    $pdo->exec($sql2);
    echo "products 테이블 생성 완료<br>";

    // 제품 이미지 테이블
    $sql3 = "CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_url VARCHAR(500) NOT NULL,
        image_type ENUM('main', 'detail', 'spec') DEFAULT 'detail',
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql3);
    echo "product_images 테이블 생성 완료<br>";

    // 카테고리 데이터 삽입
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
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    echo "카테고리 데이터 삽입 완료<br>";

    // 샘플 제품 데이터 삽입
    $products = [
        ['rebar', '철근(특판) D10', 'D10 × 8m', '콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.', 'TON', '/img/products/rebar_d10.jpg'],
        ['rebar', '철근(특판) D13', 'D13 × 8m', '콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.', 'TON', '/img/products/rebar_d13.jpg'],
        ['rebar', '철근(특판) D16', 'D16 × 8m', '콘크리트 구조물의 인장 보강재로 사용되는 고강도 이형철근입니다.', 'TON', '/img/products/rebar_d16.jpg'],
        ['h-beam', 'H형강 100×100', '100×100×6×8', '건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.', 'TON', '/img/products/h_beam_100.jpg'],
        ['h-beam', 'H형강 200×200', '200×200×8×12', '건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.', 'TON', '/img/products/h_beam_200.jpg'],
        ['h-beam', 'H형강 300×300', '300×300×10×15', '건축 구조물의 기둥과 보에 사용되는 대표적인 구조용 강재입니다.', 'TON', '/img/products/h_beam_300.jpg'],
        ['steel-plate', '일반 강판 6T', '6T × 1524 × 3048', '일반 구조용 및 용접 구조용으로 사용되는 열간 압연 강판입니다.', 'TON', '/img/products/steel_plate_6t.jpg'],
        ['steel-plate', '일반 강판 9T', '9T × 1524 × 3048', '일반 구조용 및 용접 구조용으로 사용되는 열간 압연 강판입니다.', 'TON', '/img/products/steel_plate_9t.jpg'],
        ['steel-plate', '일반 강판 12T', '12T × 1524 × 3048', '일반 구조용 및 용접 구조용으로 사용되는 열간 압연 강판입니다.', 'TON', '/img/products/steel_plate_12t.jpg']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO products (category_code, product_name, specifications, description, unit, main_image) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($products as $product) {
        $stmt->execute($product);
    }
    echo "샘플 제품 데이터 삽입 완료<br>";

    echo "<br>모든 테이블이 성공적으로 생성되었습니다!";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>