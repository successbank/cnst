-- 회원 로그인 로그 테이블
CREATE TABLE IF NOT EXISTS member_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    login_date DATETIME NOT NULL,
    login_ip VARCHAR(45),
    user_agent TEXT,
    login_status ENUM('success', 'failed') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_member_id (member_id),
    INDEX idx_login_date (login_date),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 회원 로그인 요약 테이블
CREATE TABLE IF NOT EXISTS member_login_summary (
    member_id INT PRIMARY KEY,
    total_login_count INT DEFAULT 0,
    last_30days_count INT DEFAULT 0,
    last_7days_count INT DEFAULT 0,
    today_count INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- members 테이블에 로그인 관련 컬럼 추가 (이미 없는 경우)
ALTER TABLE members 
ADD COLUMN IF NOT EXISTS total_login_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_login_imported BOOLEAN DEFAULT FALSE;