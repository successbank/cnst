-- site_visits 일별 집계 테이블 (2026-04-08)
-- 원본 데이터 7일만 보관, 그 이전은 일별 집계로 대체
-- 백업 크기 ~46MB → ~5MB 감소 목표

-- 1) 일별 총계 (1행/일)
CREATE TABLE IF NOT EXISTS site_visits_daily (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_date DATE NOT NULL,
    unique_visitors INT UNSIGNED NOT NULL DEFAULT 0,
    pageviews INT UNSIGNED NOT NULL DEFAULT 0,
    new_visitors INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_visit_date (visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) 페이지별 집계 (~17행/일)
CREATE TABLE IF NOT EXISTS site_visits_daily_pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_date DATE NOT NULL,
    page_url VARCHAR(500) NOT NULL,
    page_title VARCHAR(200) DEFAULT NULL,
    pageviews INT UNSIGNED NOT NULL DEFAULT 0,
    unique_visitors INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_date_page (visit_date, page_url(100)),
    INDEX idx_visit_date (visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) 차원별 집계 (device/browser/referrer, ~16행/일)
CREATE TABLE IF NOT EXISTS site_visits_daily_dimensions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    visit_date DATE NOT NULL,
    dimension_type ENUM('device','browser','referrer') NOT NULL,
    dimension_value VARCHAR(100) NOT NULL,
    sessions INT UNSIGNED NOT NULL DEFAULT 0,
    pageviews INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_date_dim (visit_date, dimension_type, dimension_value),
    INDEX idx_visit_date (visit_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
