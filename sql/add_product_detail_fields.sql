-- 제품 테이블에 상세내용 관련 필드 추가

-- 1. 제품 상세 설명 필드 추가 (긴 텍스트)
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS detailed_description TEXT DEFAULT NULL COMMENT '상세 설명' AFTER description;

-- 2. 제품 특장점 필드 추가 
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS key_features TEXT DEFAULT NULL COMMENT '주요 특징' AFTER detailed_description;

-- 3. 기술 사양 필드 추가
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS technical_specs TEXT DEFAULT NULL COMMENT '기술 사양' AFTER key_features;

-- 4. 사용 용도 필드 추가
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS applications TEXT DEFAULT NULL COMMENT '사용 용도' AFTER technical_specs;

-- 5. 관련 규격/인증 필드 추가
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS certifications TEXT DEFAULT NULL COMMENT '관련 규격/인증' AFTER applications;

-- 6. 추가 이미지 URL들 (JSON 형식으로 저장)
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS additional_images JSON DEFAULT NULL COMMENT '추가 이미지들' AFTER certifications;

-- 7. 제품 브로셔/카탈로그 URL
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS brochure_url VARCHAR(500) DEFAULT NULL COMMENT '브로셔 URL' AFTER additional_images;

-- 8. 상세내용 표시 여부
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS show_details BOOLEAN DEFAULT TRUE COMMENT '상세내용 표시 여부' AFTER brochure_url;

-- 9. 상세내용 최종 수정일
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS details_updated_at TIMESTAMP NULL DEFAULT NULL COMMENT '상세내용 수정일' AFTER show_details;

-- 인덱스 추가 (상세내용 검색용)
ALTER TABLE products ADD FULLTEXT INDEX idx_product_details (detailed_description, key_features, technical_specs, applications);