<?php
// 데이터베이스 연결 먼저 포함
require_once '../db.php';
session_start();
require_once 'admin_check.php';

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// CSRF 토큰 검증 함수
function verify_csrf_token() {
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// 액션 처리 (header 출력 전에 처리)
$action = $_GET['action'] ?? 'list';

// 위탁판매 삭제 처리 (POST + CSRF 필수)
if($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    if (!verify_csrf_token()) {
        $error = "보안 토큰이 유효하지 않습니다.";
    } else {
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
}

// 일괄 삭제 처리 (POST + CSRF 필수)
if($action === 'bulk_delete' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_ids'])) {
    if (!verify_csrf_token()) {
        $error = "보안 토큰이 유효하지 않습니다.";
    } else {
        $ids = $_POST['selected_ids'];
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

// 일괄 상태 변경 처리 (POST + CSRF 필수)
if($action === 'bulk_status' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_ids']) && isset($_POST['status'])) {
    if (!verify_csrf_token()) {
        $error = "보안 토큰이 유효하지 않습니다.";
    } else {
        $ids = $_POST['selected_ids'];
        $status = $_POST['status'];

        if(in_array($status, ['active', 'sold', 'inactive'])) {
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';

            try {
                $stmt = $pdo->prepare("UPDATE board_consignment SET status = ? WHERE id IN ($placeholders)");
                $params = array_merge([$status], $ids);
                $stmt->execute($params);
                header('Location: admin_consignment.php?msg=bulk_status_changed');
                exit;
            } catch(PDOException $e) {
                $error = "일괄 상태 변경 중 오류가 발생했습니다.";
            }
        }
    }
}

// 상태 변경 처리 (POST + CSRF 필수)
if($action === 'toggle_status' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    if (!verify_csrf_token()) {
        $error = "보안 토큰이 유효하지 않습니다.";
    } else {
        $id = (int)$_POST['id'];
        $new_status = $_POST['status'] ?? 'active';

        if(in_array($new_status, ['active', 'sold', 'inactive'])) {
            try {
                $stmt = $pdo->prepare("UPDATE board_consignment SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $id]);
                header('Location: admin_consignment.php?msg=status_changed');
                exit;
            } catch(PDOException $e) {
                $error = "상태 변경 중 오류가 발생했습니다.";
            }
        }
    }
}

$pageTitle = '위탁판매 관리';

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

.data-table td:nth-child(2) {
    text-align: left;
}

.filter-form input[type="text"] {
    flex: 1;
    min-width: 200px;
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

.btn-view, .btn-delete {
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

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
}

input[type="checkbox"] {
    cursor: pointer;
}
';

require_once 'admin_head.php';

// 위탁판매 목록 가져오기 (기본 액션)
$consignments = [];
$totalPages = 0;
$total = 0;

try {
    // 기본 연결 확인
    if(!$pdo) {
        throw new Exception("데이터베이스 연결 실패");
    }
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // 검색 조건
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    
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
                    case 'deleted': echo "위탁판매가 삭제되었습니다."; break;
                    case 'status_changed': echo "상태가 변경되었습니다."; break;
                    case 'bulk_deleted': echo "선택한 위탁판매가 삭제되었습니다."; break;
                    case 'bulk_status_changed': echo "선택한 위탁판매의 상태가 변경되었습니다."; break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1>위탁판매 관리</h1>
            <p>위탁판매 제품을 확인하고 관리할 수 있습니다.</p>
        </div>
        
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <input type="text" name="search" placeholder="제목, 회사명, 작성자, 내용으로 검색" 
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <select name="status">
                    <option value="">전체 상태</option>
                    <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>판매중</option>
                    <option value="sold" <?php echo ($_GET['status'] ?? '') === 'sold' ? 'selected' : ''; ?>>판매완료</option>
                    <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>비활성</option>
                </select>
                <button type="submit">검색</button>
                <?php if(isset($_GET['search']) || isset($_GET['status'])): ?>
                    <a href="admin_consignment.php" style="padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">초기화</a>
                <?php endif; ?>
            </form>
        </div>
        
        <form id="bulkActionForm" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <div class="bulk-actions" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
                <button type="button" onclick="executeAction('bulk_delete')" class="btn btn-danger" style="padding: 8px 16px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer;">
                    선택 삭제
                </button>
                <select id="bulkStatus" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">상태 변경</option>
                    <option value="active">판매중</option>
                    <option value="sold">판매완료</option>
                    <option value="inactive">비활성</option>
                </select>
                <button type="button" onclick="executeAction('bulk_status')" class="btn btn-primary" style="padding: 8px 16px; background: #1428A0; color: white; border: none; border-radius: 5px; cursor: pointer;">
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
                            <th style="width: 23%">제목</th>
                            <th style="width: 13%">카테고리</th>
                            <th style="width: 10%">회사명</th>
                            <th style="width: 8%">작성자</th>
                            <th style="width: 10%">가격</th>
                            <th style="width: 10%">작성일</th>
                            <th style="width: 8%">상태</th>
                            <th style="width: 10%">관리</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($consignments)): ?>
                        <tr>
                            <td colspan="10" class="no-data">조회된 위탁판매가 없습니다.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($consignments as $consignment): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $consignment['id']; ?>" onchange="updateCount()">
                                </td>
                                <td><?php echo $consignment['id']; ?></td>
                                <td>
                                    <div class="product-title"><?php echo htmlspecialchars($consignment['title']); ?></div>
                                    <?php if(!empty($consignment['stock_quantity'])): ?>
                                        <div class="product-meta">수량: <?php echo htmlspecialchars($consignment['stock_quantity']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($consignment['category'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($consignment['company_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($consignment['writer']); ?></td>
                                <td><?php echo htmlspecialchars($consignment['price_info'] ?? '협의'); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($consignment['created_at'])); ?></td>
                                <td>
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
                                </td>
                                <td>
                                    <div class="action-links">
                                        <a href="admin_consignment_view.php?id=<?php echo $consignment['id']; ?>" class="btn-view">보기</a>
                                        <form method="POST" action="?action=delete" style="display:inline;"
                                              onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="id" value="<?php echo $consignment['id']; ?>">
                                            <button type="submit" class="btn-delete">삭제</button>
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
                <?php endif; ?>

                <?php if($currentGroup > 1): ?>
                    <a href="<?php echo $queryPrefix . (($currentGroup - 2) * $pagesPerGroup + 1); ?>" class="page-link" title="이전 10페이지">◀</a>
                <?php endif; ?>

                <?php if($page > 1): ?>
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
                <?php endif; ?>

                <?php if($currentGroup < $totalGroups): ?>
                    <a href="<?php echo $queryPrefix . ($currentGroup * $pagesPerGroup + 1); ?>" class="page-link" title="다음 10페이지">▶</a>
                <?php endif; ?>

                <?php if($page < $totalPages): ?>
                    <a href="<?php echo $queryPrefix . $totalPages; ?>" class="page-link" title="마지막 페이지">≫</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

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
        if (!confirm(`선택한 ${checkboxes.length}개의 위탁판매를 삭제하시겠습니까?`)) {
            return;
        }
    } else if (action === 'bulk_status') {
        const status = document.getElementById('bulkStatus').value;
        if (!status) {
            alert('변경할 상태를 선택해주세요.');
            return;
        }
        
        // 상태값을 폼에 추가
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

// 개별 삭제는 각 폼의 onsubmit에서 처리
</script>

<?php require_once 'admin_tail.php'; ?>