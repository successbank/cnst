<?php
require_once __DIR__ . '/kakao_config.php';

class KakaoNotificationService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * 사업자(채널) 알림 수신번호: 설정값(KAKAO_ADMIN_PHONE) 우선 → is_admin 회원 폰 fallback
     */
    private function getAdminPhone() {
        if (defined('KAKAO_ADMIN_PHONE') && trim(KAKAO_ADMIN_PHONE) !== '') {
            return KAKAO_ADMIN_PHONE;
        }
        try {
            $st = $this->pdo->prepare("SELECT phone FROM members WHERE is_admin = 1 AND phone IS NOT NULL AND phone <> '' LIMIT 1");
            $st->execute();
            return $st->fetchColumn() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 게시글 작성 시 알림 발송 (회원 + 사업자) - 판매의뢰/견적 공통 진입점
     * @param string $boardType 'quote' | 'consignment'  (sales_request는 consignment로 매핑해 전달)
     * @param int    $boardId
     * @param array  $row 작성 데이터(member_id, phone/contact_phone, writer, company/company_name, category, contact_person)
     */
    public function notifyBoardCreated($boardType, $boardId, $row) {
        try {
            $adminPhone = $this->getAdminPhone();

            // 회원 수신번호: 가입 등록번호(members.phone) 우선 → 작성폼 연락처 fallback
            $memberPhone = null;
            if (!empty($row['member_id'])) {
                $st = $this->pdo->prepare("SELECT phone FROM members WHERE id = ?");
                $st->execute([$row['member_id']]);
                $memberPhone = $st->fetchColumn() ?: null;
            }

            if ($boardType === 'quote') {
                $memberPhone = $memberPhone ?: ($row['phone'] ?? '');
                $memberName = $row['writer'] ?? '';
                $data = [
                    'title'   => $row['title'] ?? '',
                    'writer'  => $row['writer'] ?? '',
                    'company' => $row['company'] ?? ''
                ];
                if ($adminPhone)  $this->sendNotification('quote', $boardId, $adminPhone, '관리자', 'QUOTE_NEW', $data);
                if ($memberPhone) $this->sendNotification('quote', $boardId, $memberPhone, $memberName, 'QUOTE_RECEIVED', $data);
            } else { // consignment / sales_request
                $memberPhone = $memberPhone ?: ($row['contact_phone'] ?? '');
                $memberName = $row['contact_person'] ?? ($row['writer'] ?? '');
                $data = [
                    'title'        => $row['title'] ?? '',
                    'company_name' => $row['company_name'] ?? '',
                    'category'     => $row['category'] ?? '',
                    'contact_person' => $memberName
                ];
                if ($adminPhone)  $this->sendNotification('consignment', $boardId, $adminPhone, '관리자', 'CONSIGN_NEW', $data);
                if ($memberPhone) $this->sendNotification('consignment', $boardId, $memberPhone, $memberName, 'CONSIGN_RECEIVED', $data);
            }
        } catch (Exception $e) {
            error_log("notifyBoardCreated 실패: " . $e->getMessage());
        }
    }

    /**
     * 카카오톡 알림 발송
     */
    public function sendNotification($boardType, $boardId, $recipientPhone, $recipientName, $messageType, $templateData, $options = []) {
        try {
            // 전화번호 정규화
            $phone = $this->normalizePhoneNumber($recipientPhone);
            if (!$phone) {
                throw new Exception("유효하지 않은 전화번호입니다.");
            }

            // 일일 발송 제한 체크
            if (defined('KAKAO_DAILY_LIMIT') && KAKAO_DAILY_LIMIT > 0) {
                $stmtLimit = $this->pdo->prepare("
                    SELECT COUNT(*) FROM kakao_notifications
                    WHERE DATE(created_at) = CURDATE() AND status != 'failed'
                ");
                $stmtLimit->execute();
                $todayCount = (int)$stmtLimit->fetchColumn();
                if ($todayCount >= KAKAO_DAILY_LIMIT) {
                    throw new Exception("일일 발송 한도(" . KAKAO_DAILY_LIMIT . "건)를 초과했습니다.");
                }
            }

            // 템플릿 가져오기
            $template = $this->getTemplate($messageType);
            if (!$template) {
                throw new Exception("템플릿을 찾을 수 없습니다: " . $messageType);
            }

            // 메시지 생성
            $message = $this->buildMessage($template['message_format'], $templateData);

            // 로그 기록
            $sentBy = $options['sent_by'] ?? null;
            $parentId = $options['parent_notification_id'] ?? null;
            $logId = $this->logNotification($boardType, $boardId, $phone, $recipientName, $messageType, $message, $template['template_code'], $sentBy, $parentId);

            // 테스트 모드 확인
            if (KAKAO_TEST_MODE) {
                $this->updateNotificationStatus($logId, 'sent', '테스트 모드 - 실제 발송하지 않음');
                return ['success' => true, 'test_mode' => true, 'message' => $message, 'notification_id' => $logId];
            }

            // 실제 카카오톡 발송
            $result = $this->sendKakaoMessage($phone, $message, $template['template_code']);

            if ($result['success']) {
                $this->updateNotificationStatus($logId, 'sent');
                return ['success' => true, 'message_id' => $result['message_id'], 'notification_id' => $logId];
            } else {
                $this->updateNotificationStatus($logId, 'failed', $result['error']);
                return ['success' => false, 'error' => $result['error'], 'notification_id' => $logId];
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
        // custom_message가 비어있을 때 연속 빈 줄 정리
        $message = preg_replace('/\n\s*\n\s*\n/', "\n\n", $message);
        return trim($message);
    }
    
    /**
     * 알림 로그 기록
     */
    private function logNotification($boardType, $boardId, $phone, $name, $messageType, $message, $templateCode, $sentBy = null, $parentNotificationId = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO kakao_notifications
            (board_type, board_id, recipient_phone, recipient_name, message_type, message_content, template_code, status, sent_by, parent_notification_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
        ");

        $stmt->execute([$boardType, $boardId, $phone, $name, $messageType, $message, $templateCode, $sentBy, $parentNotificationId]);
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
     * 실제 카카오톡 메시지 발송 (NHN Cloud Alimtalk API v2.2)
     */
    private function sendKakaoMessage($phone, $message, $templateCode) {
        $apiUrl = KAKAO_API_URL . '/appkeys/' . KAKAO_API_KEY . '/messages';

        $headers = [
            'Content-Type: application/json;charset=UTF-8',
            'X-Secret-Key: ' . KAKAO_API_SECRET,
        ];

        $data = [
            'senderKey' => KAKAO_SENDER_KEY,
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

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Kakao API CURL error: " . $curlError);
            return ['success' => false, 'error' => 'API 연결 실패: ' . $curlError];
        }

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['header']['isSuccessful']) && $result['header']['isSuccessful']) {
            $messageId = $result['message']['requestId'] ?? ('API_' . time());
            return ['success' => true, 'message_id' => $messageId];
        }

        $errorMsg = $result['header']['resultMessage'] ?? ('HTTP ' . $httpCode);
        error_log("Kakao API error: " . $errorMsg . " | Response: " . $response);
        return ['success' => false, 'error' => $errorMsg];
    }
    
    /**
     * 메시지 미리보기 (발송 없이 완성된 메시지 텍스트 반환)
     */
    public function previewMessage($messageType, $templateData) {
        $template = $this->getTemplate($messageType);
        if (!$template) {
            return ['success' => false, 'error' => '템플릿을 찾을 수 없습니다: ' . $messageType];
        }

        $message = $this->buildMessage($template['message_format'], $templateData);
        return ['success' => true, 'preview' => $message, 'template_name' => $template['template_name']];
    }

    /**
     * 알림 재발송
     */
    public function resendNotification($notificationId, $sentBy = null) {
        // 원본 알림 조회
        $stmt = $this->pdo->prepare("SELECT * FROM kakao_notifications WHERE id = ?");
        $stmt->execute([$notificationId]);
        $original = $stmt->fetch();

        if (!$original) {
            return ['success' => false, 'error' => '원본 알림을 찾을 수 없습니다.'];
        }

        // 원본의 retry_count 증가
        $stmt = $this->pdo->prepare("UPDATE kakao_notifications SET retry_count = retry_count + 1 WHERE id = ?");
        $stmt->execute([$notificationId]);

        // 새 알림 레코드 생성 (parent_notification_id 설정)
        $newLogId = $this->logNotification(
            $original['board_type'],
            $original['board_id'],
            $original['recipient_phone'],
            $original['recipient_name'],
            $original['message_type'],
            $original['message_content'],
            $original['template_code'],
            $sentBy,
            $notificationId
        );

        // 테스트 모드 확인
        if (KAKAO_TEST_MODE) {
            $this->updateNotificationStatus($newLogId, 'sent', '테스트 모드 - 재발송 (실제 발송하지 않음)');
            return ['success' => true, 'test_mode' => true, 'notification_id' => $newLogId];
        }

        // 실제 발송
        $result = $this->sendKakaoMessage($original['recipient_phone'], $original['message_content'], $original['template_code']);

        if ($result['success']) {
            $this->updateNotificationStatus($newLogId, 'sent');
            return ['success' => true, 'message_id' => $result['message_id'], 'notification_id' => $newLogId];
        } else {
            $this->updateNotificationStatus($newLogId, 'failed', $result['error']);
            return ['success' => false, 'error' => $result['error'], 'notification_id' => $newLogId];
        }
    }

    /**
     * 특정 게시글의 카카오 발송 이력 조회
     */
    public function getNotificationsByBoardId($boardType, $boardId) {
        $stmt = $this->pdo->prepare("
            SELECT id, message_type, message_content, status, error_message,
                   recipient_name, recipient_phone, retry_count, parent_notification_id,
                   sent_by, sent_at, created_at
            FROM kakao_notifications
            WHERE board_type = ? AND board_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$boardType, $boardId]);
        return $stmt->fetchAll();
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
                message_type,
                recipient_name,
                recipient_phone,
                recipient_phone as phone_number,
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