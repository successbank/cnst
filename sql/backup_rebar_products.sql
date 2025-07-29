-- 철근 제품 백업
CREATE TABLE IF NOT EXISTS products_rebar_backup_20250129 AS 
SELECT * FROM products WHERE category_code = 'rebar';

-- 백업 데이터 확인
SELECT COUNT(*) as total_count FROM products_rebar_backup_20250129;