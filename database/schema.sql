-- 충남스틸 데이터베이스 스키마

CREATE DATABASE IF NOT EXISTS chungnamsteel DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chungnamsteel;

-- 게시판 공통 테이블
CREATE TABLE IF NOT EXISTS boards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_type ENUM('quote', 'notice', 'news') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author VARCHAR(100) NOT NULL,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_board_type (board_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 견적문의 테이블
CREATE TABLE IF NOT EXISTS quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    contact_name VARCHAR(50) NOT NULL,
    contact_email VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    product_type VARCHAR(50) NOT NULL,
    quantity VARCHAR(100) NOT NULL,
    desired_date DATE,
    status ENUM('pending', 'processing', 'completed') DEFAULT 'pending',
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 공지사항 테이블
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL,
    is_important BOOLEAN DEFAULT FALSE,
    attachment_url VARCHAR(500),
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 철강뉴스 테이블
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL,
    source VARCHAR(100),
    external_url VARCHAR(500),
    thumbnail_url VARCHAR(500),
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 샘플 데이터 삽입

-- 공지사항 샘플
INSERT INTO boards (board_type, title, content, author) VALUES
('notice', '2024년 설 연휴 영업 안내', '안녕하세요. 충남스틸입니다.\n\n2024년 설 연휴 영업 일정을 안내드립니다.\n- 휴무: 2월 9일(금) ~ 2월 12일(월)\n- 정상영업: 2월 13일(화)부터\n\n감사합니다.', '관리자'),
('notice', '신제품 출시 안내', '고강도 철근 신제품이 출시되었습니다. 자세한 사항은 영업부로 문의해주세요.', '관리자');

INSERT INTO notices (board_id, is_important) VALUES
(1, TRUE),
(2, FALSE);

-- 철강뉴스 샘플
INSERT INTO boards (board_type, title, content, author) VALUES
('news', '2024년 철강 시장 전망', '올해 철강 시장은 건설 경기 회복과 함께 수요 증가가 예상됩니다. 전문가들은 하반기부터 본격적인 수요 회복세를 보일 것으로 전망하고 있습니다.', '편집부'),
('news', '친환경 철강 생산 기술 개발', '국내 철강업계가 탄소중립을 위한 친환경 생산 기술 개발에 박차를 가하고 있습니다. 수소환원제철 기술 등 혁신적인 기술 개발이 진행 중입니다.', '편집부');

INSERT INTO news (board_id, source, external_url) VALUES
(3, '철강신문', 'http://example.com/news1'),
(4, '산업일보', 'http://example.com/news2');