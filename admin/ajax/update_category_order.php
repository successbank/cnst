<?php
session_start();
require_once '../admin_check.php';
require_once '../../db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('잘못된 요청입니다.');
    }

    $order_data = json_decode($_POST['order_data'] ?? '[]', true);

    if (empty($order_data)) {
        throw new Exception('순서 데이터가 없습니다.');
    }

    // 트랜잭션 시작
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("UPDATE product_categories SET display_order = ? WHERE id = ?");
        
        foreach ($order_data as $item) {
            $stmt->execute([$item['order'], $item['id']]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '순서가 저장되었습니다.'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>