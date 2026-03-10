<?php
/**
 * 전자책 목차 API
 * 모바일 전자책 페이지에서 목차 데이터를 가져오기 위한 API
 */

header('Content-Type: application/json; charset=utf-8');
$allowed_origins = ['https://cnst.co.kr', 'https://www.cnst.co.kr'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Methods: GET');
}

require_once '../db.php';
require_once '../includes/input_validator.php';
if (!checkIpRateLimit('api_ebook_toc', 30, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = getDB();

    // 활성화된 목차만 조회 (카테고리별로 그룹화)
    $stmt = $pdo->query("
        SELECT
            id,
            category,
            category_icon,
            title,
            page_number,
            display_order
        FROM ebook_toc
        WHERE is_active = 1
        ORDER BY display_order, id
    ");

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 카테고리별로 그룹화
    $groupedData = [];
    foreach ($items as $item) {
        $category = $item['category'];

        if (!isset($groupedData[$category])) {
            $groupedData[$category] = [
                'category' => $category,
                'category_icon' => $item['category_icon'],
                'items' => []
            ];
        }

        $groupedData[$category]['items'][] = [
            'id' => (int)$item['id'],
            'title' => $item['title'],
            'page_number' => (int)$item['page_number']
        ];
    }

    // 배열로 변환 (카테고리 순서 유지)
    $result = array_values($groupedData);

    // 성공 응답
    echo json_encode([
        'success' => true,
        'data' => $result,
        'total_categories' => count($result),
        'total_items' => count($items),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("Ebook TOC DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Ebook TOC Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '서버 오류가 발생했습니다.'
    ], JSON_UNESCAPED_UNICODE);
}
?>
