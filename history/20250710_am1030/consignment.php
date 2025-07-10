<?php
require_once 'db.php';
require_once 'board/board_template.php';

$currentPage = 'consignment';
$pageTitle = '중계판매';
$additionalCSS = ['css/board-style.css'];
include 'head.php';

// 게시판 객체 생성
$board = new BoardTemplate($pdo, 'consignment');

// 페이지 번호 및 검색 파라미터
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// 카테고리 목록
$categories = [
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

// 게시글 목록 조회
$result = $board->getList($page, 10, $search, $category);
$posts = $result['list'];
$pagination = $result['pagination'];
?>

<style>
/* Consignment page specific styles */
.consignment-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}
</style>

<div class="page-header">
    <h2>중계판매</h2>
    <p>업체들로부터 의뢰받은 자재들의 중계판매 정보를 제공합니다.</p>
</div>

<section class="board-section">
    <div class="board-container consignment-container">
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
        <div class="alert success">게시글이 성공적으로 삭제되었습니다.</div>
        <?php endif; ?>
        
        <!-- 카테고리 필터 -->
        <div class="category-filter">
            <a href="?category=" class="category-item <?php echo $category == '' ? 'active' : ''; ?>">전체</a>
            <?php foreach ($categories as $key => $value): ?>
                <a href="?category=<?php echo urlencode($key); ?>" 
                   class="category-item <?php echo $category == $key ? 'active' : ''; ?>">
                    <?php echo $value; ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- 검색 폼 -->
        <div class="board-search">
            <form method="get" action="">
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
                    <th width="8%">번호</th>
                    <th width="12%">카테고리</th>
                    <th width="38%">제목</th>
                    <th width="14%">업체명</th>
                    <th width="10%">재고수량</th>
                    <th width="12%">작성일</th>
                    <th width="6%">조회</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        등록된 중계판매 정보가 없습니다.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo $post['id']; ?></td>
                        <td>
                            <span class="category-badge">
                                <?php echo escape($categories[$post['category']] ?? $post['category']); ?>
                            </span>
                        </td>
                        <td style="text-align: left;">
                            <a href="board_view.php?type=consignment&id=<?php echo $post['id']; ?>">
                                <?php echo escape($post['title']); ?>
                                <?php if ($post['attachment']): ?>
                                    <span class="file-icon">📎</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td><?php echo escape($post['company_name'] ?? $post['writer']); ?></td>
                        <td><?php echo escape($post['stock_quantity'] ?? '-'); ?></td>
                        <td><?php echo formatDate($post['created_at']); ?></td>
                        <td><?php echo $post['view_count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- 글쓰기 버튼 -->
        <div class="board-buttons">
            <a href="board_write.php?type=consignment" class="write-btn">중계판매 등록</a>
        </div>

        <!-- 페이지네이션 -->
        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['currentPage'] > 1): ?>
                <a href="?page=1<?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?>">처음</a>
                <a href="?page=<?php echo $pagination['currentPage']-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?>">이전</a>
            <?php endif; ?>
            
            <?php 
            $startPage = max(1, $pagination['currentPage'] - 2);
            $endPage = min($pagination['totalPages'], $startPage + 4);
            $startPage = max(1, $endPage - 4);
            
            for ($i = $startPage; $i <= $endPage; $i++): 
            ?>
                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?>" 
                   class="<?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="?page=<?php echo $pagination['currentPage']+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?>">다음</a>
                <a href="?page=<?php echo $pagination['totalPages']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?>">마지막</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* 카테고리 필터 스타일 */
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
}
</style>

<?php include 'tail.php'; ?>