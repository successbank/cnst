<?php
/**
 * 백업 복원 AJAX (PDO 기반)
 * shell_exec/docker exec 없이 SQL 파일을 PDO로 직접 실행
 */
header('Content-Type: application/json');

require_once '../admin_check.php';
require_once '../../db.php';

try {
    $filename = $_POST['filename'] ?? '';

    if (empty($filename)) {
        throw new Exception('파일명이 없습니다.');
    }

    // 파일명 보안 검증
    $filename = basename($filename);

    // SQL 파일 패턴 검증
    if (!preg_match('/^[a-zA-Z0-9_\-]+\.sql$/', $filename)) {
        throw new Exception('잘못된 파일명입니다.');
    }

    $backupDir = dirname(dirname(__DIR__)) . '/backups';
    $filepath = $backupDir . '/' . $filename;

    // 파일 존재 확인
    if (!file_exists($filepath)) {
        throw new Exception('백업 파일이 존재하지 않습니다.');
    }

    // 파일 크기 확인 (최대 50MB)
    $fileSize = filesize($filepath);
    if ($fileSize > 50 * 1024 * 1024) {
        throw new Exception('백업 파일이 너무 큽니다. (최대 50MB)');
    }

    // SQL 파일 읽기
    $sql = file_get_contents($filepath);
    if ($sql === false) {
        throw new Exception('백업 파일을 읽을 수 없습니다.');
    }

    // PDO로 SQL 실행
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    // SQL을 개별 구문으로 분리하여 실행
    $statements = parseSqlStatements($sql);
    $executed = 0;
    $errors = [];

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;

        // 주석 라인만 있는 경우 건너뜀
        if (preg_match('/^--/', $statement) && !str_contains($statement, "\n")) continue;

        // SET 구문, DROP, CREATE, INSERT 등 실행
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // 중요하지 않은 에러는 기록만 하고 계속
            $errors[] = substr($e->getMessage(), 0, 200);
            if (count($errors) > 50) {
                throw new Exception('복원 중 에러가 너무 많습니다. (50건 초과)');
            }
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    $message = "복원이 완료되었습니다. ({$executed}개 구문 실행)";
    if (!empty($errors)) {
        $message .= " 경고: " . count($errors) . "건의 비치명적 에러 발생";
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'executed' => $executed,
        'warnings' => count($errors)
    ]);

} catch (Exception $e) {
    // FK check 복원 시도
    try { $pdo->exec("SET FOREIGN_KEY_CHECKS=1"); } catch (Exception $ignored) {}

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * SQL 파일을 개별 구문으로 파싱
 * 문자열 리터럴 내 세미콜론은 무시
 */
function parseSqlStatements($sql) {
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];

        // 문자열 내부 처리
        if ($inString) {
            $current .= $char;
            if ($char === '\\' && $i + 1 < $len) {
                // 이스케이프된 문자
                $current .= $sql[++$i];
                continue;
            }
            if ($char === $stringChar) {
                // 연속된 따옴표 (이스케이프)
                if ($i + 1 < $len && $sql[$i + 1] === $stringChar) {
                    $current .= $sql[++$i];
                    continue;
                }
                $inString = false;
            }
            continue;
        }

        // 한 줄 주석 (-- )
        if ($char === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            $endOfLine = strpos($sql, "\n", $i);
            if ($endOfLine === false) {
                break;
            }
            $i = $endOfLine;
            continue;
        }

        // /* */ 블록 주석
        if ($char === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
            $endComment = strpos($sql, '*/', $i + 2);
            if ($endComment === false) {
                break;
            }
            $i = $endComment + 1;
            continue;
        }

        // 문자열 시작
        if ($char === "'" || $char === '"') {
            $inString = true;
            $stringChar = $char;
            $current .= $char;
            continue;
        }

        // 구문 구분자
        if ($char === ';') {
            $trimmed = trim($current);
            if (!empty($trimmed)) {
                $statements[] = $trimmed;
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    // 마지막 구문
    $trimmed = trim($current);
    if (!empty($trimmed)) {
        $statements[] = $trimmed;
    }

    return $statements;
}
