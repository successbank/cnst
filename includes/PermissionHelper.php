<?php
/**
 * PermissionHelper - 회원 등급별 권한 체크 헬퍼 클래스
 * 
 * 사용법:
 *   require_once 'includes/PermissionHelper.php';
 *   
 *   // 권한 체크
 *   if (PermissionHelper::hasPermission($memberId, 'view_stock')) {
 *       // 재고 확인 가능
 *   }
 *   
 *   // 등급별 할인율
 *   $discount = PermissionHelper::getGradeDiscount('gold'); // 5.00
 */

class PermissionHelper {
    private static $pdo = null;
    private static $permissionCache = [];
    private static $gradeSettingsCache = null;
    
    /**
     * PDO 인스턴스 설정
     */
    public static function setPdo($pdo) {
        self::$pdo = $pdo;
    }
    
    /**
     * PDO 인스턴스 가져오기
     */
    private static function getPdo() {
        if (self::$pdo === null) {
            require_once __DIR__ . '/../db.php';
            self::$pdo = getDB();
        }
        return self::$pdo;
    }
    
    /**
     * 회원이 특정 권한을 가지고 있는지 확인
     * 
     * @param int $memberId 회원 ID
     * @param string $permissionCode 권한 코드 (예: 'view_stock', 'view_special_price')
     * @return bool 권한 보유 여부
     */
    public static function hasPermission($memberId, $permissionCode) {
        $pdo = self::getPdo();
        
        // 캐시 확인
        $cacheKey = $memberId . ':' . $permissionCode;
        if (isset(self::$permissionCache[$cacheKey])) {
            return self::$permissionCache[$cacheKey];
        }
        
        try {
            // 회원의 등급 조회
            $stmt = $pdo->prepare("SELECT member_grade, is_admin FROM members WHERE id = ?");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();
            
            if (!$member) {
                self::$permissionCache[$cacheKey] = false;
                return false;
            }
            
            // 관리자는 모든 권한 보유
            if ($member['is_admin'] == 1) {
                self::$permissionCache[$cacheKey] = true;
                return true;
            }
            
            $gradeCode = $member['member_grade'] ?? 'normal';
            
            // 등급별 권한 확인
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM grade_permissions 
                WHERE grade_code = ? AND permission_code = ?
            ");
            $stmt->execute([$gradeCode, $permissionCode]);
            $result = $stmt->fetch();
            
            $hasPermission = $result['cnt'] > 0;
            self::$permissionCache[$cacheKey] = $hasPermission;
            
            return $hasPermission;
            
        } catch (PDOException $e) {
            error_log("PermissionHelper::hasPermission error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 세션 기반 권한 체크 (로그인된 사용자)
     * 
     * @param string $permissionCode 권한 코드
     * @return bool 권한 보유 여부
     */
    public static function currentUserHasPermission($permissionCode) {
        if (!isset($_SESSION['member_id'])) {
            return false;
        }
        return self::hasPermission($_SESSION['member_id'], $permissionCode);
    }
    
    /**
     * 등급이 특정 권한을 가지고 있는지 확인
     * 
     * @param string $gradeCode 등급 코드 (normal, silver, gold, vip)
     * @param string $permissionCode 권한 코드
     * @return bool 권한 보유 여부
     */
    public static function gradeHasPermission($gradeCode, $permissionCode) {
        $pdo = self::getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM grade_permissions 
                WHERE grade_code = ? AND permission_code = ?
            ");
            $stmt->execute([$gradeCode, $permissionCode]);
            $result = $stmt->fetch();
            
            return $result['cnt'] > 0;
            
        } catch (PDOException $e) {
            error_log("PermissionHelper::gradeHasPermission error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 등급의 모든 권한 목록 가져오기
     * 
     * @param string $gradeCode 등급 코드
     * @return array 권한 코드 배열
     */
    public static function getGradePermissions($gradeCode) {
        $pdo = self::getPdo();
        
        try {
            $stmt = $pdo->prepare("
                SELECT permission_code 
                FROM grade_permissions 
                WHERE grade_code = ?
            ");
            $stmt->execute([$gradeCode]);
            $results = $stmt->fetchAll();
            
            return array_column($results, 'permission_code');
            
        } catch (PDOException $e) {
            error_log("PermissionHelper::getGradePermissions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 등급별 할인율 가져오기
     * 
     * @param string $gradeCode 등급 코드
     * @return float 할인율 (%)
     */
    public static function getGradeDiscount($gradeCode) {
        $settings = self::getGradeSettings();
        
        foreach ($settings as $grade) {
            if ($grade['grade_code'] === $gradeCode) {
                return (float)$grade['discount_rate'];
            }
        }
        
        return 0.0;
    }
    
    /**
     * 회원 ID로 할인율 가져오기
     * 
     * @param int $memberId 회원 ID
     * @return float 할인율 (%)
     */
    public static function getMemberDiscount($memberId) {
        $pdo = self::getPdo();
        
        try {
            $stmt = $pdo->prepare("SELECT member_grade FROM members WHERE id = ?");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();
            
            if (!$member) {
                return 0.0;
            }
            
            return self::getGradeDiscount($member['member_grade'] ?? 'normal');
            
        } catch (PDOException $e) {
            error_log("PermissionHelper::getMemberDiscount error: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * 모든 등급 설정 가져오기
     * 
     * @return array 등급 설정 배열
     */
    public static function getGradeSettings() {
        if (self::$gradeSettingsCache !== null) {
            return self::$gradeSettingsCache;
        }
        
        $pdo = self::getPdo();
        
        try {
            $stmt = $pdo->query("
                SELECT * FROM member_grade_settings 
                WHERE is_active = 1 
                ORDER BY sort_order ASC
            ");
            self::$gradeSettingsCache = $stmt->fetchAll();
            return self::$gradeSettingsCache;
            
        } catch (PDOException $e) {
            error_log("PermissionHelper::getGradeSettings error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 등급명 가져오기
     * 
     * @param string $gradeCode 등급 코드
     * @return string 등급명
     */
    public static function getGradeName($gradeCode) {
        $settings = self::getGradeSettings();
        
        foreach ($settings as $grade) {
            if ($grade['grade_code'] === $gradeCode) {
                return $grade['grade_name'];
            }
        }
        
        return '일반';
    }
    
    /**
     * 할인가 계산
     * 
     * @param float $originalPrice 원래 가격
     * @param int $memberId 회원 ID
     * @return float 할인된 가격
     */
    public static function calculateDiscountedPrice($originalPrice, $memberId) {
        $discountRate = self::getMemberDiscount($memberId);
        
        if ($discountRate <= 0) {
            return $originalPrice;
        }
        
        return $originalPrice * (1 - $discountRate / 100);
    }
    
    /**
     * 캐시 초기화
     */
    public static function clearCache() {
        self::$permissionCache = [];
        self::$gradeSettingsCache = null;
    }
}
