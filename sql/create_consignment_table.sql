-- 중계판매 게시판 테이블 생성
CREATE TABLE IF NOT EXISTS board_consignment (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    writer VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    company_name VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL,
    stock_quantity VARCHAR(100),
    price_info TEXT,
    contact_person VARCHAR(100),
    contact_phone VARCHAR(50),
    contact_email VARCHAR(100),
    location VARCHAR(200),
    attachment VARCHAR(255),
    view_count INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active', -- active, sold, pending
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 인덱스 생성
CREATE INDEX idx_board_consignment_created ON board_consignment(created_at DESC);
CREATE INDEX idx_board_consignment_category ON board_consignment(category);
CREATE INDEX idx_board_consignment_status ON board_consignment(status);

-- 샘플 데이터 입력
INSERT INTO board_consignment (title, content, writer, password, company_name, category, stock_quantity, price_info, contact_person, contact_phone, location) VALUES
('H형강 200*200*8*12 재고 처분', 'H형강 200*200*8*12 규격 재고 처분합니다.\n상태 양호하며 즉시 출고 가능합니다.\n\n- 규격: 200*200*8*12\n- 길이: 12M\n- 수량: 100톤\n- 상태: A급', '김철수', '1234', '대한철강', 'H형강', '100톤', '시세 대비 5% 할인', '김철수', '010-1234-5678', '충남 천안시'),
('철근 HD16 긴급 처분', '프로젝트 잔량 철근 긴급 처분합니다.\n\n- 규격: HD16\n- 수량: 50톤\n- 제조사: 현대제철\n- 상태: 신품', '이영희', '1234', '건설자재유통', '철근', '50톤', '협의 가능', '이영희', '010-2345-6789', '충남 아산시'),
('철판 6T 재고 정리', '철판 6T 재고 정리합니다.\n\n- 두께: 6mm\n- 규격: 1219*2438\n- 수량: 200장\n- 상태: 양호', '박민수', '1234', '스틸플러스', '철강', '200장', '장당 가격 협의', '박민수', '010-3456-7890', '충남 당진시');