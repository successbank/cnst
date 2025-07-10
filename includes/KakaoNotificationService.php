<?php
require_once __DIR__ . '/kakao_config.php';

class KakaoNotificationService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * 카카오톡 알림 발송
     */
    public function sendNotification($boardType, $boardId, $recipientPhone, $recipientName, $messageType, $templateData) {
        try {
            // 전화번호 정규화
            $phone = $this->normalizePhoneNumber($recipientPhone);
            if (!$phone) {
                throw new Exception("유효하지 않은 전화번호입니다.");
            }
            
            // 템플릿 가져오기
            $template = $this->getTemplate($messageType);
            if (!$template) {
                throw new Exception("템플릿을 찾을 수 없습니다: " . $messageType);
            }
            
            // 메시지 생성
            $message = $this->buildMessage($template['message_format'], $templateData);
            
            // 로그 기록
            $logId = $this->logNotification($boardType, $boardId, $phone, $recipientName, $messageType, $message, $template['template_code']);
            
            // 테스트 모드 확인
            if (KAKAO_TEST_MODE) {
                $this->updateNotificationStatus($logId, 'sent', '테스트 모드 - 실제 발송하지 않음');
                return ['success' => true, 'test_mode' => true, 'message' => $message];
            }
            
            // 실제 카카오톡 발송
            $result = $this->sendKakaoMessage($phone, $message, $template['template_code']);
            
            if ($result['success']) {
                $this->updateNotificationStatus($logId, 'sent');
                return ['success' => true, 'message_id' => $result['message_id']];
            } else {
                $this->updateNotificationStatus($logId, 'failed', $result['error']);
                return ['success' => false, 'error' => $result['error']];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 전화번호 정규화
     */
    private function normalizePhoneNumber($phone) {
        // 숫자만 추출
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // 한국 번호 형식 확인
        if (preg_match('/^(01[016789])([0-9]{3,4})([0-9]{4})$/', $phone)) {
            return $phone;
        }
        
        return false;
    }
    
    /**
     * 템플릿 조회
     */
    private function getTemplate($templateCode) {
        $stmt = $this->pdo->prepare("SELECT * FROM kakao_templates WHERE template_code = ? AND is_active = TRUE");
        $stmt->execute([$templateCode]);
        return $stmt->fetch();
    }
    
    /**
     * 메시지 생성
     */
    private function buildMessage($format, $data) {
        $message = $format;
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        return $message;
    }
    
    /**
     * 알림 로그 기록
     */
    private function logNotification($boardType, $boardId, $phone, $name, $messageType, $message, $templateCode) {
        $stmt = $this->pdo->prepare("
            INSERT INTO kakao_notifications 
            (board_type, board_id, recipient_phone, recipient_name, message_type, message_content, template_code, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([$boardType, $boardId, $phone, $name, $messageType, $message, $templateCode]);
        return $this->pdo->lastInsertId();
    }
    
    /**
     * 알림 상태 업데이트
     */
    private function updateNotificationStatus($logId, $status, $errorMessage = null) {
        $stmt = $this->pdo->prepare("
            UPDATE kakao_notifications 
            SET status = ?, error_message = ?, sent_at = ?
            WHERE id = ?
        ");
        
        $sentAt = ($status === 'sent') ? date('Y-m-d H:i:s') : null;
        $stmt->execute([$status, $errorMessage, $sentAt, $logId]);
    }
    
    /**
     * 실제 카카오톡 메시지 발송 (API 호출)
     */
    private function sendKakaoMessage($phone, $message, $templateCode) {
        // TODO: 실제 카카오 API 연동 구현
        // 여기서는 샘플 구현만 제공
        
        $headers = [
            'Content-Type: application/json',
            'X-Secret-Key: ' . KAKAO_API_SECRET,
        ];
        
        $data = [
            'plusFriendId' => KAKAO_CHANNEL_ID,
            'templateCode' => $templateCode,
            'recipientList' => [
                [
                    'recipientNo' => $phone,
                    'templateParameter' => [
                        'message' => $message
                    ]
                ]
            ]
        ];
        
        // 실제 구현시 CURL을 사용하여 API 호출
        // $ch = curl_init(KAKAO_API_URL . '/messages');
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // $response = curl_exec($ch);
        // curl_close($ch);
        
        // 임시 성공 응답
        return [
            'success' => true,
            'message_id' => 'DEMO_' . time()
        ];
    }
    
    /**
     * 발송 통계 조회
     */
    public function getStatistics($boardType = null, $dateFrom = null, $dateTo = null) {
        $where = ["1=1"];
        $params = [];
        
        if ($boardType) {
            $where[] = "board_type = ?";
            $params[] = $boardType;
        }
        
        if ($dateFrom) {
            $where[] = "created_at >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $where[] = "created_at <= ?";
            $params[] = $dateTo;
        }
        
        $whereClause = implode(" AND ", $where);
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM kakao_notifications
            WHERE $whereClause
        ");
        
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * 최근 발송 내역 조회
     */
    public function getRecentNotifications($limit = 20, $boardType = null) {
        $where = "";
        $params = [];
        
        if ($boardType) {
            $where = "WHERE board_type = ?";
            $params[] = $boardType;
        }
        
        $sql = "
            SELECT 
                id,
                board_type,
                board_id,
                notification_type,
                recipient_name,
                recipient_phone,
                COALESCE(phone_number, recipient_phone) as phone_number,
                message_content,
                status,
                error_message,
                created_at,
                sent_at
            FROM kakao_notifications
            $where
            ORDER BY created_at DESC
            LIMIT " . (int)$limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>