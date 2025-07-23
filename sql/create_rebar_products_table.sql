-- 철근 제품을 위한 테이블 생성
-- 철근은 규격별로 단위중량이 정해져 있고, 길이와 수량에 따라 가격이 결정됨

-- 철근 규격 마스터 테이블
CREATE TABLE IF NOT EXISTS rebar_specifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spec_name VARCHAR(50) NOT NULL COMMENT '규격명 (D10, D13 등)',
    diameter DECIMAL(5,2) NOT NULL COMMENT '직경(mm)',
    unit_weight DECIMAL(10,3) NOT NULL COMMENT '단위중량(kg/m)',
    description TEXT COMMENT '설명',
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_spec_name (spec_name)
) COMMENT='철근 규격 마스터 정보';

-- 철근 길이별 정보 테이블
CREATE TABLE IF NOT EXISTS rebar_length_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spec_id INT NOT NULL COMMENT '철근 규격 ID',
    length DECIMAL(5,1) NOT NULL COMMENT '길이(m)',
    weight_per_piece DECIMAL(10,2) NOT NULL COMMENT '본중(kg/본)',
    pieces_per_ton INT NOT NULL COMMENT '톤당 본수',
    total_weight DECIMAL(10,2) DEFAULT NULL COMMENT '총중량(kg) - 특정 규격에만 해당',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (spec_id) REFERENCES rebar_specifications(id) ON DELETE CASCADE,
    UNIQUE KEY unique_spec_length (spec_id, length)
) COMMENT='철근 길이별 상세 정보';

-- 철근 가격 테이블 (관리자가 입력)
CREATE TABLE IF NOT EXISTS rebar_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spec_id INT NOT NULL COMMENT '철근 규격 ID',
    unit_price DECIMAL(12,2) NOT NULL COMMENT '단가(원/kg)',
    effective_date DATE NOT NULL COMMENT '적용일자',
    expiry_date DATE DEFAULT NULL COMMENT '만료일자',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT DEFAULT NULL COMMENT '등록자 ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (spec_id) REFERENCES rebar_specifications(id) ON DELETE CASCADE,
    KEY idx_effective_date (effective_date),
    KEY idx_spec_active (spec_id, is_active)
) COMMENT='철근 가격 정보';

-- 철근 견적 테이블
CREATE TABLE IF NOT EXISTS rebar_quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_number VARCHAR(50) NOT NULL UNIQUE COMMENT '견적번호',
    member_id INT DEFAULT NULL COMMENT '회원 ID',
    customer_name VARCHAR(100) NOT NULL COMMENT '고객명',
    customer_phone VARCHAR(20) COMMENT '연락처',
    customer_email VARCHAR(100) COMMENT '이메일',
    company_name VARCHAR(200) COMMENT '회사명',
    total_amount DECIMAL(15,2) NOT NULL COMMENT '총 금액',
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    notes TEXT COMMENT '비고',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_quote_number (quote_number),
    KEY idx_member_id (member_id),
    KEY idx_status (status)
) COMMENT='철근 견적서';

-- 철근 견적 상세 테이블
CREATE TABLE IF NOT EXISTS rebar_quote_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL COMMENT '견적 ID',
    spec_id INT NOT NULL COMMENT '철근 규격 ID',
    length DECIMAL(5,1) NOT NULL COMMENT '선택 길이(m)',
    quantity INT NOT NULL COMMENT '수량(본수)',
    unit_weight DECIMAL(10,3) NOT NULL COMMENT '단위중량(kg/m) - 계산용',
    unit_price DECIMAL(12,2) NOT NULL COMMENT '단가(원/kg)',
    total_weight DECIMAL(15,2) NOT NULL COMMENT '총중량(kg) = 수량 × 길이 × 단위중량',
    total_amount DECIMAL(15,2) NOT NULL COMMENT '총금액(원) = 총중량 × 단가',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES rebar_quotes(id) ON DELETE CASCADE,
    FOREIGN KEY (spec_id) REFERENCES rebar_specifications(id),
    KEY idx_quote_id (quote_id)
) COMMENT='철근 견적 상세 항목';

-- 뷰 생성: 현재 유효한 가격 정보
CREATE OR REPLACE VIEW v_current_rebar_prices AS
SELECT 
    rs.id AS spec_id,
    rs.spec_name,
    rs.diameter,
    rs.unit_weight,
    rp.unit_price,
    rp.effective_date
FROM rebar_specifications rs
LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id 
    AND rp.is_active = TRUE 
    AND rp.effective_date <= CURDATE()
    AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE())
WHERE rs.is_active = TRUE
ORDER BY rs.display_order;

-- 인덱스 추가
CREATE INDEX idx_rebar_spec_active ON rebar_specifications(is_active);
CREATE INDEX idx_rebar_length_spec ON rebar_length_info(spec_id);
CREATE INDEX idx_rebar_prices_date ON rebar_prices(effective_date, expiry_date);