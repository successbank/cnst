<?php
/**
 * 회원 관리 로그 서비스 클래스
 * 관리자의 회원 관리 작업(조회/수정/삭제 등)을 기록하고 조회하는 기능 제공
 */

class MemberAdminLogService {
    private $pdo;

    // 액션 타입 레이블
    private static $actionLabels = [
        'view' => '정보 조회',
        'update' => '정보 수정',
        'password_change' => '비밀번호 변경',
        'status_change' => '상태 변경',
        'grade_change' => '등급 변경',
        'delete' => '회원 삭제',
        'memo_add' => '메모 추가'
    ];

    // 액션 타입별 배지 색상
    private static $actionColors = [
        'view' => '#607D8B',       // 회색
        'update' => '#2196F3',     // 파랑
        'password_change' => '#FF9800', // 주황
        'status_change' => '#9C27B0',   // 보라
        'grade_change' => '#4CAF50',    // 초록
        'delete' => '#F44336',     // 빨강
        'memo_add' => '#795548'    // 갈색
    ];

    /**
     * 생성자
     * @param PDO $pdo 데이터베이스 연결
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * 관리 작업 로그 기록
     * 
     * @param int $targetMemberId 대상 회원 ID
     * @param int $adminId 작업 관리자 ID
     * @param string $actionType 작업 유형
     * @param array|null $oldValue 변경 전 값
     * @param array|null $newValue 변경 후 값
     * @param string|null $description 변경 요약
     * @return bool 성공 여부
     */
    public function logAction($targetMemberId, $adminId, $actionType, $oldValue = null, $newValue = null, $description = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO member_admin_logs 
                (target_member_id, admin_id, action_type, old_value, new_value, ip_address, user_agent, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $targetMemberId,
                $adminId,
                $actionType,
                $oldValue ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null,
                $newValue ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null,
                $this->getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $description
            ]);
        } catch (PDOException $e) {
            error_log('MemberAdminLogService::logAction error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 회원별 관리 로그 조회
     * 
     * @param int $memberId 회원 ID
     * @param int $limit 조회 개수
     * @return array
     */
    public function getLogsByMember($memberId, $limit = 20) {
        try {
            $limit = (int)$limit;
            $stmt = $this->pdo->prepare("
                SELECT
                    l.*,
                    m.name AS admin_name,
                    m.user_id AS admin_user_id
                FROM member_admin_logs l
                LEFT JOIN members m ON l.admin_id = m.id
                WHERE l.target_member_id = ?
                ORDER BY l.created_at DESC
                LIMIT {$limit}
            ");
            $stmt->execute([$memberId]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->formatLogs($logs);
        } catch (PDOException $e) {
            error_log('MemberAdminLogService::getLogsByMember error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 전체 관리 로그 조회 (페이지네이션)
     * 
     * @param array $filters 필터 옵션 [date_from, date_to, action_type, search, page, per_page]
     * @return array ['logs' => array, 'total' => int, 'page' => int, 'total_pages' => int]
     */
    public function getAllLogs($filters = []) {
        try {
            // 페이지네이션 설정
            $page = max(1, intval($filters['page'] ?? 1));
            $perPage = max(10, min(100, intval($filters['per_page'] ?? 20)));
            $offset = ($page - 1) * $perPage;

            // WHERE 조건 구성
            $where = [];
            $params = [];

            if (!empty($filters['date_from'])) {
                $where[] = "l.created_at >= ?";
                $params[] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $where[] = "l.created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }

            if (!empty($filters['action_type'])) {
                $where[] = "l.action_type = ?";
                $params[] = $filters['action_type'];
            }

            if (!empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $where[] = "(l.description LIKE ? OR tm.name LIKE ? OR tm.user_id LIKE ? OR am.name LIKE ?)";
                $params = array_merge($params, [$search, $search, $search, $search]);
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            // 전체 개수 조회
            $countSql = "
                SELECT COUNT(*) FROM member_admin_logs l
                LEFT JOIN members tm ON l.target_member_id = tm.id
                LEFT JOIN members am ON l.admin_id = am.id
                $whereClause
            ";
            $stmt = $this->pdo->prepare($countSql);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            // 데이터 조회
            $selectSql = "
                SELECT 
                    l.*,
                    tm.name AS target_name,
                    tm.user_id AS target_user_id,
                    am.name AS admin_name,
                    am.user_id AS admin_user_id
                FROM member_admin_logs l
                LEFT JOIN members tm ON l.target_member_id = tm.id
                LEFT JOIN members am ON l.admin_id = am.id
                $whereClause
                ORDER BY l.created_at DESC
                LIMIT $perPage OFFSET $offset
            ";
            $stmt = $this->pdo->prepare($selectSql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'logs' => $this->formatLogs($logs),
                'total' => $total,
                'page' => $page,
                'total_pages' => ceil($total / $perPage),
                'per_page' => $perPage
            ];
        } catch (PDOException $e) {
            error_log('MemberAdminLogService::getAllLogs error: ' . $e->getMessage());
            return ['logs' => [], 'total' => 0, 'page' => 1, 'total_pages' => 0, 'per_page' => 20];
        }
    }

    /**
     * 로그 데이터 포맷팅
     */
    private function formatLogs($logs) {
        foreach ($logs as &$log) {
            // JSON 필드 디코딩
            $log['old_value_decoded'] = $log['old_value'] ? json_decode($log['old_value'], true) : null;
            $log['new_value_decoded'] = $log['new_value'] ? json_decode($log['new_value'], true) : null;
            
            // 레이블 및 색상 추가
            $log['action_label'] = self::getActionTypeLabel($log['action_type']);
            $log['action_color'] = self::getActionColor($log['action_type']);
            
            // 날짜 포맷
            $log['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($log['created_at']));
        }
        return $logs;
    }

    /**
     * 액션 타입 레이블 반환
     */
    public static function getActionTypeLabel($actionType) {
        return self::$actionLabels[$actionType] ?? $actionType;
    }

    /**
     * 액션 타입 색상 반환
     */
    public static function getActionColor($actionType) {
        return self::$actionColors[$actionType] ?? '#666666';
    }

    /**
     * 모든 액션 타입 목록 반환
     */
    public static function getActionTypes() {
        return self::$actionLabels;
    }

    /**
     * 클라이언트 IP 주소 가져오기
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // 쉼표로 구분된 경우 첫 번째 IP 사용
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    /**
     * 변경사항 비교하여 diff 생성
     * 
     * @param array $oldData 변경 전 데이터
     * @param array $newData 변경 후 데이터
     * @param array $fields 비교할 필드 목록
     * @return array ['old' => array, 'new' => array, 'changed' => array]
     */
    public static function compareData($oldData, $newData, $fields) {
        $old = [];
        $new = [];
        $changed = [];
        
        foreach ($fields as $field => $label) {
            $oldVal = $oldData[$field] ?? null;
            $newVal = $newData[$field] ?? null;
            
            if ($oldVal !== $newVal) {
                $old[$field] = $oldVal;
                $new[$field] = $newVal;
                $changed[] = $label;
            }
        }
        
        return [
            'old' => $old,
            'new' => $new,
            'changed' => $changed
        ];
    }

    /**
     * 변경 설명 자동 생성
     */
    public static function generateDescription($actionType, $changedFields = []) {
        $label = self::getActionTypeLabel($actionType);
        
        if ($actionType === 'update' && !empty($changedFields)) {
            return $label . ': ' . implode(', ', $changedFields);
        }
        
        return $label;
    }
}
