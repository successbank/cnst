-- H형강 샘플 데이터
INSERT INTO product_specifications 
(product_id, spec_name, height, width, web_thickness, flange_thickness, unit_weight, standard_length, display_order)
SELECT 
    id, '100×100×6×8', 100, 100, 6, 8, 17.2, 6000, 1
FROM products WHERE product_name = 'H형강 100×100' LIMIT 1;

INSERT INTO product_specifications 
(product_id, spec_name, height, width, web_thickness, flange_thickness, unit_weight, standard_length, display_order)
SELECT 
    id, '200×200×8×12', 200, 200, 8, 12, 49.9, 6000, 2
FROM products WHERE product_name = 'H형강 200×200' LIMIT 1;

-- H형강 가격 데이터
INSERT INTO product_prices 
(product_id, spec_id, price_type, unit_price, effective_date)
SELECT 
    p.id, ps.id, 'per_kg', 1200, CURDATE()
FROM products p
JOIN product_specifications ps ON p.id = ps.product_id
WHERE p.category_code = 'h-beam' AND ps.spec_name = '100×100×6×8';

INSERT INTO product_prices 
(product_id, spec_id, price_type, unit_price, effective_date)
SELECT 
    p.id, ps.id, 'per_kg', 1150, CURDATE()
FROM products p
JOIN product_specifications ps ON p.id = ps.product_id
WHERE p.category_code = 'h-beam' AND ps.spec_name = '200×200×8×12';

-- 철판 샘플 데이터
INSERT INTO product_specifications 
(product_id, spec_name, plate_thickness, plate_width, unit_weight, standard_length, display_order)
SELECT 
    id, '6T × 1524 × 3048', 6, 1524, 71.6, 3048, 1
FROM products WHERE product_name = '일반 강판 6T' LIMIT 1;

-- 철판 가격 데이터
INSERT INTO product_prices 
(product_id, spec_id, price_type, unit_price, effective_date)
SELECT 
    p.id, ps.id, 'per_kg', 980, CURDATE()
FROM products p
JOIN product_specifications ps ON p.id = ps.product_id
WHERE p.category_code = 'steel-plate' AND ps.spec_name = '6T × 1524 × 3048';

-- 계산 공식 샘플 데이터
INSERT INTO product_formulas 
(category_code, formula_name, formula_expression, variables, description)
VALUES 
('h-beam', 'H형강 중량계산', 'weight = unit_weight * length * quantity', 
'{"unit_weight": "단위중량(kg/m)", "length": "길이(m)", "quantity": "수량"}',
'H형강의 중량은 단위중량에 길이와 수량을 곱하여 계산합니다.'),

('steel-plate', '철판 중량계산', 'weight = thickness * width * length * 7.85 * 10^-6 * quantity',
'{"thickness": "두께(mm)", "width": "폭(mm)", "length": "길이(mm)", "quantity": "수량"}',
'철판의 중량은 두께×폭×길이×비중(7.85)×10^-6×수량으로 계산합니다.'),

('square-pipe', '사각파이프 중량계산', 'weight = [(perimeter - 4*thickness) * thickness * 0.00785] * length * quantity',
'{"perimeter": "외경둘레(mm)", "thickness": "두께(mm)", "length": "길이(m)", "quantity": "수량"}',
'사각파이프의 중량은 (외경둘레-4×두께)×두께×0.00785×길이×수량으로 계산합니다.'),

('round-pipe', '원형파이프 중량계산', 'weight = [(outer_dia - thickness) * thickness * 0.02466] * length * quantity',
'{"outer_dia": "외경(mm)", "thickness": "두께(mm)", "length": "길이(m)", "quantity": "수량"}',
'원형파이프의 중량은 (외경-두께)×두께×0.02466×길이×수량으로 계산합니다.'),

('round-bar', '환봉 중량계산', 'weight = diameter^2 * 0.00617 * length * quantity',
'{"diameter": "직경(mm)", "length": "길이(m)", "quantity": "수량"}',
'환봉의 중량은 직경²×0.00617×길이×수량으로 계산합니다.'),

('flat-bar', '평철 중량계산', 'weight = width * thickness * 0.00785 * length * quantity',
'{"width": "폭(mm)", "thickness": "두께(mm)", "length": "길이(m)", "quantity": "수량"}',
'평철의 중량은 폭×두께×0.00785×길이×수량으로 계산합니다.'),

('angle', 'ㄱ형강 중량계산', 'weight = [(A + B - t) * t * 0.00785] * length * quantity',
'{"A": "한쪽 변의 길이(mm)", "B": "다른쪽 변의 길이(mm)", "t": "두께(mm)", "length": "길이(m)", "quantity": "수량"}',
'ㄱ형강의 중량은 (A+B-t)×t×0.00785×길이×수량으로 계산합니다.');