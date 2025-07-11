-- product_code 컬럼을 NULL 허용으로 변경
ALTER TABLE products 
MODIFY COLUMN product_code VARCHAR(100) DEFAULT NULL;

-- 빈 문자열을 NULL로 업데이트
UPDATE products 
SET product_code = NULL 
WHERE product_code = '';