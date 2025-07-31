-- products 테이블에 stock_types 컬럼 추가
-- JSON 형식으로 복수 재고 상태 저장
ALTER TABLE products 
ADD COLUMN stock_types TEXT DEFAULT '["일반재고"]' COMMENT '재고 상태 목록 (JSON 형식)' AFTER stock_type;

-- 기존 stock_type 데이터를 stock_types로 마이그레이션 (필요시)
UPDATE products 
SET stock_types = CASE 
    WHEN stock_type = '장기재고' THEN '["장기재고"]'
    WHEN stock_type = '중고' THEN '["중고"]'
    ELSE '["일반재고"]'
END
WHERE stock_types IS NULL OR stock_types = '';

-- 인덱스 추가 (검색 성능 향상)
-- ALTER TABLE products ADD INDEX idx_stock_types (stock_types(100));