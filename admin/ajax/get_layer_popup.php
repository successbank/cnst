<?php
/**
 * 레이어팝업 정보 조회 API
 */
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $id = intval($_GET['id'] ?? 0);

    if (!$id) {
        throw new Exception('팝업 ID가 필요합니다.');
    }

    $stmt = $pdo->prepare("SELECT * FROM layer_popups WHERE id = ?");
    $stmt->execute([$id]);
    $popup = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$popup) {
        throw new Exception('팝업을 찾을 수 없습니다.');
    }

    echo json_encode([
        'success' => true,
        'popup' => $popup
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
