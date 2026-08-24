<?php
// [보안] WAF - 모든 요청에 대한 보안 검사 (최상단 실행)
require_once __DIR__ . '/includes/waf.php';

// 세션 쿠키 보안 설정 (세션 시작 전 설정 필요)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// 데이터베이스 연결 설정 - MySQL 사용 (현재 실행 중)
if (!defined('DB_HOST')) {
    // Docker 컨테이너 실행 시: 'project1_mysql' 사용
    // Docker 중지 시: 'localhost' 사용
    $docker_host = 'project1_mysql';
    $localhost = 'localhost';
    
    // Docker 컨테이너 연결 테스트
    $test_connection = @fsockopen($docker_host, 3306, $errno, $errstr, 1);
    if ($test_connection) {
        define('DB_HOST', $docker_host);
        fclose($test_connection);
    } else {
        // localhost의 경우 TCP 연결 대신 127.0.0.1 사용
        define('DB_HOST', '127.0.0.1');
    }
    
    define('DB_PORT', '3306');
    define('DB_NAME', 'project1_db');
    define('DB_USER', 'user');
    $db_password = getenv('MYSQL_PASSWORD');
    if (empty($db_password)) {
        error_log("CRITICAL: MYSQL_PASSWORD environment variable not set");
        die("시스템 설정 오류입니다. 관리자에게 문의하세요.");
    }
    define('DB_PASS', $db_password);
}

// PDO를 사용한 MySQL 연결 함수
if (!function_exists('getDB')) {
function getDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        
        // 에러 모드 설정
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 기본 fetch 모드를 연관 배열로 설정
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // 통신 인코딩 설정
        $pdo->exec("SET NAMES utf8mb4");

        return $pdo;
    } catch (PDOException $e) {
        // 데이터베이스가 없는 경우 생성 시도
        if (strpos($e->getMessage(), 'Unknown database') !== false) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // 다시 연결
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                return $pdo;
            } catch (PDOException $e2) {
                error_log("데이터베이스 연결 실패: " . $e2->getMessage());
                die("일시적인 서비스 오류가 발생했습니다. 잠시 후 다시 시도해주세요.");
            }
        }
        error_log("데이터베이스 연결 실패: " . $e->getMessage());
        die("일시적인 서비스 오류가 발생했습니다. 잠시 후 다시 시도해주세요.");
    }
}
}

// 전역 데이터베이스 연결
if (!isset($pdo)) {
    $pdo = getDB();
}

// 유틸리티 함수들
if (!function_exists('escape')) {
    function escape($str) {
        // PHP 8+ null 처리
        if ($str === null) {
            return '';
        }
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// 이름 마스킹 함수 (홀수번째 글자 유지, 짝수번째 글자를 *로: 정한진→정*진, 프라임레일→프*임*일)
if (!function_exists('maskName')) {
    function maskName($name) {
        if ($name === null || $name === '') {
            return '';
        }
        $chars = preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY);
        $len = count($chars);
        if ($len <= 1) {
            return $name;
        }
        foreach ($chars as $i => $ch) {
            // 0-based 홀수 인덱스(=2,4번째 글자)를 마스킹
            if ($i % 2 === 1) {
                $chars[$i] = '*';
            }
        }
        return implode('', $chars);
    }
}

// 페이징 처리 함수
if (!function_exists('getPagination')) {
    function getPagination($totalCount, $currentPage, $perPage = 10) {
        $totalPages = ceil($totalCount / $perPage);
        $startPage = max(1, $currentPage - 5);
        $endPage = min($totalPages, $currentPage + 5);
        
        return [
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'startPage' => $startPage,
            'endPage' => $endPage,
            'perPage' => $perPage,
            'offset' => ($currentPage - 1) * $perPage
        ];
    }
}

// 날짜 포맷 함수
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'Y-m-d') {
        return date($format, strtotime($date));
    }
}

// 파일 업로드 처리 함수
if (!function_exists('uploadFile')) {
    function uploadFile($file, $uploadDir = 'uploads/') {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        error_log("Upload error: " . $file['error']);
        return false;
    }
    
    // 파일 확장자 검증
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedExts)) {
        error_log("Invalid file extension: " . $fileExt);
        return false;
    }

    // MIME 타입 검증 (magic bytes 기반)
    $allowedMimes = [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream', // 일부 오피스 파일이 이 타입으로 감지될 수 있음
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMime = $finfo->file($file['tmp_name']);
    if (!in_array($detectedMime, $allowedMimes)) {
        error_log("Invalid MIME type: " . $detectedMime . " for file: " . $file['name']);
        return false;
    }

    // 파일 크기 검증 (10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        error_log("File too large: " . $file['size']);
        return false;
    }
    
    // 절대 경로로 변환
    if (!preg_match('/^\//', $uploadDir)) {
        $uploadDir = rtrim(dirname(__FILE__), '/') . '/' . $uploadDir;
    }
    
    // 디렉토리 경로 정리
    $uploadDir = rtrim($uploadDir, '/') . '/';
    
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    // 디렉토리 생성
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            error_log("Failed to create directory: " . $uploadDir);
            return false;
        }
    }
    
    // 디렉토리 쓰기 권한 확인
    if (!is_writable($uploadDir)) {
        error_log("Directory not writable: " . $uploadDir);
        // 권한 변경 시도
        @chmod($uploadDir, 0777);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // 업로드된 파일에 권한 설정
        @chmod($targetPath, 0644);
        return $fileName;
    } else {
        error_log("Failed to move uploaded file from " . $file['tmp_name'] . " to " . $targetPath);
        error_log("Upload directory: " . $uploadDir);
        error_log("Target path: " . $targetPath);
        error_log("Is upload dir writable: " . (is_writable($uploadDir) ? 'yes' : 'no'));
        error_log("Upload dir exists: " . (is_dir($uploadDir) ? 'yes' : 'no'));
        error_log("Current working directory: " . getcwd());
    }
    
    return false;
    }
}
?>