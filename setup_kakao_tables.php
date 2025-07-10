<?php
require_once 'db.php';

try {
    echo "<h2>카카오톡 알림 테이블 설정</h2>";
    
    // kakao_notifications 테이블 생성
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kakao_notifications (
            id SERIAL PRIMARY KEY,
            board_type VARCHAR(50) NOT NULL,
            board_id INTEGER NOT NULL,
            recipient_phone VARCHAR(50) NOT NULL,
            recipient_name VARCHAR(100),
            message_type VARCHAR(50) NOT NULL,
            message_content TEXT NOT NULL,
            template_code VARCHAR(50),
            status VARCHAR(20) DEFAULT 'pending',
            error_message TEXT,
            sent_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_kakao_board (board_type, board_id),
            INDEX idx_kakao_status (status),
            INDEX idx_kakao_created (created_at DESC)
        )
    ");
    echo "kakao_notifications 테이블 생성 완료<br>";
    
    // kakao_templates 테이블 생성
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS kakao_templates (
            id SERIAL PRIMARY KEY,
            template_code VARCHAR(50) UNIQUE NOT NULL,
            template_name VARCHAR(100) NOT NULL,
            board_type VARCHAR(50) NOT NULL,
            message_format TEXT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "kakao_templates 테이블 생성 완료<br>";
    
    // 기본 템플릿 삽입
    $templates = [
        ['QUOTE_NEW', '견적문의 접수 알림', 'quote', '[충남스틸]\n견적문의가 접수되었습니다.\n\n제목: {title}\n작성자: {writer}\n회사명: {company}\n\n자세한 내용은 관리자 페이지에서 확인하세요.'],
        ['QUOTE_RECEIVED', '견적문의 접수 확인', 'quote', '[충남스틸]\n견적문의가 정상적으로 접수되었습니다.\n\n제목: {title}\n\n담당자가 확인 후 연락드리겠습니다.\n감사합니다.'],
        ['QUOTE_REPLY', '견적문의 답변 알림', 'quote', '[충남스틸]\n견적문의에 답변이 등록되었습니다.\n\n제목: {title}\n\n홈페이지에서 확인하세요.'],
        ['CONSIGN_NEW', '위탁판매 등록 알림', 'consignment', '[충남스틸]\n위탁판매가 등록되었습니다.\n\n제목: {title}\n업체명: {company_name}\n카테고리: {category}\n\n자세한 내용은 관리자 페이지에서 확인하세요.'],
        ['CONSIGN_RECEIVED', '위탁판매 등록 확인', 'consignment', '[충남스틸]\n위탁판매가 정상적으로 등록되었습니다.\n\n제목: {title}\n\n등록하신 내용은 홈페이지에서 확인 가능합니다.\n감사합니다.']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO kakao_templates (template_code, template_name, board_type, message_format) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE template_code=template_code");
    
    foreach ($templates as $template) {
        $stmt->execute($template);
    }
    echo "기본 템플릿 삽입 완료<br>";
    
    echo "<br><strong>카카오톡 알림 시스템 설정이 완료되었습니다!</strong><br>";
    echo "<br><a href='admin/admin_kakao.php'>카카오톡 관리 페이지로 이동</a>";
    
} catch (PDOException $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>