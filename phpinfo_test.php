<?php
// PDO 드라이버 체크
echo "<h2>PDO 드라이버 상태</h2>";
if (extension_loaded('pdo')) {
    echo "PDO: 로드됨<br>";
    echo "사용 가능한 드라이버: " . implode(', ', PDO::getAvailableDrivers()) . "<br><br>";
} else {
    echo "PDO: 로드되지 않음<br><br>";
}

// MySQL 연결 테스트
echo "<h2>MySQL 연결 테스트</h2>";
try {
    $dsn = "mysql:host=project5_mysql;port=3306;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', 'manpass!@#4');
    echo "MySQL 서버 연결: 성공<br>";
    
    // 데이터베이스 생성
    $pdo->exec("CREATE DATABASE IF NOT EXISTS project5_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "데이터베이스 생성/확인: 성공<br>";
    
    // 데이터베이스 연결
    $dsn = "mysql:host=project5_mysql;port=3306;dbname=project5_db;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', 'manpass!@#4');
    echo "project5_db 연결: 성공<br>";
    
} catch (PDOException $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}

// PHP 정보
echo "<br><h2>PHP 정보</h2>";
echo "PHP 버전: " . PHP_VERSION . "<br>";
echo "로드된 확장: <br>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";
?>