<?php
require_once 'db.php';

echo "<h2>Checking for ./html/114 image paths in content</h2>";

// Check board tables
$tables = ['board_notice', 'board_news', 'board_faq'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT id, title, content FROM $table WHERE content LIKE '%114%' OR content LIKE '%./html%' LIMIT 10");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($results)) {
            echo "<h3>Found in table: $table</h3>";
            foreach ($results as $row) {
                echo "<h4>ID: {$row['id']} - Title: " . htmlspecialchars($row['title']) . "</h4>";
                
                // Extract image tags
                preg_match_all('/<img[^>]+src=["\'](.*?)["\'][^>]*>/i', $row['content'], $matches);
                if (!empty($matches[1])) {
                    echo "<p>Images found:</p><ul>";
                    foreach ($matches[1] as $img) {
                        echo "<li>" . htmlspecialchars($img) . "</li>";
                    }
                    echo "</ul>";
                }
                
                // Show content snippet with 114
                if (strpos($row['content'], '114') !== false) {
                    $pos = strpos($row['content'], '114');
                    $start = max(0, $pos - 50);
                    $snippet = substr($row['content'], $start, 200);
                    echo "<p>Content snippet: <pre>" . htmlspecialchars($snippet) . "</pre></p>";
                }
            }
        }
    } catch (Exception $e) {
        echo "<p>Error checking table $table: " . $e->getMessage() . "</p>";
    }
}

// Check if there's any CKEditor or similar content
echo "<h3>Checking for editor uploaded images:</h3>";
$stmt = $pdo->query("
    SELECT id, title, content 
    FROM board_notice 
    WHERE content LIKE '%<img%' 
    ORDER BY created_at DESC 
    LIMIT 10
");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($posts as $post) {
    echo "<h4>Post ID: {$post['id']} - " . htmlspecialchars($post['title']) . "</h4>";
    preg_match_all('/<img[^>]+src=["\'](.*?)["\'][^>]*>/i', $post['content'], $matches);
    if (!empty($matches[1])) {
        echo "<ul>";
        foreach ($matches[1] as $img) {
            echo "<li>" . htmlspecialchars($img) . "</li>";
        }
        echo "</ul>";
    }
}
?>