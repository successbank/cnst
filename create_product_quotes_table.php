<?php
require_once 'db.php';

try {
    // 제품 견적서 테이블 생성
    $sql = "CREATE TABLE IF NOT EXISTS product_quotes (
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
    
    $pdo->exec($sql);
    echo "product_quotes 테이블이 성공적으로 생성되었습니다.<br>";
    
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
    echo "product_quote_items 테이블이 성공적으로 생성되었습니다.";
    
} catch (PDOException $e) {
    echo "테이블 생성 실패: " . $e->getMessage();
}
?>