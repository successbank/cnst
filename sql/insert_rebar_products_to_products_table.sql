-- 철근 제품을 products 테이블에 추가
INSERT INTO products (category_code, product_name, specifications, description, weight, material, unit, min_order_qty, stock_status, is_active) VALUES
('rebar', '철근 D10', 'D10', '일반용 이형철근 D10 (SD400), 직경: 9.53mm, 단위중량: 0.560kg/m', '0.560kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D13', 'D13', '일반용 이형철근 D13 (SD400), 직경: 12.7mm, 단위중량: 0.995kg/m', '0.995kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D16', 'D16', '일반용 이형철근 D16 (SD400), 직경: 15.9mm, 단위중량: 1.560kg/m', '1.560kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D19', 'D19', '일반용 이형철근 D19 (SD400), 직경: 19.1mm, 단위중량: 2.250kg/m', '2.250kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D22', 'D22', '일반용 이형철근 D22 (SD400), 직경: 22.2mm, 단위중량: 3.040kg/m', '3.040kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D25', 'D25', '일반용 이형철근 D25 (SD400), 직경: 25.4mm, 단위중량: 3.980kg/m', '3.980kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D29', 'D29', '일반용 이형철근 D29 (SD400), 직경: 28.6mm, 단위중량: 5.040kg/m', '5.040kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D32', 'D32', '일반용 이형철근 D32 (SD400), 직경: 31.8mm, 단위중량: 6.230kg/m', '6.230kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D35', 'D35', '일반용 이형철근 D35 (SD400), 직경: 34.9mm, 단위중량: 7.510kg/m', '7.510kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D38', 'D38', '일반용 이형철근 D38 (SD400), 직경: 38.1mm, 단위중량: 8.950kg/m', '8.950kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D41', 'D41', '일반용 이형철근 D41 (SD400), 직경: 41.3mm, 단위중량: 10.500kg/m', '10.500kg/m', 'SD400', 'TON', 1, 'in_stock', 1),
('rebar', '철근 D51', 'D51', '일반용 이형철근 D51 (SD400), 직경: 50.8mm, 단위중량: 15.900kg/m', '15.900kg/m', 'SD400', 'TON', 1, 'in_stock', 1);

-- SD500 고강도 철근 추가
INSERT INTO products (category_code, product_name, specifications, description, weight, material, unit, min_order_qty, stock_status, is_active) VALUES
('rebar', '고강도철근 D10', 'D10 (SD500)', '고강도 이형철근 D10 (SD500), 직경: 9.53mm, 단위중량: 0.560kg/m', '0.560kg/m', 'SD500', 'TON', 1, 'in_stock', 1),
('rebar', '고강도철근 D13', 'D13 (SD500)', '고강도 이형철근 D13 (SD500), 직경: 12.7mm, 단위중량: 0.995kg/m', '0.995kg/m', 'SD500', 'TON', 1, 'in_stock', 1),
('rebar', '고강도철근 D16', 'D16 (SD500)', '고강도 이형철근 D16 (SD500), 직경: 15.9mm, 단위중량: 1.560kg/m', '1.560kg/m', 'SD500', 'TON', 1, 'in_stock', 1),
('rebar', '고강도철근 D19', 'D19 (SD500)', '고강도 이형철근 D19 (SD500), 직경: 19.1mm, 단위중량: 2.250kg/m', '2.250kg/m', 'SD500', 'TON', 1, 'in_stock', 1),
('rebar', '고강도철근 D22', 'D22 (SD500)', '고강도 이형철근 D22 (SD500), 직경: 22.2mm, 단위중량: 3.040kg/m', '3.040kg/m', 'SD500', 'TON', 1, 'in_stock', 1),
('rebar', '고강도철근 D25', 'D25 (SD500)', '고강도 이형철근 D25 (SD500), 직경: 25.4mm, 단위중량: 3.980kg/m', '3.980kg/m', 'SD500', 'TON', 1, 'in_stock', 1),
('rebar', '고강도철근 D29', 'D29 (SD500)', '고강도 이형철근 D29 (SD500), 직경: 28.6mm, 단위중량: 5.040kg/m', '5.040kg/m', 'SD500', 'TON', 1, 'in_stock', 1),
('rebar', '고강도철근 D32', 'D32 (SD500)', '고강도 이형철근 D32 (SD500), 직경: 31.8mm, 단위중량: 6.230kg/m', '6.230kg/m', 'SD500', 'TON', 1, 'in_stock', 1),
('rebar', '고강도철근 D35', 'D35 (SD500)', '고강도 이형철근 D35 (SD500), 직경: 34.9mm, 단위중량: 7.510kg/m', '7.510kg/m', 'SD500', 'TON', 1, 'in_stock', 1),
('rebar', '고강도철근 D38', 'D38 (SD500)', '고강도 이형철근 D38 (SD500), 직경: 38.1mm, 단위중량: 8.950kg/m', '8.950kg/m', 'SD500', 'TON', 1, 'in_stock', 1),
('rebar', '고강도철근 D41', 'D41 (SD500)', '고강도 이형철근 D41 (SD500), 직경: 41.3mm, 단위중량: 10.500kg/m', '10.500kg/m', 'SD500', 'TON', 1, 'in_stock', 1);

-- SD600 초고강도 철근 추가
INSERT INTO products (category_code, product_name, specifications, description, weight, material, unit, min_order_qty, stock_status, is_active) VALUES
('rebar', '초고강도철근 D13', 'D13 (SD600)', '초고강도 이형철근 D13 (SD600), 직경: 12.7mm, 단위중량: 0.995kg/m', '0.995kg/m', 'SD600', 'TON', 1, 'in_stock', 1),
('rebar', '초고강도철근 D16', 'D16 (SD600)', '초고강도 이형철근 D16 (SD600), 직경: 15.9mm, 단위중량: 1.560kg/m', '1.560kg/m', 'SD600', 'TON', 1, 'in_stock', 1),
('rebar', '초고강도철근 D19', 'D19 (SD600)', '초고강도 이형철근 D19 (SD600), 직경: 19.1mm, 단위중량: 2.250kg/m', '2.250kg/m', 'SD600', 'TON', 1, 'in_stock', 1),
('rebar', '초고강도철근 D22', 'D22 (SD600)', '초고강도 이형철근 D22 (SD600), 직경: 22.2mm, 단위중량: 3.040kg/m', '3.040kg/m', 'SD600', 'TON', 1, 'in_stock', 1),
('rebar', '초고강도철근 D25', 'D25 (SD600)', '초고강도 이형철근 D25 (SD600), 직경: 25.4mm, 단위중량: 3.980kg/m', '3.980kg/m', 'SD600', 'TON', 1, 'in_stock', 1),
('rebar', '초고강도철근 D29', 'D29 (SD600)', '초고강도 이형철근 D29 (SD600), 직경: 28.6mm, 단위중량: 5.040kg/m', '5.040kg/m', 'SD600', 'TON', 1, 'in_stock', 1),
('rebar', '초고강도철근 D32', 'D32 (SD600)', '초고강도 이형철근 D32 (SD600), 직경: 31.8mm, 단위중량: 6.230kg/m', '6.230kg/m', 'SD600', 'TON', 1, 'in_stock', 1),
('rebar', '초고강도철근 D35', 'D35 (SD600)', '초고강도 이형철근 D35 (SD600), 직경: 34.9mm, 단위중량: 7.510kg/m', '7.510kg/m', 'SD600', 'TON', 1, 'in_stock', 1);

-- 단위중량 데이터 추가
INSERT IGNORE INTO unit_weights (specification, unit_weight, is_active) VALUES
('D10', 0.560, 1),
('D13', 0.995, 1),
('D16', 1.560, 1),
('D19', 2.250, 1),
('D22', 3.040, 1),
('D25', 3.980, 1),
('D29', 5.040, 1),
('D32', 6.230, 1),
('D35', 7.510, 1),
('D38', 8.950, 1),
('D41', 10.500, 1),
('D51', 15.900, 1);