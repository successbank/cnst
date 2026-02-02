-- 제품 임포트 이력 테이블
-- v2 임포트/엑스포트 시스템용
CREATE TABLE IF NOT EXISTS product_import_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id VARCHAR(100) NOT NULL,
    admin_name VARCHAR(100) DEFAULT NULL,
    filename VARCHAR(255) NOT NULL,
    file_size INT DEFAULT 0,
    category_filter VARCHAR(50) DEFAULT NULL,
    total_rows INT DEFAULT 0,
    created_count INT DEFAULT 0,
    updated_count INT DEFAULT 0,
    deleted_count INT DEFAULT 0,
    error_count INT DEFAULT 0,
    status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    error_details JSON DEFAULT NULL,
    change_summary JSON DEFAULT NULL,
    rollback_data JSON DEFAULT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin (admin_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
