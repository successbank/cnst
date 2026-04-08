<?php
/**
 * site_visits 일별 집계 서비스
 * 원본 데이터를 3개 집계 테이블로 요약하여 DB/백업 크기 절감
 */
class SiteVisitsAggregationService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * 특정 날짜의 site_visits 원본 데이터를 집계
     * INSERT ... ON DUPLICATE KEY UPDATE로 멱등성 보장
     *
     * @param string $date Y-m-d 형식
     * @return array ['success' => bool, 'message' => string, 'counts' => array]
     */
    public function aggregateDate(string $date): array {
        // 해당 날짜에 원본 데이터가 있는지 확인
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM site_visits WHERE DATE(created_at) = ?");
        $stmt->execute([$date]);
        $rawCount = (int)$stmt->fetchColumn();

        if ($rawCount === 0) {
            return [
                'success' => true,
                'message' => "{$date}: 원본 데이터 없음, 스킵",
                'counts' => ['daily' => 0, 'pages' => 0, 'dimensions' => 0]
            ];
        }

        $this->pdo->beginTransaction();
        try {
            // 1) site_visits_daily: 일별 총계
            $stmt = $this->pdo->prepare("
                INSERT INTO site_visits_daily (visit_date, unique_visitors, pageviews, new_visitors)
                SELECT
                    DATE(created_at),
                    COUNT(DISTINCT session_id),
                    COUNT(*),
                    SUM(is_new_visitor)
                FROM site_visits
                WHERE DATE(created_at) = ?
                GROUP BY DATE(created_at)
                ON DUPLICATE KEY UPDATE
                    unique_visitors = VALUES(unique_visitors),
                    pageviews = VALUES(pageviews),
                    new_visitors = VALUES(new_visitors)
            ");
            $stmt->execute([$date]);
            $dailyCount = $stmt->rowCount();

            // 2) site_visits_daily_pages: 페이지별 집계
            $stmt = $this->pdo->prepare("
                INSERT INTO site_visits_daily_pages (visit_date, page_url, page_title, pageviews, unique_visitors)
                SELECT
                    DATE(created_at),
                    page_url,
                    page_title,
                    COUNT(*),
                    COUNT(DISTINCT session_id)
                FROM site_visits
                WHERE DATE(created_at) = ?
                GROUP BY DATE(created_at), page_url, page_title
                ON DUPLICATE KEY UPDATE
                    page_title = VALUES(page_title),
                    pageviews = VALUES(pageviews),
                    unique_visitors = VALUES(unique_visitors)
            ");
            $stmt->execute([$date]);
            $pagesCount = $stmt->rowCount();

            // 3) site_visits_daily_dimensions: device 집계
            $stmt = $this->pdo->prepare("
                INSERT INTO site_visits_daily_dimensions (visit_date, dimension_type, dimension_value, sessions, pageviews)
                SELECT
                    DATE(created_at),
                    'device',
                    COALESCE(device_type, 'unknown'),
                    COUNT(DISTINCT session_id),
                    COUNT(*)
                FROM site_visits
                WHERE DATE(created_at) = ?
                GROUP BY DATE(created_at), device_type
                ON DUPLICATE KEY UPDATE
                    sessions = VALUES(sessions),
                    pageviews = VALUES(pageviews)
            ");
            $stmt->execute([$date]);
            $dimCount = $stmt->rowCount();

            // 4) site_visits_daily_dimensions: browser 집계
            $stmt = $this->pdo->prepare("
                INSERT INTO site_visits_daily_dimensions (visit_date, dimension_type, dimension_value, sessions, pageviews)
                SELECT
                    DATE(created_at),
                    'browser',
                    COALESCE(browser, 'Unknown'),
                    COUNT(DISTINCT session_id),
                    COUNT(*)
                FROM site_visits
                WHERE DATE(created_at) = ?
                GROUP BY DATE(created_at), browser
                ON DUPLICATE KEY UPDATE
                    sessions = VALUES(sessions),
                    pageviews = VALUES(pageviews)
            ");
            $stmt->execute([$date]);
            $dimCount += $stmt->rowCount();

            // 5) site_visits_daily_dimensions: referrer 카테고리 집계
            $stmt = $this->pdo->prepare("
                SELECT referrer, COUNT(*) as cnt
                FROM site_visits
                WHERE DATE(created_at) = ?
                GROUP BY referrer
            ");
            $stmt->execute([$date]);
            $referrerRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $refCategories = $this->classifyReferrers($referrerRows);
            foreach ($refCategories as $category => $count) {
                if ($count <= 0) continue;
                $stmt = $this->pdo->prepare("
                    INSERT INTO site_visits_daily_dimensions (visit_date, dimension_type, dimension_value, sessions, pageviews)
                    VALUES (?, 'referrer', ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        sessions = VALUES(sessions),
                        pageviews = VALUES(pageviews)
                ");
                $stmt->execute([$date, $category, $count, $count]);
                $dimCount += $stmt->rowCount();
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => "{$date}: 원본 {$rawCount}건 → 집계 완료 (daily:{$dailyCount}, pages:{$pagesCount}, dims:{$dimCount})",
                'counts' => ['daily' => $dailyCount, 'pages' => $pagesCount, 'dimensions' => $dimCount]
            ];

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => "{$date}: 집계 실패 - " . $e->getMessage(),
                'counts' => []
            ];
        }
    }

    /**
     * 날짜 범위 집계 (초기 마이그레이션용)
     */
    public function aggregateRange(string $startDate, string $endDate): array {
        $results = [];
        $current = new \DateTime($startDate);
        $end = new \DateTime($endDate);

        while ($current <= $end) {
            $date = $current->format('Y-m-d');
            $results[$date] = $this->aggregateDate($date);
            $current->modify('+1 day');
        }

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $failCount = count($results) - $successCount;

        return [
            'success' => $failCount === 0,
            'message' => "집계 완료: 성공 {$successCount}일, 실패 {$failCount}일",
            'details' => $results
        ];
    }

    /**
     * 오래된 원본 데이터 삭제
     * @param int $days 보관 일수
     * @return int 삭제된 행 수
     */
    public function purgeRawData(int $days): int {
        $stmt = $this->pdo->prepare("DELETE FROM site_visits WHERE created_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        return $stmt->rowCount();
    }

    /**
     * referrer를 카테고리로 분류
     * statistics_data.php:315-331 로직과 동일
     */
    private function classifyReferrers(array $referrerRows): array {
        $categories = [
            '직접 유입' => 0,
            '네이버' => 0,
            '구글' => 0,
            '다음' => 0,
            '소셜미디어' => 0,
            '기타' => 0,
        ];

        foreach ($referrerRows as $r) {
            $ref = strtolower($r['referrer'] ?? '');
            $count = (int)$r['cnt'];

            if (empty($ref) || $ref === '-') {
                $categories['직접 유입'] += $count;
            } elseif (strpos($ref, 'naver.com') !== false || strpos($ref, 'search.naver') !== false) {
                $categories['네이버'] += $count;
            } elseif (strpos($ref, 'google.') !== false) {
                $categories['구글'] += $count;
            } elseif (strpos($ref, 'daum.net') !== false || strpos($ref, 'search.daum') !== false) {
                $categories['다음'] += $count;
            } elseif (preg_match('/facebook|instagram|twitter|x\.com|youtube|tiktok|kakao/i', $ref)) {
                $categories['소셜미디어'] += $count;
            } else {
                $categories['기타'] += $count;
            }
        }

        return $categories;
    }
}
