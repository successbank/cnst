-- products 테이블에 사용 가능한 원산지 목록 컬럼 추가
ALTER TABLE products 
ADD COLUMN available_origins TEXT DEFAULT NULL COMMENT '사용 가능한 원산지 목록 (JSON 형식)' AFTER origin;

-- 기존 origin 값을 available_origins에 JSON 배열로 저장
UPDATE products 
SET available_origins = CONCAT('["', origin, '"]') 
WHERE origin IS NOT NULL AND origin != '' AND available_origins IS NULL;