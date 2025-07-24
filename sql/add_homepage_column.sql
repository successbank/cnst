-- members 테이블에 homepage 컬럼 추가
ALTER TABLE members ADD COLUMN homepage VARCHAR(255) DEFAULT NULL AFTER company;

-- 컬럼 추가 확인
-- SHOW COLUMNS FROM members;