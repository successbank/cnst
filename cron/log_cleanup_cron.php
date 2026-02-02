#!/usr/bin/env php
<?php
/**
 * 로그 자동 정리 CRON 스크립트
 *
 * 사용법 (crontab 설정):
 * # 매일 새벽 2시에 실행
 * 0 2 * * * /usr/bin/php /var/www/html/cron/log_cleanup_cron.php >> /var/log/log_cleanup.log 2>&1
 *
 * Docker 환경에서는 호스트에서 실행:
 * 0 2 * * * docker exec project1_php php /var/www/html/cron/log_cleanup_cron.php >> /home/cnst/www/html/webservice/logs/log_cleanup.log 2>&1
 */

// CLI에서만 실행 허용
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// 타임존 설정
date_default_timezone_set('Asia/Seoul');

echo "[" . date('Y-m-d H:i:s') . "] 로그 자동 정리 시작\n";

// 경로 설정 (Docker 내부 경로)
$basePath = dirname(__DIR__);
require_once $basePath . '/db.php';
require_once $basePath . '/includes/LogCleanupService.php';

try {
    $pdo = getDB();
    $cleanupService = new LogCleanupService($pdo);

    // 자동 정리 활성화 여부 확인
    if (!$cleanupService->isAutoCleanupEnabled()) {
        echo "[" . date('Y-m-d H:i:s') . "] 자동 정리가 비활성화되어 있습니다. 종료.\n";
        exit(0);
    }

    // 정리 실행
    $result = $cleanupService->autoCleanup();

    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] 정리 완료: " . $result['message'] . "\n";

        if (!empty($result['details'])) {
            echo "상세 내역:\n";
            foreach ($result['details'] as $table => $count) {
                echo "  - {$table}: {$count}건 삭제\n";
            }
        }
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] 정리 실패: " . $result['message'] . "\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] 오류 발생: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] 로그 자동 정리 종료\n";
exit(0);
