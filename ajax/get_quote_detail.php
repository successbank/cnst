<?php
require_once '../db.php';
require_once '../member_check.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$member_id = $_SESSION['member_id'] ?? '';

if (!$quote_id) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    // 견적서 정보 조회 (본인 것만)
    $stmt = $pdo->prepare("SELECT * FROM product_quotes WHERE id = ? AND member_id = ?");
    $stmt->execute([$quote_id, $member_id]);
    $quote = $stmt->fetch();
    
    if (!$quote) {
        echo json_encode(['success' => false, 'message' => '견적서를 찾을 수 없습니다.']);
        exit;
    }
    
    // 견적서 아이템 조회
    $items_stmt = $pdo->prepare("SELECT * FROM product_quote_items WHERE quote_id = ? ORDER BY id");
    $items_stmt->execute([$quote_id]);
    $items = $items_stmt->fetchAll();
    
    // HTML 생성
    ob_start();
    ?>
    <div class="detail-info">
        <h4>기본 정보</h4>
        <table class="detail-table">
            <tr>
                <th>요청일시</th>
                <td><?php echo date('Y-m-d H:i', strtotime($quote['created_at'])); ?></td>
            </tr>
            <tr>
                <th>상태</th>
                <td>
                    <?php
                    $status_class = '';
                    $status_text = '';
                    switch($quote['status']) {
                        case 'pending':
                            $status_class = 'status-pending';
                            $status_text = '대기중';
                            break;
                        case 'processing':
                            $status_class = 'status-processing';
                            $status_text = '처리중';
                            break;
                        case 'completed':
                            $status_class = 'status-completed';
                            $status_text = '완료';
                            break;
                        case 'cancelled':
                            $status_class = 'status-cancelled';
                            $status_text = '취소';
                            break;
                    }
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                </td>
            </tr>
            <tr>
                <th>담당자명</th>
                <td><?php echo htmlspecialchars($quote['customer_name']); ?></td>
            </tr>
            <tr>
                <th>회사명</th>
                <td><?php echo htmlspecialchars($quote['company'] ?: '-'); ?></td>
            </tr>
            <tr>
                <th>연락처</th>
                <td><?php echo htmlspecialchars($quote['phone']); ?></td>
            </tr>
            <tr>
                <th>이메일</th>
                <td><?php echo htmlspecialchars($quote['email'] ?: '-'); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="detail-info">
        <h4>제품 목록</h4>
        <div class="product-list">
            <?php foreach ($items as $index => $item): ?>
                <div style="margin-bottom: 10px;">
                    <strong><?php echo $index + 1; ?>. <?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                    규격: <?php echo htmlspecialchars($item['specifications'] ?: '-'); ?><br>
                    수량: <?php echo $item['quantity']; ?>개
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php if ($quote['notes']): ?>
    <div class="detail-info">
        <h4>추가 요청사항</h4>
        <div class="product-list">
            <?php echo nl2br(htmlspecialchars($quote['notes'])); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($quote['admin_notes']): ?>
    <div class="detail-info">
        <h4>관리자 답변</h4>
        <div class="admin-notes-box">
            <?php echo nl2br(htmlspecialchars($quote['admin_notes'])); ?>
        </div>
    </div>
    <?php endif; ?>
    <?php
    $html = ob_get_clean();
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '오류가 발생했습니다.']);
}
?>