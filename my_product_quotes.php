<?php
require_once 'member_check.php';
require_once 'db.php';
require_once 'includes/sub_layout.php';

// 로그인 체크
checkLogin();

$member_id = $_SESSION['member_id'] ?? '';

// 페이지네이션
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 제품견적서 조회
try {
    // 전체 개수
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM product_quotes WHERE member_id = ?");
    $count_stmt->execute([$member_id]);
    $total = $count_stmt->fetchColumn();
    
    // 목록 조회
    $stmt = $pdo->prepare("
        SELECT pq.*, 
               (SELECT COUNT(*) FROM product_quote_items WHERE quote_id = pq.id) as item_count,
               (SELECT SUM(quantity) FROM product_quote_items WHERE quote_id = pq.id) as total_quantity
        FROM product_quotes pq 
        WHERE member_id = ? 
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$member_id, $limit, $offset]);
    $quotes = $stmt->fetchAll();
    
    $totalPages = ceil($total / $limit);
    
} catch(Exception $e) {
    $quotes = [];
    $total = 0;
    $totalPages = 0;
}

$currentPage = 'mypage';
$pageTitle = '제품견적서 내역';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('제품견적서 내역', 'inquiries');

// 사이드바
myPageSidebar('inquiries');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>제품견적서 내역</h2>
        <p>요청하신 제품견적서의 진행 상황을 확인하실 수 있습니다.</p>
    </div>
    
    <div class="content-body">
        <div class="action-buttons">
            <a href="my_inquiries.php" class="btn btn-outline">← 전체 문의내역</a>
            <a href="my_quote_cart.php" class="btn btn-primary">새 견적 요청</a>
        </div>
        
        <?php if($total > 0): ?>
            <div class="inquiry-summary">
                <p>총 <strong><?php echo number_format($total); ?></strong>건의 제품견적서가 있습니다.</p>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="80">번호</th>
                        <th width="120">요청일</th>
                        <th>제품 정보</th>
                        <th width="100">제품수/수량</th>
                        <th width="100">상태</th>
                        <th width="120">관리자 메모</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $num = $total - $offset;
                    foreach ($quotes as $quote): 
                    ?>
                        <tr onclick="viewQuoteDetail(<?php echo $quote['id']; ?>)" style="cursor: pointer;">
                            <td class="text-center"><?php echo $num--; ?></td>
                            <td class="text-center"><?php echo date('Y-m-d', strtotime($quote['created_at'])); ?></td>
                            <td>
                                <?php 
                                $products = $quote['products'];
                                if (strlen($products) > 80) {
                                    echo htmlspecialchars(mb_substr($products, 0, 80)) . '...';
                                } else {
                                    echo htmlspecialchars($products);
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <?php echo $quote['item_count']; ?>개 / <?php echo $quote['total_quantity']; ?>
                            </td>
                            <td class="text-center">
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
                            <td class="text-center">
                                <?php if ($quote['admin_notes']): ?>
                                    <span class="has-note" title="<?php echo htmlspecialchars($quote['admin_notes']); ?>">있음</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- 페이지네이션 -->
            <?php if($totalPages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=1" class="page-link">처음</a>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-link">이전</a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $start + 4);
                    
                    for($i = $start; $i <= $end; $i++):
                    ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-link">다음</a>
                        <a href="?page=<?php echo $totalPages; ?>" class="page-link">마지막</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>요청하신 제품견적서가 없습니다.</p>
                <a href="my_quote_cart.php" class="btn btn-primary">제품견적서 작성하기</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- 상세보기 모달 -->
<div id="quoteDetailModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3>제품견적서 상세</h3>
            <button type="button" class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="quoteDetailContent">
            <!-- 동적으로 로드됨 -->
        </div>
    </div>
</div>

<style>
.action-buttons {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #cfe2ff;
    color: #084298;
}

.status-completed {
    background: #d1e7dd;
    color: #0f5132;
}

.status-cancelled {
    background: #f8d7da;
    color: #842029;
}

.has-note {
    color: #0066cc;
    cursor: help;
    text-decoration: underline;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    max-height: 90vh;
    overflow: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e5e7;
}

.modal-header h3 {
    margin: 0;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.close-btn:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
}

.detail-info {
    margin-bottom: 20px;
}

.detail-info h4 {
    font-size: 16px;
    margin-bottom: 10px;
    color: #333;
}

.detail-table {
    width: 100%;
    border-collapse: collapse;
}

.detail-table th,
.detail-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #e5e5e7;
}

.detail-table th {
    background: #f8f9fa;
    font-weight: 600;
    width: 120px;
}

.product-list {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin: 10px 0;
}

.admin-notes-box {
    background: #e3f2fd;
    padding: 15px;
    border-radius: 4px;
    margin-top: 10px;
}
</style>

<script>
function viewQuoteDetail(quoteId) {
    // AJAX로 상세 정보 로드
    fetch(`ajax/get_quote_detail.php?id=${quoteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('quoteDetailContent').innerHTML = data.html;
                document.getElementById('quoteDetailModal').style.display = 'flex';
            } else {
                alert('상세 정보를 불러올 수 없습니다.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('오류가 발생했습니다.');
        });
}

function closeModal() {
    document.getElementById('quoteDetailModal').style.display = 'none';
}

// 모달 외부 클릭 시 닫기
document.getElementById('quoteDetailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php 
endSubPage();
include 'tail.php'; 
?>