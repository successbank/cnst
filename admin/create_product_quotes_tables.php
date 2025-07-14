<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>제품견적서 테이블 생성</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .success {
            color: green;
            padding: 10px;
            background: #e8f5e9;
            border-radius: 4px;
            margin: 10px 0;
        }
        .error {
            color: red;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
            margin: 10px 0;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #1428A0;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>제품견적서 테이블 생성</h1>
    
    <?php
    try {
        // 제품 견적서 테이블 생성
        $sql1 = "CREATE TABLE IF NOT EXISTS product_quotes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100) NOT NULL,
            company VARCHAR(200),
            phone VARCHAR(50),
            email VARCHAR(100),
            products TEXT NOT NULL,
            notes TEXT,
            admin_notes TEXT,
            status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
            member_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql1);
        echo '<div class="success">✓ product_quotes 테이블이 성공적으로 생성되었습니다.</div>';
        echo '<pre>' . htmlspecialchars($sql1) . '</pre>';
        
        // 제품 견적서 아이템 테이블 생성
        $sql2 = "CREATE TABLE IF NOT EXISTS product_quote_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_id INT NOT NULL,
            product_id INT,
            product_name VARCHAR(255) NOT NULL,
            specifications TEXT,
            quantity INT NOT NULL DEFAULT 1,
            unit_price DECIMAL(12,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quote_id) REFERENCES product_quotes(id) ON DELETE CASCADE,
            INDEX idx_quote_id (quote_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql2);
        echo '<div class="success">✓ product_quote_items 테이블이 성공적으로 생성되었습니다.</div>';
        echo '<pre>' . htmlspecialchars($sql2) . '</pre>';
        
        // 테이블 확인
        $tables = $pdo->query("SHOW TABLES LIKE 'product_quote%'")->fetchAll(PDO::FETCH_COLUMN);
        echo '<div class="success">생성된 테이블 목록: ' . implode(', ', $tables) . '</div>';
        
    } catch (PDOException $e) {
        echo '<div class="error">테이블 생성 실패: ' . $e->getMessage() . '</div>';
    }
    ?>
    
    <a href="admin_product_quotes.php">제품견적서 관리 페이지로 이동</a>
</body>
</html>