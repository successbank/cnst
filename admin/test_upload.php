<?php
// 오류 표시
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 세션 확인
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "세션 없음<br>";
} else {
    echo "세션 ID: " . $_SESSION['admin_id'] . "<br>";
}

// GD 라이브러리 확인
if (extension_loaded('gd')) {
    echo "GD 라이브러리: 사용 가능<br>";
    $gd_info = gd_info();
    echo "GD 버전: " . $gd_info['GD Version'] . "<br>";
} else {
    echo "GD 라이브러리: 사용 불가<br>";
}

// 업로드 디렉토리 확인
$uploadDir = '../uploads/notices/';
if (file_exists($uploadDir)) {
    echo "업로드 디렉토리: 존재<br>";
    if (is_writable($uploadDir)) {
        echo "업로드 디렉토리: 쓰기 가능<br>";
    } else {
        echo "업로드 디렉토리: 쓰기 불가<br>";
    }
} else {
    echo "업로드 디렉토리: 존재하지 않음<br>";
}

// PHP 설정 확인
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";

// admin_check.php 파일 확인
if (file_exists('admin_check.php')) {
    echo "admin_check.php: 존재<br>";
} else {
    echo "admin_check.php: 존재하지 않음<br>";
}

// db.php 파일 확인
if (file_exists('../db.php')) {
    echo "../db.php: 존재<br>";
} else {
    echo "../db.php: 존재하지 않음<br>";
}
?>