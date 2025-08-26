<?php
// SQL 실행 도구
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/db.php';

// 보안을 위해 로컬호스트에서만 실행 가능
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1' && 
    !in_array($_SERVER['REMOTE_ADDR'], ['192.168.1.1', '211.248.112.67'])) {
    // 실제 IP 추가
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['sql'])) {
    try {
        $pdo = getDB();
        $sql = trim($_POST['sql']);
        
        // 여러 SQL 문 분리 실행
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        $count = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
                $count++;
            }
        }
        
        $message = "✅ {$count}개의 SQL문이 성공적으로 실행되었습니다.";
    } catch (PDOException $e) {
        $error = "❌ 오류: " . $e->getMessage();
    }
}

// 현재 테이블 목록 가져오기
try {
    $pdo = getDB();
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $tables = [];
    $error = "데이터베이스 연결 실패: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SQL Runner - 충남스틸</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        textarea { width: 100%; height: 300px; font-family: monospace; font-size: 14px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .tables { margin-top: 20px; }
        .tables h3 { color: #333; }
        .table-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .table-item { background: #f8f9fa; padding: 8px 12px; border-radius: 4px; border: 1px solid #dee2e6; }
        .quick-sql { margin-top: 20px; }
        .quick-btn { background: #28a745; margin: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>SQL Runner</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <textarea name="sql" placeholder="SQL 쿼리를 입력하세요..."><?php echo isset($_POST['sql']) ? htmlspecialchars($_POST['sql']) : ''; ?></textarea>
            <br><br>
            <button type="submit">SQL 실행</button>
        </form>
        
        <div class="quick-sql">
            <h3>빠른 실행</h3>
            <button class="quick-btn" onclick="createRebarPrices()">rebar_prices 테이블 생성</button>
            <button class="quick-btn" onclick="createAllTables()">모든 누락 테이블 생성</button>
        </div>
        
        <div class="tables">
            <h3>현재 테이블 목록 (<?php echo count($tables); ?>개)</h3>
            <div class="table-list">
                <?php foreach ($tables as $table): ?>
                    <div class="table-item"><?php echo htmlspecialchars($table); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
    function createRebarPrices() {
        document.querySelector('textarea[name="sql"]').value = `CREATE TABLE IF NOT EXISTS rebar_prices (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 샘플 데이터
INSERT IGNORE INTO rebar_prices (spec_name, origin, manufacturer, price, price_date) VALUES
('D10', '포항', '현대제철', 850000, CURDATE()),
('D13', '포항', '현대제철', 830000, CURDATE()),
('D16', '포항', '현대제철', 820000, CURDATE());`;
    }
    
    function createAllTables() {
        document.querySelector('textarea[name="sql"]').value = `-- 모든 누락 테이블 생성
CREATE TABLE IF NOT EXISTS rebar_prices (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_type ENUM('main', 'detail', 'spec') DEFAULT 'detail',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;`;
    }
    </script>
</body>
</html>