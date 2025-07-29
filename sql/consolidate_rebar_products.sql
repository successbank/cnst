-- 기존 철근 제품 삭제
DELETE FROM products WHERE category_code = 'rebar';

-- 규격별 단일 제품으로 재등록
INSERT INTO products (category_code, product_name, specifications, description, stock_status, is_active, created_at) VALUES
('rebar', '철근 D10', 'D10', '이형철근 D10, 직경: 9.53mm, 단위중량: 0.560kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D13', 'D13', '이형철근 D13, 직경: 12.7mm, 단위중량: 0.995kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D16', 'D16', '이형철근 D16, 직경: 15.9mm, 단위중량: 1.560kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D19', 'D19', '이형철근 D19, 직경: 19.1mm, 단위중량: 2.250kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D22', 'D22', '이형철근 D22, 직경: 22.2mm, 단위중량: 3.040kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D25', 'D25', '이형철근 D25, 직경: 25.4mm, 단위중량: 3.980kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D29', 'D29', '이형철근 D29, 직경: 28.6mm, 단위중량: 5.040kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D32', 'D32', '이형철근 D32, 직경: 31.8mm, 단위중량: 6.230kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D35', 'D35', '이형철근 D35, 직경: 34.9mm, 단위중량: 7.510kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D38', 'D38', '이형철근 D38, 직경: 38.1mm, 단위중량: 8.950kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D41', 'D41', '이형철근 D41, 직경: 41.3mm, 단위중량: 10.500kg/m', 'in_stock', 1, NOW()),
('rebar', '철근 D51', 'D51', '이형철근 D51, 직경: 50.8mm, 단위중량: 15.900kg/m', 'in_stock', 1, NOW());