-- products 테이블에 재질별 추가 비용 정보를 저장할 컬럼 추가
-- JSON 형식으로 저장: {"SS400": 0, "SM490": 100, "SUS304": 500}
-- 값은 kg당 추가 비용 (원)

ALTER TABLE products 
ADD COLUMN material_price_data LONGTEXT DEFAULT NULL 
COMMENT '재질별 추가 비용 정보 (JSON 형식, kg당 원)' 
AFTER origin_price_data;

-- 컬럼이 추가되었는지 확인
DESCRIBE products;