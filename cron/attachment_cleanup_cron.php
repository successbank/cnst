#!/usr/bin/env php
<?php
/**
 * 게시판 첨부파일 30일 보관 후 자동 삭제 CRON 스크립트
 *
 * - uploads/{board}/ 하위의 mtime 30일 초과 파일을 삭제
 * - 30일 초과 게시글의 attachment 컬럼을 정리하여 죽은 다운로드 링크 방지
 *
 * 사용법 (crontab, 호스트에서 실행):
 *   # 매일 새벽 3시 30분
 *   30 3 * * * docker exec project1_php php /var/www/html/cron/attachment_cleanup_cron.php >> /home/cnst/www/html/webservice/logs/attachment_cleanup.log 2>&1
 *
 * 미리보기(삭제 없이 대상만 출력):
 *   docker exec project1_php php /var/www/html/cron/attachment_cleanup_cron.php --dry-run
 */

// CLI에서만 실행 허용
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

date_default_timezone_set('Asia/Seoul');

// ===== 설정 =====
$RETENTION_DAYS = 30;                 // 첨부 보관 기간(일)
$DRY_RUN = in_array('--dry-run', $argv, true);

$basePath   = dirname(__DIR__);       // /var/www/html
$uploadBase = $basePath . '/uploads/';

// 첨부가 저장되는 게시판 업로드 디렉토리
// 주의: notice(공지)/news(뉴스)는 관리자가 게시한 영구 콘텐츠이므로 자동삭제 대상에서 제외.
//       견적문의/위탁판매/판매의뢰(문의성 게시판) 첨부만 30일 후 삭제한다.
$boardDirs = ['quote', 'consignment', 'sales_request', 'brokerage'];
// 첨부 참조를 정리할 게시판 테이블 (sales_request/brokerage는 board_consignment로 통합)
$boardTables = ['board_quote', 'board_consignment'];

$cutoff = time() - ($RETENTION_DAYS * 86400);

function logmsg($m) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n";
}

logmsg(($DRY_RUN ? '[DRY-RUN] ' : '') . "첨부파일 자동정리 시작 (보관 {$RETENTION_DAYS}일, 삭제기준일 " . date('Y-m-d H:i:s', $cutoff) . ')');

// ===== 1단계: 파일 삭제 (mtime 30일 초과) =====
$deletedFiles = 0;
$freedBytes   = 0;
$failedFiles  = 0;

foreach ($boardDirs as $d) {
    $dir = $uploadBase . $d;
    if (!is_dir($dir)) {
        continue;
    }
    $entries = @scandir($dir);
    if ($entries === false) {
        logmsg("디렉토리 접근 실패: uploads/$d/");
        continue;
    }
    foreach ($entries as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        $path = $dir . '/' . $f;
        if (!is_file($path)) {
            continue;
        }
        $mtime = @filemtime($path);
        if ($mtime === false || $mtime >= $cutoff) {
            continue;
        }
        $sz = @filesize($path) ?: 0;
        if ($DRY_RUN) {
            $deletedFiles++;
            $freedBytes += $sz;
            logmsg("[예정] 삭제 uploads/$d/$f (" . round($sz / 1024) . 'KB, 수정일 ' . date('Y-m-d', $mtime) . ')');
        } elseif (@unlink($path)) {
            $deletedFiles++;
            $freedBytes += $sz;
            logmsg("삭제 uploads/$d/$f (" . round($sz / 1024) . 'KB, 수정일 ' . date('Y-m-d', $mtime) . ')');
        } else {
            $failedFiles++;
            logmsg("삭제 실패 uploads/$d/$f");
        }
    }
}

// ===== 2단계: 30일 초과 게시글의 attachment 참조 정리 (죽은 링크 방지) =====
$clearedRows = 0;
try {
    require_once $basePath . '/db.php';
    $pdo = getDB();

    foreach ($boardTables as $t) {
        try {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM `$t`
                 WHERE created_at < (NOW() - INTERVAL {$RETENTION_DAYS} DAY)
                   AND attachment IS NOT NULL AND attachment <> ''"
            );
            $stmt->execute();
            $cnt = (int)$stmt->fetchColumn();

            if ($cnt > 0 && !$DRY_RUN) {
                $upd = $pdo->prepare(
                    "UPDATE `$t` SET attachment = ''
                     WHERE created_at < (NOW() - INTERVAL {$RETENTION_DAYS} DAY)
                       AND attachment IS NOT NULL AND attachment <> ''"
                );
                $upd->execute();
            }
            $clearedRows += $cnt;
            logmsg(($DRY_RUN ? '[예정] ' : '') . "$t: 30일 초과 첨부참조 {$cnt}건 정리");
        } catch (Exception $e) {
            logmsg("$t 처리 오류: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    logmsg('DB 연결 오류(파일 삭제는 완료됨): ' . $e->getMessage());
}

logmsg(($DRY_RUN ? '[DRY-RUN] ' : '') . "완료 - 파일 {$deletedFiles}개 삭제(" . round($freedBytes / 1048576, 2) . 'MB), '
    . "삭제실패 {$failedFiles}개, DB 참조정리 {$clearedRows}건");
