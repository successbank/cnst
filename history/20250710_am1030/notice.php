<?php
require_once 'db.php';
require_once 'board/board_template.php';

$currentPage = 'notice';
$pageTitle = '공지사항';
$additionalCSS = ['css/board-style.css'];
include 'head.php';

// 게시판 객체 생성
$board = new BoardTemplate($pdo, 'notice');

// 페이지 번호
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 게시글 목록 조회
$result = $board->getList($page, 10, $search);
$posts = $result['list'];
$pagination = $result['pagination'];
?>

<div class="page-header">
    <h2>공지사항</h2>
    <p>충남스틸의 새로운 소식과 공지사항을 전해드립니다.</p>
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
                    <th width="10%">번호</th>
                    <th width="54%">제목</th>
                    <th width="14%">작성자</th>
                    <th width="14%">작성일</th>
                    <th width="8%">조회</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="5" class="empty-state">
                        등록된 공지사항이 없습니다.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <?php if ($post['is_important']): ?>
                                <span class="badge-important">공지</span>
                            <?php else: ?>
                                <?php echo $post['id']; ?>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: left;">
                            <a href="board_view.php?type=notice&id=<?php echo $post['id']; ?>">
                                <?php if ($post['is_important']): ?>
                                    <strong><?php echo escape($post['title']); ?></strong>
                                <?php else: ?>
                                    <?php echo escape($post['title']); ?>
                                <?php endif; ?>
                                <?php if ($post['attachment']): ?>
                                    <span class="file-icon">📎</span>
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

        <!-- 글쓰기 버튼 (관리자용 - 실제로는 로그인 체크 필요) -->
        <div class="board-buttons">
            <a href="board_write.php?type=notice" class="write-btn">글쓰기</a>
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
            <h4>이용 안내</h4>
            <ul>
                <li>공지사항은 충남스틸의 중요한 소식과 변경사항을 안내드립니다.</li>
                <li>중요 공지는 상단에 고정되어 표시됩니다.</li>
                <li>첨부파일이 있는 경우 📎 아이콘이 표시됩니다.</li>
                <li>문의사항은 대표전화(041-123-4567)로 연락 주시기 바랍니다.</li>
            </ul>
        </div>
    </div>
</section>

<?php include 'tail.php'; ?>