<?php
require_once 'db.php';

try {
    echo "=== 철근 번들 테이블 생성 시작 ===\n\n";
    
    // 1. 철근 번들 데이터 테이블 생성
    $sql = "CREATE TABLE IF NOT EXISTS rebar_bundle_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        p_code VARCHAR(50) UNIQUE,
        it_code INT,
        p_name VARCHAR(100),
        p_standard VARCHAR(20) NOT NULL COMMENT '규격 (HD10, HD13, etc.)',
        p_material VARCHAR(20) NOT NULL COMMENT '재질 (SD400, SD500, etc.)',
        p_unit_weight DECIMAL(10,3) NOT NULL COMMENT '단위중량 (kg/m)',
        p_unit_length DECIMAL(4,1) NOT NULL COMMENT '길이 (m)',
        p_weight INT DEFAULT 0 COMMENT '무게 (사용안함)',
        p_bd_count INT NOT NULL COMMENT '번들당 본수',
        p_bd_weight INT NOT NULL COMMENT '번들당 중량 (kg)',
        p_low_cost DECIMAL(10,2) COMMENT '최저원가',
        p_high_cost DECIMAL(10,2) COMMENT '최고원가',
        p_low_price INT COMMENT '최저가격',
        p_high_price INT COMMENT '최고가격',
        p_country VARCHAR(50) COMMENT '원산지',
        p_maker VARCHAR(100) COMMENT '제작사',
        p_class VARCHAR(50) COMMENT '등급',
        p_remark TEXT COMMENT '비고',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_standard (p_standard),
        INDEX idx_material (p_material),
        INDEX idx_length (p_unit_length),
        INDEX idx_standard_material_length (p_standard, p_material, p_unit_length)
    )";
    $pdo->exec($sql);
    echo "✓ rebar_bundle_data 테이블 생성 완료\n";
    
    // 2. 철근 가격 정보 테이블 생성 (원산지별, 재질별)
    $sql = "CREATE TABLE IF NOT EXISTS rebar_bundle_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        standard VARCHAR(20) NOT NULL COMMENT '규격',
        material VARCHAR(20) NOT NULL COMMENT '재질',
        country VARCHAR(50) NOT NULL COMMENT '원산지',
        base_price DECIMAL(10,2) NOT NULL COMMENT '기준단가 (원/kg)',
        effective_date DATE NOT NULL COMMENT '효력일자',
        expiry_date DATE COMMENT '만료일자',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_price (standard, material, country, effective_date),
        INDEX idx_active_price (standard, material, country, is_active)
    )";
    $pdo->exec($sql);
    echo "✓ rebar_bundle_prices 테이블 생성 완료\n";
    
    // 3. 재질별 추가 단가 테이블
    $sql = "CREATE TABLE IF NOT EXISTS rebar_material_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        material VARCHAR(20) NOT NULL UNIQUE COMMENT '재질',
        additional_price DECIMAL(10,2) DEFAULT 0 COMMENT '추가단가 (원/kg)',
        description VARCHAR(200),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "✓ rebar_material_prices 테이블 생성 완료\n";
    
    // 4. 원산지별 추가 단가 테이블
    $sql = "CREATE TABLE IF NOT EXISTS rebar_country_prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        country VARCHAR(50) NOT NULL UNIQUE COMMENT '원산지',
        additional_price DECIMAL(10,2) DEFAULT 0 COMMENT '추가단가 (원/kg)',
        description VARCHAR(200),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "✓ rebar_country_prices 테이블 생성 완료\n";
    
    // 5. 기본 재질 데이터 삽입
    $materials = [
        ['SD300', 0, '일반 구조용'],
        ['SD400', 0, '일반 고장력'],
        ['SD400S', 50, '내진용'],
        ['SD500', 100, '고강도'],
        ['SD600', 150, '초고강도'],
        ['SD600S', 200, '초고강도 내진용']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO rebar_material_prices (material, additional_price, description) 
                          VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE additional_price = ?, description = ?");
    
    foreach ($materials as $material) {
        $stmt->execute([$material[0], $material[1], $material[2], $material[1], $material[2]]);
    }
    echo "✓ 재질별 추가단가 데이터 삽입 완료\n";
    
    // 6. 기본 원산지 데이터 삽입
    $countries = [
        ['대한민국', 0, '국내산'],
        ['중국', -50, '중국산'],
        ['일본', 100, '일본산'],
        ['기타', 0, '기타 국가']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO rebar_country_prices (country, additional_price, description) 
                          VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE additional_price = ?, description = ?");
    
    foreach ($countries as $country) {
        $stmt->execute([$country[0], $country[1], $country[2], $country[1], $country[2]]);
    }
    echo "✓ 원산지별 추가단가 데이터 삽입 완료\n";
    
    echo "\n=== 모든 테이블 생성 완료 ===\n";
    
} catch (PDOException $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>