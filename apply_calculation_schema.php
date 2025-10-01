<?php
/**
 * 계산식 관리 시스템 DB 스키마 적용 스크립트
 */

require_once 'db.php';

echo "=== 계산식 관리 시스템 DB 스키마 적용 시작 ===\n\n";

$sqlFile = __DIR__ . '/database/calculation_formula_schema.sql';

if (!file_exists($sqlFile)) {
    die("Error: SQL 파일을 찾을 수 없습니다: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

// SQL 주석 제거 및 정리
$lines = explode("\n", $sql);
$queries = [];
$currentQuery = '';

foreach ($lines as $line) {
    $line = trim($line);

    // 빈 줄이나 주석 라인 건너뛰기
    if (empty($line) || strpos($line, '--') === 0) {
        continue;
    }

    $currentQuery .= ' ' . $line;

    // 세미콜론으로 끝나면 쿼리 완성
    if (substr(trim($line), -1) === ';') {
        $queries[] = trim($currentQuery);
        $currentQuery = '';
    }
}

echo "총 " . count($queries) . "개의 SQL 쿼리를 실행합니다.\n\n";

$pdo->beginTransaction();

try {
    $success = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($queries as $index => $query) {
        if (empty($query)) {
            continue;
        }

        try {
            $pdo->exec($query);
            $success++;

            // 쿼리 타입 출력
            if (stripos($query, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $query, $matches);
                echo "[✓] 테이블 생성: " . ($matches[1] ?? 'unknown') . "\n";
            } elseif (stripos($query, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO\s+`?(\w+)`?/i', $query, $matches);
                echo "[✓] 데이터 삽입: " . ($matches[1] ?? 'unknown') . "\n";
            } elseif (stripos($query, 'ALTER TABLE') !== false) {
                echo "[✓] 테이블 수정\n";
            }

        } catch (PDOException $e) {
            // 중복 키 오류는 무시 (이미 존재하는 데이터)
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $skipped++;
                echo "[~] 건너뜀 (이미 존재): " . substr($query, 0, 60) . "...\n";
            } else {
                $errors++;
                echo "[✗] 오류 발생:\n";
                echo "    Query: " . substr($query, 0, 100) . "...\n";
                echo "    Error: " . $e->getMessage() . "\n\n";
            }
        }
    }

    if ($pdo->inTransaction()) {
        $pdo->commit();
    }

    echo "\n=== 실행 완료 ===\n";
    echo "성공: $success\n";
    echo "건너뜀: $skipped\n";
    echo "오류: $errors\n";

    // 생성된 테이블 확인
    echo "\n=== 생성된 테이블 확인 ===\n";
    $tables = ['calculation_formulas', 'calculation_parameters', 'calculation_constants', 'calculation_history'];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '$table'");
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "[✓] $table: $count 행\n";
        } else {
            echo "[✗] $table: 존재하지 않음\n";
        }
    }

    if ($errors > 0) {
        echo "\n⚠ 일부 오류가 발생했습니다. 위의 오류 메시지를 확인해주세요.\n";
    } else {
        echo "\n✓ 모든 작업이 성공적으로 완료되었습니다!\n";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[✗] 치명적 오류 발생: " . $e->getMessage() . "\n";
    echo "모든 변경사항이 롤백되었습니다.\n";
    exit(1);
}