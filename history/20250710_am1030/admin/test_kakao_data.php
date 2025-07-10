<?php
require_once '../db.php';

// 관리자 권한 확인 (보안을 위해)
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("관리자 권한이 필요합니다.");
}

try {
    // 기존 테스트 데이터 삭제 옵션
    if (isset($_GET['clear']) && $_GET['clear'] == '1') {
        $pdo->exec("DELETE FROM kakao_notifications WHERE message_content LIKE '%[TEST]%'");
        echo "기존 테스트 데이터가 삭제되었습니다.<br><br>";
    }

    // 먼저 테이블 구조 확인
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM kakao_notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // notification_type 컬럼이 없으면 추가
        if (!in_array('notification_type', $columns)) {
            $pdo->exec("ALTER TABLE kakao_notifications ADD COLUMN notification_type VARCHAR(50) DEFAULT NULL");
            echo "notification_type 컬럼을 추가했습니다.<br>";
        }
        
        // phone_number 컬럼이 없으면 추가
        if (!in_array('phone_number', $columns)) {
            $pdo->exec("ALTER TABLE kakao_notifications ADD COLUMN phone_number VARCHAR(20) DEFAULT NULL");
            echo "phone_number 컬럼을 추가했습니다.<br>";
        }
        
        // message_type 컬럼이 있으면 기본값 설정
        if (in_array('message_type', $columns)) {
            $pdo->exec("ALTER TABLE kakao_notifications MODIFY COLUMN message_type VARCHAR(50) DEFAULT NULL");
            echo "message_type 컬럼에 기본값을 설정했습니다.<br>";
        }
    } catch (Exception $e) {
        // 테이블이 없으면 생성
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS kakao_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            board_type VARCHAR(50) NOT NULL,
            board_id INT NOT NULL,
            notification_type VARCHAR(50),
            template_code VARCHAR(100),
            recipient_name VARCHAR(100),
            recipient_phone VARCHAR(20),
            message_content TEXT,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            error_message TEXT,
            sent_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            phone_number VARCHAR(20),
            INDEX idx_board (board_type, board_id),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        )";
        $pdo->exec($createTableSQL);
        echo "kakao_notifications 테이블을 생성했습니다.<br>";
    }

    // 테스트 데이터 생성
    $testData = [];
    
    // 최근 7일간의 데이터 생성
    for ($day = 6; $day >= 0; $day--) {
        $date = date('Y-m-d', strtotime("-{$day} days"));
        
        // 각 날짜별로 랜덤한 수의 알림 생성
        $quoteSent = rand(3, 15);      // 견적문의 성공
        $quoteFailed = rand(0, 3);     // 견적문의 실패
        $quotePending = rand(0, 2);    // 견적문의 대기
        
        $consignSent = rand(2, 10);    // 위탁판매 성공
        $consignFailed = rand(0, 2);   // 위탁판매 실패
        $consignPending = rand(0, 1);  // 위탁판매 대기
        
        // 견적문의 알림 - 성공
        for ($i = 0; $i < $quoteSent; $i++) {
            $testData[] = [
                'board_type' => 'quote',
                'board_id' => rand(1, 100),
                'notification_type' => 'quote',
                'message_type' => 'admin_notification',
                'template_code' => 'QUOTE_ADMIN_001',
                'recipient_name' => '테스트관리자' . rand(1, 5),
                'recipient_phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'message_content' => "[TEST] 새로운 견적문의가 등록되었습니다.\n문의제목: 테스트 견적문의 " . rand(1, 1000),
                'phone_number' => '010' . rand(10000000, 99999999),
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 18), rand(0, 59), rand(0, 59)),
                'created_at' => $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 18), rand(0, 59), rand(0, 59))
            ];
        }
        
        // 견적문의 알림 - 실패
        for ($i = 0; $i < $quoteFailed; $i++) {
            $testData[] = [
                'board_type' => 'quote',
                'board_id' => rand(1, 100),
                'notification_type' => 'quote',
                'message_type' => 'admin_notification',
                'template_code' => 'QUOTE_ADMIN_001',
                'recipient_name' => '테스트관리자' . rand(1, 5),
                'recipient_phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'message_content' => "[TEST] 새로운 견적문의가 등록되었습니다.\n문의제목: 실패 테스트 " . rand(1, 1000),
                'phone_number' => '010' . rand(10000000, 99999999),
                'status' => 'failed',
                'error_message' => '전화번호 형식 오류',
                'created_at' => $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 18), rand(0, 59), rand(0, 59))
            ];
        }
        
        // 견적문의 알림 - 대기
        for ($i = 0; $i < $quotePending; $i++) {
            $testData[] = [
                'board_type' => 'quote',
                'board_id' => rand(1, 100),
                'notification_type' => 'quote',
                'message_type' => 'admin_notification',
                'template_code' => 'QUOTE_ADMIN_001',
                'recipient_name' => '테스트관리자' . rand(1, 5),
                'recipient_phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'message_content' => "[TEST] 새로운 견적문의가 등록되었습니다.\n문의제목: 대기중 테스트 " . rand(1, 1000),
                'phone_number' => '010' . rand(10000000, 99999999),
                'status' => 'pending',
                'error_message' => null,
                'sent_at' => null,
                'created_at' => $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 18), rand(0, 59), rand(0, 59))
            ];
        }
        
        // 위탁판매 알림 - 성공
        for ($i = 0; $i < $consignSent; $i++) {
            $testData[] = [
                'board_type' => 'consignment',
                'board_id' => rand(1, 100),
                'notification_type' => 'consignment',
                'message_type' => 'admin_notification',
                'template_code' => 'CONSIGN_ADMIN_001',
                'recipient_name' => '테스트관리자' . rand(1, 5),
                'recipient_phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'message_content' => "[TEST] 새로운 위탁판매가 등록되었습니다.\n제품명: 테스트 제품 " . rand(1, 1000),
                'phone_number' => '010' . rand(10000000, 99999999),
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 18), rand(0, 59), rand(0, 59)),
                'created_at' => $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 18), rand(0, 59), rand(0, 59))
            ];
        }
        
        // 위탁판매 알림 - 실패
        for ($i = 0; $i < $consignFailed; $i++) {
            $testData[] = [
                'board_type' => 'consignment',
                'board_id' => rand(1, 100),
                'notification_type' => 'consignment',
                'message_type' => 'admin_notification',
                'template_code' => 'CONSIGN_ADMIN_001',
                'recipient_name' => '테스트관리자' . rand(1, 5),
                'recipient_phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'message_content' => "[TEST] 새로운 위탁판매가 등록되었습니다.\n제품명: 실패 테스트 " . rand(1, 1000),
                'phone_number' => '010' . rand(10000000, 99999999),
                'status' => 'failed',
                'error_message' => 'API 연결 실패',
                'created_at' => $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 18), rand(0, 59), rand(0, 59))
            ];
        }
        
        // 위탁판매 알림 - 대기
        for ($i = 0; $i < $consignPending; $i++) {
            $testData[] = [
                'board_type' => 'consignment',
                'board_id' => rand(1, 100),
                'notification_type' => 'consignment',
                'message_type' => 'admin_notification',
                'template_code' => 'CONSIGN_ADMIN_001',
                'recipient_name' => '테스트관리자' . rand(1, 5),
                'recipient_phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'message_content' => "[TEST] 새로운 위탁판매가 등록되었습니다.\n제품명: 대기중 테스트 " . rand(1, 1000),
                'phone_number' => '010' . rand(10000000, 99999999),
                'status' => 'pending',
                'error_message' => null,
                'sent_at' => null,
                'created_at' => $date . ' ' . sprintf('%02d:%02d:%02d', rand(8, 18), rand(0, 59), rand(0, 59))
            ];
        }
    }
    
    // 먼저 message_type 컬럼이 있는지 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM kakao_notifications WHERE Field = 'message_type'");
    $hasMessageType = $stmt->rowCount() > 0;
    
    // 데이터 삽입 - message_type 컬럼 유무에 따라 다른 쿼리 사용
    if ($hasMessageType) {
        $stmt = $pdo->prepare("
            INSERT INTO kakao_notifications 
            (board_type, board_id, notification_type, message_type, template_code, recipient_name, 
             recipient_phone, message_content, phone_number, status, error_message, sent_at, created_at)
            VALUES 
            (:board_type, :board_id, :notification_type, :message_type, :template_code, :recipient_name,
             :recipient_phone, :message_content, :phone_number, :status, :error_message, :sent_at, :created_at)
        ");
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO kakao_notifications 
            (board_type, board_id, notification_type, template_code, recipient_name, 
             recipient_phone, message_content, phone_number, status, error_message, sent_at, created_at)
            VALUES 
            (:board_type, :board_id, :notification_type, :template_code, :recipient_name,
             :recipient_phone, :message_content, :phone_number, :status, :error_message, :sent_at, :created_at)
        ");
    }
    
    $insertCount = 0;
    foreach ($testData as $data) {
        // message_type 컬럼이 없으면 배열에서 제거
        if (!$hasMessageType && isset($data['message_type'])) {
            unset($data['message_type']);
        }
        
        $stmt->execute($data);
        $insertCount++;
    }
    
    echo "<h2>테스트 데이터 생성 완료</h2>";
    echo "<p>총 {$insertCount}개의 카카오톡 알림 테스트 데이터가 생성되었습니다.</p>";
    echo "<ul>";
    echo "<li>최근 7일간의 데이터</li>";
    echo "<li>견적문의 및 위탁판매 알림</li>";
    echo "<li>성공, 실패, 대기 상태 포함</li>";
    echo "</ul>";
    echo "<br>";
    echo '<a href="admin_kakao.php" style="padding: 10px 20px; background: #1A237E; color: white; text-decoration: none; border-radius: 8px;">카카오톡 관리 페이지로 이동</a>';
    echo ' ';
    echo '<a href="test_kakao_data.php?clear=1" style="padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 8px;">테스트 데이터 삭제 후 재생성</a>';
    
    // 생성된 데이터 요약 표시
    echo "<h3 style='margin-top: 30px;'>생성된 데이터 요약</h3>";
    echo "<table style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr style='background: #f5f5f5;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>날짜</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>견적문의</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>위탁판매</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>총계</th>";
    echo "</tr>";
    
    for ($day = 6; $day >= 0; $day--) {
        $date = date('Y-m-d', strtotime("-{$day} days"));
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN notification_type = 'quote' THEN 1 ELSE 0 END) as quote_count,
                SUM(CASE WHEN notification_type = 'consignment' THEN 1 ELSE 0 END) as consign_count,
                COUNT(*) as total
            FROM kakao_notifications 
            WHERE DATE(created_at) = ?
            AND message_content LIKE '%[TEST]%'
        ");
        $stmt->execute([$date]);
        $row = $stmt->fetch();
        
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$date}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>{$row['quote_count']}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'>{$row['consign_count']}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'><strong>{$row['total']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>