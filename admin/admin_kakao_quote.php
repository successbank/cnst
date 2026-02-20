<?php
$pageTitle = '견적문의 카카오톡 알림';

// 추가 스타일 정의
$additionalStyles = '
.kakao-tabs {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
    overflow: hidden;
}

.tab-menu {
    display: flex;
    border-bottom: 1px solid #E5E5E7;
}

.tab-item {
    flex: 1;
    padding: 16px 24px;
    text-align: center;
    text-decoration: none;
    color: #666;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.tab-item:hover {
    background: #F5F5F7;
    color: #1A237E;
}

.tab-item.active {
    color: #1A237E;
    border-bottom-color: #1A237E;
    background: #F8F9FA;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    text-align: center;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: #666;
}

.stat-total { color: #1A237E; }
.stat-sent { color: #2E7D32; }
.stat-failed { color: #C62828; }
.stat-pending { color: #F57C00; }

.status-sent {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-failed {
    background: #FFEBEE;
    color: #C62828;
}

.status-pending {
    background: #FFF3E0;
    color: #F57C00;
}

.message-preview {
    max-width: 300px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
}

.message-preview:hover {
    white-space: normal;
}

.data-table table {
    min-width: 900px;
}
';

require_once 'admin_head.php';
require_once '../includes/KakaoNotificationService.php';

$kakaoService = new KakaoNotificationService($pdo);

// 페이지네이션
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 검색 조건
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// 조건 쿼리 생성
$where = ["n.board_type = 'quote'"];
$params = [];

if ($search) {
    $where[] = "(n.recipient_name LIKE ? OR n.recipient_phone LIKE ? OR n.message_content LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($status) {
    $where[] = "n.status = ?";
    $params[] = $status;
}

if ($dateFrom) {
    $where[] = "n.created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if ($dateTo) {
    $where[] = "n.created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

$whereClause = implode(" AND ", $where);

// 전체 개수 조회
$countQuery = "SELECT COUNT(*) FROM kakao_notifications n WHERE $whereClause";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// 데이터 조회
$query = "
    SELECT n.*, q.title as quote_title, q.company 
    FROM kakao_notifications n
    LEFT JOIN board_quote q ON n.board_id = q.id
    WHERE $whereClause
    ORDER BY n.created_at DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// 통계
$stats = $kakaoService->getStatistics('quote', $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59');
?>

        <div class="page-header">
            <h1>카카오톡 관리</h1>
            <p>카카오톡 알림 발송 현황을 확인하고 관리합니다.</p>
        </div>
        
        <!-- 탭 메뉴 -->
        <div class="kakao-tabs">
            <div class="tab-menu">
                <a href="admin_kakao.php" class="tab-item">대시보드</a>
                <a href="admin_kakao_quote.php" class="tab-item active">견적문의 알림</a>
                <a href="admin_kakao_consignment.php" class="tab-item">위탁판매 알림</a>
                <a href="admin_kakao_settings.php" class="tab-item">설정</a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value stat-total"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">전체 발송</div>
            </div>
            <div class="stat-card">
                <div class="stat-value stat-sent"><?php echo $stats['sent'] ?? 0; ?></div>
                <div class="stat-label">발송 성공</div>
            </div>
            <div class="stat-card">
                <div class="stat-value stat-failed"><?php echo $stats['failed'] ?? 0; ?></div>
                <div class="stat-label">발송 실패</div>
            </div>
            <div class="stat-card">
                <div class="stat-value stat-pending"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">대기중</div>
            </div>
        </div>
        
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <input type="date" name="date_from" value="<?php echo $dateFrom; ?>">
                <span>~</span>
                <input type="date" name="date_to" value="<?php echo $dateTo; ?>">
                <select name="status">
                    <option value="">전체 상태</option>
                    <option value="sent" <?php echo $status === 'sent' ? 'selected' : ''; ?>>발송완료</option>
                    <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>발송실패</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>대기중</option>
                </select>
                <input type="text" name="search" placeholder="수신자명, 전화번호 검색" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">검색</button>
                <?php if($search || $status): ?>
                <a href="admin_kakao_quote.php" style="padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">초기화</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th width="150">발송일시</th>
                        <th width="150">견적문의</th>
                        <th width="120">수신자</th>
                        <th width="120">전화번호</th>
                        <th>메시지 내용</th>
                        <th width="100">상태</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 60px; color: #999;">
                            조회된 알림 내역이 없습니다.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?></td>
                            <td>
                                <?php if ($notif['quote_title']): ?>
                                    <a href="admin_quote_view.php?id=<?php echo $notif['board_id']; ?>" style="color: #1A237E;">
                                        <?php echo htmlspecialchars($notif['quote_title']); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">삭제된 문의</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($notif['recipient_name']); ?>
                                <?php if ($notif['company']): ?>
                                    <br><small style="color: #666;"><?php echo htmlspecialchars($notif['company']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($notif['recipient_phone']); ?></td>
                            <td>
                                <div class="message-preview" title="클릭하여 전체 내용 보기">
                                    <?php echo nl2br(htmlspecialchars($notif['message_content'])); ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $notif['status']; ?>">
                                    <?php 
                                    switch($notif['status']) {
                                        case 'sent': echo '발송완료'; break;
                                        case 'failed': echo '발송실패'; break;
                                        case 'pending': echo '대기중'; break;
                                    }
                                    ?>
                                </span>
                                <?php if ($notif['error_message']): ?>
                                    <br><small style="color: #C62828;"><?php echo htmlspecialchars($notif['error_message']); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&<?php echo htmlspecialchars(http_build_query(array_diff_key($_GET, ['page' => ''])), ENT_QUOTES, 'UTF-8'); ?>"
                   class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

<?php require_once 'admin_tail.php'; ?>