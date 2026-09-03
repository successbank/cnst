<?php
// 게시판 템플릿 시스템
class BoardTemplate {
    private $db;
    private $boardType;
    private $tableName;
    private $boardTitle;
    private $allowUpload;
    private $isSecret;
    
    public function __construct($db, $boardType) {
        $this->db = $db;
        $this->boardType = $boardType;
        
        // 게시판 타입별 설정
        switch($boardType) {
            case 'quote':
                $this->tableName = 'board_quote';
                $this->boardTitle = '견적문의';
                $this->allowUpload = true;
                $this->isSecret = true;
                break;
            case 'notice':
                $this->tableName = 'board_notice';
                $this->boardTitle = '공지사항';
                $this->allowUpload = true;
                $this->isSecret = false;
                break;
            case 'news':
                $this->tableName = 'board_news';
                $this->boardTitle = '철강뉴스';
                $this->allowUpload = true;
                $this->isSecret = false;
                break;
            case 'consignment':
                $this->tableName = 'board_consignment';
                $this->boardTitle = '중계판매';
                $this->allowUpload = true;
                $this->isSecret = false;
                break;
            case 'sales_request':
                $this->tableName = 'board_consignment';
                $this->boardTitle = '판매의뢰';
                $this->allowUpload = true;
                $this->isSecret = true;
                break;
            case 'brokerage':
                $this->tableName = 'board_consignment';
                $this->boardTitle = '중개판매';
                $this->allowUpload = true;
                $this->isSecret = false;
                break;
            default:
                throw new Exception("Invalid board type");
        }
    }
    
    // 게시글 목록 조회
    public function getList($page = 1, $perPage = 10, $search = '', $category = '', $extraFilters = []) {
        // page는 1 이상으로 보정 (0/음수/문자 입력 시 OFFSET 음수로 인한 SQL 1064 오류 방지)
        $page = max(1, (int)$page);
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        // sales_request: 본인 판매의뢰만 표시
        if ($this->boardType === 'sales_request') {
            $where[] = "status IN ('pending','rejected')";
            if (isset($extraFilters['member_id'])) {
                $where[] = "member_id = :filter_member_id";
                $params[':filter_member_id'] = $extraFilters['member_id'];
            }
        }

        // brokerage: 승인된 매물만 표시
        if ($this->boardType === 'brokerage') {
            $where[] = "status = 'approved'";
            // 비관리자: 작성자 미연결(기존) 매물은 공개 + 본인 작성분만 (관리자는 미전달=전체)
            if (isset($extraFilters['member_id'])) {
                $where[] = "(member_id IS NULL OR member_id = :filter_member_id)";
                $params[':filter_member_id'] = $extraFilters['member_id'];
            }
        }

        // item_type 필터
        if (!empty($extraFilters['item_type'])) {
            $where[] = "item_type = :filter_item_type";
            $params[':filter_item_type'] = $extraFilters['item_type'];
        }

        if ($search) {
            $searchPattern = '%' . $search . '%';
            if (in_array($this->boardType, ['consignment', 'sales_request', 'brokerage'])) {
                $where[] = "(title LIKE :search OR content LIKE :search OR company_name LIKE :search)";
            } else {
                $where[] = "(title LIKE :search OR content LIKE :search)";
            }
            $params[':search'] = $searchPattern;
        }

        if ($category && in_array($this->boardType, ['consignment', 'sales_request', 'brokerage'])) {
            $where[] = "category = :category";
            $params[':category'] = $category;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // 전체 게시글 수 조회
        $countSql = "SELECT COUNT(*) FROM {$this->tableName} {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $totalCount = $stmt->fetchColumn();
        
        // 게시글 목록 조회
        $orderBy = "created_at DESC, id DESC";
        
        // 공지사항은 중요 공지를 먼저 표시
        if ($this->boardType === 'notice') {
            $orderBy = "is_important DESC, created_at DESC, id DESC";
        }
        
        // 견적문의는 display_order 우선
        if ($this->boardType === 'quote') {
            $orderBy = "COALESCE(display_order, 999999), id DESC";
        }
        
        $sql = "SELECT * FROM {$this->tableName} {$whereClause} 
                ORDER BY {$orderBy} 
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'list' => $stmt->fetchAll(),
            'totalCount' => $totalCount,
            'pagination' => getPagination($totalCount, $page, $perPage)
        ];
    }
    
    // 게시글 조회
    public function getPost($id) {
        // 조회수 증가
        $updateSql = "UPDATE {$this->tableName} SET view_count = view_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($updateSql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // 게시글 조회
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    // 게시글 작성
    public function writePost($data) {
        // 기본 필드
        $fields = ['title', 'content', 'writer', 'attachment'];
        $values = [':title', ':content', ':writer', ':attachment'];
        $params = [
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':writer' => $data['writer'],
            ':attachment' => $data['attachment'] ?? null
        ];
        
        // 비밀번호 처리 (bcrypt 해시)
        $fields[] = 'password';
        $values[] = ':password';
        if (!empty($data['password'])) {
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        } else {
            $params[':password'] = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        }
        
        // member_id 처리 - 로그인한 경우에만 저장
        if (isset($data['member_id']) && $data['member_id']) {
            $fields[] = 'member_id';
            $values[] = ':member_id';
            $params[':member_id'] = $data['member_id'];
        }
        
        // sales_request 타입일 때 status, item_type 자동 세팅
        if ($this->boardType === 'sales_request') {
            $fields[] = 'status';
            $values[] = ':status';
            $params[':status'] = 'pending';
        }

        // item_type 처리
        if (in_array($this->boardType, ['consignment', 'sales_request', 'brokerage']) && isset($data['item_type'])) {
            $fields[] = 'item_type';
            $values[] = ':item_type';
            $params[':item_type'] = $data['item_type'];
        }

        // 게시판별 추가 필드
        if ($this->boardType === 'quote') {
            if (isset($data['company'])) {
                $fields[] = 'company';
                $values[] = ':company';
                $params[':company'] = $data['company'];
            }
            if (isset($data['email'])) {
                $fields[] = 'email';
                $values[] = ':email';
                $params[':email'] = $data['email'];
            }
            if (isset($data['phone'])) {
                $fields[] = 'phone';
                $values[] = ':phone';
                $params[':phone'] = $data['phone'];
            }
        } elseif ($this->boardType === 'news') {
            if (isset($data['source'])) {
                $fields[] = 'source';
                $values[] = ':source';
                $params[':source'] = $data['source'];
            }
        } elseif (in_array($this->boardType, ['consignment', 'sales_request', 'brokerage'])) {
            if (isset($data['company_name'])) {
                $fields[] = 'company_name';
                $values[] = ':company_name';
                $params[':company_name'] = $data['company_name'];
            }
            if (isset($data['category'])) {
                $fields[] = 'category';
                $values[] = ':category';
                $params[':category'] = $data['category'];
            }
            if (isset($data['stock_quantity'])) {
                $fields[] = 'stock_quantity';
                $values[] = ':stock_quantity';
                $params[':stock_quantity'] = $data['stock_quantity'];
            }
            if (isset($data['price_info'])) {
                $fields[] = 'price_info';
                $values[] = ':price_info';
                $params[':price_info'] = $data['price_info'];
            }
            if (isset($data['contact_person'])) {
                $fields[] = 'contact_person';
                $values[] = ':contact_person';
                $params[':contact_person'] = $data['contact_person'];
            }
            if (isset($data['contact_phone'])) {
                $fields[] = 'contact_phone';
                $values[] = ':contact_phone';
                $params[':contact_phone'] = $data['contact_phone'];
            }
            if (isset($data['contact_email'])) {
                $fields[] = 'contact_email';
                $values[] = ':contact_email';
                $params[':contact_email'] = $data['contact_email'];
            }
            if (isset($data['location'])) {
                $fields[] = 'location';
                $values[] = ':location';
                $params[':location'] = $data['location'];
            }
        }
        
        $fields[] = 'created_at';
        $values[] = 'NOW()';
        
        $sql = "INSERT INTO {$this->tableName} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $values) . ")";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $result = $stmt->execute();
        
        // 성공적으로 등록된 경우 카카오톡 알림 + 이메일 알림 발송
        if ($result) {
            $insertId = $this->db->lastInsertId();
            $this->sendKakaoNotification($insertId, $data);
            $this->sendEmailNotification($insertId, $data);
        }

        return $result;
    }

    // 문의/등록 수신 이메일로 알림 발송 (견적문의/위탁판매/판매의뢰)
    // - 관리자 [사이트관리 > 문의 설정]의 contact_email(쉼표/줄바꿈 다중) 주소로 발송
    // - 첨부파일 포함, 실패해도 게시글 등록에는 영향 없음
    private function sendEmailNotification($boardId, $data) {
        // 이메일 알림 대상 게시판 및 표시명 (카카오 알림과 동일 범위)
        $labels = [
            'quote'         => '견적문의',
            'consignment'   => '위탁판매',
            'sales_request' => '판매의뢰',
        ];
        if (!isset($labels[$this->boardType])) {
            return;
        }
        $label = $labels[$this->boardType];

        try {
            require_once __DIR__ . '/../includes/settings.php';
            require_once __DIR__ . '/../includes/EmailService.php';

            // 문의 수신 이메일 (쉼표 구분 다중 수신자). EmailService가 분해/검증 처리.
            $recipients = trim(getSetting('contact_email', ''));
            if ($recipients === '') {
                error_log('[BoardEmail] contact_email 미설정 - 알림 미발송 (' . $this->boardType . ' #' . $boardId . ')');
                return;
            }

            // 첨부파일 절대경로 수집 (uploads/{게시판}/ 하위)
            $attachments = [];
            if (!empty($data['attachment'])) {
                $files = json_decode($data['attachment'], true);
                if (is_array($files)) {
                    foreach ($files as $fname) {
                        $path = __DIR__ . '/../uploads/' . $this->boardType . '/' . basename($fname);
                        if (is_file($path)) {
                            $attachments[] = ['path' => $path, 'name' => $fname];
                        }
                    }
                }
            }

            $esc = function ($v) {
                return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
            };

            // 게시판별 표시 필드 (라벨 => 값). 값이 빈 항목은 본문에서 자동 생략.
            if ($this->boardType === 'quote') {
                $rows = [
                    '작성자' => $data['writer']  ?? '',
                    '회사명' => $data['company'] ?? '',
                    '이메일' => $data['email']   ?? '',
                    '연락처' => $data['phone']   ?? '',
                ];
            } else {
                // 위탁판매 / 판매의뢰 공통 필드
                $rows = [
                    '작성자'   => $data['writer']         ?? '',
                    '회사명'   => $data['company_name']   ?? '',
                    '담당자'   => $data['contact_person'] ?? '',
                    '연락처'   => $data['contact_phone']  ?? '',
                    '이메일'   => $data['contact_email']  ?? '',
                    '품목종류' => $data['item_type']      ?? '',
                    '카테고리' => $data['category']       ?? '',
                    '재고수량' => $data['stock_quantity'] ?? '',
                    '가격정보' => $data['price_info']     ?? '',
                    '위치'     => $data['location']       ?? '',
                ];
            }

            $title   = $data['title']   ?? '(제목 없음)';
            $content = $data['content'] ?? '';
            $subject = '[충남스틸] 새 ' . $label . ': ' . $title;
            $viewUrl = 'https://cnst.co.kr/board_view.php?type=' . $this->boardType . '&id=' . (int)$boardId;

            $body  = '<div style="font-family:sans-serif;font-size:14px;color:#333;line-height:1.7">';
            $body .= '<h2 style="color:#1A237E">새 ' . $esc($label) . '(이)가 접수되었습니다</h2>';
            $body .= '<table cellpadding="6" style="border-collapse:collapse;border:1px solid #eee">';
            $body .= '<tr><td style="font-weight:bold;background:#f5f5f5;width:100px">제목</td><td>' . $esc($title) . '</td></tr>';
            foreach ($rows as $k => $v) {
                if ($v === '' || $v === null) {
                    continue;
                }
                $body .= '<tr><td style="font-weight:bold;background:#f5f5f5">' . $esc($k) . '</td><td>' . $esc($v) . '</td></tr>';
            }
            $body .= '</table>';
            $body .= '<h3 style="color:#1A237E;margin-top:20px">내용</h3>';
            $body .= '<div style="white-space:pre-wrap;border:1px solid #eee;padding:12px;border-radius:6px">' . nl2br($esc($content)) . '</div>';
            if (!empty($attachments)) {
                $body .= '<p style="margin-top:16px">📎 첨부파일 ' . count($attachments) . '개가 이 메일에 포함되어 있습니다.</p>';
            }
            $body .= '<p style="margin-top:20px"><a href="' . $viewUrl . '" style="background:#1A237E;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none">관리자에서 보기</a></p>';
            $body .= '</div>';

            $emailService = new EmailService($this->db);
            if (!empty($attachments)) {
                $result = $emailService->sendWithAttachments($recipients, $subject, $body, $attachments);
            } else {
                $result = $emailService->send($recipients, $subject, $body);
            }

            if (empty($result['success'])) {
                error_log('[BoardEmail] 발송 실패 (' . $this->boardType . ' #' . $boardId . '): ' . ($result['message'] ?? '알 수 없음'));
            }
        } catch (\Throwable $e) {
            // 이메일 실패(Exception/Error 무엇이든)가 게시글 등록을 막지 않도록 로그만 남김
            error_log('[BoardEmail] 예외 (' . $this->boardType . ' #' . $boardId . '): ' . $e->getMessage());
        }
    }
    
    // 카카오톡 알림 발송
    private function sendKakaoNotification($boardId, $data) {
        // 작성 시 회원+사업자 알림 (견적문의, 판매의뢰만). 수신자 결정 로직은 KakaoNotificationService에 중앙화.
        if ($this->boardType === 'quote' || in_array($this->boardType, ['consignment', 'sales_request'])) {
            try {
                require_once __DIR__ . '/../includes/KakaoNotificationService.php';
                $kakaoService = new KakaoNotificationService($this->db);
                $notifyType = ($this->boardType === 'quote') ? 'quote' : 'consignment';
                $kakaoService->notifyBoardCreated($notifyType, $boardId, $data);
            } catch (Exception $e) {
                // 알림 발송 실패시 로그만 남기고 게시글 등록은 계속 진행
                error_log("카카오톡 알림 발송 실패: " . $e->getMessage());
            }
        }
    }
    
    // 게시글 수정
    public function updatePost($id, $data) {
        $updateFields = [
            'title = :title',
            'content = :content',
            'writer = :writer'
        ];
        
        $params = [
            ':id' => $id,
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':writer' => $data['writer']
        ];
        
        // 첨부파일이 있는 경우
        if (isset($data['attachment'])) {
            $updateFields[] = 'attachment = :attachment';
            $params[':attachment'] = $data['attachment'];
        }
        
        // 게시판별 추가 필드
        if ($this->boardType === 'quote') {
            if (isset($data['company'])) {
                $updateFields[] = 'company = :company';
                $params[':company'] = $data['company'];
            }
            if (isset($data['email'])) {
                $updateFields[] = 'email = :email';
                $params[':email'] = $data['email'];
            }
            if (isset($data['phone'])) {
                $updateFields[] = 'phone = :phone';
                $params[':phone'] = $data['phone'];
            }
        } elseif ($this->boardType === 'news' && isset($data['source'])) {
            $updateFields[] = 'source = :source';
            $params[':source'] = $data['source'];
        } elseif (in_array($this->boardType, ['consignment', 'sales_request', 'brokerage'])) {
            if (isset($data['company_name'])) {
                $updateFields[] = 'company_name = :company_name';
                $params[':company_name'] = $data['company_name'];
            }
            if (isset($data['category'])) {
                $updateFields[] = 'category = :category';
                $params[':category'] = $data['category'];
            }
            if (isset($data['stock_quantity'])) {
                $updateFields[] = 'stock_quantity = :stock_quantity';
                $params[':stock_quantity'] = $data['stock_quantity'];
            }
            if (isset($data['price_info'])) {
                $updateFields[] = 'price_info = :price_info';
                $params[':price_info'] = $data['price_info'];
            }
            if (isset($data['contact_person'])) {
                $updateFields[] = 'contact_person = :contact_person';
                $params[':contact_person'] = $data['contact_person'];
            }
            if (isset($data['contact_phone'])) {
                $updateFields[] = 'contact_phone = :contact_phone';
                $params[':contact_phone'] = $data['contact_phone'];
            }
            if (isset($data['contact_email'])) {
                $updateFields[] = 'contact_email = :contact_email';
                $params[':contact_email'] = $data['contact_email'];
            }
            if (isset($data['location'])) {
                $updateFields[] = 'location = :location';
                $params[':location'] = $data['location'];
            }
        }
        
        $sql = "UPDATE {$this->tableName} 
                SET " . implode(', ', $updateFields) . " 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
    
    // 게시글 삭제 (bcrypt 검증 후 삭제)
    public function deletePost($id, $password) {
        if (!$this->checkPassword($id, $password)) {
            return false;
        }
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // 비밀번호 확인 (bcrypt password_verify 사용)
    public function checkPassword($id, $password) {
        $sql = "SELECT password FROM {$this->tableName} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $hash = $stmt->fetchColumn();
        if (!$hash) return false;
        return password_verify($password, $hash);
    }
    
    // Getter 메소드들
    public function getBoardTitle() {
        return $this->boardTitle;
    }
    
    public function allowsUpload() {
        return $this->allowUpload;
    }
    
    public function isSecretBoard() {
        return $this->isSecret;
    }
}
?>