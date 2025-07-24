-- members 테이블에 memo 컬럼 추가
ALTER TABLE members ADD COLUMN memo TEXT AFTER position;

-- 컬럼이 성공적으로 추가되었는지 확인
-- DESCRIBE members;