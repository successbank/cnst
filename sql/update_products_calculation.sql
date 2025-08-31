-- 제품 테이블에 계산 관련 필드 추가
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS calculation_type ENUM('linear', 'sheet') DEFAULT 'linear' COMMENT '계산 유형: linear(선형), sheet(판재)',
ADD COLUMN IF NOT EXISTS unit_weight_data JSON COMMENT '규격별 단위중량 데이터',
ADD COLUMN IF NOT EXISTS available_materials JSON COMMENT '사용 가능한 재질 목록',
ADD COLUMN IF NOT EXISTS available_sizes JSON COMMENT '사용 가능한 규격 목록',
ADD COLUMN IF NOT EXISTS has_calculator TINYINT(1) DEFAULT 0 COMMENT '계산기 사용 여부';

-- 제품 규격 데이터 테이블 (별도 관리 시)
CREATE TABLE IF NOT EXISTS product_specifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    category_code VARCHAR(50) NOT NULL,
    specification VARCHAR(100) NOT NULL COMMENT '규격 (예: 25*25*3T)',
    unit_weight DECIMAL(10,3) NOT NULL COMMENT '단위중량 (kg)',
    material VARCHAR(50) DEFAULT NULL COMMENT '재질',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_category (category_code),
    INDEX idx_spec (specification),
    INDEX idx_material (material)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 계산 로그 테이블 (옵션)
CREATE TABLE IF NOT EXISTS calculation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    category_code VARCHAR(50) NOT NULL,
    specification VARCHAR(100),
    material VARCHAR(50),
    length DECIMAL(10,2),
    quantity INT,
    calculated_weight DECIMAL(10,2),
    user_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product (product_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;