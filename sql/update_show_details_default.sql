-- 기존 레코드의 show_details 필드를 기본값으로 업데이트
UPDATE products 
SET show_details = 1 
WHERE show_details IS NULL;