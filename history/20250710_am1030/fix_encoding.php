<?php
require_once 'db.php';

echo "Starting encoding fix for 'board_news' table...\n";

try {
    // 1. 모든 게시물 가져오기
    $stmt = $db->query("SELECT id, title, source FROM board_news");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateCount = 0;

    // 2. 각 게시물을 순회하며 인코딩 변환 및 업데이트
    foreach ($posts as $post) {
        $id = $post['id'];
        $originalTitle = $post['title'];
        $originalSource = $post['source'];

        // EUC-KR에서 UTF-8로 변환 시도
        $correctedTitle = mb_convert_encoding($originalTitle, 'UTF-8', 'EUC-KR');
        $correctedSource = mb_convert_encoding($originalSource, 'UTF-8', 'EUC-KR');

        // 변경된 경우에만 업데이트
        if ($correctedTitle !== $originalTitle || $correctedSource !== $originalSource) {
            $updateStmt = $db->prepare(
                "UPDATE board_news SET title = :title, source = :source WHERE id = :id"
            );
            $updateStmt->execute([
                ':title' => $correctedTitle,
                ':source' => $correctedSource,
                ':id' => $id
            ]);
            $updateCount++;
            echo "Updated post ID: $id\n";
        }
    }

    echo "----------------------------------------\n";
    echo "Fix completed. Total $updateCount posts updated.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

