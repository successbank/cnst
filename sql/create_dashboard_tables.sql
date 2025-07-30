-- 공지사항 테이블
CREATE TABLE IF NOT EXISTS board_notice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    writer VARCHAR(100),
    views INT DEFAULT 0,
    is_important TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_important (is_important)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 견적문의 테이블
CREATE TABLE IF NOT EXISTS board_quote (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    writer VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    content TEXT,
    is_answered TINYINT(1) DEFAULT 0,
    answer_content TEXT,
    answered_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_answered (is_answered)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 철강뉴스 테이블
CREATE TABLE IF NOT EXISTS board_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    image_url VARCHAR(500),
    source VARCHAR(100),
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 위탁판매 테이블
CREATE TABLE IF NOT EXISTS board_consignment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    specifications VARCHAR(255),
    quantity VARCHAR(100),
    price VARCHAR(100),
    seller_name VARCHAR(100),
    seller_phone VARCHAR(20),
    seller_email VARCHAR(100),
    status ENUM('pending', 'active', 'sold', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 카카오톡 알림 테이블
CREATE TABLE IF NOT EXISTS kakao_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_phone VARCHAR(20),
    template_code VARCHAR(50),
    message TEXT,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at DATETIME,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 샘플 데이터 삽입
INSERT INTO board_notice (title, content, writer, is_important) VALUES
('충남스틸 홈페이지 리뉴얼 안내', '고객님께 더 나은 서비스를 제공하기 위해 홈페이지를 리뉴얼하였습니다.', '관리자', 1),
('2025년 설 연휴 휴무 안내', '설 연휴 기간 동안 휴무 예정입니다. 불편을 드려 죄송합니다.', '관리자', 1),
('철강 가격 동향 안내', '최근 철강 시장 가격 변동에 대한 안내입니다.', '관리자', 0);

INSERT INTO board_news (title, content, source) VALUES
('국내 철강 수요 증가 전망', '2025년 국내 건설 경기 회복으로 철강 수요가 증가할 것으로 전망됩니다.', '철강신문'),
('포스코, 친환경 철강 생산 확대', '포스코가 탄소중립 실현을 위한 친환경 철강 생산을 확대한다고 발표했습니다.', '경제일보');

INSERT INTO board_quote (title, writer, email, phone, content) VALUES
('H빔 300*300 견적 문의', '김철수', 'kim@example.com', '010-1234-5678', 'H빔 300*300 100톤 견적 부탁드립니다.'),
('철근 D22 대량 구매 문의', '이영희', 'lee@example.com', '010-2345-6789', '철근 D22 500톤 구매 가능한지 문의드립니다.');