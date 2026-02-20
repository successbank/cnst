<?php
/**
 * CSP 위반 리포트 수집기
 * Content-Security-Policy-Report-Only의 report-uri 엔드포인트
 */

// POST만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(204);
    exit;
}

// Content-Type 확인
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/csp-report') === false && strpos($contentType, 'application/json') === false) {
    http_response_code(204);
    exit;
}

// 요청 본문 읽기
$body = file_get_contents('php://input');
if (empty($body)) {
    http_response_code(204);
    exit;
}

// JSON 파싱 검증
$data = json_decode($body, true);
if (!$data) {
    http_response_code(204);
    exit;
}

// 로그 파일 경로
$logDir = dirname(__DIR__) . '/logs';
$logFile = $logDir . '/csp_violations.log';

// 로그 디렉토리 생성
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// 로그 파일 크기 제한 (5MB)
if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
    @rename($logFile, $logFile . '.' . date('Ymd_His') . '.bak');
}

// 리포트 데이터 추출
$report = $data['csp-report'] ?? $data;
$entry = sprintf(
    "[%s] IP=%s blocked-uri=%s violated-directive=%s document-uri=%s source-file=%s line=%s\n",
    date('Y-m-d H:i:s'),
    $_SERVER['REMOTE_ADDR'] ?? '-',
    $report['blocked-uri'] ?? '-',
    $report['violated-directive'] ?? '-',
    $report['document-uri'] ?? '-',
    $report['source-file'] ?? '-',
    $report['line-number'] ?? '-'
);

@file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

// 204 No Content 응답 (브라우저는 응답을 기대하지 않음)
http_response_code(204);
