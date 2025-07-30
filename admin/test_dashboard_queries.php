<?php
// 데이터베이스 연결
require_once dirname(__DIR__) . '/db.php';
$pdo = getDB();

echo "=== 대시보드 쿼리 테스트 ===\n\n";

try {
    // 공지사항
    echo "1. 공지사항 테이블 구조:\n";
    $stmt = $pdo->query("DESCRIBE board_notice");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "Columns: " . implode(", ", $columns) . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM board_notice");
    $count = $stmt->fetchColumn();
    echo "공지사항 수: $count\n\n";
    
    // 견적문의
    echo "2. 견적문의 테이블 구조:\n";
    $stmt = $pdo->query("DESCRIBE board_quote");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "Columns: " . implode(", ", $columns) . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM board_quote");
    $result = $stmt->fetch();
    echo "견적문의 수: " . $result['total'] . "\n";
    
    // is_answered 컬럼 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM board_quote LIKE 'is_answered'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM board_quote WHERE is_answered = 0 OR is_answered IS NULL");
        $pending = $stmt->fetchColumn();
        echo "대기중 견적: $pending\n\n";
    } else {
        echo "is_answered 컬럼 없음\n\n";
    }
    
    // 철강뉴스
    echo "3. 철강뉴스:\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM board_news");
    $count = $stmt->fetchColumn();
    echo "철강뉴스 수: $count\n\n";
    
    // 위탁판매
    echo "4. 위탁판매:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM board_consignment LIKE 'status'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM board_consignment");
        $result = $stmt->fetch();
        echo "위탁판매 수: " . $result['total'] . " (진행중: " . ($result['active'] ?? 0) . ")\n\n";
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM board_consignment");
        $count = $stmt->fetchColumn();
        echo "위탁판매 수: $count (status 컬럼 없음)\n\n";
    }
    
    // 회원
    echo "5. 회원:\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE is_admin = 0");
    $count = $stmt->fetchColumn();
    echo "일반 회원 수: $count\n\n";
    
    // 카카오톡
    echo "6. 카카오톡 알림:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM kakao_notifications");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM kakao_notifications WHERE DATE(created_at) = CURDATE()");
        $count = $stmt->fetchColumn();
        echo "오늘 카카오톡 발송: $count\n";
    } else {
        echo "kakao_notifications 테이블 구조 문제\n";
    }
    
    // 샘플 데이터 확인
    echo "\n7. 샘플 데이터 확인:\n";
    $stmt = $pdo->query("SELECT * FROM board_notice LIMIT 1");
    $sample = $stmt->fetch();
    if ($sample) {
        echo "공지사항 샘플: " . json_encode($sample, JSON_UNESCAPED_UNICODE) . "\n";
    }
    
} catch (PDOException $e) {
    echo "에러: " . $e->getMessage() . "\n";
}