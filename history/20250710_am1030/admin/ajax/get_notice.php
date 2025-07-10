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
    // 공지사항 조회
    $stmt = $pdo->prepare("SELECT * FROM board_notice WHERE id = ?");
    $stmt->execute([$id]);
    $notice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($notice) {
        // 날짜 포맷
        $notice['created_at'] = date('Y-m-d H:i:s', strtotime($notice['created_at']));
        
        echo json_encode([
            'success' => true,
            'notice' => $notice
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '공지사항을 찾을 수 없습니다.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류가 발생했습니다.'
    ]);
}
?>