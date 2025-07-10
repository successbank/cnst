<?php
require_once 'db.php';
require_once 'board/board_template.php';

$currentPage = 'quote';
$pageTitle = '견적문의';
include 'head.php';

// 게시판 객체 생성
$board = new BoardTemplate($pdo, 'quote');

// 페이지 번호
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 게시글 목록 조회
$result = $board->getList($page, 10, $search);
$posts = $result['list'];
$pagination = $result['pagination'];
?>

<style>
/* Quote page specific styles */
.page-header {
    background: linear-gradient(135deg, #E8F0FE 0%, #F8F9FA 100%);
    padding: 60px 0;
    text-align: center;
}

.page-header h2 {
    font-size: 36px;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 12px;
}

.page-header p {
    font-size: 18px;
    color: #666;
}

.board-section {
    background: #F8F9FA;
    padding: 40px 0;
    min-height: 600px;
}

.board-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.board-search {
    margin-bottom: 30px;
    text-align: right;
}

.board-search form {
    display: inline-flex;
    gap: 8px;
}

.board-search input {
    width: 300px;
    padding: 10px 16px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
}

.board-search button {
    padding: 10px 24px;
    background: var(--primary-blue);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.board-search button:hover {
    background: #0F1F7A;
}

.board-table {
    width: 100%;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.board-table thead {
    background: #F8F9FA;
}

.board-table th {
    padding: 16px;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    text-align: center;
    border-bottom: 1px solid #E5E5E7;
}

.board-table td {
    padding: 16px;
    font-size: 14px;
    text-align: center;
    border-bottom: 1px solid #E5E5E7;
}

.board-table tbody tr:last-child td {
    border-bottom: none;
}

.board-table tbody tr:hover {
    background: #F8F9FA;
}

.board-table a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.board-table a:hover {
    color: var(--primary-blue);
}

.file-icon {
    color: #999;
    margin-left: 4px;
}

.board-buttons {
    margin-top: 24px;
    text-align: right;
}

.submit-btn {
    display: inline-block;
    padding: 12px 32px;
    background: var(--primary-blue);
    color: white;
    text-decoration: none;
    border-radius: 28px;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.submit-btn:hover {
    background: #0F1F7A;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(20, 40, 160, 0.3);
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 40px;
}

.pagination a,
.pagination span {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    color: #333;
    background: white;
    border: 1px solid #E5E5E7;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background: #F8F9FA;
    border-color: var(--primary-blue);
    color: var(--primary-blue);
}

.pagination .current {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

.board-notice {
    margin-top: 40px;
    padding: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.board-notice h4 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
}

.board-notice ul {
    list-style: none;
    padding: 0;
}

.board-notice li {
    position: relative;
    padding-left: 20px;
    margin-bottom: 12px;
    line-height: 1.6;
    color: #666;
}

.board-notice li::before {
    content: "•";
    position: absolute;
    left: 8px;
    color: var(--primary-blue);
}

.status-answered {
    color: #28A745;
    font-weight: 500;
}

.status-waiting {
    color: #FD7E14;
    font-weight: 500;
}
</style>

<div class="page-header">
    <h2>견적문의</h2>
    <p>철강 제품 견적을 문의해주시면 신속하게 답변드리겠습니다.</p>
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
                    <th width="48%">제목</th>
                    <th width="12%">작성자</th>
                    <th width="12%">작성일</th>
                    <th width="8%">조회</th>
                    <th width="12%">답변상태</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 60px 20px; color: #999;">
                        등록된 문의가 없습니다.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo $post['display_order'] ?? $post['id']; ?></td>
                        <td style="text-align: left;">
                            <a href="board_view.php?type=quote&id=<?php echo $post['id']; ?>">
                                🔒 <?php echo escape($post['title']); ?>
                                <?php if ($post['attachment']): ?>
                                    <span class="file-icon">📎</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td><?php echo escape($post['writer']); ?></td>
                        <td><?php echo formatDate($post['created_at']); ?></td>
                        <td><?php echo $post['view_count']; ?></td>
                        <td>
                            <?php if ($post['is_answered']): ?>
                                <span class="status-answered">답변완료</span>
                            <?php else: ?>
                                <span class="status-waiting">대기중</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- 글쓰기 버튼 -->
        <div class="board-buttons">
            <a href="board_write.php?type=quote" class="submit-btn">견적문의 작성</a>
        </div>

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

        <!-- 안내사항 -->
        <div class="board-notice">
            <h4>견적문의 안내</h4>
            <ul>
                <li>견적문의는 비밀글로 작성되며, 작성자와 관리자만 확인할 수 있습니다.</li>
                <li>정확한 견적을 위해 제품명, 규격, 수량을 상세히 기재해 주세요.</li>
                <li>도면이나 참고자료가 있으시면 파일을 첨부해 주세요.</li>
                <li>문의하신 내용은 영업일 기준 1~2일 이내에 답변드립니다.</li>
                <li>긴급한 견적은 대표전화(041-123-4567)로 연락 주시기 바랍니다.</li>
            </ul>
        </div>
    </div>
</section>

<?php include 'tail.php'; ?>