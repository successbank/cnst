-- 철근 카테고리 추가
INSERT INTO product_categories (name, display_name, description, icon, display_order, is_active) 
VALUES ('rebar', '철근', '건축용 철근 - D10, D13, D16, D19, D22, D25, D29, D32, D35, D38, D41', 'fas fa-bars', 11, 1);

-- 철근 제품 추가 (D10/0.56 - D10:직경, 0.56:단위중량)
SET @category_id = (SELECT id FROM product_categories WHERE name = 'rebar');

INSERT INTO products (category_id, product_name, korean_name, specifications, unit, description, is_active) VALUES
(@category_id, 'D10', '이형철근 D10', '직경: 9.53mm, 단위중량: 0.56kg/m', 'TON', '건축용 이형철근 D10 (SD400)', 1),
(@category_id, 'D13', '이형철근 D13', '직경: 12.7mm, 단위중량: 0.995kg/m', 'TON', '건축용 이형철근 D13 (SD400)', 1),
(@category_id, 'D16', '이형철근 D16', '직경: 15.9mm, 단위중량: 1.56kg/m', 'TON', '건축용 이형철근 D16 (SD400)', 1),
(@category_id, 'D19', '이형철근 D19', '직경: 19.1mm, 단위중량: 2.25kg/m', 'TON', '건축용 이형철근 D19 (SD400)', 1),
(@category_id, 'D22', '이형철근 D22', '직경: 22.2mm, 단위중량: 3.04kg/m', 'TON', '건축용 이형철근 D22 (SD400)', 1),
(@category_id, 'D25', '이형철근 D25', '직경: 25.4mm, 단위중량: 3.98kg/m', 'TON', '건축용 이형철근 D25 (SD400)', 1),
(@category_id, 'D29', '이형철근 D29', '직경: 28.6mm, 단위중량: 5.04kg/m', 'TON', '건축용 이형철근 D29 (SD400)', 1),
(@category_id, 'D32', '이형철근 D32', '직경: 31.8mm, 단위중량: 6.23kg/m', 'TON', '건축용 이형철근 D32 (SD400)', 1),
(@category_id, 'D35', '이형철근 D35', '직경: 34.9mm, 단위중량: 7.51kg/m', 'TON', '건축용 이형철근 D35 (SD400)', 1),
(@category_id, 'D38', '이형철근 D38', '직경: 38.1mm, 단위중량: 8.95kg/m', 'TON', '건축용 이형철근 D38 (SD400)', 1),
(@category_id, 'D41', '이형철근 D41', '직경: 41.3mm, 단위중량: 10.5kg/m', 'TON', '건축용 이형철근 D41 (SD400)', 1);

-- 철근 제품의 단위중량 정보를 별도 필드로 저장
ALTER TABLE products ADD COLUMN IF NOT EXISTS unit_weight DECIMAL(10,3) DEFAULT NULL COMMENT '단위중량(kg/m)' AFTER specifications;

-- 단위중량 업데이트
UPDATE products SET unit_weight = 0.56 WHERE product_name = 'D10' AND category_id = @category_id;
UPDATE products SET unit_weight = 0.995 WHERE product_name = 'D13' AND category_id = @category_id;
UPDATE products SET unit_weight = 1.56 WHERE product_name = 'D16' AND category_id = @category_id;
UPDATE products SET unit_weight = 2.25 WHERE product_name = 'D19' AND category_id = @category_id;
UPDATE products SET unit_weight = 3.04 WHERE product_name = 'D22' AND category_id = @category_id;
UPDATE products SET unit_weight = 3.98 WHERE product_name = 'D25' AND category_id = @category_id;
UPDATE products SET unit_weight = 5.04 WHERE product_name = 'D29' AND category_id = @category_id;
UPDATE products SET unit_weight = 6.23 WHERE product_name = 'D32' AND category_id = @category_id;
UPDATE products SET unit_weight = 7.51 WHERE product_name = 'D35' AND category_id = @category_id;
UPDATE products SET unit_weight = 8.95 WHERE product_name = 'D38' AND category_id = @category_id;
UPDATE products SET unit_weight = 10.5 WHERE product_name = 'D41' AND category_id = @category_id;