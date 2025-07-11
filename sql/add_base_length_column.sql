-- products 테이블에 기준길이 컬럼 추가
ALTER TABLE products 
ADD COLUMN base_length INT DEFAULT 6 COMMENT '기준길이(m)' AFTER min_order_qty;

-- 기본값 설정 (선택사항)
UPDATE products SET base_length = 6 WHERE base_length IS NULL;