-- 규격별 제품 표시를 위한 데이터베이스 업데이트

-- products 테이블에 새 필드 추가
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS display_mode ENUM('single', 'by_specification') DEFAULT 'single' COMMENT '표시 모드: single(단일), by_specification(규격별)',
ADD COLUMN IF NOT EXISTS parent_product_id INT DEFAULT NULL COMMENT '부모 제품 ID (규격별 제품인 경우)',
ADD COLUMN IF NOT EXISTS specification VARCHAR(100) DEFAULT NULL COMMENT '개별 규격 (규격별 제품인 경우)',
ADD COLUMN IF NOT EXISTS specification_weight DECIMAL(10,3) DEFAULT NULL COMMENT '해당 규격의 단위중량',
ADD INDEX idx_parent_product (parent_product_id),
ADD INDEX idx_specification (specification);

-- 외래키 제약 추가
ALTER TABLE products 
ADD CONSTRAINT fk_parent_product 
FOREIGN KEY (parent_product_id) REFERENCES products(id) ON DELETE CASCADE;