<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: admin_consignment.php');
    exit;
}

// 액션 처리 (헤더 출력 전에 처리)
$action = $_GET['action'] ?? '';

// 상태 변경 처리
if($action === 'change_status' && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    
    if(in_array($new_status, ['active', 'sold', 'inactive'])) {
        try {
            $stmt = $pdo->prepare("UPDATE board_consignment SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            header('Location: admin_consignment_view.php?id=' . $id . '&msg=status_changed');
            exit;
        } catch(PDOException $e) {
            $error = "상태 변경 중 오류가 발생했습니다.";
        }
    }
}

// 삭제 처리
if($action === 'delete') {
    try {
        $stmt = $pdo->prepare("DELETE FROM board_consignment WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: admin_consignment.php?msg=deleted');
        exit;
    } catch(PDOException $e) {
        $error = "삭제 중 오류가 발생했습니다.";
    }
}

// 위탁판매 정보 조회
try {
    $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
    $stmt->execute([$id]);
    $consignment = $stmt->fetch();
    
    if (!$consignment) {
        header('Location: admin_consignment.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: admin_consignment.php');
    exit;
}

$pageTitle = '위탁판매 상세 - ' . $consignment['title'];

// 추가 스타일 정의
$additionalStyles = '
.detail-section {
    background: #fff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.detail-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-size: 13px;
    color: #666;
    font-weight: 500;
}

.info-value {
    font-size: 15px;
    color: #333;
    font-weight: 500;
}

.content-section {
    margin-top: 32px;
    padding-top: 32px;
    border-top: 1px solid #E5E5E7;
}

.content-box {
    background: #F8F9FA;
    padding: 20px;
    border-radius: 8px;
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.6;
}

.status-form {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 24px;
}

.status-form select {
    padding: 8px 16px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
}

.btn-primary {
    background: #1A237E;
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #283593;
}

.btn-danger {
    background: #DC3545;
    color: white;
}

.btn-danger:hover {
    background: #C82333;
}

.btn-secondary {
    background: #6C757D;
    color: white;
}

.btn-secondary:hover {
    background: #5A6268;
}

.status-active {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-sold {
    background: #E3F2FD;
    color: #1976D2;
}

.status-inactive {
    background: #F5F5F7;
    color: #666;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}
';

require_once 'admin_head.php';
?>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg success">
                <?php
                if($_GET['msg'] === 'status_changed') echo "상태가 변경되었습니다.";
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1>위탁판매 상세</h1>
            <a href="admin_consignment.php" class="btn-back">← 목록으로</a>
        </div>
        
        <div class="detail-section">
            <div class="detail-header">
                <h2><?php echo htmlspecialchars($consignment['title']); ?></h2>
                <div class="action-buttons">
                    <a href="?action=delete&id=<?php echo $id; ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
                    <a href="admin_consignment.php" class="btn btn-secondary">목록</a>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">작성자</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['writer']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">회사명</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['company_name'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">카테고리</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['category'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">재고수량</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['stock_quantity'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">가격정보</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['price_info'] ?? '협의'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">소재지</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['location'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">담당자</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['contact_person'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">연락처</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['contact_phone'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">이메일</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['contact_email'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">등록일</span>
                    <span class="info-value"><?php echo date('Y-m-d H:i', strtotime($consignment['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">조회수</span>
                    <span class="info-value"><?php echo number_format($consignment['view_count']); ?>회</span>
                </div>
                <div class="info-item">
                    <span class="info-label">현재 상태</span>
                    <span class="info-value">
                        <?php
                        $statusClass = 'status-' . $consignment['status'];
                        $statusText = '';
                        switch($consignment['status']) {
                            case 'active':
                                $statusText = '판매중';
                                break;
                            case 'sold':
                                $statusText = '판매완료';
                                break;
                            case 'inactive':
                                $statusText = '비활성';
                                break;
                        }
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </span>
                </div>
            </div>
            
            <div class="content-section">
                <h3>상세 내용</h3>
                <div class="content-box">
                    <?php echo nl2br(htmlspecialchars($consignment['content'])); ?>
                </div>
            </div>
            
            <?php if ($consignment['attachment']): ?>
            <div class="content-section">
                <h3>첨부파일</h3>
                <div class="info-value">
                    <a href="../<?php echo htmlspecialchars($consignment['attachment']); ?>" 
                       download><?php echo basename($consignment['attachment']); ?></a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="content-section">
                <h3>상태 변경</h3>
                <form method="POST" action="?action=change_status&id=<?php echo $id; ?>" class="status-form">
                    <select name="status">
                        <option value="active" <?php echo $consignment['status'] === 'active' ? 'selected' : ''; ?>>판매중</option>
                        <option value="sold" <?php echo $consignment['status'] === 'sold' ? 'selected' : ''; ?>>판매완료</option>
                        <option value="inactive" <?php echo $consignment['status'] === 'inactive' ? 'selected' : ''; ?>>비활성</option>
                    </select>
                    <button type="submit" class="btn btn-primary">상태 변경</button>
                </form>
            </div>
        </div>

<?php require_once 'admin_tail.php'; ?>