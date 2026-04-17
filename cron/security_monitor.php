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

// [보안 2026-04-17 감사 L-2] WAF 차단 로그 스파이크 탐지
// waf.log 는 `[YYYY-mm-dd HH:MM:SS] IP=X ... REASON=...` 형태로 append
// 최근 15분 이내 라인만 대상으로 15분 집계/IP별 집계 실행
try {
    $wafLog = "/var/www/html/logs/waf.log";
    if (is_readable($wafLog) && ($fp = @fopen($wafLog, "r"))) {
        $cutoffTs = time() - (15 * 60);
        $totalRecent = 0;
        $perIp = [];

        // 말단부터 역방향 청크 리딩 - 대용량 로그에서도 최근 15분만 빠르게 스캔
        // 파일 끝에서 256KB 청크를 읽어 줄 단위 파싱
        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);
        $leftover = "";
        $chunkSize = 262144;
        $scannedLines = 0;
        $maxLines = 20000; // 안전 상한 (DoS 방지)

        while ($pos > 0 && $scannedLines < $maxLines) {
            $read = min($chunkSize, $pos);
            $pos -= $read;
            fseek($fp, $pos);
            $buf = fread($fp, $read) . $leftover;
            $lines = explode("\n", $buf);
            if ($pos > 0) {
                $leftover = array_shift($lines); // 맨 앞 라인은 이전 청크와 합쳐야 완전
            } else {
                $leftover = "";
            }
            // 최신 라인부터 역순으로 처리
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $line = $lines[$i];
                if ($line === "") continue;
                $scannedLines++;
                // `[2026-04-17 01:02:52] IP=... REASON=...`
                if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m)) continue;
                $ts = strtotime($m[1]);
                if ($ts === false) continue;
                if ($ts < $cutoffTs) {
                    // 15분 이전 라인을 만나면 더 과거는 볼 필요 없음
                    $pos = 0;
                    break;
                }
                $totalRecent++;
                if (preg_match('/IP=([^\s]+)/', $line, $ipMatch)) {
                    $ip = $ipMatch[1];
                    if ($ip !== '-' && $ip !== '') {
                        $perIp[$ip] = ($perIp[$ip] ?? 0) + 1;
                    }
                }
            }
        }
        fclose($fp);

        // 스파이크: 15분 내 전체 차단 100건 초과
        if ($totalRecent > 100) {
            $alerts[] = "[WARNING] WAF 차단 스파이크: 최근 15분 " . $totalRecent . "건 (정상치 대비 급증 여부 확인 필요)";
        }
        // 단일 IP 집중 공격: 15분 내 동일 IP 30건 초과
        foreach ($perIp as $ip => $cnt) {
            if ($cnt > 30) {
                $alerts[] = "[WARNING] WAF 단일 IP 집중 공격: IP=" . $ip . ", 차단=" . $cnt . "회/15분";
            }
        }
    }
} catch (Exception $e) {
    // WAF 로그 읽기 실패는 모니터링 자체를 멈추게 하지 않음
    error_log("security_monitor: WAF 로그 분석 오류: " . $e->getMessage());
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
