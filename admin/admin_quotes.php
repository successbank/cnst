<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

// 액션 처리 (헤더 출력 전에 처리)
$action = $_GET['action'] ?? 'list';

// 견적문의 삭제 처리
if($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM board_quote WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: admin_quotes.php?msg=deleted');
        exit;
    } catch(PDOException $e) {
        $error = "삭제 중 오류가 발생했습니다.";
    }
}

// 답변완료 처리
if($action === 'toggle_answer' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("UPDATE board_quote SET is_answered = IF(is_answered = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: admin_quotes.php');
        exit;
    } catch(PDOException $e) {
        $error = "상태 변경 중 오류가 발생했습니다.";
    }
}

$pageTitle = '견적문의 관리';

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

.status-waiting {
    background: #FFF3E0;
    color: #F57C00;
}

.status-answered {
    background: #E8F5E9;
    color: #2E7D32;
}
';

require_once 'admin_head.php';

// 견적문의 목록 가져오기 (기본 액션)
$quotes = [];
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
        $where .= " AND (title LIKE ? OR company LIKE ? OR writer LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if($status === 'answered') {
        $where .= " AND is_answered = 1";
    } elseif($status === 'waiting') {
        $where .= " AND (is_answered = 0 OR is_answered IS NULL)";
    }
    
    // 전체 개수 쿼리
    $countQuery = "SELECT COUNT(*) FROM board_quote $where";
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    // 목록 조회 쿼리
    $listQuery = "SELECT * FROM board_quote $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($listQuery);
    $stmt->execute($params);
    $quotes = $stmt->fetchAll();
    
    $totalPages = ceil($total / $limit);
    
} catch(PDOException $e) {
    $quotes = [];
    $totalPages = 0;
    $total = 0;
    $error = "데이터베이스 오류: " . $e->getMessage() . " (쿼리: " . ($countQuery ?? "N/A") . ")";
} catch(Exception $e) {
    $quotes = [];
    $totalPages = 0;
    $total = 0;
    $error = "일반 오류: " . $e->getMessage();
}
?>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg success">
                <?php
                if($_GET['msg'] === 'deleted') echo "견적문의가 삭제되었습니다.";
                ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1>견적문의 관리</h1>
            <p>고객이 문의한 견적 내역을 확인하고 관리할 수 있습니다.</p>
        </div>
        
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <input type="text" name="search" placeholder="제목, 회사명, 작성자로 검색" 
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <select name="status">
                    <option value="">전체 상태</option>
                    <option value="waiting" <?php echo ($_GET['status'] ?? '') === 'waiting' ? 'selected' : ''; ?>>대기중</option>
                    <option value="answered" <?php echo ($_GET['status'] ?? '') === 'answered' ? 'selected' : ''; ?>>답변완료</option>
                </select>
                <button type="submit">검색</button>
                <?php if(isset($_GET['search']) || isset($_GET['status'])): ?>
                    <a href="admin_quotes.php" style="padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">초기화</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%">번호</th>
                        <th style="width: 25%">제목</th>
                        <th style="width: 12%">회사명</th>
                        <th style="width: 8%">작성자</th>
                        <th style="width: 10%">연락처</th>
                        <th style="width: 15%">작성일</th>
                        <th style="width: 8%">상태</th>
                        <th style="width: 17%">관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($quotes)): ?>
                        <tr>
                            <td colspan="8" class="no-data">조회된 견적문의가 없습니다.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($quotes as $quote): ?>
                            <tr>
                                <td><?php echo $quote['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($quote['title']); ?></strong>
                                    <?php if($quote['attachment']): ?>
                                        <br><small style="color: #666;">📎 첨부파일</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($quote['company'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($quote['writer']); ?></td>
                                <td><?php echo htmlspecialchars($quote['phone'] ?? '-'); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($quote['created_at'])); ?></td>
                                <td>
                                    <?php if($quote['is_answered']): ?>
                                        <span class="status-badge status-answered">답변완료</span>
                                    <?php else: ?>
                                        <span class="status-badge status-waiting">대기중</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-links">
                                        <a href="admin_quote_view.php?id=<?php echo $quote['id']; ?>" class="btn-view">상세보기</a>
                                        <a href="?action=toggle_answer&id=<?php echo $quote['id']; ?>" class="btn-toggle">상태변경</a>
                                        <a href="?action=delete&id=<?php echo $quote['id']; ?>" 
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