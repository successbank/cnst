<?php
require_once 'db.php';
require_once 'board/board_template.php';
require_once 'member_check.php';
require_once 'includes/BoardPermissionHelper.php';

// 게시판 목록 접근 권한 체크
BoardPermissionHelper::requireAccess('brokerage', 'list');

$currentPage = 'brokerage';
$pageTitle = '중개판매';
$additionalCSS = ['css/board-style.css'];
include 'head.php';

// 게시판 객체 생성
$board = new BoardTemplate($pdo, 'brokerage');

// 페이지 번호 및 검색 파라미터
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$item_type = isset($_GET['item_type']) ? $_GET['item_type'] : '';

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

// 유형별 카테고리 선택
if ($item_type === 'steel') {
    $display_categories = $steel_categories;
} elseif ($item_type === 'lumber') {
    $display_categories = $lumber_categories;
} else {
    $display_categories = $all_categories;
}

// 게시글 목록 조회
$extraFilters = [];
if ($item_type) {
    $extraFilters['item_type'] = $item_type;
}
// 관리자가 아니면 본인이 작성한 매물만 표시 (admin은 전체 열람)
if (BoardPermissionHelper::getCurrentRole() !== 'admin') {
    $extraFilters['member_id'] = $_SESSION['member_id'] ?? 0;
    $extraFilters['writer']    = $_SESSION['member_name'] ?? '';
}
$result = $board->getList($page, 10, $search, $category, $extraFilters);
$posts = $result['list'];
$pagination = $result['pagination'];

// 업체명 마스킹 함수
function maskCompanyName($name) {
    if (empty($name)) return '-';
    $length = mb_strlen($name, 'UTF-8');
    if (preg_match('/[가-힣]/', $name)) {
        if ($length <= 1) return $name;
        $firstChar = mb_substr($name, 0, 1, 'UTF-8');
        return $firstChar . str_repeat('*', $length - 1);
    } else {
        if ($length <= 2) return $name;
        $firstTwo = mb_substr($name, 0, 2, 'UTF-8');
        return $firstTwo . str_repeat('*', $length - 2);
    }
}

// 제목 마스킹 함수
function maskTitle($title) {
    if (empty($title)) return '-';
    $title = preg_replace('/[\s\p{P}\p{S}]/u', '', $title);
    $title = mb_substr($title, 0, 20, 'UTF-8');
    $chars = preg_split('//u', $title, -1, PREG_SPLIT_NO_EMPTY);
    $masked = [];
    $totalChars = count($chars);
    if ($totalChars > 0) $masked[] = $chars[0];
    for ($i = 1; $i < $totalChars; $i++) {
        $masked[] = ($i % 2 == 0) ? $chars[$i] : '*';
    }
    return implode('', $masked);
}
?>

<style>
.consignment-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* 유형 탭 */
.type-tabs {
    display: flex;
    gap: 0;
    margin-bottom: 20px;
    border-bottom: 2px solid #E5E5E7;
}

.type-tab {
    padding: 12px 24px;
    text-decoration: none;
    color: #666;
    font-size: 15px;
    font-weight: 600;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.3s ease;
}

.type-tab:hover {
    color: #1428A0;
}

.type-tab.active {
    color: #1428A0;
    border-bottom-color: #1428A0;
}

/* 카테고리 필터 */
.category-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.category-item {
    padding: 8px 16px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 20px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    transition: all 0.3s ease;
}

.category-item:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.category-item.active {
    background: #1428A0;
    color: white;
    border-color: #1428A0;
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

.type-badge {
    display: inline-block;
    padding: 3px 6px;
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

.consignment-table td {
    vertical-align: middle;
}

@media (max-width: 768px) {
    .category-filter {
        padding: 15px;
    }
    .category-item {
        font-size: 12px;
        padding: 6px 12px;
    }
    .board-table th:nth-child(2),
    .board-table td:nth-child(2),
    .board-table th:nth-child(5),
    .board-table td:nth-child(5),
    .board-table th:nth-child(7),
    .board-table td:nth-child(7),
    .board-table th:nth-child(8),
    .board-table td:nth-child(8) {
        display: none;
    }
}
</style>

<div class="page-header">
    <h2>중개판매</h2>
    <p>업체들로부터 의뢰받은 자재들의 중개판매 정보를 제공합니다.</p>
</div>

<section class="board-section">
    <div class="board-container consignment-container">
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
        <div class="alert success">게시글이 성공적으로 삭제되었습니다.</div>
        <?php endif; ?>

        <!-- 유형 탭 -->
        <div class="type-tabs">
            <a href="?item_type=<?php echo $category ? '&category='.urlencode($category) : ''; ?>"
               class="type-tab <?php echo $item_type == '' ? 'active' : ''; ?>">전체</a>
            <a href="?item_type=steel<?php echo $category ? '&category='.urlencode($category) : ''; ?>"
               class="type-tab <?php echo $item_type == 'steel' ? 'active' : ''; ?>">철강류</a>
            <a href="?item_type=lumber<?php echo $category ? '&category='.urlencode($category) : ''; ?>"
               class="type-tab <?php echo $item_type == 'lumber' ? 'active' : ''; ?>">목재류</a>
        </div>

        <!-- 카테고리 필터 -->
        <div class="category-filter">
            <a href="?<?php echo $item_type ? 'item_type='.urlencode($item_type) : ''; ?>"
               class="category-item <?php echo $category == '' ? 'active' : ''; ?>">전체</a>
            <?php foreach ($display_categories as $key => $value): ?>
                <a href="?category=<?php echo urlencode($key); ?><?php echo $item_type ? '&item_type='.urlencode($item_type) : ''; ?>"
                   class="category-item <?php echo $category == $key ? 'active' : ''; ?>">
                    <?php echo $value; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- 검색 폼 -->
        <div class="board-search">
            <form method="get" action="">
                <?php if ($item_type): ?>
                <input type="hidden" name="item_type" value="<?php echo escape($item_type); ?>">
                <?php endif; ?>
                <?php if ($category): ?>
                <input type="hidden" name="category" value="<?php echo escape($category); ?>">
                <?php endif; ?>
                <input type="text" name="search" value="<?php echo escape($search); ?>" placeholder="제목, 내용, 업체명 검색">
                <button type="submit">검색</button>
            </form>
        </div>

        <!-- 게시글 목록 -->
        <table class="board-table consignment-table">
            <thead>
                <tr>
                    <th width="6%">번호</th>
                    <th width="8%">유형</th>
                    <th width="12%">카테고리</th>
                    <th width="34%">제목</th>
                    <th width="12%">업체명</th>
                    <th width="10%">재고수량</th>
                    <th width="10%">작성일</th>
                    <th width="6%">조회</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="8" class="empty-state">
                        등록된 중개판매 정보가 없습니다.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo $post['id']; ?></td>
                        <td>
                            <span class="type-badge type-<?php echo $post['item_type']; ?>">
                                <?php echo $post['item_type'] === 'steel' ? '철강' : '목재'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="category-badge">
                                <?php echo escape($all_categories[$post['category']] ?? $post['category']); ?>
                            </span>
                        </td>
                        <td style="text-align: left;">
                            <?php if (BoardPermissionHelper::canAccess('brokerage', 'read')): ?>
                            <a href="board_view.php?type=brokerage&id=<?php echo $post['id']; ?>">
                                <?php echo escape(maskTitle($post['title'])); ?>
                                <?php if ($post['attachment']): ?>
                                    <span class="file-icon">📎</span>
                                <?php endif; ?>
                            </a>
                            <?php else: ?>
                            <span class="title-locked">
                                <?php echo escape(maskTitle($post['title'])); ?>
                                <span class="lock-icon" title="로그인 후 열람 가능">🔒</span>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo escape(maskCompanyName($post['company_name'] ?? $post['writer'])); ?></td>
                        <td><?php echo escape($post['stock_quantity'] ?? '-'); ?></td>
                        <td><?php echo formatDate($post['created_at']); ?></td>
                        <td><?php echo $post['view_count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- 글쓰기 버튼 -->
        <?php if (isLoggedIn() || isAdmin() || !empty($_SESSION['admin_logged_in'])): ?>
        <div class="board-buttons">
            <a href="board_write.php?type=sales_request" class="write-btn">판매의뢰 등록</a>
        </div>
        <?php endif; ?>

        <!-- 페이지네이션 -->
        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['currentPage'] > 1): ?>
                <a href="?page=1<?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?><?php echo $item_type ? '&item_type='.urlencode($item_type) : ''; ?>">처음</a>
                <a href="?page=<?php echo $pagination['currentPage']-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?><?php echo $item_type ? '&item_type='.urlencode($item_type) : ''; ?>">이전</a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $pagination['currentPage'] - 2);
            $endPage = min($pagination['totalPages'], $startPage + 4);
            $startPage = max(1, $endPage - 4);

            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?><?php echo $item_type ? '&item_type='.urlencode($item_type) : ''; ?>"
                   class="<?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="?page=<?php echo $pagination['currentPage']+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?><?php echo $item_type ? '&item_type='.urlencode($item_type) : ''; ?>">다음</a>
                <a href="?page=<?php echo $pagination['totalPages']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?><?php echo $item_type ? '&item_type='.urlencode($item_type) : ''; ?>">마지막</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'tail.php'; ?>
