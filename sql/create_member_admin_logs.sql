-- 회원 관리 로그 테이블
-- 관리자의 회원 정보 조회/수정/삭제 등 관리 작업 기록

CREATE TABLE IF NOT EXISTS member_admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_member_id INT NOT NULL COMMENT '대상 회원 ID',
    admin_id INT NOT NULL COMMENT '작업 관리자 ID',
    action_type ENUM('view', 'update', 'password_change', 'status_change', 'grade_change', 'delete', 'memo_add') NOT NULL COMMENT '작업 유형',
    old_value TEXT DEFAULT NULL COMMENT 'JSON 형식 변경 전 값',
    new_value TEXT DEFAULT NULL COMMENT 'JSON 형식 변경 후 값',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP 주소 (IPv6 지원)',
    user_agent TEXT DEFAULT NULL COMMENT '브라우저/클라이언트 정보',
    description VARCHAR(500) DEFAULT NULL COMMENT '변경 요약 설명',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '작업 시간',

    INDEX idx_target_member (target_member_id),
    INDEX idx_admin (admin_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='회원 관리 작업 로그';

-- action_type 설명:
-- view: 회원 상세 정보 조회 (선택적 - 현재 미사용)
-- update: 회원 정보 수정
-- password_change: 비밀번호 변경
-- status_change: 활성/비활성 상태 변경
-- grade_change: 회원 등급 변경
-- delete: 회원 삭제
-- memo_add: 메모 추가
