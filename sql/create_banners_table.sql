-- 배너 관리 테이블 생성
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) COMMENT '배너 제목',
    subtitle TEXT COMMENT '배너 부제목',
    image_path VARCHAR(500) COMMENT '이미지 경로',
    link_url VARCHAR(500) COMMENT '링크 URL',
    link_target VARCHAR(20) DEFAULT '_self' COMMENT '링크 타겟 (_self, _blank)',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    is_active TINYINT DEFAULT 1 COMMENT '활성화 여부 (1: 활성, 0: 비활성)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='배너 관리 테이블';

-- 인덱스 추가
CREATE INDEX idx_display_order ON banners(display_order);
CREATE INDEX idx_is_active ON banners(is_active);