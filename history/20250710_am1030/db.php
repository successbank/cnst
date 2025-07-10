<?php
// 데이터베이스 연결 설정 - MySQL 사용 (현재 실행 중)
if (!defined('DB_HOST')) {
    define('DB_HOST', 'project1_mysql');
    define('DB_PORT', '3306');
    define('DB_NAME', 'project1_db');
    define('DB_USER', 'root');
    define('DB_PASS', 'rootpassword');
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
                die("데이터베이스 연결 실패: " . $e2->getMessage());
            }
        }
        die("데이터베이스 연결 실패: " . $e->getMessage());
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
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
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
    
    // 파일 크기 검증 (10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        error_log("File too large: " . $file['size']);
        return false;
    }
    
    $fileName = time() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $fileName;
    
    // 디렉토리 생성
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
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
    }
    
    return false;
    }
}
?>