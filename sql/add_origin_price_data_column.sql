-- products 테이블에 원산지별 추가 비용 정보를 저장할 컬럼 추가
-- JSON 형식으로 저장: {"국산": 0, "중국산": -50, "일본산": 200}
-- 값은 kg당 추가 비용 (원)

ALTER TABLE products 
ADD COLUMN origin_price_data LONGTEXT DEFAULT NULL 
COMMENT '원산지별 추가 비용 정보 (JSON 형식, kg당 원)' 
AFTER available_origins;

-- 컬럼이 추가되었는지 확인
DESCRIBE products;