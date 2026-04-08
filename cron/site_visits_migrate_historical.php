#!/usr/bin/env php
<?php
/**
 * site_visits 기존 데이터 마이그레이션 (일회성 실행)
 * 7일 이전 원본 데이터를 집계 테이블로 이관 후 원본 삭제
 *
 * 실행:
 * sudo docker exec project1_php php /var/www/html/cron/site_visits_migrate_historical.php
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only.');
}

date_default_timezone_set('Asia/Seoul');
putenv('WAF_DISABLED=1');

echo "[" . date('Y-m-d H:i:s') . "] site_visits 마이그레이션 시작\n";

$basePath = dirname(__DIR__);
require_once $basePath . '/db.php';
require_once $basePath . '/includes/SiteVisitsAggregationService.php';

try {
    $pdo = getDB();
    $aggService = new SiteVisitsAggregationService($pdo);

    // 7일 이전 데이터의 날짜 범위 확인
    $boundary = date('Y-m-d', strtotime('-7 days'));
    $stmt = $pdo->prepare("SELECT MIN(DATE(created_at)) as oldest, MAX(DATE(created_at)) as newest, COUNT(*) as total FROM site_visits WHERE DATE(created_at) < ?");
    $stmt->execute([$boundary]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info['oldest']) {
        echo "7일 이전 데이터 없음. 종료.\n";
        exit(0);
    }

    echo "대상: {$info['oldest']} ~ {$info['newest']}, 총 {$info['total']}건\n";

    // 날짜별 집계 실행
    $result = $aggService->aggregateRange($info['oldest'], $info['newest']);
    echo $result['message'] . "\n";

    foreach ($result['details'] as $date => $r) {
        echo "  " . $r['message'] . "\n";
    }

    if (!$result['success']) {
        echo "[오류] 일부 집계 실패. 원본 삭제를 건너뜁니다.\n";
        exit(1);
    }

    // 집계 완료 검증: 날짜별 pageviews 합이 원본과 일치하는지
    $stmt = $pdo->prepare("SELECT SUM(pageviews) FROM site_visits_daily WHERE visit_date < ?");
    $stmt->execute([$boundary]);
    $aggTotal = (int)$stmt->fetchColumn();

    echo "\n검증: 원본 {$info['total']}건, 집계 pageviews 합계 {$aggTotal}\n";

    if ($aggTotal !== (int)$info['total']) {
        echo "[경고] pageviews 불일치! 원본 삭제를 건너뜁니다.\n";
        echo "수동으로 확인 후 삭제하세요: DELETE FROM site_visits WHERE DATE(created_at) < '{$boundary}'\n";
        exit(1);
    }

    // 원본 데이터 삭제
    $deleted = $aggService->purgeRawData(7);
    echo "\n원본 삭제: {$deleted}건\n";

    // OPTIMIZE TABLE
    echo "OPTIMIZE TABLE 실행 중...\n";
    $optStmt = $pdo->query("OPTIMIZE TABLE site_visits");
    $optStmt->fetchAll(); // 결과셋 소비
    $optStmt->closeCursor();
    echo "완료.\n";

    // 결과 확인
    $remaining = (int)$pdo->query("SELECT COUNT(*) FROM site_visits")->fetchColumn();
    $aggDays = (int)$pdo->query("SELECT COUNT(*) FROM site_visits_daily")->fetchColumn();
    echo "\n최종: 원본 {$remaining}건 (7일), 집계 {$aggDays}일\n";

} catch (Exception $e) {
    echo "[오류] " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] 마이그레이션 완료\n";
exit(0);
