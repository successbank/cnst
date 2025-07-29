-- 회원 배송지 테이블 생성
CREATE TABLE IF NOT EXISTS member_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    address_name VARCHAR(100) NOT NULL COMMENT '배송지 별칭',
    recipient_name VARCHAR(100) COMMENT '수령인명',
    recipient_phone VARCHAR(20) COMMENT '수령인 연락처',
    zipcode VARCHAR(10) NOT NULL,
    address VARCHAR(255) NOT NULL,
    address_detail VARCHAR(255),
    is_default TINYINT(1) DEFAULT 0 COMMENT '기본 배송지 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_member_id (member_id),
    INDEX idx_default (member_id, is_default),
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 회원당 하나의 기본 배송지만 허용하는 트리거
DELIMITER $$
CREATE TRIGGER before_insert_member_addresses
BEFORE INSERT ON member_addresses
FOR EACH ROW
BEGIN
    IF NEW.is_default = 1 THEN
        UPDATE member_addresses 
        SET is_default = 0 
        WHERE member_id = NEW.member_id;
    END IF;
END$$

CREATE TRIGGER before_update_member_addresses
BEFORE UPDATE ON member_addresses
FOR EACH ROW
BEGIN
    IF NEW.is_default = 1 AND OLD.is_default = 0 THEN
        UPDATE member_addresses 
        SET is_default = 0 
        WHERE member_id = NEW.member_id AND id != NEW.id;
    END IF;
END$$
DELIMITER ;