<?php
/**
 * CSV 파일 미리보기 및 검증 AJAX 엔드포인트
 * 업로드 전 CSV 파일의 내용을 분석하여 INSERT/UPDATE/DELETE 건수 및 오류를 반환
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../../db.php';
require_once '../admin_check.php';

// JSON 응답 함수
function jsonResponse($success, $data = []) {
    echo json_encode(array_merge(['success' => $success], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, ['error' => '잘못된 요청입니다.']);
}

// CSRF 토큰 검증
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    jsonResponse(false, ['error' => '보안 토큰이 유효하지 않습니다.']);
}

// 파일 검증
if (!isset($_FILES['preview_file']) || $_FILES['preview_file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, ['error' => '파일 업로드에 실패했습니다.']);
}

$uploadedFile = $_FILES['preview_file'];

// 확장자 검증
$fileExt = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
if (!in_array($fileExt, ['csv', 'txt'])) {
    jsonResponse(false, ['error' => 'CSV 파일만 업로드 가능합니다.']);
}

// MIME 타입 검증
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($uploadedFile['tmp_name']);
$allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
if (!in_array($mimeType, $allowedMimes)) {
    jsonResponse(false, ['error' => '허용되지 않은 파일 형식입니다.']);
}

// 파일 크기 검증 (10MB)
$maxSize = 10 * 1024 * 1024;
if ($uploadedFile['size'] > $maxSize) {
    jsonResponse(false, ['error' => '파일 크기가 너무 큽니다. (최대 10MB)']);
}

// 미리보기 행 수 제한
$previewLimit = 10;

// 결과 초기화
$result = [
    'total_rows' => 0,
    'preview_data' => [],
    'summary' => [
        'insert' => 0,
        'update' => 0,
        'delete' => 0,
        'error' => 0
    ],
    'validation_errors' => [],
    'has_errors' => false
];

try {
    // CSV 파일 읽기
    $handle = fopen($uploadedFile['tmp_name'], 'r');
    if (!$handle) {
        jsonResponse(false, ['error' => '파일을 열 수 없습니다.']);
    }

    // BOM 제거
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    // 헤더 읽기
    $headers = fgetcsv($handle);
    if (!$headers) {
        jsonResponse(false, ['error' => 'CSV 헤더를 읽을 수 없습니다.']);
    }

    // 컬럼 수 확인
    $columnCount = count($headers);
    $isExtendedFormat = ($columnCount >= 35);
    $hasDeleteColumn = ($columnCount >= 36);

    // 기존 제품 ID 목록 (UPDATE 판별용)
    $existingIds = [];
    $stmt = $pdo->query("SELECT id FROM products");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingIds[$row['id']] = true;
    }

    // 유효한 카테고리 코드 목록
    $validCategories = [];
    $stmt = $pdo->query("SELECT category_code FROM product_categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $validCategories[$row['category_code']] = true;
    }

    // 데이터 분석
    $lineNumber = 2;
    $previewCount = 0;

    while (($data = fgetcsv($handle)) !== FALSE) {
        $result['total_rows']++;

        $rowErrors = [];
        $action = '';

        // 기본 필드 추출
        $id = isset($data[0]) ? trim($data[0]) : '';
        $category_code = isset($data[1]) ? trim($data[1]) : '';
        $product_name = isset($data[3]) ? trim($data[3]) : '';
        $specifications = isset($data[5]) ? trim($data[5]) : '';

        // 삭제 플래그 확인
        $deleteFlag = $hasDeleteColumn ? strtoupper(trim($data[35] ?? '')) : '';
        $isDeleteRequest = ($deleteFlag === 'Y');

        if ($isDeleteRequest) {
            // 삭제 요청
            if (!$id || !is_numeric($id)) {
                $rowErrors[] = '삭제하려면 유효한 ID가 필요합니다';
                $result['summary']['error']++;
            } elseif (!isset($existingIds[(int)$id])) {
                $rowErrors[] = '존재하지 않는 제품 ID입니다';
                $result['summary']['error']++;
            } else {
                $action = 'DELETE';
                $result['summary']['delete']++;
            }
        } else {
            // 필수 필드 검증
            if (empty($category_code)) {
                $rowErrors[] = '카테고리코드 누락';
            } elseif (!isset($validCategories[$category_code])) {
                $rowErrors[] = '존재하지 않는 카테고리코드';
            }
            if (empty($product_name)) {
                $rowErrors[] = '제품명 누락';
            }
            if (empty($specifications)) {
                $rowErrors[] = '규격 누락';
            }

            if (!empty($rowErrors)) {
                $result['summary']['error']++;
            } elseif ($id && is_numeric($id)) {
                if (isset($existingIds[(int)$id])) {
                    $action = 'UPDATE';
                    $result['summary']['update']++;
                } else {
                    $action = 'INSERT';
                    $result['summary']['insert']++;
                    $rowErrors[] = 'ID가 존재하지 않아 새 제품으로 추가됩니다';
                }
            } else {
                $action = 'INSERT';
                $result['summary']['insert']++;
            }
        }

        // 미리보기 데이터 수집 (처음 N개만)
        if ($previewCount < $previewLimit) {
            $result['preview_data'][] = [
                'line' => $lineNumber,
                'id' => $id,
                'category' => $category_code,
                'name' => mb_substr($product_name, 0, 30) . (mb_strlen($product_name) > 30 ? '...' : ''),
                'spec' => mb_substr($specifications, 0, 20) . (mb_strlen($specifications) > 20 ? '...' : ''),
                'action' => $action ?: 'ERROR',
                'errors' => $rowErrors
            ];
            $previewCount++;
        }

        // 오류가 있으면 validation_errors에 추가 (최대 20개)
        if (!empty($rowErrors) && count($result['validation_errors']) < 20) {
            $result['validation_errors'][] = [
                'line' => $lineNumber,
                'errors' => $rowErrors
            ];
        }

        $lineNumber++;
    }

    fclose($handle);

    // 오류 유무 플래그
    $result['has_errors'] = $result['summary']['error'] > 0;

    jsonResponse(true, $result);

} catch (Exception $e) {
    jsonResponse(false, ['error' => '파일 처리 중 오류: ' . $e->getMessage()]);
}
