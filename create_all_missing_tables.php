<?php
// 누락된 모든 테이블 생성
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';

echo "=== 누락된 테이블 생성 시작 ===\n\n";

try {
    $pdo = getDB();
    $created = 0;
    $errors = 0;
    
    // 생성할 테이블 목록
    $tables = [
        // rebar_prices 테이블
        "CREATE TABLE IF NOT EXISTS rebar_prices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            spec_name VARCHAR(50) NOT NULL,
            origin VARCHAR(100),
            manufacturer VARCHAR(100),
            price DECIMAL(12,2),
            price_date DATE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_spec_origin (spec_name, origin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // product_images 테이블
        "CREATE TABLE IF NOT EXISTS product_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_url VARCHAR(500) NOT NULL,
            image_type ENUM('main', 'detail', 'spec') DEFAULT 'detail',
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // member_addresses 테이블
        "CREATE TABLE IF NOT EXISTS member_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            address_name VARCHAR(50) NOT NULL,
            recipient_name VARCHAR(50) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            zipcode VARCHAR(10),
            address1 VARCHAR(255) NOT NULL,
            address2 VARCHAR(255),
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // product_quotes 테이블
        "CREATE TABLE IF NOT EXISTS product_quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT,
            company_name VARCHAR(100),
            contact_name VARCHAR(50) NOT NULL,
            contact_phone VARCHAR(20) NOT NULL,
            contact_email VARCHAR(100),
            delivery_address TEXT,
            request_date DATE,
            memo TEXT,
            status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            admin_memo TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // product_quote_items 테이블
        "CREATE TABLE IF NOT EXISTS product_quote_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_id INT NOT NULL,
            product_id INT,
            product_name VARCHAR(200) NOT NULL,
            specifications TEXT,
            quantity DECIMAL(10,2) NOT NULL,
            unit VARCHAR(50),
            unit_price DECIMAL(12,2),
            total_price DECIMAL(12,2),
            memo TEXT,
            FOREIGN KEY (quote_id) REFERENCES product_quotes(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // site_settings 테이블
        "CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // login_logs 테이블
        "CREATE TABLE IF NOT EXISTS login_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT,
            user_id VARCHAR(50),
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            status ENUM('success', 'failed') DEFAULT 'success',
            failure_reason VARCHAR(255),
            FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
            INDEX idx_login_time (login_time),
            INDEX idx_member_id (member_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        // kakao_messages 테이블
        "CREATE TABLE IF NOT EXISTS kakao_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_code VARCHAR(50) NOT NULL,
            receiver_name VARCHAR(50),
            receiver_phone VARCHAR(20) NOT NULL,
            message_content TEXT NOT NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            sent_at TIMESTAMP NULL,
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    // 각 테이블 생성
    foreach ($tables as $i => $sql) {
        preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $matches);
        $tableName = $matches[1] ?? 'Unknown';
        
        echo ($i + 1) . ". $tableName 테이블 생성 중... ";
        
        try {
            $pdo->exec($sql);
            echo "✓ 완료\n";
            $created++;
        } catch (PDOException $e) {
            echo "✗ 실패: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    // 샘플 데이터 추가
    echo "\n=== 샘플 데이터 추가 ===\n";
    
    // rebar_prices 샘플 데이터
    echo "rebar_prices 샘플 데이터 추가 중... ";
    try {
        $pdo->exec("INSERT IGNORE INTO rebar_prices (spec_name, origin, manufacturer, price, price_date) VALUES
            ('D10', '포항', '현대제철', 850000, CURDATE()),
            ('D13', '포항', '현대제철', 830000, CURDATE()),
            ('D16', '포항', '현대제철', 820000, CURDATE()),
            ('D10', '당진', '동국제강', 845000, CURDATE()),
            ('D13', '당진', '동국제강', 825000, CURDATE())");
        echo "✓ 완료\n";
    } catch (PDOException $e) {
        echo "✗ 실패: " . $e->getMessage() . "\n";
    }
    
    // site_settings 기본값
    echo "site_settings 기본값 추가 중... ";
    try {
        $pdo->exec("INSERT IGNORE INTO site_settings (setting_key, setting_value, description) VALUES
            ('site_name', '충남스틸', '사이트 이름'),
            ('company_phone', '032-564-1616', '대표 전화번호'),
            ('company_email', 'info@chungnamsteel.com', '대표 이메일'),
            ('business_hours', '평일 08:00 ~ 18:00', '영업 시간')");
        echo "✓ 완료\n";
    } catch (PDOException $e) {
        echo "✗ 실패: " . $e->getMessage() . "\n";
    }
    
    // 결과 출력
    echo "\n=== 작업 완료 ===\n";
    echo "생성된 테이블: $created 개\n";
    echo "오류 발생: $errors 개\n";
    
    // 전체 테이블 목록 확인
    echo "\n=== 현재 데이터베이스 테이블 목록 ===\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "✓ $table (레코드 수: $count)\n";
    }
    
    echo "\n✅ 모든 작업이 완료되었습니다!\n";
    
} catch (PDOException $e) {
    echo "\n❌ 데이터베이스 연결 오류: " . $e->getMessage() . "\n";
}

// 파일 자동 삭제 (보안)
echo "\n이 파일은 5초 후 자동 삭제됩니다...\n";
sleep(5);
@unlink(__FILE__);
?>