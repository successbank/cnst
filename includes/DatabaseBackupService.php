<?php
/**
 * 스트리밍 방식 데이터베이스 백업/복원 서비스
 *
 * 메모리 효율적 처리:
 * - 백업: unbuffered query + fwrite() 직접 파일 기록 + 배치 INSERT
 * - 복원: fread() 청크 읽기 + 스트리밍 SQL 파서 + 구문별 즉시 실행
 */
class DatabaseBackupService
{
    private PDO $pdo;
    private int $batchSize = 100;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * 스트리밍 방식으로 백업 SQL 파일 생성
     *
     * @param string $filepath 저장할 파일 경로
     * @return array ['success' => bool, 'tables_count' => int, 'error' => ?string]
     */
    public function generateBackupToFile(string $filepath): array
    {
        $tablesCount = 0;

        $fp = fopen($filepath, 'w');
        if ($fp === false) {
            return ['success' => false, 'tables_count' => 0, 'error' => '백업 파일을 열 수 없습니다.'];
        }

        try {
            // 헤더
            fwrite($fp, "-- 충남스틸 데이터베이스 백업\n");
            fwrite($fp, "-- 생성일시: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fp, "-- 데이터베이스: " . DB_NAME . "\n");
            fwrite($fp, "-- -------------------------------------------------\n\n");
            fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($fp, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($fp, "SET time_zone = '+09:00';\n\n");

            // 모든 테이블 가져오기
            $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // 시스템/손상된 테이블 건너뛰기
                if (strpos($table, 'RECOVER_YOUR_DATA') !== false) {
                    continue;
                }

                try {
                    $this->dumpTable($fp, $table);
                    $tablesCount++;
                } catch (PDOException $e) {
                    fwrite($fp, "-- 오류: 테이블 `$table` 백업 실패 - " . $e->getMessage() . "\n\n");
                }
            }

            fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
            fwrite($fp, "\n-- 백업 완료\n");
            fclose($fp);

            return ['success' => true, 'tables_count' => $tablesCount, 'error' => null];
        } catch (Exception $e) {
            fclose($fp);
            @unlink($filepath);
            return ['success' => false, 'tables_count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * 단일 테이블을 파일에 덤프 (unbuffered query + 배치 INSERT)
     */
    private function dumpTable($fp, string $table): void
    {
        fwrite($fp, "-- -------------------------------------------------\n");
        fwrite($fp, "-- 테이블 구조: `$table`\n");
        fwrite($fp, "-- -------------------------------------------------\n\n");
        fwrite($fp, "DROP TABLE IF EXISTS `$table`;\n");

        $createTable = $this->pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        fwrite($fp, $createTable['Create Table'] . ";\n\n");

        // unbuffered query로 한 행씩 가져오기 (메모리 절약)
        $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        try {
            $stmt = $this->pdo->query("SELECT * FROM `$table`");
            $columns = null;
            $batch = [];
            $hasData = false;

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!$hasData) {
                    $hasData = true;
                    $columns = array_keys($row);
                    fwrite($fp, "-- 데이터 덤프: `$table`\n");
                }

                $values = '(' . implode(', ', array_map(function ($val) {
                    if ($val === null) {
                        return 'NULL';
                    }
                    return $this->pdo->quote($val);
                }, array_values($row))) . ')';

                $batch[] = $values;

                if (count($batch) >= $this->batchSize) {
                    $this->writeBatchInsert($fp, $table, $columns, $batch);
                    $batch = [];
                }
            }

            // 남은 배치 기록
            if (!empty($batch)) {
                $this->writeBatchInsert($fp, $table, $columns, $batch);
            }

            if ($hasData) {
                fwrite($fp, "\n");
            }

            // 결과셋 해제 (unbuffered query 필수)
            $stmt->closeCursor();
        } finally {
            // buffered 모드 복원
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

    /**
     * 배치 INSERT 구문 기록
     */
    private function writeBatchInsert($fp, string $table, array $columns, array $batch): void
    {
        $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
        $sql .= implode(",\n", $batch) . ";\n";
        fwrite($fp, $sql);
    }

    /**
     * 스트리밍 방식으로 SQL 파일 복원
     *
     * @param string $filepath SQL 파일 경로
     * @return array ['success' => bool, 'executed' => int, 'errors' => array, 'error' => ?string]
     */
    public function restoreFromFile(string $filepath): array
    {
        if (!file_exists($filepath)) {
            return ['success' => false, 'executed' => 0, 'errors' => [], 'error' => '백업 파일이 존재하지 않습니다.'];
        }

        $fp = fopen($filepath, 'r');
        if ($fp === false) {
            return ['success' => false, 'executed' => 0, 'errors' => [], 'error' => '백업 파일을 열 수 없습니다.'];
        }

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS=0");

        $executed = 0;
        $errors = [];
        $current = '';
        $inString = false;
        $stringChar = '';

        try {
            while (!feof($fp)) {
                $chunk = fread($fp, 8192);
                if ($chunk === false) {
                    break;
                }

                $len = strlen($chunk);
                for ($i = 0; $i < $len; $i++) {
                    $char = $chunk[$i];

                    // 문자열 내부 처리
                    if ($inString) {
                        $current .= $char;
                        if ($char === '\\' && $i + 1 < $len) {
                            $current .= $chunk[++$i];
                            continue;
                        }
                        if ($char === $stringChar) {
                            if ($i + 1 < $len && $chunk[$i + 1] === $stringChar) {
                                $current .= $chunk[++$i];
                                continue;
                            }
                            $inString = false;
                        }
                        continue;
                    }

                    // 한 줄 주석 (--)
                    if ($char === '-' && $i + 1 < $len && $chunk[$i + 1] === '-') {
                        $endOfLine = strpos($chunk, "\n", $i);
                        if ($endOfLine === false) {
                            // 청크 끝까지 주석 → 나머지 건너뛰기
                            break;
                        }
                        $i = $endOfLine;
                        continue;
                    }

                    // 블록 주석
                    if ($char === '/' && $i + 1 < $len && $chunk[$i + 1] === '*') {
                        $endComment = strpos($chunk, '*/', $i + 2);
                        if ($endComment === false) {
                            // 청크 내에서 종료 못 찾음 → 다음 청크에서 처리
                            // 간단히 나머지 건너뛰기 (백업 SQL에 블록 주석은 거의 없음)
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
                            try {
                                $this->pdo->exec($trimmed);
                                $executed++;
                            } catch (PDOException $e) {
                                $errors[] = substr($e->getMessage(), 0, 200);
                                if (count($errors) > 500) {
                                    fclose($fp);
                                    $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                                    return [
                                        'success' => false,
                                        'executed' => $executed,
                                        'errors' => array_slice($errors, 0, 50),
                                        'error' => '복원 중 에러가 너무 많습니다. (500건 초과)'
                                    ];
                                }
                            }
                        }
                        $current = '';
                        continue;
                    }

                    $current .= $char;
                }
            }

            // 마지막 구문 처리
            $trimmed = trim($current);
            if (!empty($trimmed)) {
                try {
                    $this->pdo->exec($trimmed);
                    $executed++;
                } catch (PDOException $e) {
                    $errors[] = substr($e->getMessage(), 0, 200);
                }
            }

            fclose($fp);
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1");

            return [
                'success' => true,
                'executed' => $executed,
                'errors' => $errors,
                'error' => null
            ];
        } catch (Exception $e) {
            fclose($fp);
            try {
                $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            } catch (Exception $ignored) {
            }
            return [
                'success' => false,
                'executed' => $executed,
                'errors' => $errors,
                'error' => $e->getMessage()
            ];
        }
    }
}
