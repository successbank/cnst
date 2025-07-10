<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>데이터베이스 연결 테스트</h1>";

// PDO 확인
echo "<h2>1. PDO 상태 확인</h2>";
if (class_exists('PDO')) {
    echo "✅ PDO 클래스 존재<br>";
    $drivers = PDO::getAvailableDrivers();
    echo "✅ 사용 가능한 드라이버: " . implode(', ', $drivers) . "<br>";
} else {
    echo "❌ PDO 클래스를 찾을 수 없음<br>";
}

// MySQL 연결 테스트
echo "<h2>2. MySQL 연결 테스트</h2>";
try {
    $host = 'project1_mysql';
    $user = 'root';
    $pass = 'rootpassword';
    
    // 서버 연결
    $dsn = "mysql:host=$host";
    $pdo = new PDO($dsn, $user, $pass);
    echo "✅ MySQL 서버 연결 성공<br>";
    
    // 데이터베이스 생성
    $pdo->exec("CREATE DATABASE IF NOT EXISTS project1_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ 데이터베이스 생성/확인 완료<br>";
    
    // 데이터베이스 연결
    $dsn = "mysql:host=$host;dbname=project1_db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    echo "✅ project1_db 데이터베이스 연결 성공<br>";
    
    // 테이블 확인
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<br><strong>현재 테이블 목록:</strong><br>";
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "- $table<br>";
        }
    } else {
        echo "테이블이 없습니다. <a href='init_db.php'>데이터베이스 초기화</a>를 실행하세요.<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ 오류: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// PHP 정보
echo "<h2>3. PHP 환경 정보</h2>";
echo "PHP 버전: " . PHP_VERSION . "<br>";
echo "로드된 확장 모듈 수: " . count(get_loaded_extensions()) . "개<br>";
?>