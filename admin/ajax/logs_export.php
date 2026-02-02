<?php
/**
 * 로그 CSV 내보내기
 * GET: log_type, date_from, date_to, search
 */

require_once '../admin_check.php';
require_once '../../db.php';
require_once '../../includes/LogService.php';

try {
    $logService = new LogService($pdo);

    $logType = $_GET['log_type'] ?? 'login';
    $filters = [
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null,
        'search' => $_GET['search'] ?? '',
        'page' => 1,
        'per_page' => 50000 // 최대 5만 건
    ];

    // 유효한 로그 유형인지 확인
    $logTypes = $logService->getLogTypes();
    if (!isset($logTypes[$logType])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo '유효하지 않은 로그 유형입니다.';
        exit;
    }

    // 데이터 조회
    $result = $logService->getLogs($logType, $filters);
    $logs = $result['logs'];

    if (empty($logs)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo '내보낼 데이터가 없습니다.';
        exit;
    }

    // 파일명 생성
    $logTypeName = $logTypes[$logType];
    $filename = "logs_{$logType}_" . date('Y-m-d_His') . '.csv';

    // CSV 헤더 설정
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    // 출력 스트림
    $output = fopen('php://output', 'w');

    // BOM 추가 (Excel UTF-8 호환)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // 헤더 행 작성
    $headers = getExportHeaders($logType);
    fputcsv($output, $headers);

    // 데이터 행 작성
    foreach ($logs as $log) {
        $row = getExportRow($logType, $log);
        fputcsv($output, $row);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo '내보내기 중 오류가 발생했습니다: ' . $e->getMessage();
    exit;
}

/**
 * 로그 유형별 CSV 헤더 반환
 */
function getExportHeaders($logType) {
    switch ($logType) {
        case 'login':
            return ['ID', '일시', '회원명', '회원ID', 'IP주소', 'User-Agent'];
        case 'delete':
            return ['ID', '일시', '제품ID', '제품명', '카테고리', '삭제자', '삭제방법'];
        case 'backup':
            return ['ID', '일시', '파일명', '크기(bytes)', '크기', '유형', '상태', '테이블수'];
        case 'email':
            return ['ID', '생성일시', '수신자', '제목', '상태', '발송일시'];
        case 'calculator':
            return ['ID', '일시', '제품ID', '제품명', '카테고리', '규격', '재질', 'IP주소'];
        default:
            return [];
    }
}

/**
 * 로그 유형별 CSV 행 데이터 반환
 */
function getExportRow($logType, $log) {
    switch ($logType) {
        case 'login':
            return [
                $log['id'] ?? '',
                $log['login_date'] ?? '',
                $log['member_name'] ?? '',
                $log['member_user_id'] ?? '',
                $log['login_ip'] ?? '',
                $log['user_agent'] ?? ''
            ];
        case 'delete':
            return [
                $log['id'] ?? '',
                $log['deleted_at'] ?? '',
                $log['product_id'] ?? '',
                $log['product_name'] ?? '',
                $log['category_code'] ?? '',
                $log['deleted_by'] ?? '',
                $log['deleted_via'] ?? ''
            ];
        case 'backup':
            return [
                $log['id'] ?? '',
                $log['created_at'] ?? '',
                $log['filename'] ?? '',
                $log['file_size'] ?? 0,
                $log['file_size_formatted'] ?? '',
                $log['backup_type'] === 'auto' ? '자동' : '수동',
                $log['status_label'] ?? $log['status'] ?? '',
                $log['tables_count'] ?? 0
            ];
        case 'email':
            return [
                $log['id'] ?? '',
                $log['created_at'] ?? '',
                $log['recipient'] ?? '',
                $log['subject'] ?? '',
                $log['status_label'] ?? $log['status'] ?? '',
                $log['sent_at'] ?? ''
            ];
        case 'calculator':
            return [
                $log['id'] ?? '',
                $log['created_at'] ?? '',
                $log['product_id'] ?? '',
                $log['product_name'] ?? '',
                $log['category_code'] ?? '',
                $log['specification'] ?? '',
                $log['material'] ?? '',
                $log['user_ip'] ?? ''
            ];
        default:
            return [];
    }
}
