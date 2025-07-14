<?php
require_once 'db.php';
require_once 'board/board_template.php';

$currentPage = 'news';
$pageTitle = '철강뉴스';
$additionalCSS = ['css/board-style.css'];
include 'head.php';

// 게시판 객체 생성
$board = new BoardTemplate($pdo, 'news');

// 페이지 번호
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 게시글 목록 조회
$result = $board->getList($page, 10, $search);
$posts = $result['list'];
$pagination = $result['pagination'];
?>

<div class="page-header">
    <h2>철강뉴스</h2>
    <p>철강업계의 최신 뉴스와 시장 동향을 전해드립니다.</p>
</div>

<section class="board-section">
    <div class="board-container">
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
        <div class="alert success">게시글이 성공적으로 삭제되었습니다.</div>
        <?php endif; ?>
        
        <!-- 검색 폼 -->
        <div class="board-search">
            <form method="get" action="">
                <input type="text" name="search" value="<?php echo escape($search); ?>" placeholder="제목 또는 내용 검색">
                <button type="submit">검색</button>
            </form>
        </div>

        <!-- 게시글 목록 -->
        <table class="board-table">
            <thead>
                <tr>
                    <th width="8%">번호</th>
                    <th width="50%">제목</th>
                    <th width="15%">작성자</th>
                    <th width="17%">작성일</th>
                    <th width="10%">조회</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="5" class="empty-state">
                        등록된 뉴스가 없습니다.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo $post['id']; ?></td>
                        <td style="text-align: left;">
                            <a href="board_view.php?type=news&id=<?php echo $post['id']; ?>">
                                <?php echo escape($post['title']); ?>
                                <?php if ($post['attachment']): ?>
                                    <span class="file-icon">📎</span>
                                <?php endif; ?>
                                <?php 
                                // 오늘 날짜의 글이면 NEW 표시
                                if (date('Y-m-d', strtotime($post['created_at'])) == date('Y-m-d')): 
                                ?>
                                    <span class="badge-new">NEW</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td><?php echo escape($post['writer']); ?></td>
                        <td><?php echo formatDate($post['created_at']); ?></td>
                        <td><?php echo $post['view_count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>


        <!-- 페이지네이션 -->
        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="pagination">
            <?php if ($pagination['currentPage'] > 1): ?>
                <a href="?page=1<?php echo $search ? '&search='.urlencode($search) : ''; ?>">처음</a>
                <a href="?page=<?php echo $pagination['currentPage']-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">이전</a>
            <?php endif; ?>
            
            <?php for ($i = $pagination['startPage']; $i <= $pagination['endPage']; $i++): ?>
                <?php if ($i == $pagination['currentPage']): ?>
                    <span class="current"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                <a href="?page=<?php echo $pagination['currentPage']+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">다음</a>
                <a href="?page=<?php echo $pagination['totalPages']; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">마지막</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 뉴스 카테고리 -->
        <div class="news-categories">
            <h4>주요 뉴스 카테고리</h4>
            <div class="category-tags">
                <a href="?category=price" class="category-tag">가격동향</a>
                <a href="?category=policy" class="category-tag">정책/규제</a>
                <a href="?category=tech" class="category-tag">기술/혁신</a>
                <a href="?category=market" class="category-tag">시장분석</a>
                <a href="?category=company" class="category-tag">기업소식</a>
                <a href="?category=global" class="category-tag">해외동향</a>
            </div>
        </div>

        <!-- 안내사항 -->
        <div class="board-notice">
            <h4>철강뉴스 안내</h4>
            <ul>
                <li>국내외 철강업계의 최신 뉴스와 시장 동향을 제공합니다.</li>
                <li>뉴스 출처는 각 기사에 표시되어 있습니다.</li>
                <li>오늘 등록된 새로운 뉴스는 NEW 표시가 됩니다.</li>
                <li>카테고리별로 뉴스를 분류하여 보실 수 있습니다.</li>
            </ul>
        </div>
    </div>
</section>

<style>
/* News page specific styles */
.badge-new {
    display: inline-block;
    padding: 2px 8px;
    background: #28A745;
    color: white;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
    margin-left: 8px;
    text-transform: uppercase;
}

.news-categories {
    margin-top: 40px;
    padding: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.news-categories h4 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 20px;
}

.category-tags {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.category-tag {
    display: inline-flex;
    align-items: center;
    padding: 10px 20px;
    background: #F8F9FA;
    border: 2px solid #E5E5E7;
    border-radius: 24px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.category-tag:hover {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(20, 40, 160, 0.2);
}

/* Responsive */
@media (max-width: 768px) {
    .category-tags {
        gap: 8px;
    }
    
    .category-tag {
        padding: 8px 16px;
        font-size: 13px;
    }
    
    /* 모바일에서 작성자, 작성일 컬럼 숨김 */
    .board-table th:nth-child(3),
    .board-table td:nth-child(3),
    .board-table th:nth-child(4),
    .board-table td:nth-child(4) {
        display: none;
    }
    
    /* 컬럼 너비 재조정 */
    .board-table th:nth-child(1) { width: 15%; }  /* 번호 */
    .board-table th:nth-child(2) { width: 70%; }  /* 제목 */
    .board-table th:nth-child(5) { width: 15%; }  /* 조회 */
}
</style>

<?php include 'tail.php'; ?>