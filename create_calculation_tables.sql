-- 제품 계산을 위한 테이블 구조

-- 1. 제품 규격 테이블 (이미 존재하는 products 테이블 확장)
ALTER TABLE products ADD COLUMN IF NOT EXISTS calculation_type ENUM('weight_based', 'area_based', 'length_based', 'custom') DEFAULT 'weight_based';
ALTER TABLE products ADD COLUMN IF NOT EXISTS base_unit_weight DECIMAL(10,4) COMMENT '기본 단위 중량 (kg/m, kg/m2 등)';
ALTER TABLE products ADD COLUMN IF NOT EXISTS density DECIMAL(10,4) DEFAULT 7850 COMMENT '밀도 (kg/m3, 철강 기본값 7850)';

-- 2. 제품 규격 상세 테이블
CREATE TABLE IF NOT EXISTS product_specifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    spec_name VARCHAR(100) NOT NULL COMMENT '규격명 (예: 100x100x6x8)',
    -- 형강류 규격
    height DECIMAL(10,2) COMMENT '높이(H) mm',
    width DECIMAL(10,2) COMMENT '폭(B) mm', 
    web_thickness DECIMAL(10,2) COMMENT '웨브 두께(t1) mm',
    flange_thickness DECIMAL(10,2) COMMENT '플랜지 두께(t2) mm',
    -- 파이프류 규격
    outer_diameter DECIMAL(10,2) COMMENT '외경 mm',
    inner_diameter DECIMAL(10,2) COMMENT '내경 mm',
    thickness DECIMAL(10,2) COMMENT '두께 mm',
    -- 판재류 규격
    plate_thickness DECIMAL(10,2) COMMENT '판 두께 mm',
    plate_width DECIMAL(10,2) COMMENT '판 폭 mm',
    -- 공통
    unit_weight DECIMAL(10,4) NOT NULL COMMENT '단위중량 kg/m or kg/m2',
    standard_length DECIMAL(10,2) DEFAULT 6000 COMMENT '표준 길이 mm',
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_spec (product_id, is_active)
);

-- 3. 제품 가격 테이블
CREATE TABLE IF NOT EXISTS product_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    spec_id INT COMMENT '특정 규격의 가격인 경우',
    price_type ENUM('per_kg', 'per_ton', 'per_meter', 'per_piece', 'per_m2') NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    min_quantity DECIMAL(10,2) DEFAULT 0 COMMENT '최소 수량',
    max_quantity DECIMAL(10,2) COMMENT '최대 수량 (NULL = 무제한)',
    effective_date DATE NOT NULL,
    expiry_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (spec_id) REFERENCES product_specifications(id) ON DELETE CASCADE,
    INDEX idx_product_price (product_id, is_active, effective_date)
);

-- 4. 제품 계산 공식 테이블 (특수한 계산이 필요한 경우)
CREATE TABLE IF NOT EXISTS product_formulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(50) NOT NULL,
    product_type VARCHAR(50),
    formula_name VARCHAR(100) NOT NULL,
    formula_expression TEXT NOT NULL COMMENT '계산식 (예: weight = (outer_dia - thickness) * thickness * 0.02466 * length)',
    variables JSON COMMENT '변수 목록 및 설명',
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_formula (category_code, product_type, is_active)
);

-- 5. 제품 재고/원산지 정보
CREATE TABLE IF NOT EXISTS product_stock_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    spec_id INT,
    origin VARCHAR(100) COMMENT '원산지',
    stock_type ENUM('stock', 'order') DEFAULT 'stock' COMMENT '재고/주문',
    stock_quantity DECIMAL(10,2) DEFAULT 0,
    lead_time INT COMMENT '납기일수',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (spec_id) REFERENCES product_specifications(id) ON DELETE CASCADE,
    INDEX idx_product_stock (product_id, spec_id, stock_type)
);

-- 6. 계산 이력 저장 (선택사항)
CREATE TABLE IF NOT EXISTS calculation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100),
    product_id INT,
    spec_id INT,
    input_data JSON COMMENT '입력값',
    calculated_weight DECIMAL(12,3) COMMENT '계산된 중량',
    calculated_price DECIMAL(12,2) COMMENT '계산된 가격',
    user_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_created (created_at)
);