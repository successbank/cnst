<?php
require_once 'db.php';

try {
    // 테이블 생성
    $sql = "CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type VARCHAR(50) DEFAULT 'text',
        setting_group VARCHAR(50) DEFAULT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "site_settings 테이블이 생성되었습니다.<br>";
    
    // 기본 설정 값 삽입
    $settings = [
        ['company_name', '충남스틸', 'company'],
        ['company_address', '충청남도 아산시 둔포면 아산밸리로 63', 'company'],
        ['company_tel', '032-564-1616', 'company'],
        ['company_fax', '032-564-0090', 'company'],
        ['company_email', 'info@chungnamsteel.com', 'company'],
        ['site_title', '충남스틸 - 철강 전문 유통업체', 'seo'],
        ['site_description', '충남스틸은 철강 제품의 판매 및 가공을 전문으로 하는 철강 유통업체입니다. 고품질의 철강 제품과 신속한 납품으로 고객 만족을 추구합니다.', 'seo'],
        ['site_keywords', '철강, 철강유통, 충남스틸, 아산철강, 철강가공', 'seo'],
        ['contact_email', 'sales@chungnamsteel.com', 'contact'],
        ['contact_phone', '032-564-1616', 'contact']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=setting_value");
    
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    
    echo "기본 설정이 추가되었습니다.<br>";
    
} catch (PDOException $e) {
    echo "오류: " . $e->getMessage();
}
?>