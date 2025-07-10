<?php
require_once 'db.php';

try {
    // Check if column already exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM board_news LIKE 'source_url'");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // Add source_url column to board_news table
        $sql = "ALTER TABLE board_news ADD COLUMN source_url VARCHAR(500)";
        $pdo->exec($sql);
        echo "Successfully added source_url column to board_news table.";
    } else {
        echo "Column source_url already exists in board_news table.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>