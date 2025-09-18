-- 철근 제품 데이터 저장을 위한 컬럼 추가
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS length_data JSON COMMENT '길이별 철근 데이터 (본중, 톤당본수, 톤당중량)',
ADD COLUMN IF NOT EXISTS weight_per_meter DECIMAL(10,4) COMMENT '미터당 중량(kg)',
ADD COLUMN IF NOT EXISTS pieces_per_ton JSON COMMENT '길이별 톤당 본수 데이터';

-- 인덱스 추가 (철근 제품 검색 최적화)
CREATE INDEX IF NOT EXISTS idx_products_category_name 
ON products(category_code, product_name);