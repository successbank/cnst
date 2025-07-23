-- members 테이블에 일반전화번호 컬럼 추가
ALTER TABLE members 
ADD COLUMN landline VARCHAR(20) AFTER phone;

-- 컬럼 추가 확인
SHOW COLUMNS FROM members;