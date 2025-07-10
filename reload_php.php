<?php
// PHP 프로세스 정보
echo "<h1>PHP 재로드 시도</h1>";

// 현재 프로세스 ID
echo "현재 프로세스 ID: " . getmypid() . "<br>";

// opcache 재설정 시도
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache 재설정 완료<br>";
}

// 확장 동적 로드 시도 (일반적으로 비활성화되어 있음)
if (function_exists('dl')) {
    try {
        dl('pdo_mysql.so');
        echo "✅ pdo_mysql 동적 로드 성공<br>";
    } catch (Exception $e) {
        echo "❌ 동적 로드 실패: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ dl() 함수가 비활성화되어 있음<br>";
}

// 환경 확인
echo "<br><h2>PHP 환경 재확인</h2>";
echo "PHP SAPI: " . PHP_SAPI . "<br>";
echo "로드된 ini 파일: " . php_ini_loaded_file() . "<br>";
echo "추가 ini 파일 경로: " . php_ini_scanned_files() . "<br>";

// PDO 드라이버 재확인
echo "<br><h2>PDO 드라이버 상태</h2>";
if (extension_loaded('pdo')) {
    echo "PDO: ✅ 로드됨<br>";
    echo "드라이버: " . implode(', ', PDO::getAvailableDrivers()) . "<br>";
} else {
    echo "PDO: ❌ 로드 안됨<br>";
}

// 권장사항
echo "<br><h2>권장사항</h2>";
echo "PHP-FPM을 완전히 재시작하려면 다음 명령을 실행하세요:<br>";
echo "<code>sudo docker restart project1_php</code><br>";
echo "또는<br>";
echo "<code>sudo docker-compose -f /home/successbank/projects/docker/project1/docker-compose.yml restart php</code><br>";
?>