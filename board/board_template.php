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
            default:
                throw new Exception("Invalid board type");
        }
    }
    
    // 게시글 목록 조회
    public function getList($page = 1, $perPage = 10, $search = '', $category = '') {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        
        if ($search) {
            $searchPattern = '%' . $search . '%';
            if ($this->boardType === 'consignment') {
                $where[] = "(title LIKE :search OR content LIKE :search OR company_name LIKE :search)";
            } else {
                $where[] = "(title LIKE :search OR content LIKE :search)";
            }
            $params[':search'] = $searchPattern;
        }
        
        if ($category && $this->boardType === 'consignment') {
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
        if (!empty($data['password'])) {
            $fields[] = 'password';
            $values[] = ':password';
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        // member_id 처리 - 로그인한 경우에만 저장
        if (isset($data['member_id']) && $data['member_id']) {
            $fields[] = 'member_id';
            $values[] = ':member_id';
            $params[':member_id'] = $data['member_id'];
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
        } elseif ($this->boardType === 'consignment') {
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
        
        // 성공적으로 등록된 경우 카카오톡 알림 발송
        if ($result) {
            $insertId = $this->db->lastInsertId();
            $this->sendKakaoNotification($insertId, $data);
        }
        
        return $result;
    }
    
    // 카카오톡 알림 발송
    private function sendKakaoNotification($boardId, $data) {
        // 관리자에게 알림 발송 (견적문의, 위탁판매만)
        if ($this->boardType === 'quote' || $this->boardType === 'consignment') {
            try {
                require_once __DIR__ . '/../includes/KakaoNotificationService.php';
                $kakaoService = new KakaoNotificationService($this->db);
                
                // 관리자 정보 조회
                $adminStmt = $this->db->prepare("SELECT phone FROM members WHERE is_admin = 1 AND phone IS NOT NULL LIMIT 1");
                $adminStmt->execute();
                $admin = $adminStmt->fetch();
                
                if ($admin && $admin['phone']) {
                    $adminPhone = $admin['phone'];
                    $adminName = '관리자';
                    
                    if ($this->boardType === 'quote') {
                        // 견적문의 알림
                        $templateData = [
                            'title' => $data['title'],
                            'writer' => $data['writer'],
                            'company' => $data['company'] ?? ''
                        ];
                        $kakaoService->sendNotification('quote', $boardId, $adminPhone, $adminName, 'QUOTE_NEW', $templateData);
                        
                        // 작성자에게도 알림 발송 (접수 확인)
                        if (!empty($data['phone'])) {
                            $kakaoService->sendNotification('quote', $boardId, $data['phone'], $data['writer'], 'QUOTE_RECEIVED', [
                                'title' => $data['title']
                            ]);
                        }
                    } elseif ($this->boardType === 'consignment') {
                        // 위탁판매 알림
                        $templateData = [
                            'title' => $data['title'],
                            'company_name' => $data['company_name'] ?? '',
                            'category' => $data['category'] ?? ''
                        ];
                        $kakaoService->sendNotification('consignment', $boardId, $adminPhone, $adminName, 'CONSIGN_NEW', $templateData);
                        
                        // 작성자에게도 알림 발송 (등록 확인)
                        if (!empty($data['contact_phone'])) {
                            $kakaoService->sendNotification('consignment', $boardId, $data['contact_phone'], $data['contact_person'] ?? $data['writer'], 'CONSIGN_RECEIVED', [
                                'title' => $data['title']
                            ]);
                        }
                    }
                }
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
        } elseif ($this->boardType === 'consignment') {
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