<?php
// Debug script to check board_consignment records for test account
require_once 'db.php';

// Test account info
$test_username = 'test';
$test_name = '테스트';

echo "<h2>Board Consignment Debug Information</h2>";
echo "<hr>";

try {
    // 1. Check all records with writer='test' or similar
    echo "<h3>1. Records with writer containing 'test' or '테스트':</h3>";
    $stmt = $pdo->prepare("
        SELECT id, title, writer, status, created_at, company_name 
        FROM board_consignment 
        WHERE writer LIKE ? OR writer LIKE ? OR writer = ? OR writer = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute(['%test%', '%테스트%', 'test', '테스트']);
    $results = $stmt->fetchAll();
    
    if ($results) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Writer</th><th>Status</th><th>Company</th><th>Created At</th></tr>";
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['writer']) . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . htmlspecialchars($row['company_name']) . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>Total records found: " . count($results) . "</p>";
    } else {
        echo "<p>No records found for test user.</p>";
    }
    
    // 2. Check recent deletions (if there's a deletion log)
    echo "<hr>";
    echo "<h3>2. All board_consignment records (last 20):</h3>";
    $stmt = $pdo->prepare("
        SELECT id, title, writer, status, created_at 
        FROM board_consignment 
        ORDER BY id DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $allRecords = $stmt->fetchAll();
    
    if ($allRecords) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Writer</th><th>Status</th><th>Created At</th></tr>";
        foreach ($allRecords as $row) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['writer']) . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Check unique writers
    echo "<hr>";
    echo "<h3>3. Unique writers in board_consignment:</h3>";
    $stmt = $pdo->prepare("
        SELECT DISTINCT writer, COUNT(*) as count 
        FROM board_consignment 
        GROUP BY writer 
        ORDER BY count DESC
    ");
    $stmt->execute();
    $writers = $stmt->fetchAll();
    
    if ($writers) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Writer</th><th>Count</th></tr>";
        foreach ($writers as $writer) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($writer['writer']) . "</td>";
            echo "<td>" . $writer['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Check if there are any soft delete fields
    echo "<hr>";
    echo "<h3>4. Table structure check:</h3>";
    $stmt = $pdo->prepare("DESCRIBE board_consignment");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        foreach ($col as $val) {
            echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Look for any soft delete columns
    $hasSoftDelete = false;
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['deleted', 'deleted_at', 'is_deleted', 'del_flag'])) {
            $hasSoftDelete = true;
            echo "<p style='color: red;'><strong>Found soft delete column: " . $col['Field'] . "</strong></p>";
            
            // Check for soft deleted records
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_consignment WHERE {$col['Field']} IS NOT NULL OR {$col['Field']} != 0");
            $stmt->execute();
            $deletedCount = $stmt->fetchColumn();
            echo "<p>Soft deleted records: $deletedCount</p>";
        }
    }
    
    if (!$hasSoftDelete) {
        echo "<p>No soft delete columns found - deletions are permanent.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>