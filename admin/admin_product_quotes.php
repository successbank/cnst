<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

// 페이지 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 검색 파라미터
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// 요청자 구분 필터 (전체 / 회원 / 비회원)
$requester = isset($_GET['requester']) ? $_GET['requester'] : '';
if (!in_array($requester, ['member', 'guest'], true)) {
    $requester = '';
}

// 답변여부 필터 (전체 / 답변완료 / 미답변)
$replied = isset($_GET['replied']) ? $_GET['replied'] : '';
if (!in_array($replied, ['yes', 'no'], true)) {
    $replied = '';
}

// WHERE 절 구성
$where_clauses = [];
$params = [];

if ($search) {
    $where_clauses[] = "(pq.customer_name LIKE ? OR pq.company LIKE ? OR pq.phone LIKE ? OR pq.email LIKE ? OR pq.products LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($status !== '') {
    $where_clauses[] = "pq.status = ?";
    $params[] = $status;
}

if ($date_from) {
    $where_clauses[] = "DATE(pq.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_clauses[] = "DATE(pq.created_at) <= ?";
    $params[] = $date_to;
}

// 요청자 구분
if ($requester === 'member') {
    $where_clauses[] = "pq.member_id IS NOT NULL";
} elseif ($requester === 'guest') {
    $where_clauses[] = "pq.member_id IS NULL";
}

// 답변여부
if ($replied === 'yes') {
    $where_clauses[] = "(pq.admin_reply IS NOT NULL AND pq.admin_reply <> '')";
} elseif ($replied === 'no') {
    $where_clauses[] = "(pq.admin_reply IS NULL OR pq.admin_reply = '')";
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// 전체 개수 조회
$count_sql = "SELECT COUNT(*) FROM product_quotes pq $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetchColumn();
$total_pages = ceil($total_count / $per_page);

// 데이터 조회
// pq.* 에 member_id, source, admin_reply, replied_at 가 포함된다
$sql = "SELECT pq.*,
        m.name AS member_name, m.user_id AS member_user_id,
        (SELECT COUNT(*) FROM product_quote_items WHERE quote_id = pq.id) as item_count,
        (SELECT SUM(quantity) FROM product_quote_items WHERE quote_id = pq.id) as total_quantity
        FROM product_quotes pq
        LEFT JOIN members m ON m.id = pq.member_id
        $where_sql
        ORDER BY pq.created_at DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$quotes = $stmt->fetchAll();

// 상태 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $quote_id = (int)$_POST['quote_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $update_stmt = $pdo->prepare("UPDATE product_quotes SET status = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$new_status, $quote_id]);
        
        header('Location: admin_product_quotes.php?success=1');
        exit;
    } catch (PDOException $e) {
        error_log('[admin_product_quotes] 상태 업데이트 실패: ' . $e->getMessage());
        $error = "상태 업데이트에 실패했습니다. 잠시 후 다시 시도해 주세요.";
    }
}

$currentFile = basename($_SERVER['PHP_SELF']);
require_once 'admin_head.php';
?>

<div class="content">
    <div class="products-header">
        <h2>제품견적서 관리</h2>
        <div class="header-actions">
            <span class="data-info">총 <?php echo number_format($total_count); ?>건</span>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">상태가 성공적으로 업데이트되었습니다.</div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- 검색 폼 -->
    <div class="search-box">
        <form method="get" action="">
            <div class="search-form-group">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="고객명, 회사명, 연락처, 이메일, 제품명으로 검색" class="form-control">
                    </div>
                    <div class="form-group" style="width: 150px;">
                        <select name="status" class="form-control">
                            <option value="">전체 상태</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>접수</option>
                            <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>처리중</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>완료</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>취소</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="width: 150px;">
                        <select name="requester" class="form-control">
                            <option value="">요청자 전체</option>
                            <option value="member" <?php echo $requester === 'member' ? 'selected' : ''; ?>>회원</option>
                            <option value="guest" <?php echo $requester === 'guest' ? 'selected' : ''; ?>>비회원</option>
                        </select>
                    </div>
                    <div class="form-group" style="width: 150px;">
                        <select name="replied" class="form-control">
                            <option value="">답변 전체</option>
                            <option value="yes" <?php echo $replied === 'yes' ? 'selected' : ''; ?>>답변완료</option>
                            <option value="no" <?php echo $replied === 'no' ? 'selected' : ''; ?>>미답변</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="width: 150px;">
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="form-control" placeholder="시작일">
                    </div>
                    <div class="form-group" style="width: 150px;">
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="form-control" placeholder="종료일">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">검색</button>
                        <a href="admin_product_quotes.php" class="btn btn-secondary">초기화</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- 견적서 목록 -->
    <div class="data-table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="60">번호</th>
                    <th width="120">작성일시</th>
                    <th width="80">구분</th>
                    <th width="100">고객명</th>
                    <th width="150">회사명</th>
                    <th>제품 정보</th>
                    <th width="100">제품수/수량</th>
                    <th width="120">연락처</th>
                    <th width="100">상태</th>
                    <th width="90">답변</th>
                    <th width="100">작업</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotes as $quote): ?>
                    <tr>
                        <td class="text-center"><?php echo $quote['id']; ?></td>
                        <td class="text-center"><?php echo date('Y-m-d H:i', strtotime($quote['created_at'])); ?></td>
                        <td class="text-center">
                            <?php if (!empty($quote['member_id'])): ?>
                                <?php
                                // 회원 이름/아이디를 툴팁으로 표시 (탈퇴 등으로 조회 안 되면 회원번호)
                                if (!empty($quote['member_name']) || !empty($quote['member_user_id'])) {
                                    $member_tip = trim(($quote['member_name'] ?? '') . ' (' . ($quote['member_user_id'] ?? '') . ')');
                                } else {
                                    $member_tip = '회원번호 ' . (int)$quote['member_id'];
                                }
                                ?>
                                <span class="badge badge-info" title="<?php echo htmlspecialchars($member_tip); ?>">회원</span>
                            <?php else: ?>
                                <span class="badge badge-secondary" title="비회원 요청">비회원</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?php echo htmlspecialchars($quote['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($quote['company'] ?? ''); ?></td>
                        <td>
                            <?php 
                            $products = $quote['products'] ?? '';
                            if (strlen($products) > 100) {
                                echo htmlspecialchars(mb_substr($products, 0, 100)) . '...';
                            } else {
                                echo htmlspecialchars($products);
                            }
                            ?>
                        </td>
                        <td class="text-center">
                            <?php echo $quote['item_count']; ?>개 / <?php echo $quote['total_quantity']; ?>
                        </td>
                        <td class="text-center"><?php echo htmlspecialchars($quote['phone']); ?></td>
                        <td class="text-center">
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch($quote['status']) {
                                case 'pending':
                                    $status_class = 'badge-warning';
                                    $status_text = '접수';
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
                        <td class="text-center">
                            <?php $is_replied = isset($quote['admin_reply']) && trim((string)$quote['admin_reply']) !== ''; ?>
                            <?php if ($is_replied): ?>
                                <span class="badge badge-success"<?php echo !empty($quote['replied_at']) ? ' title="' . htmlspecialchars(date('Y-m-d H:i', strtotime($quote['replied_at']))) . ' 답변"' : ''; ?>>답변완료</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">미답변</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="admin_product_quote_view.php?id=<?php echo $quote['id']; ?>" class="btn btn-sm btn-info">상세</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($quotes)): ?>
                    <tr>
                        <td colspan="11" class="text-center" style="padding: 50px 0;">
                            검색 결과가 없습니다.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 페이지네이션 -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            // 현재 검색/필터 파라미터를 그대로 유지한다 (page 만 교체)
            $queryParams = $_GET;
            unset($queryParams['page'], $queryParams['success']);
            $queryString = http_build_query($queryParams);
            $queryPrefix = $queryString ? '?' . $queryString . '&page=' : '?page=';
            ?>
            <?php if ($page > 1): ?>
                <a href="<?php echo htmlspecialchars($queryPrefix . 1); ?>" class="page-link">처음</a>
                <a href="<?php echo htmlspecialchars($queryPrefix . ($page - 1)); ?>" class="page-link">이전</a>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $start_page + 4);

            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <a href="<?php echo htmlspecialchars($queryPrefix . $i); ?>"
                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="<?php echo htmlspecialchars($queryPrefix . ($page + 1)); ?>" class="page-link">다음</a>
                <a href="<?php echo htmlspecialchars($queryPrefix . $total_pages); ?>" class="page-link">마지막</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
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

.data-info {
    font-size: 16px;
    color: #666;
    font-weight: 500;
}

.search-box {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.search-form-group {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-row {
    display: flex;
    gap: 15px;
    align-items: center;
}

.form-group {
    flex: 1;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #4A90E2;
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

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-info:hover {
    background: #138496;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 13px;
}

.data-table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8f9fa;
    padding: 15px 10px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #dee2e6;
}

.data-table td {
    padding: 15px 10px;
    border-bottom: 1px solid #dee2e6;
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

.text-center {
    text-align: center;
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

/* 구분(비회원) / 답변(미답변) 표시용 회색 배지 */
.badge-secondary {
    background-color: #e9ecef;
    color: #495057;
    border: 1px solid #dee2e6;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 5px;
    margin-top: 30px;
}

.page-link {
    display: inline-block;
    padding: 8px 12px;
    background: white;
    border: 1px solid #dee2e6;
    color: #4A90E2;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.page-link:hover {
    background: #e9ecef;
}

.page-link.active {
    background: #4A90E2;
    color: white;
    border-color: #4A90E2;
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