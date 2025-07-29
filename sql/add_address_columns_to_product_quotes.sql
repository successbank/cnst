-- product_quotes 테이블에 주소 관련 컬럼 추가
ALTER TABLE product_quotes 
ADD COLUMN zipcode VARCHAR(10) AFTER email,
ADD COLUMN address VARCHAR(255) AFTER zipcode,
ADD COLUMN address_detail VARCHAR(255) AFTER address;