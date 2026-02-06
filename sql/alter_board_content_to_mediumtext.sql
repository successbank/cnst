-- board_news, board_notice 테이블의 content 컬럼을 MEDIUMTEXT로 변경
-- 목적: HWP 문서 복사-붙여넣기 시 64KB TEXT 제한 초과 오류 해결
-- 날짜: 2026-02-06

-- board_news 테이블 변경
ALTER TABLE board_news
MODIFY COLUMN content MEDIUMTEXT NOT NULL
COMMENT '뉴스 본문 (MEDIUMTEXT: 최대 16MB)';

-- board_notice 테이블 변경 (일관성 및 예방)
ALTER TABLE board_notice
MODIFY COLUMN content MEDIUMTEXT NOT NULL
COMMENT '공지사항 본문 (MEDIUMTEXT: 최대 16MB)';
