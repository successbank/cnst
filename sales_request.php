<?php
require_once 'db.php';
require_once 'board/board_template.php';
require_once 'member_check.php';
require_once 'includes/BoardPermissionHelper.php';

// 로그인 필수
checkLogin();

$currentPage = 'sales_request';
$pageTitle = '판매의뢰';
$additionalCSS = ['css/board-style.css'];
include 'head.php';

// 게시판 객체 생성
$board = new BoardTemplate($pdo, 'sales_request');

// 페이지 번호 및 검색 파라미터
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// 철강류 카테고리
$steel_categories = [
    '철근' => '철근(특판)',
    'H형강' => 'H형강(H빔)',
    '철강' => '철강(강판)',
    '메탈라스' => '메탈라스(망철판)',
    '경량H형강' => '경량H형강',
    'I형강' => 'I형강(빔)',
    'ㄱ형강' => 'ㄱ형강(앵글)',
    'ㄷ형강' => 'ㄷ형강(찬넬)',
    '환봉' => '환봉(원형강)',
    '평철' => '평철',
    'C형강' => 'C형강',
    '테크플레이트' => '테크플레이트',
    '사각파이프' => '사각파이프(각관)',
    '원형파이프' => '원형파이프(강관)',
    '레일' => '레일',
    '강널말뚝' => '강널말뚝(쉬트파일)',
    '스테인레스' => '스테인레스(STS)',
    '기타' => '기타'
];

// 목재류 카테고리
$lumber_categories = [
    '합판' => '합판(Plywood)',
    '각재' => '각재(Squared Timber)',
    '원목' => '원목(Raw Lumber)',
    'MDF' => 'MDF',
    '집성목' => '집성목(Laminated)',
    '파티클보드' => '파티클보드(PB)',
    '데크재' => '데크재(Deck)',
    '구조재' => '구조재(Structural)',
    '방부목' => '방부목(Treated)',
    '기타목재' => '기타 목재'
];

$all_categories = array_merge($steel_categories, $lumber_categories);

// 본인 판매의뢰만 조회
$result = $board->getList($page, 10, $search, $category, ['member_id' => $_SESSION['member_id']]);
$posts = $result['list'];
$pagination = $result['pagination'];
?>

<style>
.consignment-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
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
    cursor: pointer;
}

.status-sold {
    background: #E3F2FD;
    color: #1976D2;
}

.status-inactive {
    background: #F5F5F5;
    color: #666;
}

.type-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.type-steel {
    background: #E3F2FD;
    color: #1565C0;
}

.type-lumber {
    background: #FFF8E1;
    color: #F57F17;
}

.category-badge {
    display: inline-block;
    padding: 4px 8px;
    background: #e7f1ff;
    color: #1428A0;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.consignment-table td {
    vertical-align: middle;
}

.reject-tooltip {
    position: relative;
}

.reject-tooltip .tooltip-text {
    visibility: hidden;
    background: #333;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    position: absolute;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    white-space: nowrap;
    max-width: 300px;
    font-size: 12px;
    z-index: 10;
}

.reject-tooltip:hover .tooltip-text {
    visibility: visible;
}

.board-search {
    margin-bottom: 20px;
}

.board-search form {
    display: flex;
    gap: 8px;
}

.board-search input {
    flex: 1;
    padding: 10px 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}

.board-search button {
    padding: 10px 20px;
    background: #1428A0;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .board-table th:nth-child(2),
    .board-table td:nth-child(2),
    .board-table th:nth-child(6),
    .board-table td:nth-child(6) {
        display: none;
    }
}
</style>

<div class="page-header">
    <h2>판매의뢰</h2>
    <p>판매하실 자재를 등록하시면 관리자 검토 후 중개판매 게시판에 게시됩니다.</p>
</div>

<section class="board-section">
    <div class="board-container consignment-container">
        <?php if (isset($_GET['submitted'])): ?>
        <div class="alert success">판매의뢰가 접수되었습니다. 관리자 승인 후 중개판매 게시판에 게시됩니다.</div>
        <?php endif; ?>

        <!-- 검색 폼 -->
        <div class="board-search">
            <form method="get" action="">
                <input type="text" name="search" value="<?php echo escape($search); ?>" placeholder="제목, 내용, 업체명 검색">
                <button type="submit">검색</button>
            </form>
        </div>

        <!-- 게시글 목록 -->
        <table class="board-table consignment-table">
            <thead>
                <tr>
                    <th width="8%">번호</th>
                    <th width="10%">유형</th>
                    <th width="12%">카테고리</th>
                    <th width="38%">제목</th>
                    <th width="12%">상태</th>
                    <th width="12%">등록일</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="6" class="empty-state">
                        등록된 판매의뢰가 없습니다.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo $post['id']; ?></td>
                        <td>
                            <span class="type-badge type-<?php echo $post['item_type']; ?>">
                                <?php echo $post['item_type'] === 'steel' ? '철강류' : '목재류'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="category-badge">
                                <?php echo escape($all_categories[$post['category']] ?? $post['category']); ?>
                            </span>
                        </td>
                        <td style="text-align: left;">
                            <a href="board_view.php?type=sales_request&id=<?php echo $post['id']; ?>">
                                <?php echo escape($post['title']); ?>
                                <?php if ($post['attachment']): ?>
                                    <span class="file-icon">📎</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            $statusClass = 'status-' . $post['status'];
                            $statusText = '';
                            switch($post['status']) {
                                case 'pending': $statusText = '심사중'; break;
                                case 'approved': $statusText = '게시중'; break;
                                case 'rejected': $statusText = '반려'; break;
                                case 'sold': $statusText = '판매완료'; break;
                                case 'inactive': $statusText = '종료'; break;
                                default: $statusText = $post['status'];
                            }
                            ?>
                            <?php if ($post['status'] === 'rejected' && !empty($post['reject_reason'])): ?>
                            <span class="status-badge <?php echo $statusClass; ?> reject-tooltip">
                                <?php echo $statusText; ?>
                                <span class="tooltip-text">사유: <?php echo escape($post['reject_reason']); ?></span>
                            </span>
                            <?php else: ?>
                            <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatDate($post['created_at']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- 글쓰기 버튼 -->
        <div class="board-buttons">
            <a href="board_write.php?type=sales_request" class="write-btn">판매의뢰 등록</a>
        </div>

        <!-- 페이지네이션 -->
        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['currentPage'] > 1): ?>
                <a href="?page=1<?php echo $search ? '&search='.urlencode($search) : ''; ?>">처음</a>
                <a href="?page=<?php echo $pagination['currentPage']-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">이전</a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $pagination['currentPage'] - 2);
            $endPage = min($pagination['totalPages'], $startPage + 4);
            $startPage = max(1, $endPage - 4);

            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"
                   class="<?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="?page=<?php echo $pagination['currentPage']+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">다음</a>
                <a href="?page=<?php echo $pagination['totalPages']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">마지막</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'tail.php'; ?>
