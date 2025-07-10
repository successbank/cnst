-- 회원 테이블 생성
CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(100),
    position VARCHAR(50),
    address VARCHAR(255),
    address_detail VARCHAR(255),
    zipcode VARCHAR(10),
    is_active TINYINT(1) DEFAULT 1,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 기본 관리자 계정 생성 (비밀번호: admin1234)
INSERT INTO members (user_id, password, name, email, phone, is_admin) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', '관리자', 'admin@chungnamsteel.com', '010-0000-0000', 1)
ON DUPLICATE KEY UPDATE user_id=user_id;