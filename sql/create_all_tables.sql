-- 충남스틸 웹사이트 모든 테이블 생성 SQL (MySQL/MariaDB)

-- board_quote 테이블
CREATE TABLE IF NOT EXISTS board_quote (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    writer VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    company VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(50),
    attachment VARCHAR(255),
    view_count INT DEFAULT 0,
    is_answered BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- board_notice 테이블
CREATE TABLE IF NOT EXISTS board_notice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    writer VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    attachment VARCHAR(255),
    view_count INT DEFAULT 0,
    is_important BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- board_news 테이블
CREATE TABLE IF NOT EXISTS board_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    writer VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    source VARCHAR(200),
    attachment VARCHAR(255),
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- board_consignment 테이블
CREATE TABLE IF NOT EXISTS board_consignment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    writer VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    company_name VARCHAR(100),
    category VARCHAR(50),
    attachment VARCHAR(255),
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- board_comments 테이블
CREATE TABLE IF NOT EXISTS board_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_type VARCHAR(50) NOT NULL,
    board_id INT NOT NULL,
    content TEXT NOT NULL,
    writer VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 인덱스 생성
CREATE INDEX IF NOT EXISTS idx_board_quote_created ON board_quote(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_board_notice_created ON board_notice(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_board_news_created ON board_news(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_board_consignment_created ON board_consignment(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_comments_board ON board_comments(board_type, board_id);

-- 샘플 데이터 삽입
INSERT INTO board_notice (title, content, writer, password, is_important) VALUES
('충남스틸 홈페이지 오픈 안내', '안녕하세요. 충남스틸입니다.\n\n저희 홈페이지가 새롭게 오픈하였습니다.\n앞으로 더 나은 서비스로 찾아뵙겠습니다.\n\n감사합니다.', '관리자', '1234', true),
('2024년 설 연휴 영업 안내', '2024년 설 연휴 영업 일정을 안내드립니다.\n\n- 2월 9일(금) ~ 2월 12일(월): 휴무\n- 2월 13일(화): 정상 영업\n\n즐거운 명절 보내시기 바랍니다.', '관리자', '1234', false);

INSERT INTO board_news (title, content, writer, password, source) VALUES
('철강 가격 동향 및 전망', '최근 국제 철강 가격이 상승세를 보이고 있습니다.\n\n중국의 생산 감축과 원자재 가격 상승이 주요 원인으로 분석됩니다.', '관리자', '1234', '한국철강신문'),
('친환경 철강 생산 기술 개발', '포스코가 수소환원제철 기술 개발에 성공했다고 발표했습니다.\n\n이를 통해 탄소 배출을 획기적으로 줄일 수 있을 것으로 기대됩니다.', '관리자', '1234', '철강업계뉴스');