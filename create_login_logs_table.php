<?php
// 직접 DB 연결
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 데이터베이스 연결 성공\n\n";
} catch (PDOException $e) {
    die("❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

echo "=== 로그인 로그 테이블 생성 ===\n";

try {
    // 1. 로그인 로그 테이블 생성
    $sql = "CREATE TABLE IF NOT EXISTS member_login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        user_id VARCHAR(50) NOT NULL,
        login_date DATETIME NOT NULL,
        login_count INT DEFAULT 0,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_member_id (member_id),
        INDEX idx_user_id (user_id),
        INDEX idx_login_date (login_date),
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ member_login_logs 테이블 생성 완료\n";
    
    // 2. 회원 테이블에 로그인 통계 컬럼 추가 (없는 경우)
    $columns_to_add = [
        "ALTER TABLE members ADD COLUMN IF NOT EXISTS total_login_count INT DEFAULT 0",
        "ALTER TABLE members ADD COLUMN IF NOT EXISTS last_login_imported DATETIME",
        "ALTER TABLE members ADD COLUMN IF NOT EXISTS login_history_imported TINYINT DEFAULT 0"
    ];
    
    foreach ($columns_to_add as $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ 컬럼 추가/확인: " . substr($sql, 35, 30) . "...\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "⚠️ 경고: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // 3. 로그인 요약 테이블 생성 (대시보드용)
    $sql = "CREATE TABLE IF NOT EXISTS member_login_summary (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL UNIQUE,
        user_id VARCHAR(50) NOT NULL,
        first_login_date DATETIME,
        last_login_date DATETIME,
        total_login_count INT DEFAULT 0,
        avg_logins_per_month DECIMAL(10,2) DEFAULT 0,
        last_30_days_count INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_member_id (member_id),
        INDEX idx_last_login (last_login_date),
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ member_login_summary 테이블 생성 완료\n";
    
    echo "\n=== 테이블 구조 확인 ===\n";
    
    // 테이블 정보 출력
    $tables = ['member_login_logs', 'member_login_summary'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        echo "\n[$table]\n";
        foreach ($columns as $col) {
            echo sprintf("  %-25s %-20s %s\n", $col['Field'], $col['Type'], $col['Key']);
        }
    }
    
} catch (PDOException $e) {
    echo "❌ 오류: " . $e->getMessage() . "\n";
}

echo "\n✅ 로그인 로그 테이블 생성 완료!\n";