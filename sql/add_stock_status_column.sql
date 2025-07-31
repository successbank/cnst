-- products 테이블에 재고 상태 컬럼 추가 (이미 origin 컬럼은 있음)
ALTER TABLE products 
ADD COLUMN stock_type VARCHAR(50) DEFAULT 'normal' COMMENT '재고 유형 (normal: 일반, long_term: 장기재고, used: 중고)' AFTER stock_status;

-- 기존 stock_status 값에 따라 stock_type 설정
UPDATE products 
SET stock_type = CASE 
    WHEN stock_status = 'long_term' THEN 'long_term'
    WHEN stock_status = 'used' THEN 'used'
    ELSE 'normal'
END;

-- 원산지 기본값 설정 (origin 컬럼이 NULL인 경우)
UPDATE products 
SET origin = '국산' 
WHERE origin IS NULL OR origin = '';

-- 인덱스 추가로 검색 성능 향상
ALTER TABLE products ADD INDEX idx_origin (origin);
ALTER TABLE products ADD INDEX idx_stock_type (stock_type);