-- products 테이블에 원산지와 제조사 컬럼 추가
ALTER TABLE products 
ADD COLUMN origin VARCHAR(100) DEFAULT NULL COMMENT '원산지' AFTER stock_status,
ADD COLUMN manufacturer VARCHAR(100) DEFAULT NULL COMMENT '제조사' AFTER origin;

-- 기본값 설정 (선택사항)
UPDATE products SET origin = '대한민국' WHERE origin IS NULL;
UPDATE products SET manufacturer = '포스코' WHERE manufacturer IS NULL AND category_code IN ('h-beam', 'i-beam');