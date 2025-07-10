<?php
// 세션 체크
require_once '../admin_check.php';
require_once '../../db.php';

// JSON 응답 헤더
header('Content-Type: application/json');

// ID 확인
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

$id = (int)$_GET['id'];

try {
    // 뉴스 조회 및 조회수 증가
    $stmt = $pdo->prepare("UPDATE board_news SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$id]);
    
    $stmt = $pdo->prepare("SELECT * FROM board_news WHERE id = ?");
    $stmt->execute([$id]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($news) {
        // 날짜 포맷
        $news['created_at'] = date('Y-m-d H:i:s', strtotime($news['created_at']));
        
        echo json_encode([
            'success' => true,
            'news' => $news
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '뉴스를 찾을 수 없습니다.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류가 발생했습니다.'
    ]);
}
?>