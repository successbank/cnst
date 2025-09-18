<?php
session_start();
require_once '../../db.php';

// 관리자 권한 확인
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => '인증되지 않은 요청입니다.']);
    exit;
}

// JSON 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    // 아이콘 정보 가져오기 (이미지 삭제를 위해)
    $stmt = $pdo->prepare("SELECT icon_image FROM product_icons WHERE id = ?");
    $stmt->execute([$id]);
    $icon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$icon) {
        throw new Exception('존재하지 않는 아이콘입니다.');
    }
    
    // 아이콘 삭제
    $stmt = $pdo->prepare("DELETE FROM product_icons WHERE id = ?");
    $stmt->execute([$id]);
    
    // 이미지 파일 삭제
    if ($icon['icon_image'] && file_exists('../../' . $icon['icon_image'])) {
        unlink('../../' . $icon['icon_image']);
    }
    
    // 트랜잭션 커밋
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => '아이콘이 삭제되었습니다.']);
    
} catch (Exception $e) {
    // 트랜잭션 롤백
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}