<?php
require_once 'db.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$pageTitle = '통합 검색 결과';
include 'head.php';

$results = [
    'notice' => [],
    'news' => []
];
$total_found = 0;

if (!empty($query)) {
    try {
        // 공지사항 검색
        $stmt_notice = $db->prepare("SELECT id, title, content, created_at FROM board_notice WHERE title LIKE :query OR content LIKE :query ORDER BY created_at DESC");
        $stmt_notice->bindValue(':query', '%' . $query . '%');
        $stmt_notice->execute();
        $results['notice'] = $stmt_notice->fetchAll();
        $total_found += count($results['notice']);

        // 철강뉴스 검색
        $stmt_news = $db->prepare("SELECT id, title, content, created_at FROM board_news WHERE title LIKE :query OR content LIKE :query ORDER BY created_at DESC");
        $stmt_news->bindValue(':query', '%' . $query . '%');
        $stmt_news->execute();
        $results['news'] = $stmt_news->fetchAll();
        $total_found += count($results['news']);

    } catch (PDOException $e) {
        $error = "데이터��이스 오류: " . $e->getMessage();
    }
}

function highlight_text($text, $query) {
    if (empty($query)) return $text;
    return preg_replace('/(' . preg_quote($query, '/') . ')/i', '<mark>$1</mark>', $text);
}
?>

<style>
.search-results-page { padding: 40px 0; }
.search-results-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.search-summary { font-size: 1.2rem; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
.search-summary strong { color: var(--primary-blue); }
.results-section { margin-bottom: 40px; }
.results-section h3 { font-size: 1.8rem; margin-bottom: 20px; color: #333; }
.result-item { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 15px; }
.result-item h4 { margin-bottom: 10px; }
.result-item h4 a { text-decoration: none; color: var(--primary-blue); font-size: 1.2rem; }
.result-item .snippet { color: #666; font-size: 0.95rem; line-height: 1.6; }
.result-item .meta { font-size: 0.85rem; color: #888; margin-top: 10px; }
.no-results { text-align: center; padding: 50px; font-size: 1.2rem; color: #777; }
mark { background-color: #FFF3CD; padding: 2px; border-radius: 3px; }
</style>

<div class="search-results-page">
    <div class="search-results-container">
        <div class="page-header">
            <h2>통합 검색</h2>
            <p>"<?php echo escape($query); ?>"에 대한 검색 결과입니다.</p>
        </div>

        <div class="search-summary">
            총 <strong><?php echo $total_found; ?></strong>개의 결과를 찾았습니다.
        </div>

        <?php if ($total_found > 0): ?>
            <?php if (!empty($results['notice'])): ?>
            <section class="results-section">
                <h3>공지사항 (<?php echo count($results['notice']); ?>)</h3>
                <?php foreach ($results['notice'] as $item): ?>
                <div class="result-item">
                    <h4><a href="board_view.php?type=notice&id=<?php echo $item['id']; ?>"><?php echo highlight_text(escape($item['title']), $query); ?></a></h4>
                    <p class="snippet"><?php echo highlight_text(escape(mb_substr(strip_tags($item['content']), 0, 150) . '...'), $query); ?></p>
                    <p class="meta"><?php echo formatDate($item['created_at']); ?></p>
                </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <?php if (!empty($results['news'])): ?>
            <section class="results-section">
                <h3>철강뉴스 (<?php echo count($results['news']); ?>)</h3>
                <?php foreach ($results['news'] as $item): ?>
                <div class="result-item">
                    <h4><a href="board_view.php?type=news&id=<?php echo $item['id']; ?>"><?php echo highlight_text(escape($item['title']), $query); ?></a></h4>
                    <p class="snippet"><?php echo highlight_text(escape(mb_substr(strip_tags($item['content']), 0, 150) . '...'), $query); ?></p>
                    <p class="meta"><?php echo formatDate($item['created_at']); ?></p>
                </div>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                "<?php echo escape($query); ?>"에 대한 검색 결과가 없습니다.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'tail.php'; ?>
