-- ============================================================
-- 제품 견적요청(장바구니) 시스템 v2 마이그레이션
-- 문서: dev_docs/PRD_product_quote_v2.md
-- 작성: 2026-09-11
-- 원칙: 추가 전용(additive only). 기존 컬럼 변경/삭제 없음.
-- ============================================================

-- 1) 장바구니 테이블
CREATE TABLE IF NOT EXISTS quote_cart_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  member_id     INT NULL            COMMENT '회원 ID (비회원이면 NULL)',
  cart_token    CHAR(64) NULL       COMMENT '비회원 카트 토큰',
  product_id    INT NULL            COMMENT '제품 ID',
  product_name  VARCHAR(200) NOT NULL,
  product_spec  VARCHAR(200) NULL,
  origin        VARCHAR(50) NULL    COMMENT '원산지',
  material      VARCHAR(50) NULL    COMMENT '재질',
  length_value  DECIMAL(10,2) NULL  COMMENT '길이',
  length_unit   VARCHAR(10) NOT NULL DEFAULT 'M',
  quantity      DECIMAL(12,3) NOT NULL DEFAULT 1,
  quantity_unit VARCHAR(10) NOT NULL DEFAULT 'EA' COMMENT 'EA/본/TON/장/SET',
  note          VARCHAR(500) NULL   COMMENT '항목별 요청사항',
  created_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_member (member_id),
  KEY idx_token (cart_token),
  KEY idx_created (created_at),
  CONSTRAINT fk_qci_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='제품 견적 장바구니';

-- 2) product_quotes 확장
ALTER TABLE product_quotes
  ADD COLUMN member_id   INT NULL      COMMENT '회원 ID (비회원 NULL)' AFTER id,
  ADD COLUMN notes       TEXT NULL     COMMENT '고객 요청사항' AFTER products,
  ADD COLUMN source      VARCHAR(20) NOT NULL DEFAULT 'legacy' COMMENT 'v2=신규 견적요청' AFTER status,
  ADD COLUMN admin_reply TEXT NULL     COMMENT '고객 공개 답변' AFTER admin_note,
  ADD COLUMN replied_at  DATETIME NULL AFTER admin_reply,
  ADD KEY idx_member (member_id);

-- 3) product_quote_items 확장
ALTER TABLE product_quote_items
  ADD COLUMN origin        VARCHAR(50) NULL   AFTER product_spec,
  ADD COLUMN material      VARCHAR(50) NULL   AFTER origin,
  ADD COLUMN length_value  DECIMAL(10,2) NULL AFTER material,
  ADD COLUMN length_unit   VARCHAR(10) NOT NULL DEFAULT 'M' AFTER length_value,
  ADD COLUMN quantity_unit VARCHAR(10) NOT NULL DEFAULT 'EA' AFTER quantity;

ALTER TABLE product_quote_items
  MODIFY COLUMN quantity DECIMAL(12,3) NOT NULL DEFAULT 1 COMMENT '수량';

-- 4) 모드 전환 설정 키 (기본값 calc = 기존 자동계산 방식)
INSERT INTO site_settings (setting_key, setting_value, setting_type, setting_group, description)
VALUES ('product_detail_mode', 'calc', 'text', 'product',
        '제품 상세페이지 모드: calc=기존 자동계산, quote=신규 견적요청')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
