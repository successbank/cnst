-- 단중표 테이블 생성
CREATE TABLE IF NOT EXISTS unit_weights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_type VARCHAR(50) NOT NULL COMMENT '제품유형 (H형강, I형강 등)',
    specification VARCHAR(100) NOT NULL COMMENT '규격 (예: 100*100*6*8)',
    unit_weight DECIMAL(10,2) NOT NULL COMMENT '단위중량 (kg/m)',
    material VARCHAR(50) DEFAULT NULL COMMENT '재질 (SS400, SM490 등)',
    height INT DEFAULT NULL COMMENT '높이(mm)',
    width INT DEFAULT NULL COMMENT '너비(mm)',
    web_thickness DECIMAL(5,1) DEFAULT NULL COMMENT '웹두께(mm)',
    flange_thickness DECIMAL(5,1) DEFAULT NULL COMMENT '플랜지두께(mm)',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_spec (product_type, specification)
) COMMENT='제품 단중표';

-- 인덱스 추가
CREATE INDEX idx_product_type ON unit_weights(product_type);
CREATE INDEX idx_specification ON unit_weights(specification);
CREATE INDEX idx_is_active ON unit_weights(is_active);