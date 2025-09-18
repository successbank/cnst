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
$is_active = isset($input['is_active']) ? (bool)$input['is_active'] : false;

if (!$id) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    // 상태 업데이트
    $stmt = $pdo->prepare("UPDATE product_icons SET is_active = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$is_active ? 1 : 0, $id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => '상태가 변경되었습니다.',
            'is_active' => $is_active
        ]);
    } else {
        throw new Exception('상태 변경에 실패했습니다.');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}