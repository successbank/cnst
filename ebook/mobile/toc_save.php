<?php
/**
 * 목차 편집 API
 * - 비밀번호 검증
 * - toc_data.json 저장
 */

header('Content-Type: application/json; charset=utf-8');
$allowed_origins = ['https://cnst.co.kr', 'https://www.cnst.co.kr'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

// 설정
define('EDIT_PASSWORD', 'cnst2024');
define('TOC_FILE', __DIR__ . '/toc_data.json');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

// JSON 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$action = $input['action'];

// 비밀번호 검증
if ($action === 'verify_password') {
    $password = isset($input['password']) ? $input['password'] : '';

    if ($password === EDIT_PASSWORD) {
        echo json_encode(['success' => true, 'message' => '인증 성공']);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '비밀번호가 일치하지 않습니다.']);
    }
    exit;
}

// 데이터 저장
if ($action === 'save') {
    $password = isset($input['password']) ? $input['password'] : '';
    $tocData = isset($input['data']) ? $input['data'] : null;

    // 비밀번호 재검증
    if ($password !== EDIT_PASSWORD) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => '비밀번호가 일치하지 않습니다.']);
        exit;
    }

    // 데이터 검증
    if (!$tocData || !is_array($tocData)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '유효하지 않은 데이터입니다.']);
        exit;
    }

    // 데이터 구조 검증
    $validationResult = validateTocStructure($tocData);
    if ($validationResult !== true) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $validationResult]);
        exit;
    }

    // 백업 생성
    if (file_exists(TOC_FILE)) {
        $backupFile = __DIR__ . '/toc_data_backup_' . date('Ymd_His') . '.json';
        copy(TOC_FILE, $backupFile);

        // 오래된 백업 정리 (최근 5개만 유지)
        cleanupOldBackups(__DIR__, 5);
    }

    // JSON 저장
    $jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
    $result = file_put_contents(TOC_FILE, json_encode($tocData, $jsonOptions));

    if ($result === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => '파일 저장에 실패했습니다.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
    exit;
}

// 알 수 없는 액션
http_response_code(400);
echo json_encode(['success' => false, 'message' => '알 수 없는 요청입니다.']);

/**
 * 목차 데이터 구조 검증
 */
function validateTocStructure($data) {
    if (!is_array($data)) {
        return '데이터는 배열이어야 합니다.';
    }

    foreach ($data as $index => $category) {
        // 필수 필드 검증
        if (!isset($category['category']) || empty(trim($category['category']))) {
            return '카테고리 ' . ($index + 1) . '번: 카테고리 이름이 필요합니다.';
        }

        if (!isset($category['icon']) || empty($category['icon'])) {
            return '카테고리 "' . $category['category'] . '": 아이콘이 필요합니다.';
        }

        if (!isset($category['items']) || !is_array($category['items'])) {
            return '카테고리 "' . $category['category'] . '": items 배열이 필요합니다.';
        }

        // 아이템 검증
        foreach ($category['items'] as $itemIndex => $item) {
            if (!isset($item['title']) || empty(trim($item['title']))) {
                return '카테고리 "' . $category['category'] . '" 항목 ' . ($itemIndex + 1) . '번: 제목이 필요합니다.';
            }

            if (!isset($item['page']) || !is_numeric($item['page']) || $item['page'] < 1) {
                return '카테고리 "' . $category['category'] . '" 항목 "' . $item['title'] . '": 유효한 페이지 번호가 필요합니다.';
            }
        }
    }

    return true;
}

/**
 * 오래된 백업 파일 정리
 */
function cleanupOldBackups($dir, $keepCount) {
    $pattern = $dir . '/toc_data_backup_*.json';
    $backups = glob($pattern);

    if (count($backups) > $keepCount) {
        // 날짜순 정렬 (오래된 것 먼저)
        sort($backups);

        // 오래된 백업 삭제
        $deleteCount = count($backups) - $keepCount;
        for ($i = 0; $i < $deleteCount; $i++) {
            unlink($backups[$i]);
        }
    }
}
