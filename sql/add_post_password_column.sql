-- board_consignment 테이블에 비밀번호 및 member_id 컬럼 추가
ALTER TABLE board_consignment 
ADD COLUMN post_password VARCHAR(255) DEFAULT NULL COMMENT '게시글 비밀번호',
ADD COLUMN member_id INT DEFAULT NULL COMMENT '작성자 회원 ID',
ADD INDEX idx_member_id (member_id);

-- board_quote 테이블에도 동일하게 적용 (필요한 경우)
ALTER TABLE board_quote 
ADD COLUMN post_password VARCHAR(255) DEFAULT NULL COMMENT '게시글 비밀번호',
ADD COLUMN member_id INT DEFAULT NULL COMMENT '작성자 회원 ID',
ADD INDEX idx_member_id (member_id);

-- board_notice 테이블에도 동일하게 적용 (필요한 경우)
ALTER TABLE board_notice 
ADD COLUMN post_password VARCHAR(255) DEFAULT NULL COMMENT '게시글 비밀번호',
ADD COLUMN member_id INT DEFAULT NULL COMMENT '작성자 회원 ID',
ADD INDEX idx_member_id (member_id);

-- board_news 테이블에도 동일하게 적용 (필요한 경우)
ALTER TABLE board_news 
ADD COLUMN post_password VARCHAR(255) DEFAULT NULL COMMENT '게시글 비밀번호',
ADD COLUMN member_id INT DEFAULT NULL COMMENT '작성자 회원 ID',
ADD INDEX idx_member_id (member_id);