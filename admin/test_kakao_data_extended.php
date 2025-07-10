<?php
require_once '../db.php';

// 관리자 권한 확인 (보안을 위해)
session_start();
if (!isset($_SESSION['admin_id'])) {
    die("관리자 권한이 필요합니다.");
}

try {
    // 기존 모든 테스트 데이터 삭제
    $pdo->exec("DELETE FROM kakao_notifications WHERE message_content LIKE '%[TEST]%'");
    echo "기존 테스트 데이터가 삭제되었습니다.<br><br>";

    // 테이블 구조 확인 및 필요한 컬럼 추가
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
        echo "테이블 구조 확인 중 오류: " . $e->getMessage() . "<br>";
    }

    // 테스트 데이터 생성 (5월 1일부터 7월 9일까지)
    $startDate = new DateTime('2025-05-01');
    $endDate = new DateTime('2025-07-09');
    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($startDate, $interval, $endDate->add($interval));
    
    $testData = [];
    $totalRecords = 501;
    $daysCount = iterator_count($period);
    $recordsPerDay = floor($totalRecords / $daysCount);
    $remainingRecords = $totalRecords - ($recordsPerDay * $daysCount);
    
    $dayIndex = 0;
    foreach ($period as $date) {
        $dateStr = $date->format('Y-m-d');
        
        // 각 날짜별 레코드 수 결정 (남은 레코드를 처음 날짜들에 분배)
        $dayRecords = $recordsPerDay;
        if ($dayIndex < $remainingRecords) {
            $dayRecords++;
        }
        $dayIndex++;
        
        // 각 날짜의 레코드를 상태별로 분배
        $sentRatio = rand(60, 80) / 100;      // 60-80% 성공
        $failedRatio = rand(5, 15) / 100;     // 5-15% 실패
        $pendingRatio = 1 - $sentRatio - $failedRatio; // 나머지 대기
        
        $sentCount = round($dayRecords * $sentRatio);
        $failedCount = round($dayRecords * $failedRatio);
        $pendingCount = $dayRecords - $sentCount - $failedCount;
        
        // 타입별로도 분배 (견적문의 60%, 위탁판매 40%)
        $quoteRatio = 0.6;
        
        // 성공 레코드 생성
        for ($i = 0; $i < $sentCount; $i++) {
            $isQuote = ($i < $sentCount * $quoteRatio);
            $hour = rand(8, 18);
            $minute = rand(0, 59);
            $second = rand(0, 59);
            
            $testData[] = [
                'board_type' => $isQuote ? 'quote' : 'consignment',
                'board_id' => rand(1, 1000),
                'notification_type' => $isQuote ? 'quote' : 'consignment',
                'message_type' => 'admin_notification',
                'template_code' => $isQuote ? 'QUOTE_ADMIN_001' : 'CONSIGN_ADMIN_001',
                'recipient_name' => '테스트관리자' . rand(1, 10),
                'recipient_phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'message_content' => "[TEST] " . ($isQuote ? 
                    "새로운 견적문의가 등록되었습니다.\n문의제목: 테스트 견적문의 #" . rand(1000, 9999) :
                    "새로운 위탁판매가 등록되었습니다.\n제품명: 테스트 제품 #" . rand(1000, 9999)),
                'phone_number' => '010' . rand(10000000, 99999999),
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => $dateStr . ' ' . sprintf('%02d:%02d:%02d', $hour, $minute, $second),
                'created_at' => $dateStr . ' ' . sprintf('%02d:%02d:%02d', $hour, $minute, $second)
            ];
        }
        
        // 실패 레코드 생성
        for ($i = 0; $i < $failedCount; $i++) {
            $isQuote = ($i < $failedCount * $quoteRatio);
            $hour = rand(8, 18);
            $minute = rand(0, 59);
            $second = rand(0, 59);
            
            $errorMessages = [
                '전화번호 형식 오류',
                'API 연결 실패',
                '템플릿 오류',
                '발송 한도 초과',
                '수신자 거부'
            ];
            
            $testData[] = [
                'board_type' => $isQuote ? 'quote' : 'consignment',
                'board_id' => rand(1, 1000),
                'notification_type' => $isQuote ? 'quote' : 'consignment',
                'message_type' => 'admin_notification',
                'template_code' => $isQuote ? 'QUOTE_ADMIN_001' : 'CONSIGN_ADMIN_001',
                'recipient_name' => '테스트관리자' . rand(1, 10),
                'recipient_phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'message_content' => "[TEST] " . ($isQuote ? 
                    "새로운 견적문의가 등록되었습니다.\n문의제목: 실패 테스트 #" . rand(1000, 9999) :
                    "새로운 위탁판매가 등록되었습니다.\n제품명: 실패 테스트 #" . rand(1000, 9999)),
                'phone_number' => '010' . rand(10000000, 99999999),
                'status' => 'failed',
                'error_message' => $errorMessages[array_rand($errorMessages)],
                'sent_at' => null,
                'created_at' => $dateStr . ' ' . sprintf('%02d:%02d:%02d', $hour, $minute, $second)
            ];
        }
        
        // 대기 레코드 생성
        for ($i = 0; $i < $pendingCount; $i++) {
            $isQuote = ($i < $pendingCount * $quoteRatio);
            $hour = rand(8, 18);
            $minute = rand(0, 59);
            $second = rand(0, 59);
            
            $testData[] = [
                'board_type' => $isQuote ? 'quote' : 'consignment',
                'board_id' => rand(1, 1000),
                'notification_type' => $isQuote ? 'quote' : 'consignment',
                'message_type' => 'admin_notification',
                'template_code' => $isQuote ? 'QUOTE_ADMIN_001' : 'CONSIGN_ADMIN_001',
                'recipient_name' => '테스트관리자' . rand(1, 10),
                'recipient_phone' => '010-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'message_content' => "[TEST] " . ($isQuote ? 
                    "새로운 견적문의가 등록되었습니다.\n문의제목: 대기중 테스트 #" . rand(1000, 9999) :
                    "새로운 위탁판매가 등록되었습니다.\n제품명: 대기중 테스트 #" . rand(1000, 9999)),
                'phone_number' => '010' . rand(10000000, 99999999),
                'status' => 'pending',
                'error_message' => null,
                'sent_at' => null,
                'created_at' => $dateStr . ' ' . sprintf('%02d:%02d:%02d', $hour, $minute, $second)
            ];
        }
    }
    
    // message_type 컬럼 존재 여부 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM kakao_notifications WHERE Field = 'message_type'");
    $hasMessageType = $stmt->rowCount() > 0;
    
    // 데이터 삽입
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
    
    echo "<h2>확장 테스트 데이터 생성 완료</h2>";
    echo "<p>총 <strong>{$insertCount}개</strong>의 카카오톡 알림 테스트 데이터가 생성되었습니다.</p>";
    echo "<ul>";
    echo "<li>기간: 2025년 5월 1일 ~ 7월 9일 (" . $daysCount . "일간)</li>";
    echo "<li>견적문의: 약 " . round($insertCount * 0.6) . "개 (60%)</li>";
    echo "<li>위탁판매: 약 " . round($insertCount * 0.4) . "개 (40%)</li>";
    echo "</ul>";
    echo "<br>";
    echo '<a href="admin_kakao.php" style="padding: 10px 20px; background: #1A237E; color: white; text-decoration: none; border-radius: 8px;">카카오톡 관리 페이지로 이동</a>';
    echo ' ';
    echo '<a href="test_kakao_data_extended.php" style="padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 8px;">데이터 재생성</a>';
    
    // 월별 요약 통계
    echo "<h3 style='margin-top: 30px;'>월별 발송 통계</h3>";
    echo "<table style='border-collapse: collapse; margin-top: 10px; width: 600px;'>";
    echo "<tr style='background: #f5f5f5;'>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>월</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>성공</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>실패</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>대기</th>";
    echo "<th style='border: 1px solid #ddd; padding: 8px;'>총계</th>";
    echo "</tr>";
    
    $months = ['2025-05', '2025-06', '2025-07'];
    foreach ($months as $month) {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                COUNT(*) as total
            FROM kakao_notifications 
            WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
            AND message_content LIKE '%[TEST]%'
        ");
        $stmt->execute([$month]);
        $row = $stmt->fetch();
        
        echo "<tr>";
        echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$month}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center; color: #2E7D32;'>{$row['sent']}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center; color: #C62828;'>{$row['failed']}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center; color: #F57C00;'>{$row['pending']}</td>";
        echo "<td style='border: 1px solid #ddd; padding: 8px; text-align: center;'><strong>{$row['total']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "오류 발생: " . $e->getMessage();
}
?>