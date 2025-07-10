<?php
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
';

require_once 'admin_head.php';

// 액션 처리
$action = $_GET['action'] ?? 'list';

// 위탁판매 삭제 처리
if($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM board_consignment WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: admin_consignment.php?msg=deleted');
        exit;
    } catch(PDOException $e) {
        $error = "삭제 중 오류가 발생했습니다.";
    }
}

// 상태 변경 처리
if($action === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = $_GET['status'] ?? 'active';
    
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
        $where .= " AND (title LIKE ? OR product_name LIKE ? OR company LIKE ? OR writer LIKE ?)";
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
    $error = "데이터베이스 오류: " . $e->getMessage();
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
                <input type="text" name="search" placeholder="제목, 제품명, 회사명, 작성자로 검색" 
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
        
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%">번호</th>
                        <th style="width: 25%">제목</th>
                        <th style="width: 15%">제품명</th>
                        <th style="width: 10%">회사명</th>
                        <th style="width: 8%">작성자</th>
                        <th style="width: 10%">가격</th>
                        <th style="width: 10%">작성일</th>
                        <th style="width: 7%">상태</th>
                        <th style="width: 10%">관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($consignments)): ?>
                        <tr>
                            <td colspan="9" class="no-data">조회된 위탁판매가 없습니다.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($consignments as $consignment): ?>
                            <tr>
                                <td><?php echo $consignment['id']; ?></td>
                                <td>
                                    <div class="product-title"><?php echo htmlspecialchars($consignment['title']); ?></div>
                                    <?php if(!empty($consignment['quantity'])): ?>
                                        <div class="product-meta">수량: <?php echo htmlspecialchars($consignment['quantity']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($consignment['product_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($consignment['company'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($consignment['writer']); ?></td>
                                <td><?php echo !empty($consignment['price']) ? number_format($consignment['price']) . '원' : '협의'; ?></td>
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
                                        <a href="?action=delete&id=<?php echo $consignment['id']; ?>" 
                                           class="btn-delete"
                                           onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $queryString = http_build_query(array_merge($_GET, ['page' => '']));
                for($i = 1; $i <= $totalPages; $i++):
                ?>
                    <a href="?<?php echo $queryString . $i; ?>" 
                       class="page-link <?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

<?php require_once 'admin_tail.php'; ?>