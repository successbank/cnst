-- products 테이블에 최저단가, 최대단가 컬럼 추가
ALTER TABLE products 
ADD COLUMN min_price DECIMAL(10,2) DEFAULT NULL COMMENT '최저단가' AFTER price,
ADD COLUMN max_price DECIMAL(10,2) DEFAULT NULL COMMENT '최대단가' AFTER min_price;

-- 기존 데이터에 대해 기본값 설정 (기준단가의 95%, 105%)
UPDATE products 
SET min_price = ROUND(price * 0.95, 2),
    max_price = ROUND(price * 1.05, 2)
WHERE price IS NOT NULL AND price > 0;