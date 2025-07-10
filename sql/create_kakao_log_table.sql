-- 카카오톡 알림 로그 테이블
CREATE TABLE IF NOT EXISTS kakao_notifications (
    id SERIAL PRIMARY KEY,
    board_type VARCHAR(50) NOT NULL, -- 'quote' or 'consignment'
    board_id INTEGER NOT NULL,
    recipient_phone VARCHAR(50) NOT NULL,
    recipient_name VARCHAR(100),
    message_type VARCHAR(50) NOT NULL, -- 'new_post', 'status_change'
    message_content TEXT NOT NULL,
    template_code VARCHAR(50),
    status VARCHAR(20) DEFAULT 'pending', -- pending, sent, failed
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kakao_board (board_type, board_id),
    INDEX idx_kakao_status (status),
    INDEX idx_kakao_created (created_at DESC)
);

-- 카카오톡 템플릿 설정 테이블
CREATE TABLE IF NOT EXISTS kakao_templates (
    id SERIAL PRIMARY KEY,
    template_code VARCHAR(50) UNIQUE NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    board_type VARCHAR(50) NOT NULL,
    message_format TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 기본 템플릿 삽입
INSERT INTO kakao_templates (template_code, template_name, board_type, message_format) VALUES
('QUOTE_NEW', '견적문의 접수 알림', 'quote', '[충남스틸]\n견적문의가 접수되었습니다.\n\n제목: {title}\n작성자: {writer}\n회사명: {company}\n\n자세한 내용은 관리자 페이지에서 확인하세요.'),
('QUOTE_RECEIVED', '견적문의 접수 확인', 'quote', '[충남스틸]\n견적문의가 정상적으로 접수되었습니다.\n\n제목: {title}\n\n담당자가 확인 후 연락드리겠습니다.\n감사합니다.'),
('QUOTE_REPLY', '견적문의 답변 알림', 'quote', '[충남스틸]\n견적문의에 답변이 등록되었습니다.\n\n제목: {title}\n\n홈페이지에서 확인하세요.'),
('CONSIGN_NEW', '위탁판매 등록 알림', 'consignment', '[충남스틸]\n위탁판매가 등록되었습니다.\n\n제목: {title}\n업체명: {company_name}\n카테고리: {category}\n\n자세한 내용은 관리자 페이지에서 확인하세요.'),
('CONSIGN_RECEIVED', '위탁판매 등록 확인', 'consignment', '[충남스틸]\n위탁판매가 정상적으로 등록되었습니다.\n\n제목: {title}\n\n등록하신 내용은 홈페이지에서 확인 가능합니다.\n감사합니다.');