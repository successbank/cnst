<?php
// 보안 모니터링 크론 스크립트
// 15분마다 실행

require_once "/var/www/html/db.php";

$alerts = [];

try {
    $pdo = getDB();

    // 1. 동일 IP에서 1시간 내 5회 이상 로그인 실패 탐지
    $stmt = $pdo->query("
        SELECT login_ip, COUNT(*) as fail_count
        FROM member_login_logs
        WHERE login_status = 'failed'
          AND login_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY login_ip
        HAVING fail_count >= 5
    ");
    $suspicious_ips = $stmt->fetchAll();
    foreach ($suspicious_ips as $row) {
        $alerts[] = "[ALERT] 회원 로그인 brute-force 의심: IP=" . $row["login_ip"] . ", 실패=" . $row["fail_count"] . "회/1시간";
    }

    // 2. 관리자 로그인 실패 탐지
    try {
        $stmt = $pdo->query("
            SELECT login_ip, admin_username, COUNT(*) as fail_count
            FROM admin_login_logs
            WHERE login_status = 'failed'
              AND login_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY login_ip, admin_username
            HAVING fail_count >= 3
        ");
        $admin_fails = $stmt->fetchAll();
        foreach ($admin_fails as $row) {
            $alerts[] = "[CRITICAL] 관리자 로그인 brute-force 의심: IP=" . $row["login_ip"] . ", user=" . $row["admin_username"] . ", 실패=" . $row["fail_count"] . "회/1시간";
        }
    } catch (Exception $e) {
        // admin_login_logs 테이블이 없을 수 있음
    }

    // 3. 비정상 시간대 관리자 로그인 탐지 (새벽 2-5시)
    try {
        $stmt = $pdo->query("
            SELECT admin_username, login_ip, login_date
            FROM admin_login_logs
            WHERE login_status = 'success'
              AND HOUR(login_date) BETWEEN 2 AND 4
              AND login_date > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $odd_hour_logins = $stmt->fetchAll();
        foreach ($odd_hour_logins as $row) {
            $alerts[] = "[WARNING] 비정상 시간 관리자 로그인: user=" . $row["admin_username"] . ", IP=" . $row["login_ip"] . ", time=" . $row["login_date"];
        }
    } catch (Exception $e) {
        // 무시
    }

    // 4. 짧은 시간 내 대량 회원가입 시도 (스팸/봇)
    $stmt = $pdo->query("
        SELECT COUNT(*) as cnt
        FROM members
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $new_members = $stmt->fetchColumn();
    if ($new_members > 10) {
        $alerts[] = "[WARNING] 15분 내 대량 회원가입: " . $new_members . "건 - 봇 공격 가능성";
    }

} catch (Exception $e) {
    $alerts[] = "[ERROR] 보안 모니터링 DB 오류: " . $e->getMessage();
}

// 로그 출력
$timestamp = date("Y-m-d H:i:s");
if (empty($alerts)) {
    echo "[" . $timestamp . "] OK - 이상 없음\n";
} else {
    foreach ($alerts as $alert) {
        echo "[" . $timestamp . "] " . $alert . "\n";
    }
}
