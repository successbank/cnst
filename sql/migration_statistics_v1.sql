-- ============================================
-- 접속통계 기능 마이그레이션 v1
-- 실행일: 2026-02-07
-- ============================================

-- 1. member_login_logs: login_status 컬럼 추가
-- login.php:120에서 INSERT 시 이 컬럼을 참조하지만 테이블에 없어서 실패
ALTER TABLE member_login_logs
ADD COLUMN login_status VARCHAR(20) DEFAULT 'success' AFTER user_agent;

-- 2. member_login_summary: 누락 컬럼 3개 추가
-- login.php:130-147에서 total_login_count, last_7days_count, today_count 참조
-- 실제 테이블에는 total_count, last_90days_count만 존재
ALTER TABLE member_login_summary
ADD COLUMN total_login_count INT DEFAULT 0 AFTER member_id;

ALTER TABLE member_login_summary
ADD COLUMN last_7days_count INT DEFAULT 0 AFTER last_30days_count;

ALTER TABLE member_login_summary
ADD COLUMN today_count INT DEFAULT 0 AFTER last_7days_count;

-- 3. site_visits 테이블 신규 생성
CREATE TABLE IF NOT EXISTS site_visits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    page_url VARCHAR(500) NOT NULL,
    page_title VARCHAR(200) DEFAULT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    referrer VARCHAR(500) DEFAULT NULL,
    device_type ENUM('desktop','mobile','tablet') DEFAULT 'desktop',
    browser VARCHAR(50) DEFAULT NULL,
    os VARCHAR(50) DEFAULT NULL,
    member_id INT DEFAULT NULL,
    is_new_visitor TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_session_id (session_id),
    INDEX idx_page_url (page_url(100)),
    INDEX idx_device_type (device_type),
    INDEX idx_date_page (created_at, page_url(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
