<?php
// 데이터베이스 연결 먼저 포함
require_once '../db.php';
session_start();
require_once 'admin_check.php';

// CSRF: admin_check.php에서 공통 csrf.php를 통해 POST 시 자동 검증됨
$csrf_token = generateCsrfToken();

// 액션 처리 (header 출력 전에 처리)
$action = $_GET['action'] ?? 'list';

// 삭제 처리 (POST, CSRF는 admin_check.php에서 검증됨)
if($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM board_consignment WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: admin_consignment.php?msg=deleted');
        exit;
    } catch(PDOException $e) {
        $error = "삭제 중 오류가 발생했습니다.";
    }
}

// 일괄 삭제 처리
if($action === 'bulk_delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_ids'])) {
    $ids = array_map('intval', $_POST['selected_ids']);
    $ids = array_filter($ids, function($id) { return $id > 0; });

    if (!empty($ids)) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        try {
            $stmt = $pdo->prepare("DELETE FROM board_consignment WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            header('Location: admin_consignment.php?msg=bulk_deleted');
            exit;
        } catch(PDOException $e) {
            $error = "일괄 삭제 중 오류가 발생했습니다.";
        }
    }
}

// 일괄 상태 변경 처리
if($action === 'bulk_status' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_ids']) && isset($_POST['status'])) {
    $ids = array_map('intval', $_POST['selected_ids']);
    $ids = array_filter($ids, function($id) { return $id > 0; });
    $status = $_POST['status'];

    if(!empty($ids) && in_array($status, ['pending', 'approved', 'rejected', 'sold', 'inactive'])) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        try {
            $admin_id = $_SESSION['admin_id'] ?? null;
            if (in_array($status, ['approved', 'rejected'])) {
                $stmt = $pdo->prepare("UPDATE board_consignment SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id IN ($placeholders)");
                $params = array_merge([$status, $admin_id], $ids);
            } else {
                $stmt = $pdo->prepare("UPDATE board_consignment SET status = ? WHERE id IN ($placeholders)");
                $params = array_merge([$status], $ids);
            }
            $stmt->execute($params);
            header('Location: admin_consignment.php?msg=bulk_status_changed');
            exit;
        } catch(PDOException $e) {
            $error = "일괄 상태 변경 중 오류가 발생했습니다.";
        }
    }
}

// 일괄 승인 처리
if($action === 'bulk_approve' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_ids'])) {
    $ids = array_map('intval', $_POST['selected_ids']);
    $ids = array_filter($ids, function($id) { return $id > 0; });

    if (!empty($ids)) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        try {
            $admin_id = $_SESSION['admin_id'] ?? null;
            $stmt = $pdo->prepare("UPDATE board_consignment SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id IN ($placeholders)");
            $params = array_merge([$admin_id], $ids);
            $stmt->execute($params);
            header('Location: admin_consignment.php?msg=bulk_approved');
            exit;
        } catch(PDOException $e) {
            $error = "일괄 승인 중 오류가 발생했습니다.";
        }
    }
}

// 상태 변경 처리
if($action === 'toggle_status' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $new_status = $_POST['status'] ?? 'approved';

    if(in_array($new_status, ['pending', 'approved', 'rejected', 'sold', 'inactive'])) {
        try {
            $admin_id = $_SESSION['admin_id'] ?? null;
            if (in_array($new_status, ['approved', 'rejected'])) {
                $stmt = $pdo->prepare("UPDATE board_consignment SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
                $stmt->execute([$new_status, $admin_id, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE board_consignment SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $id]);
            }
            header('Location: admin_consignment.php?msg=status_changed');
            exit;
        } catch(PDOException $e) {
            $error = "상태 변경 중 오류가 발생했습니다.";
        }
    }
}

$pageTitle = '판매의뢰/중개판매 관리';

// 추가 스타일 정의
$additionalStyles = '
.data-table table {
    table-layout: fixed;
}

.data-table th {
    text-align: center;
}

.data-table td {
    text-align: center;
    word-break: break-word;
}

.data-table td:nth-child(3) {
    text-align: left;
}

.filter-form input[type="text"] {
    flex: 1;
    min-width: 200px;
}

.status-pending {
    background: #FFF3E0;
    color: #F57C00;
}

.status-approved {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-rejected {
    background: #FFEBEE;
    color: #C62828;
}

.status-sold {
    background: #E3F2FD;
    color: #1976D2;
}

.status-inactive {
    background: #F5F5F7;
    color: #666;
}

.type-steel {
    background: #E3F2FD;
    color: #1565C0;
}

.type-lumber {
    background: #FFF8E1;
    color: #F57F17;
}

.product-title {
    font-weight: 600;
}

.product-meta {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}

.bulk-actions {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.action-links {
    display: flex;
    gap: 5px;
    justify-content: center;
}

.btn-view, .btn-delete-sm {
    padding: 4px 8px;
    font-size: 12px;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.2s;
}

.btn-view {
    background: #17a2b8;
    color: white;
}

.btn-view:hover {
    background: #138496;
}

.btn-delete-sm {
    background: #dc3545;
    color: white;
    border: none;
    cursor: pointer;
}

.btn-delete-sm:hover {
    background: #c82333;
}

input[type="checkbox"] {
    cursor: pointer;
}

/* 상태 탭 */
.status-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 20px;
    border-bottom: 2px solid #E5E5E7;
    flex-wrap: wrap;
}

.status-tab {
    padding: 10px 18px;
    text-decoration: none;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.3s ease;
}

.status-tab:hover {
    color: #1A237E;
    background: #F8F9FA;
}

.status-tab.active {
    color: #1A237E;
    border-bottom-color: #1A237E;
    font-weight: 600;
}

.tab-count {
    background: #E5E5E7;
    color: #666;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    margin-left: 4px;
}

.status-tab.active .tab-count {
    background: #1A237E;
    color: white;
}

/* 카카오 발송 상태 배지 */
.kakao-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.kakao-badge.sent { background: #E8F5E9; color: #2E7D32; }
.kakao-badge.failed { background: #FFEBEE; color: #C62828; }
.kakao-badge.pending { background: #FFF3E0; color: #F57C00; }

/* 거절 모달 */
.reject-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
}

.reject-modal {
    background: white;
    border-radius: 12px;
    padding: 32px;
    max-width: 500px;
    width: 90%;
}

.reject-modal h3 {
    margin-bottom: 16px;
    font-size: 18px;
}

.reject-modal textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    min-height: 100px;
    resize: vertical;
    font-size: 14px;
    margin-bottom: 16px;
}

.reject-modal-buttons {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}
';

require_once 'admin_head.php';

// 목록 가져오기
$consignments = [];
$totalPages = 0;
$total = 0;

// 상태별 개수 조회
$statusCounts = [];
try {
    $countStmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM board_consignment GROUP BY status");
    while ($row = $countStmt->fetch()) {
        $statusCounts[$row['status']] = $row['cnt'];
    }
} catch(PDOException $e) {
    // 무시
}

$totalAll = array_sum($statusCounts);

try {
    if(!$pdo) {
        throw new Exception("데이터베이스 연결 실패");
    }

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // 검색 조건
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $item_type = $_GET['item_type'] ?? '';

    $where = "WHERE 1=1";
    $params = [];

    if($search) {
        $where .= " AND (title LIKE ? OR company_name LIKE ? OR writer LIKE ? OR content LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if($status) {
        $where .= " AND status = ?";
        $params[] = $status;
    }

    if($item_type) {
        $where .= " AND item_type = ?";
        $params[] = $item_type;
    }

    // 전체 개수 쿼리
    $countQuery = "SELECT COUNT(*) FROM board_consignment $where";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // 목록 조회 쿼리
    $listQuery = "SELECT * FROM board_consignment $where ORDER BY id DESC LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($listQuery);
    $stmt->execute($params);
    $consignments = $stmt->fetchAll();

    $totalPages = ceil($total / $limit);

} catch(PDOException $e) {
    $consignments = [];
    $totalPages = 0;
    $total = 0;
    error_log("admin_consignment DB error: " . $e->getMessage());
    $error = "데이터베이스 오류가 발생했습니다.";
} catch(Exception $e) {
    $consignments = [];
    $totalPages = 0;
    $total = 0;
    $error = "일반 오류: " . $e->getMessage();
}
?>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg success">
                <?php
                switch($_GET['msg']) {
                    case 'deleted': echo "게시글이 삭제되었습니다."; break;
                    case 'status_changed': echo "상태가 변경되었습니다."; break;
                    case 'bulk_deleted': echo "선택한 게시글이 삭제되었습니다."; break;
                    case 'bulk_status_changed': echo "선택한 게시글의 상태가 변경되었습니다."; break;
                    case 'bulk_approved': echo "선택한 판매의뢰가 승인되었습니다."; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="msg error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="page-header">
            <h1>판매의뢰/중개판매 관리</h1>
            <p>판매의뢰 승인/거절 및 중개판매 제품을 관리할 수 있습니다.</p>
        </div>

        <!-- 상태 탭 -->
        <div class="status-tabs">
            <a href="?<?php echo $item_type ? 'item_type='.urlencode($item_type).'&' : ''; ?><?php echo $search ? 'search='.urlencode($search).'&' : ''; ?>status="
               class="status-tab <?php echo $status == '' ? 'active' : ''; ?>">
                전체 <span class="tab-count"><?php echo $totalAll; ?></span>
            </a>
            <a href="?<?php echo $item_type ? 'item_type='.urlencode($item_type).'&' : ''; ?><?php echo $search ? 'search='.urlencode($search).'&' : ''; ?>status=pending"
               class="status-tab <?php echo $status == 'pending' ? 'active' : ''; ?>">
                대기중 <span class="tab-count"><?php echo $statusCounts['pending'] ?? 0; ?></span>
            </a>
            <a href="?<?php echo $item_type ? 'item_type='.urlencode($item_type).'&' : ''; ?><?php echo $search ? 'search='.urlencode($search).'&' : ''; ?>status=approved"
               class="status-tab <?php echo $status == 'approved' ? 'active' : ''; ?>">
                승인 <span class="tab-count"><?php echo $statusCounts['approved'] ?? 0; ?></span>
            </a>
            <a href="?<?php echo $item_type ? 'item_type='.urlencode($item_type).'&' : ''; ?><?php echo $search ? 'search='.urlencode($search).'&' : ''; ?>status=rejected"
               class="status-tab <?php echo $status == 'rejected' ? 'active' : ''; ?>">
                거절 <span class="tab-count"><?php echo $statusCounts['rejected'] ?? 0; ?></span>
            </a>
            <a href="?<?php echo $item_type ? 'item_type='.urlencode($item_type).'&' : ''; ?><?php echo $search ? 'search='.urlencode($search).'&' : ''; ?>status=sold"
               class="status-tab <?php echo $status == 'sold' ? 'active' : ''; ?>">
                판매완료 <span class="tab-count"><?php echo $statusCounts['sold'] ?? 0; ?></span>
            </a>
            <a href="?<?php echo $item_type ? 'item_type='.urlencode($item_type).'&' : ''; ?><?php echo $search ? 'search='.urlencode($search).'&' : ''; ?>status=inactive"
               class="status-tab <?php echo $status == 'inactive' ? 'active' : ''; ?>">
                비활성 <span class="tab-count"><?php echo $statusCounts['inactive'] ?? 0; ?></span>
            </a>
        </div>

        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <input type="text" name="search" placeholder="제목, 회사명, 작성자, 내용으로 검색"
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <?php if($status): ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
                <?php endif; ?>
                <select name="item_type">
                    <option value="">전체 유형</option>
                    <option value="steel" <?php echo ($item_type ?? '') === 'steel' ? 'selected' : ''; ?>>철강류</option>
                    <option value="lumber" <?php echo ($item_type ?? '') === 'lumber' ? 'selected' : ''; ?>>목재류</option>
                </select>
                <button type="submit">검색</button>
                <?php if(isset($_GET['search']) || isset($_GET['status']) || isset($_GET['item_type'])): ?>
                    <a href="admin_consignment.php" style="padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">초기화</a>
                <?php endif; ?>
            </form>
        </div>

        <form id="bulkActionForm" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="bulk-actions" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" onclick="executeAction('bulk_approve')" class="btn" style="padding: 8px 16px; background: #2E7D32; color: white; border: none; border-radius: 5px; cursor: pointer;" title="일괄 승인 시 카카오 알림톡은 발송되지 않습니다. 개별 상세 페이지에서 금액 입력 후 발송 가능합니다.">
                    일괄 승인
                </button>
                <button type="button" onclick="showBulkRejectModal()" class="btn" style="padding: 8px 16px; background: #C62828; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    일괄 거절
                </button>
                <button type="button" onclick="executeAction('bulk_delete')" class="btn" style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    선택 삭제
                </button>
                <select id="bulkStatus" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">상태 변경</option>
                    <option value="pending">대기중</option>
                    <option value="approved">승인</option>
                    <option value="sold">판매완료</option>
                    <option value="inactive">비활성</option>
                </select>
                <button type="button" onclick="executeAction('bulk_status')" class="btn" style="padding: 8px 16px; background: #1428A0; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    상태 변경
                </button>
                <span style="margin-left: auto; color: #666;">선택: <span id="selectedCount">0</span>개</span>
            </div>

            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 3%">
                                <input type="checkbox" id="selectAll" onchange="toggleAll(this)">
                            </th>
                            <th style="width: 5%">번호</th>
                            <th style="width: 18%">제목</th>
                            <th style="width: 7%">유형</th>
                            <th style="width: 9%">카테고리</th>
                            <th style="width: 8%">회사명</th>
                            <th style="width: 7%">작성자</th>
                            <th style="width: 9%">작성일</th>
                            <th style="width: 7%">상태</th>
                            <th style="width: 6%">발송</th>
                            <th style="width: 9%">심사일</th>
                            <th style="width: 11%">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($consignments)): ?>
                        <tr>
                            <td colspan="12" class="no-data">조회된 게시글이 없습니다.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($consignments as $consignment): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $consignment['id']; ?>" onchange="updateCount()">
                                </td>
                                <td><?php echo $consignment['id']; ?></td>
                                <td style="text-align: left;">
                                    <div class="product-title"><?php echo htmlspecialchars($consignment['title']); ?></div>
                                    <?php if(!empty($consignment['stock_quantity'])): ?>
                                        <div class="product-meta">수량: <?php echo htmlspecialchars($consignment['stock_quantity']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge type-<?php echo $consignment['item_type']; ?>" style="font-size: 11px;">
                                        <?php echo $consignment['item_type'] === 'steel' ? '철강류' : '목재류'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($consignment['category'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($consignment['company_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($consignment['writer']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($consignment['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $statusClass = 'status-' . $consignment['status'];
                                    $statusText = '';
                                    switch($consignment['status']) {
                                        case 'pending': $statusText = '대기중'; break;
                                        case 'approved': $statusText = '승인'; break;
                                        case 'rejected': $statusText = '거절'; break;
                                        case 'sold': $statusText = '판매완료'; break;
                                        case 'inactive': $statusText = '비활성'; break;
                                        default: $statusText = $consignment['status'];
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $kakaoStatus = $consignment['kakao_send_status'] ?? null;
                                    if ($kakaoStatus) {
                                        $kakaoClass = $kakaoStatus;
                                        $kakaoText = '';
                                        switch($kakaoStatus) {
                                            case 'sent': $kakaoText = '발송'; break;
                                            case 'failed': $kakaoText = '실패'; break;
                                            case 'pending': $kakaoText = '대기'; break;
                                            default: $kakaoText = $kakaoStatus;
                                        }
                                        echo '<span class="kakao-badge ' . $kakaoClass . '">' . $kakaoText . '</span>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php echo $consignment['reviewed_at'] ? date('Y-m-d', strtotime($consignment['reviewed_at'])) : '-'; ?>
                                </td>
                                <td>
                                    <div class="action-links">
                                        <a href="admin_consignment_view.php?id=<?php echo $consignment['id']; ?>" class="btn-view">보기</a>
                                        <form method="POST" action="?action=delete" style="display:inline;"
                                              onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="id" value="<?php echo $consignment['id']; ?>">
                                            <button type="submit" class="btn-delete-sm">삭제</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </form>

        <?php if($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $queryParams = $_GET;
                unset($queryParams['page']);
                $queryString = http_build_query($queryParams);
                $queryPrefix = $queryString ? '?' . $queryString . '&page=' : '?page=';

                $pagesPerGroup = 10;
                $currentGroup = ceil($page / $pagesPerGroup);
                $totalGroups = ceil($totalPages / $pagesPerGroup);
                $groupStart = ($currentGroup - 1) * $pagesPerGroup + 1;
                $groupEnd = min($currentGroup * $pagesPerGroup, $totalPages);
                ?>

                <?php if($page > 1): ?>
                    <a href="<?php echo $queryPrefix . 1; ?>" class="page-link" title="첫 페이지">≪</a>
                    <a href="<?php echo $queryPrefix . ($page - 1); ?>" class="page-link" title="이전 페이지">＜</a>
                <?php endif; ?>

                <?php for($i = $groupStart; $i <= $groupEnd; $i++): ?>
                    <a href="<?php echo $queryPrefix . $i; ?>"
                       class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if($page < $totalPages): ?>
                    <a href="<?php echo $queryPrefix . ($page + 1); ?>" class="page-link" title="다음 페이지">＞</a>
                    <a href="<?php echo $queryPrefix . $totalPages; ?>" class="page-link" title="마지막 페이지">≫</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

<!-- 일괄 거절 모달 -->
<div class="reject-modal-overlay" id="bulkRejectModal">
    <div class="reject-modal">
        <h3>일괄 거절 사유 입력</h3>
        <textarea id="bulkRejectReason" placeholder="거절 사유를 입력해주세요..."></textarea>
        <div class="reject-modal-buttons">
            <button type="button" onclick="closeBulkRejectModal()" style="padding: 8px 16px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer;">취소</button>
            <button type="button" onclick="executeBulkReject()" style="padding: 8px 16px; background: #C62828; color: white; border: none; border-radius: 5px; cursor: pointer;">거절 확인</button>
        </div>
    </div>
</div>

<script>
function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateCount();
}

function updateCount() {
    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]:checked');
    document.getElementById('selectedCount').textContent = checkboxes.length;
}

function executeAction(action) {
    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]:checked');

    if (checkboxes.length === 0) {
        alert('선택한 항목이 없습니다.');
        return;
    }

    if (action === 'bulk_delete') {
        if (!confirm(`선택한 ${checkboxes.length}개의 게시글을 삭제하시겠습니까?`)) {
            return;
        }
    } else if (action === 'bulk_approve') {
        if (!confirm(`선택한 ${checkboxes.length}개의 판매의뢰를 승인하시겠습니까?\n\n※ 카카오 알림톡은 개별 상세 페이지에서 금액 입력 후 발송 가능합니다.`)) {
            return;
        }
    } else if (action === 'bulk_status') {
        const status = document.getElementById('bulkStatus').value;
        if (!status) {
            alert('변경할 상태를 선택해주세요.');
            return;
        }

        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        document.getElementById('bulkActionForm').appendChild(statusInput);
    }

    const form = document.getElementById('bulkActionForm');
    form.action = `admin_consignment.php?action=${action}`;
    form.submit();
}

function showBulkRejectModal() {
    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]:checked');
    if (checkboxes.length === 0) {
        alert('선택한 항목이 없습니다.');
        return;
    }
    document.getElementById('bulkRejectModal').style.display = 'flex';
}

function closeBulkRejectModal() {
    document.getElementById('bulkRejectModal').style.display = 'none';
}

function executeBulkReject() {
    const reason = document.getElementById('bulkRejectReason').value.trim();
    if (!reason) {
        alert('거절 사유를 입력해주세요.');
        return;
    }

    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]:checked');
    const csrfToken = '<?php echo $csrf_token; ?>';

    // 각 선택된 항목에 대해 거절 처리
    const ids = Array.from(checkboxes).map(cb => cb.value);

    Promise.all(ids.map(id => {
        const formData = new FormData();
        formData.append('action', 'reject');
        formData.append('id', id);
        formData.append('reject_reason', reason);
        formData.append('csrf_token', csrfToken);

        return fetch('../ajax/admin_review_consignment.php', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        });
    })).then(() => {
        closeBulkRejectModal();
        location.reload();
    }).catch(() => {
        alert('처리 중 오류가 발생했습니다.');
    });
}
</script>

<?php require_once 'admin_tail.php'; ?>
