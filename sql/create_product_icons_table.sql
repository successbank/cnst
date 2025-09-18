-- 제품 아이콘 관리 테이블
CREATE TABLE IF NOT EXISTS product_icons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(50),
    icon_name VARCHAR(100) NOT NULL COMMENT '아이콘 표시명',
    icon_image VARCHAR(500) COMMENT '아이콘 이미지 경로',
    icon_url VARCHAR(500) COMMENT '클릭시 이동할 URL',
    url_target ENUM('_self', '_blank') DEFAULT '_self' COMMENT 'URL 타겟 (현재창/새창)',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    is_active BOOLEAN DEFAULT TRUE COMMENT '활성화 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='메인페이지 제품 아이콘 관리';

-- 초기 데이터 삽입 (현재 메인페이지의 17개 제품)
INSERT INTO product_icons (category_code, icon_name, icon_url, display_order, is_active) VALUES
('rebar', '철근(특판)', 'products.php#rebar', 1, TRUE),
('hbeam', 'H형강(H빔)', 'products.php#hbeam', 2, TRUE),
('plate', '철강(강판)', 'products.php#plate', 3, TRUE),
('metalath', '메탈라스(망철판)', 'products.php#metalath', 4, TRUE),
('light-hbeam', '경량H형강', 'products.php#light-hbeam', 5, TRUE),
('ibeam', 'I형강(빔)', 'products.php#ibeam', 6, TRUE),
('angle', 'ㄱ형강(앵글)', 'products.php#angle', 7, TRUE),
('channel', 'ㄷ형강(찬넬)', 'products.php#channel', 8, TRUE),
('round-bar', '환봉(원형강)', 'products.php#round-bar', 9, TRUE),
('flat-bar', '평철', 'products.php#flat-bar', 10, TRUE),
('c-channel', 'C형강', 'products.php#c-channel', 11, TRUE),
('tech-plate', '테크플레이트', 'products.php#tech-plate', 12, TRUE),
('square-pipe', '사각파이프(각관)', 'products.php#square-pipe', 13, TRUE),
('round-pipe', '원형파이프(강관)', 'products.php#round-pipe', 14, TRUE),
('rail', '레일', 'products.php#rail', 15, TRUE),
('sheet-pile', '강널말뚝(쉬트파일)', 'products.php#sheet-pile', 16, TRUE),
('stainless', '스테인레스(STS)', 'products.php#stainless', 17, TRUE);