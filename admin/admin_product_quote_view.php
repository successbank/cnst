<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$quote_id) {
    header('Location: admin_product_quotes.php');
    exit;
}

// 견적서 정보 조회
$stmt = $pdo->prepare("SELECT pq.*, m.user_id as member_user_id, m.email as member_email 
                       FROM product_quotes pq 
                       LEFT JOIN members m ON pq.member_id = m.id 
                       WHERE pq.id = ?");
$stmt->execute([$quote_id]);
$quote = $stmt->fetch();

if (!$quote) {
    header('Location: admin_product_quotes.php');
    exit;
}

// 견적서 아이템 조회
$items_stmt = $pdo->prepare("SELECT * FROM product_quote_items WHERE quote_id = ? ORDER BY id");
$items_stmt->execute([$quote_id]);
$items = $items_stmt->fetchAll();

// 상태 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    try {
        $update_stmt = $pdo->prepare("UPDATE product_quotes SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$new_status, $admin_notes, $quote_id]);
        
        header('Location: admin_product_quote_view.php?id=' . $quote_id . '&success=1');
        exit;
    } catch (PDOException $e) {
        $error = "상태 업데이트 실패: " . $e->getMessage();
    }
}

$currentFile = basename($_SERVER['PHP_SELF']);
require_once 'admin_head.php';
?>

<div class="content">
    <div class="products-header">
        <h2>제품견적서 상세</h2>
        <div class="header-actions">
            <a href="admin_product_quotes.php" class="btn btn-secondary">목록으로</a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">정보가 성공적으로 업데이트되었습니다.</div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="detail-container">
        <div class="detail-section">
            <h3>견적 정보</h3>
            <table class="detail-table">
                <tr>
                    <th width="150">견적번호</th>
                    <td>#<?php echo $quote['id']; ?></td>
                    <th width="150">작성일시</th>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($quote['created_at'])); ?></td>
                </tr>
                <tr>
                    <th>상태</th>
                    <td>
                        <?php
                        $status_class = '';
                        $status_text = '';
                        switch($quote['status']) {
                            case 'pending':
                                $status_class = 'badge-warning';
                                $status_text = '대기중';
                                break;
                            case 'processing':
                                $status_class = 'badge-info';
                                $status_text = '처리중';
                                break;
                            case 'completed':
                                $status_class = 'badge-success';
                                $status_text = '완료';
                                break;
                            case 'cancelled':
                                $status_class = 'badge-danger';
                                $status_text = '취소';
                                break;
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </td>
                    <th>최종수정</th>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($quote['updated_at'])); ?></td>
                </tr>
            </table>
        </div>

        <div class="detail-section">
            <h3>고객 정보</h3>
            <table class="detail-table">
                <tr>
                    <th width="150">담당자명</th>
                    <td><?php echo htmlspecialchars($quote['customer_name']); ?></td>
                    <th width="150">회사명</th>
                    <td><?php echo htmlspecialchars($quote['company'] ?: '-'); ?></td>
                </tr>
                <tr>
                    <th>연락처</th>
                    <td><?php echo htmlspecialchars($quote['phone']); ?></td>
                    <th>이메일</th>
                    <td><?php echo htmlspecialchars($quote['email'] ?: '-'); ?></td>
                </tr>
                <?php if ($quote['member_id']): ?>
                <tr>
                    <th>회원ID</th>
                    <td colspan="3">
                        <?php echo htmlspecialchars($quote['member_user_id']); ?> 
                        (<?php echo htmlspecialchars($quote['member_email']); ?>)
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="detail-section">
            <h3>제품 목록</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="60">번호</th>
                        <th>제품명</th>
                        <th>규격</th>
                        <th width="100">수량</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td class="text-center"><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['specifications'] ?: '-'); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($quote['notes']): ?>
        <div class="detail-section">
            <h3>추가 요청사항</h3>
            <div class="notes-content">
                <?php echo nl2br(htmlspecialchars($quote['notes'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="detail-section">
            <h3>상태 관리</h3>
            <form method="POST" action="">
                <input type="hidden" name="update_status" value="1">
                
                <div class="form-group">
                    <label>상태 변경</label>
                    <select name="status" class="form-control" style="width: 200px;">
                        <option value="pending" <?php echo $quote['status'] === 'pending' ? 'selected' : ''; ?>>대기중</option>
                        <option value="processing" <?php echo $quote['status'] === 'processing' ? 'selected' : ''; ?>>처리중</option>
                        <option value="completed" <?php echo $quote['status'] === 'completed' ? 'selected' : ''; ?>>완료</option>
                        <option value="cancelled" <?php echo $quote['status'] === 'cancelled' ? 'selected' : ''; ?>>취소</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>관리자 메모</label>
                    <textarea name="admin_notes" class="form-control" rows="4"><?php echo htmlspecialchars($quote['admin_notes'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.products-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #4A90E2;
    color: white;
}

.btn-primary:hover {
    background: #357ABD;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.detail-container {
    max-width: 1000px;
}

.detail-section {
    background: white;
    padding: 25px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.detail-section h3 {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e5e7;
    font-size: 18px;
    color: #333;
    font-weight: 600;
}

.detail-table {
    width: 100%;
    border-collapse: collapse;
}

.detail-table th,
.detail-table td {
    padding: 12px;
    border-bottom: 1px solid #e5e5e7;
    text-align: left;
}

.detail-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
    width: 150px;
}

.detail-table td {
    color: #666;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #dee2e6;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.text-center {
    text-align: center;
}

.notes-content {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    line-height: 1.6;
    color: #666;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.badge-warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.badge-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.badge-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.badge-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #4A90E2;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.form-actions {
    margin-top: 20px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
    font-size: 14px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<?php require_once 'admin_tail.php'; ?>