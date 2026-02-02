<?php
/**
 * 로그 서비스 클래스
 * 다양한 시스템 로그 조회 및 통계 기능 제공
 */

class LogService {
    private $pdo;

    // 지원하는 로그 유형별 테이블 매핑
    private $logTables = [
        'login' => [
            'table' => 'member_login_logs',
            'name' => '로그인',
            'dateColumn' => 'login_date',
            'columns' => ['id', 'member_id', 'login_date', 'login_ip', 'user_agent']
        ],
        'member_admin' => [
            'table' => 'member_admin_logs',
            'name' => '회원관리',
            'dateColumn' => 'created_at',
            'columns' => ['id', 'target_member_id', 'admin_id', 'action_type', 'description', 'ip_address', 'created_at']
        ],
        'delete' => [
            'table' => 'product_delete_logs',
            'name' => '삭제',
            'dateColumn' => 'deleted_at',
            'columns' => ['id', 'product_id', 'product_name', 'category_code', 'deleted_by', 'deleted_via', 'deleted_at']
        ],
        'backup' => [
            'table' => 'backup_logs',
            'name' => '백업',
            'dateColumn' => 'created_at',
            'columns' => ['id', 'filename', 'file_size', 'backup_type', 'status', 'tables_count', 'created_at']
        ],
        'email' => [
            'table' => 'email_logs',
            'name' => '이메일',
            'dateColumn' => 'created_at',
            'columns' => ['id', 'recipient', 'subject', 'status', 'created_at', 'sent_at']
        ],
        'calculator' => [
            'table' => 'calculation_logs',
            'name' => '계산기',
            'dateColumn' => 'created_at',
            'columns' => ['id', 'product_id', 'category_code', 'specification', 'material', 'user_ip', 'created_at']
        ]
    ];

    /**
     * 생성자
     * @param PDO $pdo 데이터베이스 연결
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * 로그 유형 목록 가져오기
     * @return array
     */
    public function getLogTypes() {
        $types = [];
        foreach ($this->logTables as $key => $config) {
            $types[$key] = $config['name'];
        }
        return $types;
    }

    /**
     * 로그 테이블 정보 가져오기
     * @param string $logType 로그 유형
     * @return array|null
     */
    public function getLogTableConfig($logType) {
        return $this->logTables[$logType] ?? null;
    }

    /**
     * 로그 목록 조회
     * @param string $logType 로그 유형
     * @param array $filters 필터 옵션 [date_from, date_to, search, page, per_page]
     * @return array ['logs' => array, 'total' => int, 'page' => int, 'total_pages' => int]
     */
    public function getLogs($logType, $filters = []) {
        $config = $this->getLogTableConfig($logType);
        if (!$config) {
            return ['logs' => [], 'total' => 0, 'page' => 1, 'total_pages' => 0];
        }

        $table = $config['table'];
        $dateColumn = $config['dateColumn'];
        $columns = $config['columns'];

        // 페이지네이션 설정
        $page = max(1, intval($filters['page'] ?? 1));
        $perPage = max(10, min(100, intval($filters['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        // JOIN을 사용하는 로그 유형 확인
        $usesJoin = in_array($logType, ['login', 'calculator', 'member_admin']);

        // WHERE 조건 구성 (COUNT용 - 별칭 없음)
        $whereCount = [];
        $whereSelect = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $whereCount[] = "$dateColumn >= ?";
            $whereSelect[] = ($usesJoin ? "l.$dateColumn" : $dateColumn) . " >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $whereCount[] = "$dateColumn <= ?";
            $whereSelect[] = ($usesJoin ? "l.$dateColumn" : $dateColumn) . " <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        // 검색어 처리 (로그 유형별 검색 대상 컬럼)
        if (!empty($filters['search'])) {
            // COUNT용 (별칭 없음)
            $searchConditionsCount = $this->getSearchConditions($logType, $filters['search'], false);
            // SELECT용 (JOIN 시 별칭 사용)
            $searchConditionsSelect = $this->getSearchConditions($logType, $filters['search'], $usesJoin);
            if ($searchConditionsCount) {
                $whereCount[] = $searchConditionsCount['condition'];
                $whereSelect[] = $searchConditionsSelect['condition'];
                $params = array_merge($params, $searchConditionsCount['params']);
            }
        }

        $whereClauseCount = !empty($whereCount) ? 'WHERE ' . implode(' AND ', $whereCount) : '';
        $whereClauseSelect = !empty($whereSelect) ? 'WHERE ' . implode(' AND ', $whereSelect) : '';

        // 전체 개수 조회 (별칭 없이)
        $countSql = "SELECT COUNT(*) FROM $table $whereClauseCount";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // 데이터 조회 (JOIN 포함, 별칭 사용)
        $selectSql = $this->buildSelectQuery($logType, $config, $whereClauseSelect, $dateColumn, $perPage, $offset);
        $stmt = $this->pdo->prepare($selectSql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 후처리 (로그 유형별)
        $logs = $this->postProcessLogs($logType, $logs);

        return [
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $perPage),
            'per_page' => $perPage
        ];
    }

    /**
     * SELECT 쿼리 구성 (JOIN 처리)
     */
    private function buildSelectQuery($logType, $config, $whereClause, $dateColumn, $perPage, $offset) {
        $table = $config['table'];

        switch ($logType) {
            case 'login':
                return "SELECT l.*, m.name as member_name, m.user_id as member_user_id
                        FROM $table l
                        LEFT JOIN members m ON l.member_id = m.id
                        $whereClause
                        ORDER BY l.$dateColumn DESC
                        LIMIT $perPage OFFSET $offset";

            case 'member_admin':
                return "SELECT l.*,
                        tm.name as target_name, tm.user_id as target_user_id,
                        am.name as admin_name, am.user_id as admin_user_id
                        FROM $table l
                        LEFT JOIN members tm ON l.target_member_id = tm.id
                        LEFT JOIN members am ON l.admin_id = am.id
                        $whereClause
                        ORDER BY l.$dateColumn DESC
                        LIMIT $perPage OFFSET $offset";

            case 'calculator':
                return "SELECT l.*, p.product_name as product_name
                        FROM $table l
                        LEFT JOIN products p ON l.product_id = p.id
                        $whereClause
                        ORDER BY l.$dateColumn DESC
                        LIMIT $perPage OFFSET $offset";

            default:
                $columns = implode(', ', $config['columns']);
                return "SELECT $columns FROM $table $whereClause ORDER BY $dateColumn DESC LIMIT $perPage OFFSET $offset";
        }
    }

    /**
     * 검색 조건 구성
     * @param bool $withAlias JOIN 쿼리용 별칭 사용 여부
     */
    private function getSearchConditions($logType, $search, $withAlias = false) {
        $search = '%' . $search . '%';
        $alias = $withAlias ? 'l.' : '';

        switch ($logType) {
            case 'login':
                return [
                    'condition' => "({$alias}login_ip LIKE ? OR {$alias}user_agent LIKE ?)",
                    'params' => [$search, $search]
                ];
            case 'member_admin':
                return [
                    'condition' => "({$alias}description LIKE ? OR {$alias}action_type LIKE ? OR {$alias}ip_address LIKE ?)",
                    'params' => [$search, $search, $search]
                ];
            case 'delete':
                return [
                    'condition' => '(product_name LIKE ? OR deleted_by LIKE ?)',
                    'params' => [$search, $search]
                ];
            case 'backup':
                return [
                    'condition' => 'filename LIKE ?',
                    'params' => [$search]
                ];
            case 'email':
                return [
                    'condition' => '(recipient LIKE ? OR subject LIKE ?)',
                    'params' => [$search, $search]
                ];
            case 'calculator':
                return [
                    'condition' => "({$alias}specification LIKE ? OR {$alias}material LIKE ? OR {$alias}user_ip LIKE ?)",
                    'params' => [$search, $search, $search]
                ];
            default:
                return null;
        }
    }

    /**
     * 로그 데이터 후처리
     */
    private function postProcessLogs($logType, $logs) {
        foreach ($logs as &$log) {
            switch ($logType) {
                case 'backup':
                    $log['file_size_formatted'] = $this->formatFileSize($log['file_size'] ?? 0);
                    $log['status_label'] = $this->getStatusLabel($log['status'] ?? '');
                    break;
                case 'email':
                    $log['status_label'] = $this->getEmailStatusLabel($log['status'] ?? '');
                    break;
                case 'member_admin':
                    $log['action_label'] = $this->getMemberAdminActionLabel($log['action_type'] ?? '');
                    break;
            }
        }
        return $logs;
    }

    /**
     * 회원관리 액션 타입 레이블
     */
    private function getMemberAdminActionLabel($actionType) {
        $labels = [
            'view' => '정보 조회',
            'update' => '정보 수정',
            'password_change' => '비밀번호 변경',
            'status_change' => '상태 변경',
            'grade_change' => '등급 변경',
            'delete' => '회원 삭제',
            'memo_add' => '메모 추가'
        ];
        return $labels[$actionType] ?? $actionType;
    }

    /**
     * 통계 정보 조회
     * @param string $period 기간 (daily, weekly, monthly)
     * @return array
     */
    public function getStats($period = 'daily') {
        $stats = [
            'total_logs' => 0,
            'today_logs' => 0,
            'cleanup_target' => 0,
            'by_type' => [],
            'storage_usage' => 0
        ];

        $retentionDays = (int) $this->getSetting('log_retention_days', 90);
        $thresholdDate = date('Y-m-d', strtotime("-{$retentionDays} days"));
        $today = date('Y-m-d');

        foreach ($this->logTables as $key => $config) {
            $table = $config['table'];
            $dateColumn = $config['dateColumn'];

            try {
                // 전체 개수
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table");
                $total = (int) $stmt->fetchColumn();
                $stats['by_type'][$key] = [
                    'name' => $config['name'],
                    'total' => $total
                ];
                $stats['total_logs'] += $total;

                // 오늘 생성 개수
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table WHERE DATE($dateColumn) = ?");
                $stmt->execute([$today]);
                $todayCount = (int) $stmt->fetchColumn();
                $stats['by_type'][$key]['today'] = $todayCount;
                $stats['today_logs'] += $todayCount;

                // 정리 대상 (보관 기간 초과)
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table WHERE DATE($dateColumn) < ?");
                $stmt->execute([$thresholdDate]);
                $cleanupCount = (int) $stmt->fetchColumn();
                $stats['by_type'][$key]['cleanup_target'] = $cleanupCount;
                $stats['cleanup_target'] += $cleanupCount;
            } catch (PDOException $e) {
                // 테이블 없으면 스킵
                continue;
            }
        }

        // 스토리지 사용량 계산 (근사치)
        $stats['storage_usage'] = $this->estimateStorageUsage();
        $stats['storage_formatted'] = $this->formatFileSize($stats['storage_usage']);

        return $stats;
    }

    /**
     * 스토리지 사용량 추정 (근사치)
     * @return int bytes
     */
    private function estimateStorageUsage() {
        $totalSize = 0;

        try {
            $sql = "SELECT
                        SUM(data_length + index_length) as total_size
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                    AND table_name IN ('member_login_logs', 'member_admin_logs', 'backup_logs', 'email_logs', 'product_delete_logs', 'calculation_logs')";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalSize = (int) ($result['total_size'] ?? 0);
        } catch (PDOException $e) {
            // 오류 시 0 반환
        }

        return $totalSize;
    }

    /**
     * 로그 정리 이력 조회
     * @param int $limit
     * @return array
     */
    public function getCleanupHistory($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM log_cleanup_history
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * 파일 크기 포맷
     */
    private function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * 백업 상태 레이블
     */
    private function getStatusLabel($status) {
        $labels = [
            'success' => '성공',
            'failed' => '실패',
            'in_progress' => '진행중'
        ];
        return $labels[$status] ?? $status;
    }

    /**
     * 이메일 상태 레이블
     */
    private function getEmailStatusLabel($status) {
        $labels = [
            'pending' => '대기',
            'sent' => '발송완료',
            'failed' => '실패'
        ];
        return $labels[$status] ?? $status;
    }

    /**
     * 설정 값 가져오기 (헬퍼)
     */
    private function getSetting($key, $default = '') {
        try {
            $stmt = $this->pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            return $result ? $result['setting_value'] : $default;
        } catch (PDOException $e) {
            return $default;
        }
    }
}
