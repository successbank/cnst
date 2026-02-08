<?php
/**
 * BoardPermissionHelper - 게시판 역할별 권한 체크 헬퍼 클래스
 *
 * 사용법:
 *   require_once 'includes/BoardPermissionHelper.php';
 *
 *   // 권한 체크
 *   if (BoardPermissionHelper::canAccess('consignment', 'read')) { ... }
 *
 *   // 권한 없으면 리다이렉트
 *   BoardPermissionHelper::requireAccess('consignment', 'list');
 */

class BoardPermissionHelper {
    private static $pdo = null;
    private static $cache = [];

    private static function getPdo() {
        if (self::$pdo === null) {
            require_once __DIR__ . '/../db.php';
            self::$pdo = getDB();
        }
        return self::$pdo;
    }

    /**
     * 현재 사용자의 역할 코드 반환
     * admin_logged_in === true OR is_admin == 1 → 'admin'
     * member_id 존재 → $_SESSION['member_grade'] ?? 'normal'
     * 그 외 → 'guest'
     */
    public static function getCurrentRole() {
        if (!isset($_SESSION)) {
            session_start();
        }

        // 관리자 세션 체크
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            return 'admin';
        }
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
            return 'admin';
        }

        // 로그인 회원
        if (isset($_SESSION['member_id'])) {
            return $_SESSION['member_grade'] ?? 'normal';
        }

        return 'guest';
    }

    /**
     * 현재 사용자가 특정 게시판·액션에 접근 가능한지 확인
     */
    public static function canAccess($boardType, $action) {
        $role = self::getCurrentRole();

        // 관리자는 항상 허용
        if ($role === 'admin') {
            return true;
        }

        $cacheKey = "{$boardType}:{$role}:{$action}";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        try {
            $pdo = self::getPdo();
            $stmt = $pdo->prepare("
                SELECT is_allowed FROM board_permissions
                WHERE board_type = ? AND role_code = ? AND action_code = ?
                LIMIT 1
            ");
            $stmt->execute([$boardType, $role, $action]);
            $row = $stmt->fetch();

            $allowed = $row ? (bool)$row['is_allowed'] : false;
            self::$cache[$cacheKey] = $allowed;
            return $allowed;
        } catch (PDOException $e) {
            error_log("BoardPermissionHelper::canAccess error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 권한이 없으면 적절한 페이지로 리다이렉트
     */
    public static function requireAccess($boardType, $action) {
        if (self::canAccess($boardType, $action)) {
            return true;
        }

        $role = self::getCurrentRole();

        if ($role === 'guest') {
            // 비회원: 로그인 페이지로
            header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        } else {
            // 회원이지만 권한 부족: 목록 페이지로
            header('Location: /' . $boardType . '.php?error=noaccess');
        }
        exit;
    }

    /**
     * 관리자 UI용 전체 권한 매트릭스 조회
     * @return array ['consignment' => ['guest' => ['list' => 1, 'read' => 0, ...], ...], ...]
     */
    public static function getFullMatrix() {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->query("SELECT board_type, role_code, action_code, is_allowed FROM board_permissions ORDER BY board_type, role_code, action_code");
            $rows = $stmt->fetchAll();

            $matrix = [];
            foreach ($rows as $row) {
                $matrix[$row['board_type']][$row['role_code']][$row['action_code']] = (int)$row['is_allowed'];
            }
            return $matrix;
        } catch (PDOException $e) {
            error_log("BoardPermissionHelper::getFullMatrix error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 관리자가 설정한 매트릭스를 UPSERT로 저장
     * @param array $matrix ['consignment' => ['guest' => ['list' => 1, ...], ...], ...]
     */
    public static function saveMatrix($matrix) {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO board_permissions (board_type, role_code, action_code, is_allowed)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE is_allowed = VALUES(is_allowed), updated_at = CURRENT_TIMESTAMP
            ");

            $pdo->beginTransaction();
            foreach ($matrix as $boardType => $roles) {
                foreach ($roles as $roleCode => $actions) {
                    foreach ($actions as $actionCode => $isAllowed) {
                        $stmt->execute([$boardType, $roleCode, $actionCode, (int)$isAllowed]);
                    }
                }
            }
            $pdo->commit();

            // 캐시 초기화
            self::$cache = [];
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("BoardPermissionHelper::saveMatrix error: " . $e->getMessage());
            return false;
        }
    }

    public static function clearCache() {
        self::$cache = [];
    }
}
