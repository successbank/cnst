<?php
/**
 * SimpleWAF - PHP 레벨 Web Application Firewall
 * db.php 최상단에서 require하여 모든 요청에 대해 검사
 */

class SimpleWAF {
    private static string $logFile = __DIR__ . '/../logs/waf.log';
    private static string $blockDir = '/tmp/waf_blocks/';

    /**
     * 메인 검사 - 킬스위치 및 전체 규칙 실행
     */
    public static function inspect(): void {
        // 킬스위치: 환경변수 WAF_DISABLED=1이면 바이패스
        if (getenv('WAF_DISABLED') === '1') {
            return;
        }

        // [보안 2026-04-17 감사] CLI/phpdbg(cron·관리 스크립트)는 HTTP 요청이 아니므로 검사 대상 아님
        // - REQUEST_METHOD 미지정으로 인한 false-positive 차단 방지
        // - backup_cron.php, log_cleanup_cron.php, security_monitor.php 등 정상 동작 보장
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // 로컬/내부 IP는 자동 차단 대상에서 제외 (규칙 검사는 수행)
        $skipAutoBlock = in_array($ip, ['127.0.0.1', '::1']) || strpos($ip, '172.') === 0 || strpos($ip, '10.') === 0;

        // IP 자동 차단 확인
        if (!$skipAutoBlock && self::isIpBlocked($ip)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo '접근이 차단되었습니다.';
            exit;
        }

        // 각 규칙 검사
        self::checkHttpMethod();
        self::checkUriPath();
        self::checkUserAgent();
        self::checkQueryString();
        self::checkRequestHeaders();
        self::checkRequestBody();
    }

    /**
     * [보안 2026-04-17 감사 H-6] POST 본문 검사
     * - form-urlencoded: $_POST 배열 재귀 스캔
     * - application/json: 원본 본문 최대 1MB까지 스캔
     * - multipart/form-data (파일 업로드)는 대상 아님 (콘텐츠 스캔 아닌 업로드 검증은 별도 레이어)
     *
     * false-positive 최소화를 위해 게시글 본문에 자연스럽게 등장할 수 있는
     * `<script>`, `javascript:`, `on*=` 같은 XSS 일반 패턴은 포함하지 않음.
     * XSS는 출력 시 이스케이프(htmlspecialchars)로 방어하고 WAF는 최후 방어.
     */
    private static function checkRequestBody(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // 파일 업로드는 본문 스캔 대상이 아님 (별도 업로드 검증 레이어에서 처리)
        if (stripos($contentType, 'multipart/form-data') !== false) {
            return;
        }

        // form-urlencoded: 파싱된 $_POST 재귀 검사
        if (!empty($_POST)) {
            self::scanArrayValues($_POST);
        }

        // application/json: 원본 본문 스캔 (1MB 제한 - 대용량 페이로드로 인한 메모리/CPU 부하 방지)
        if (stripos($contentType, 'application/json') !== false) {
            $raw = @file_get_contents('php://input', false, null, 0, 1048576);
            if (is_string($raw) && $raw !== '') {
                self::scanBodyString($raw, 'json');
            }
        }
    }

    /**
     * $_POST 같은 배열을 재귀적으로 순회하며 문자열 값 검사
     */
    private static function scanArrayValues(array $arr, string $prefix = ''): void {
        foreach ($arr as $k => $v) {
            $path = $prefix === '' ? (string)$k : $prefix . '.' . $k;
            if (is_array($v)) {
                self::scanArrayValues($v, $path);
            } elseif (is_string($v)) {
                self::scanBodyString($v, $path);
            }
        }
    }

    /**
     * 본문 문자열에 대한 엄격한 SQLi/쉘 실행 패턴 검사
     * 차단 패턴만 명시 — 일반 텍스트에 드물게 등장하는 것만 포함
     */
    private static function scanBodyString(string $s, string $location): void {
        // null byte
        if (strpos($s, "\0") !== false) {
            self::block('Null byte in POST body field: ' . substr($location, 0, 80));
            return;
        }

        $patterns = [
            '/\bUNION\s+(ALL\s+)?SELECT\b/i',           // UNION 기반 SQLi
            '/\bBENCHMARK\s*\(/i',                      // MySQL 시간 기반
            '/\bSLEEP\s*\(\s*\d+\s*\)/i',               // 블라인드 시간 기반
            '/\bLOAD_FILE\s*\(/i',                      // 파일 읽기 SQLi
            '/\bINTO\s+(OUT|DUMP)FILE\b/i',             // 파일 쓰기 SQLi
            '/\bWAITFOR\s+DELAY\b/i',                   // MSSQL 시간 기반
            '/\bxp_cmdshell\b/i',                       // MSSQL 셸 실행
            '/(\'|")\s*(OR|AND)\s+\d+\s*[=<>]\s*\d+/i', // '1'='1 계열 동등 비교
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $s)) {
                self::block('Suspicious POST body at ' . substr($location, 0, 80));
                return;
            }
        }
    }

    /**
     * HTTP 메서드 검증: GET, POST, HEAD, OPTIONS만 허용
     */
    private static function checkHttpMethod(): void {
        $allowed = ['GET', 'POST', 'HEAD', 'OPTIONS'];
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        if (!in_array($method, $allowed, true)) {
            self::block('Disallowed HTTP method: ' . $method);
        }
    }

    /**
     * URI 경로 검증: 경로 탐색, null 바이트 차단
     */
    private static function checkUriPath(): void {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $decodedUri = rawurldecode($uri);

        $patterns = [
            '/\.\.[\\/]/',                  // 경로 탐색 ../ ..\
            '/\x00/',                       // null 바이트
            '/\/etc\/(passwd|shadow|hosts)/i',  // 시스템 파일
            '/\/proc\/self/i',              // proc filesystem
            '/\.(htaccess|htpasswd)/i',     // Apache 설정
            '/wp-(admin|login|content|includes)/i',  // WordPress 스캐닝
            '/phpmyadmin/i',                // phpMyAdmin 스캐닝
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $decodedUri)) {
                self::block('Suspicious URI path: ' . substr($uri, 0, 200));
            }
        }
    }

    /**
     * User-Agent 필터링: 알려진 스캐너 시그니처 차단
     */
    private static function checkUserAgent(): void {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($ua === '') {
            return; // 빈 UA는 일부 정상 요청에서도 발생
        }

        $scanners = [
            'sqlmap', 'nikto', 'acunetix', 'nessus', 'openvas',
            'zmeu', 'masscan', 'nmap', 'dirbuster', 'gobuster',
            'wpscan', 'havij', 'w3af', 'burpsuite', 'metasploit',
            'nuclei', 'httpx', 'subfinder', 'zgrab',
        ];

        $uaLower = strtolower($ua);
        foreach ($scanners as $scanner) {
            if (strpos($uaLower, $scanner) !== false) {
                self::block('Scanner UA detected: ' . $scanner);
            }
        }
    }

    /**
     * 쿼리 스트링 검사: SQL 인젝션, XSS 패턴
     */
    private static function checkQueryString(): void {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        if ($qs === '') {
            return;
        }
        $decodedQs = urldecode($qs);

        $patterns = [
            // SQL 인젝션
            '/\bUNION\b.*\bSELECT\b/i',
            '/\bSELECT\b.*\bFROM\b.*\bWHERE\b/i',
            '/\bINSERT\b.*\bINTO\b/i',
            '/\bDELETE\b.*\bFROM\b/i',
            '/\bDROP\b.*\b(TABLE|DATABASE)\b/i',
            '/\bUPDATE\b.*\bSET\b/i',
            '/\bEXEC\s*\(/i',
            '/\bEXECUTE\b/i',
            '/\bxp_cmdshell\b/i',
            '/(\'|")\s*(OR|AND)\s+\d+\s*[=<>]/i',
            '/\bWAITFOR\b.*\bDELAY\b/i',
            '/\bBENCHMARK\s*\(/i',
            '/\bSLEEP\s*\(\s*\d+\s*\)/i',
            '/\bLOAD_FILE\s*\(/i',
            '/\bINTO\s+(OUT|DUMP)FILE\b/i',
            // XSS
            '/<script[\s>]/i',
            '/javascript\s*:/i',
            '/on(error|load|click|mouseover|focus|blur)\s*=/i',
            // 경로 탐색 (쿼리 파라미터)
            '/\.\.[\\/]/',
            '/\x00/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $decodedQs)) {
                self::block('Suspicious query string: ' . substr($decodedQs, 0, 200));
            }
        }
    }

    /**
     * 요청 헤더 이상 탐지
     */
    private static function checkRequestHeaders(): void {
        // 과도하게 긴 쿠키 (8KB 이상)
        $cookie = $_SERVER['HTTP_COOKIE'] ?? '';
        if (strlen($cookie) > 8192) {
            self::block('Oversized cookie header: ' . strlen($cookie) . ' bytes');
        }

        // 과도하게 긴 Referer
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strlen($referer) > 2048) {
            self::block('Oversized referer header: ' . strlen($referer) . ' bytes');
        }
    }

    /**
     * 요청 차단 + 로그 + IP 카운터 증가
     */
    private static function block(string $reason): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        self::logBlock($reason);
        // 로컬/내부 IP는 자동 차단 카운터에서 제외
        $skipAutoBlock = in_array($ip, ['127.0.0.1', '::1']) || strpos($ip, '172.') === 0 || strpos($ip, '10.') === 0;
        if (!$skipAutoBlock) {
            self::incrementBlockCount($ip);
        }

        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
        echo '접근이 거부되었습니다.';
        exit;
    }

    /**
     * WAF 차단 로그 기록
     */
    private static function logBlock(string $reason): void {
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // 로그 로테이션: 10MB 초과 시
        if (@filesize(self::$logFile) > 10485760) {
            @rename(self::$logFile, self::$logFile . '.' . date('Ymd'));
        }

        $entry = sprintf(
            "[%s] IP=%s METHOD=%s URI=%s UA=%s REASON=%s\n",
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'] ?? '-',
            $_SERVER['REQUEST_METHOD'] ?? '-',
            substr($_SERVER['REQUEST_URI'] ?? '-', 0, 500),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 200),
            $reason
        );

        @file_put_contents(self::$logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * IP별 차단 횟수 증가 (파일 기반)
     * 5분 내 10회 이상 → 30분 임시 차단
     */
    private static function incrementBlockCount(string $ip): void {
        if (!is_dir(self::$blockDir)) {
            @mkdir(self::$blockDir, 0755, true);
        }
        $file = self::$blockDir . md5($ip) . '.json';
        $data = ['count' => 0, 'timestamps' => [], 'blocked_until' => 0];
        if (file_exists($file)) {
            $json = @file_get_contents($file);
            if ($json) {
                $data = json_decode($json, true) ?: $data;
            }
        }

        $now = time();
        // 5분 윈도우 내 타임스탬프만 유지
        $data['timestamps'] = array_values(array_filter(
            $data['timestamps'] ?? [],
            fn($t) => ($now - $t) < 300
        ));
        $data['timestamps'][] = $now;
        $data['count'] = count($data['timestamps']);

        // 10회 이상이면 30분 차단
        if ($data['count'] >= 10) {
            $data['blocked_until'] = $now + 1800;
        }

        @file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * IP가 현재 차단 상태인지 확인
     */
    private static function isIpBlocked(string $ip): bool {
        $file = self::$blockDir . md5($ip) . '.json';
        if (!file_exists($file)) {
            return false;
        }
        $json = @file_get_contents($file);
        if (!$json) {
            return false;
        }
        $data = json_decode($json, true);
        if (!$data || empty($data['blocked_until'])) {
            return false;
        }

        if (time() < $data['blocked_until']) {
            return true;
        }

        // 차단 기간 만료 → 파일 삭제
        @unlink($file);
        return false;
    }
}

// 자동 실행
SimpleWAF::inspect();
