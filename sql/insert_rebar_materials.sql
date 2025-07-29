-- 철근 재질 데이터 삽입
INSERT INTO rebar_materials (material_code, material_name, additional_price, description, display_order, is_active) VALUES
('SD300', '일반용 철근 SD300', 0, '항복강도 300MPa 이상의 일반 철근', 1, 1),
('SD400', '일반용 철근 SD400', 0, '항복강도 400MPa 이상의 표준 철근', 2, 1),
('SD500', '고강도 철근 SD500', 50000, '항복강도 500MPa 이상의 고강도 철근', 3, 1),
('SD600', '초고강도 철근 SD600', 100000, '항복강도 600MPa 이상의 초고강도 철근', 4, 1),
('SD700', '특수고강도 철근 SD700', 150000, '항복강도 700MPa 이상의 특수고강도 철근', 5, 1);