-- 철근 재질 테이블 생성
CREATE TABLE IF NOT EXISTS rebar_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_code VARCHAR(20) NOT NULL COMMENT '재질 코드 (SD300, SD400 등)',
    material_name VARCHAR(50) NOT NULL COMMENT '재질 명칭',
    additional_price DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '추가 단가(원/kg)',
    description TEXT COMMENT '재질 설명',
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_material_code (material_code)
) COMMENT='철근 재질 마스터 정보';

-- 철근 재질별 단가 테이블 (견적에 재질 정보 추가)
ALTER TABLE rebar_quote_items 
ADD COLUMN material_id INT AFTER spec_id,
ADD COLUMN material_additional_price DECIMAL(10,2) DEFAULT 0 COMMENT '재질 추가단가',
ADD FOREIGN KEY (material_id) REFERENCES rebar_materials(id);

-- 현재 가격 정보 뷰 수정 (재질 포함)
CREATE OR REPLACE VIEW v_current_rebar_prices_with_materials AS
SELECT 
    rs.id AS spec_id,
    rs.spec_name,
    rs.diameter,
    rs.unit_weight,
    rp.unit_price AS base_price,
    rm.id AS material_id,
    rm.material_code,
    rm.material_name,
    rm.additional_price,
    (COALESCE(rp.unit_price, 0) + COALESCE(rm.additional_price, 0)) AS total_price,
    rp.effective_date
FROM rebar_specifications rs
CROSS JOIN rebar_materials rm
LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id 
    AND rp.is_active = TRUE 
    AND rp.effective_date <= CURDATE()
    AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE())
WHERE rs.is_active = TRUE AND rm.is_active = TRUE
ORDER BY rs.display_order, rm.display_order;

-- 인덱스 추가
CREATE INDEX idx_rebar_materials_active ON rebar_materials(is_active);
CREATE INDEX idx_rebar_materials_code ON rebar_materials(material_code);