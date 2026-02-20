<?php
// 카카오 API 설정 - DB에서 로드
// RCE 방지: 설정값을 PHP 파일에 직접 쓰지 않고 DB에서 조회

if (!function_exists('getKakaoSettings')) {
    function getKakaoSettings() {
        static $settings = null;
        if ($settings !== null) return $settings;

        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_group = 'kakao'");
            $stmt->execute();
            $rows = $stmt->fetchAll();

            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
        } catch (Exception $e) {
            error_log("Failed to load kakao settings: " . $e->getMessage());
            return [];
        }
    }
}

$kakaoSettings = getKakaoSettings();

// define 상수로 유지 (기존 코드 호환성)
if (!defined('KAKAO_API_KEY')) {
    define('KAKAO_API_KEY', $kakaoSettings['kakao_api_key'] ?? '');
    define('KAKAO_API_SECRET', $kakaoSettings['kakao_api_secret'] ?? '');
    define('KAKAO_SENDER_KEY', $kakaoSettings['kakao_sender_key'] ?? '');
    define('KAKAO_CHANNEL_ID', $kakaoSettings['kakao_channel_id'] ?? '@chungnamsteel');
    define('KAKAO_MAP_API_KEY', $kakaoSettings['kakao_map_api_key'] ?? '');
    define('KAKAO_API_URL', $kakaoSettings['kakao_api_url'] ?? 'https://api-alimtalk.cloud.toast.com/alimtalk/v2.2');
    define('KAKAO_NOTIFICATION_ENABLED', ($kakaoSettings['kakao_notification_enabled'] ?? '0') === '1');
    define('KAKAO_TEST_MODE', ($kakaoSettings['kakao_test_mode'] ?? '1') === '1');
    define('KAKAO_TEST_PHONE', $kakaoSettings['kakao_test_phone'] ?? '');
    define('KAKAO_DAILY_LIMIT', (int)($kakaoSettings['kakao_daily_limit'] ?? 1000));
    define('KAKAO_RETRY_COUNT', (int)($kakaoSettings['kakao_retry_count'] ?? 3));
}
?>
