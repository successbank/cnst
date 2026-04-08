<?php
/**
 * 로그 정리 서비스 클래스
 * 오래된 로그 데이터 정리 및 관리
 */

class LogCleanupService {
    private $pdo;

    // 정리 가능한 로그 테이블 목록
    private $cleanableTables = [
        'member_login_logs' => [
            'name' => '로그인 로그',
            'dateColumn' => 'login_date'
        ],
        'calculation_logs' => [
            'name' => '계산기 로그',
            'dateColumn' => 'created_at'
        ],
        'email_logs' => [
            'name' => '이메일 로그',
            'dateColumn' => 'created_at'
        ],
        'backup_logs' => [
            'name' => '백업 로그',
            'dateColumn' => 'created_at'
        ],
        'product_delete_logs' => [
            'name' => '삭제 로그',
            'dateColumn' => 'deleted_at'
        ],
        'site_visits' => [
            'name' => '방문자 기록',
            'dateColumn' => 'created_at',
            'retention_days' => 7
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
     * 정리 가능한 테이블 목록 가져오기
     * @return array
     */
    public function getCleanableTables() {
        return $this->cleanableTables;
    }

    /**
     * 수동 로그 정리 실행
     * @param array $targets 정리 대상 테이블 배열
     * @param int $daysBefore 며칠 이전 데이터 삭제할지
     * @param string|null $executedBy 실행자 정보
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public function manualCleanup($targets, $daysBefore, $executedBy = null) {
        $results = [];
        $totalDeleted = 0;
        $errors = [];

        $thresholdDate = date('Y-m-d', strtotime("-{$daysBefore} days"));

        $this->pdo->beginTransaction();

        try {
            foreach ($targets as $table) {
                if (!isset($this->cleanableTables[$table])) {
                    continue;
                }

                $config = $this->cleanableTables[$table];
                $dateColumn = $config['dateColumn'];

                // per-table retention 오버라이드
                $tableThreshold = $thresholdDate;
                if (isset($config['retention_days'])) {
                    $tableDays = $config['retention_days'];
                    $tableThreshold = date('Y-m-d', strtotime("-{$tableDays} days"));
                }

                // 삭제 대상 개수 조회
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table WHERE DATE($dateColumn) < ?");
                $stmt->execute([$tableThreshold]);
                $count = (int) $stmt->fetchColumn();

                if ($count > 0) {
                    // 삭제 실행
                    $stmt = $this->pdo->prepare("DELETE FROM $table WHERE DATE($dateColumn) < ?");
                    $stmt->execute([$tableThreshold]);
                    $deleted = $stmt->rowCount();
                    $results[$table] = $deleted;
                    $totalDeleted += $deleted;
                } else {
                    $results[$table] = 0;
                }
            }

            // 정리 이력 기록
            $this->logCleanupHistory(
                'manual',
                implode(',', $targets),
                $totalDeleted,
                $thresholdDate,
                $executedBy,
                'success'
            );

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => "총 {$totalDeleted}건의 로그가 정리되었습니다.",
                'details' => $results,
                'threshold_date' => $thresholdDate
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();

            // 실패 이력 기록
            $this->logCleanupHistory(
                'manual',
                implode(',', $targets),
                0,
                $thresholdDate,
                $executedBy,
                'failed',
                $e->getMessage()
            );

            return [
                'success' => false,
                'message' => '로그 정리 중 오류가 발생했습니다: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * 자동 로그 정리 실행 (CRON용)
     * @return array
     */
    public function autoCleanup() {
        // 자동 정리 활성화 여부 확인
        if (!$this->isAutoCleanupEnabled()) {
            return [
                'success' => false,
                'message' => '자동 정리가 비활성화되어 있습니다.',
                'details' => []
            ];
        }

        $retentionDays = (int) $this->getSetting('log_retention_days', 90);
        $targets = $this->getCleanupTargets();

        if (empty($targets)) {
            return [
                'success' => true,
                'message' => '정리 대상 테이블이 설정되지 않았습니다.',
                'details' => []
            ];
        }

        return $this->manualCleanup($targets, $retentionDays, 'CRON');
    }

    /**
     * 정리 대상별 삭제 예정 건수 조회
     * @param int $daysBefore 기준 일수
     * @return array
     */
    public function getCleanupPreview($daysBefore) {
        $thresholdDate = date('Y-m-d', strtotime("-{$daysBefore} days"));
        $preview = [];

        foreach ($this->cleanableTables as $table => $config) {
            try {
                $dateColumn = $config['dateColumn'];
                $tableThreshold = $thresholdDate;
                if (isset($config['retention_days'])) {
                    $tableDays = $config['retention_days'];
                    $tableThreshold = date('Y-m-d', strtotime("-{$tableDays} days"));
                }
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM $table WHERE DATE($dateColumn) < ?");
                $stmt->execute([$tableThreshold]);
                $count = (int) $stmt->fetchColumn();

                $preview[$table] = [
                    'name' => $config['name'],
                    'count' => $count
                ];
            } catch (PDOException $e) {
                $preview[$table] = [
                    'name' => $config['name'],
                    'count' => 0,
                    'error' => true
                ];
            }
        }

        return [
            'threshold_date' => $thresholdDate,
            'days_before' => $daysBefore,
            'tables' => $preview
        ];
    }

    /**
     * 자동 정리 활성화 여부
     * @return bool
     */
    public function isAutoCleanupEnabled() {
        return $this->getSetting('log_auto_cleanup_enabled', '0') === '1';
    }

    /**
     * 정리 대상 테이블 목록 가져오기
     * @return array
     */
    public function getCleanupTargets() {
        $targetsJson = $this->getSetting('log_cleanup_targets', '[]');
        $targets = json_decode($targetsJson, true);
        return is_array($targets) ? $targets : [];
    }

    /**
     * 정리 이력 기록
     */
    private function logCleanupHistory($type, $targets, $deletedCount, $thresholdDate, $executedBy, $status, $errorMessage = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO log_cleanup_history
                (cleanup_type, target_logs, deleted_count, date_threshold, executed_by, status, error_message)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $type,
                $targets,
                $deletedCount,
                $thresholdDate,
                $executedBy,
                $status,
                $errorMessage
            ]);
        } catch (PDOException $e) {
            // 이력 기록 실패는 무시
            error_log("Failed to log cleanup history: " . $e->getMessage());
        }
    }

    /**
     * 정리 이력 조회
     * @param int $limit
     * @return array
     */
    public function getCleanupHistory($limit = 20) {
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
     * 설정 저장
     * @param array $settings [key => value]
     * @return bool
     */
    public function saveSettings($settings) {
        try {
            foreach ($settings as $key => $value) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO site_settings (setting_key, setting_value, setting_type)
                    VALUES (?, ?, 'log')
                    ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP
                ");
                $stmt->execute([$key, $value, $value]);
            }
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * 설정 값 가져오기
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

    /**
     * 모든 로그 설정 가져오기
     * @return array
     */
    public function getAllSettings() {
        return [
            'log_retention_days' => (int) $this->getSetting('log_retention_days', 90),
            'log_auto_cleanup_enabled' => $this->getSetting('log_auto_cleanup_enabled', '0') === '1',
            'log_cleanup_schedule_time' => $this->getSetting('log_cleanup_schedule_time', '02:00'),
            'log_cleanup_targets' => $this->getCleanupTargets()
        ];
    }
}
