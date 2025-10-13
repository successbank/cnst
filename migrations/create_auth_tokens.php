<?php
/**
 * 인증 토큰 및 세션 설정 테이블 생성 마이그레이션
 */

require_once __DIR__ . '/../db.php';

try {
    // 1. auth_tokens 테이블 생성
    $sql_tokens = "
    CREATE TABLE IF NOT EXISTS auth_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        selector VARCHAR(32) NOT NULL,
        hashed_validator VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        UNIQUE KEY unique_selector (selector),
        INDEX idx_member_id (member_id),
        INDEX idx_expires (expires_at),
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_tokens);
    echo "✓ auth_tokens 테이블 생성 완료\n";

    // 2. member_session_preferences 테이블 생성
    $sql_preferences = "
    CREATE TABLE IF NOT EXISTS member_session_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        session_duration INT DEFAULT 0 COMMENT '세션 유지 시간(초), 0=기본값, -1=종일',
        remember_me_enabled TINYINT(1) DEFAULT 1 COMMENT '로그인 유지 기능 활성화',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_member (member_id),
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql_preferences);
    echo "✓ member_session_preferences 테이블 생성 완료\n";

    // 3. 만료된 토큰 정리를 위한 이벤트 생성 (선택적)
    $sql_event = "
    CREATE EVENT IF NOT EXISTS cleanup_expired_tokens
    ON SCHEDULE EVERY 1 DAY
    STARTS CURRENT_TIMESTAMP
    DO
        DELETE FROM auth_tokens WHERE expires_at < NOW();
    ";

    try {
        $pdo->exec($sql_event);
        echo "✓ 만료 토큰 자동 정리 이벤트 생성 완료\n";
    } catch(PDOException $e) {
        echo "⚠ 이벤트 생성 실패 (이벤트 스케줄러가 비활성화되어 있을 수 있습니다): " . $e->getMessage() . "\n";
    }

    echo "\n마이그레이션 완료!\n";

} catch(PDOException $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
    exit(1);
}
?>
