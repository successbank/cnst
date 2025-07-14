<?php
require_once '../db.php';
require_once '../member_check.php';

header('Content-Type: application/json');

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode(['updated' => false]);
    exit;
}

$member_id = $_SESSION['member_id'];
$user_name = $_SESSION['name'] ?? '';

try {
    // 세션에 저장된 마지막 체크 시간
    $lastCheck = $_SESSION['last_inquiry_check'] ?? date('Y-m-d H:i:s', strtotime('-1 hour'));
    
    // 마지막 체크 이후 업데이트된 문의가 있는지 확인
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM board_quote 
        WHERE (member_id = ? OR writer = ?) 
        AND updated_at > ?
    ");
    $stmt->execute([$member_id, $user_name, $lastCheck]);
    $updatedCount = $stmt->fetchColumn();
    
    // 현재 시간을 마지막 체크 시간으로 저장
    $_SESSION['last_inquiry_check'] = date('Y-m-d H:i:s');
    
    echo json_encode([
        'updated' => $updatedCount > 0,
        'count' => $updatedCount
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['updated' => false, 'error' => $e->getMessage()]);
}
?>