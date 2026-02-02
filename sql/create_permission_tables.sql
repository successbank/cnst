-- 회원 권한 관련 테이블 생성 스크립트
-- 생성일: 2026-01-29

-- 1. 등급 설정 테이블
CREATE TABLE IF NOT EXISTS member_grade_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_code VARCHAR(20) NOT NULL UNIQUE,
    grade_name VARCHAR(50) NOT NULL,
    discount_rate DECIMAL(5,2) DEFAULT 0.00,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 권한 정의 테이블
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_code VARCHAR(50) NOT NULL UNIQUE,
    permission_name VARCHAR(100) NOT NULL,
    permission_group VARCHAR(50),
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 등급별 권한 매핑 테이블
CREATE TABLE IF NOT EXISTS grade_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_code VARCHAR(20) NOT NULL,
    permission_code VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_grade_permission (grade_code, permission_code),
    INDEX idx_grade_code (grade_code),
    INDEX idx_permission_code (permission_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
