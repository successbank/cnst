<?php
require_once 'db.php';
session_start();

// Set headers
header('Content-Type: text/html; charset=UTF-8');

// Debug styles
?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Consignment Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        th { background: #e9ecef; }
        .query-box { background: #e3f2fd; padding: 10px; border-left: 4px solid #2196f3; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Comprehensive Consignment Deletion Debug</h1>

<div class="section">
    <h2>1. Session Information</h2>
    <?php
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Session Name:</strong> " . ($_SESSION['name'] ?? 'Not set') . "</p>";
    echo "<p><strong>Session User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
    echo "<p><strong>Session Member ID:</strong> " . ($_SESSION['member_id'] ?? 'Not set') . "</p>";
    ?>
</div>

<div class="section">
    <h2>2. Database Connection Check</h2>
    <?php
    try {
        $testQuery = $pdo->query("SELECT DATABASE()");
        $dbName = $testQuery->fetchColumn();
        echo "<p class='success'>✓ Database connected: $dbName</p>";
        
        // Check autocommit
        $stmt = $pdo->query("SELECT @@autocommit");
        $autocommit = $stmt->fetchColumn();
        echo "<p><strong>Autocommit:</strong> " . ($autocommit ? 'ON' : 'OFF') . "</p>";
        
        // Check isolation level (MySQL 8.0+ compatible)
        try {
            $stmt = $pdo->query("SELECT @@transaction_isolation");
            $isolation = $stmt->fetchColumn();
            echo "<p><strong>Transaction Isolation:</strong> $isolation</p>";
        } catch (PDOException $e) {
            // Try older variable name
            try {
                $stmt = $pdo->query("SELECT @@tx_isolation");
                $isolation = $stmt->fetchColumn();
                echo "<p><strong>Transaction Isolation:</strong> $isolation</p>";
            } catch (PDOException $e2) {
                echo "<p class='warning'>Transaction isolation level check not available</p>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>✗ Database connection error: " . $e->getMessage() . "</p>";
    }
    ?>
</div>

<div class="section">
    <h2>3. Board Consignment Table Check</h2>
    <?php
    try {
        // Check if it's a table or view
        $stmt = $pdo->prepare("SELECT TABLE_TYPE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'board_consignment'");
        $stmt->execute();
        $tableInfo = $stmt->fetch();
        
        if ($tableInfo) {
            echo "<p><strong>Table Type:</strong> " . $tableInfo['TABLE_TYPE'] . "</p>";
            if ($tableInfo['TABLE_TYPE'] === 'VIEW') {
                echo "<p class='warning'>⚠ board_consignment is a VIEW, not a TABLE. This might affect deletion behavior.</p>";
            }
        }
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE board_consignment");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Look for soft delete columns
        $softDeleteColumns = ['deleted', 'is_deleted', 'deleted_at', 'status'];
        $foundSoftDelete = false;
        foreach ($columns as $col) {
            if (in_array($col['Field'], $softDeleteColumns)) {
                echo "<p class='warning'>⚠ Found potential soft delete column: {$col['Field']}</p>";
                $foundSoftDelete = true;
            }
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>Error checking table: " . $e->getMessage() . "</p>";
    }
    ?>
</div>

<div class="section">
    <h2>4. Current Consignment Records for Test User</h2>
    <?php
    try {
        // Check with different writer variations
        $writers = ['test', 'TEST', ' test', 'test ', ' test '];
        
        foreach ($writers as $writer) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_consignment WHERE writer = ?");
            $stmt->execute([$writer]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                echo "<p class='info'>Found $count records where writer = '$writer'</p>";
                
                // Show sample records
                $stmt = $pdo->prepare("SELECT id, title, writer, created_at, status FROM board_consignment WHERE writer = ? LIMIT 5");
                $stmt->execute([$writer]);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table>";
                echo "<tr><th>ID</th><th>Title</th><th>Writer</th><th>Created</th><th>Status</th></tr>";
                foreach ($records as $record) {
                    echo "<tr>";
                    echo "<td>{$record['id']}</td>";
                    echo "<td>{$record['title']}</td>";
                    echo "<td>'{$record['writer']}'</td>";
                    echo "<td>{$record['created_at']}</td>";
                    echo "<td>{$record['status']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        // Check all unique writers
        echo "<h3>All Unique Writers in Database:</h3>";
        $stmt = $pdo->query("SELECT DISTINCT writer, COUNT(*) as count FROM board_consignment GROUP BY writer ORDER BY count DESC LIMIT 10");
        $writers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Writer</th><th>Count</th><th>Length</th></tr>";
        foreach ($writers as $writer) {
            echo "<tr>";
            echo "<td>'{$writer['writer']}'</td>";
            echo "<td>{$writer['count']}</td>";
            echo "<td>" . strlen($writer['writer']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (PDOException $e) {
        echo "<p class='error'>Error querying records: " . $e->getMessage() . "</p>";
    }
    ?>
</div>

<div class="section">
    <h2>5. Test Deletion Process</h2>
    <?php
    try {
        // Create a test record
        $testTitle = 'TEST_DELETE_' . uniqid();
        $stmt = $pdo->prepare("INSERT INTO board_consignment (title, content, writer, password, company_name, category, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$testTitle, 'Test content', 'test_debug_user', '1234', 'Test Company', 'Test', 'active']);
        $testId = $pdo->lastInsertId();
        
        echo "<p class='success'>✓ Created test record with ID: $testId</p>";
        
        // Verify it exists
        $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
        $stmt->execute([$testId]);
        $record = $stmt->fetch();
        
        if ($record) {
            echo "<p class='success'>✓ Test record verified in database</p>";
            
            // Now delete it
            $stmt = $pdo->prepare("DELETE FROM board_consignment WHERE id = ?");
            $result = $stmt->execute([$testId]);
            $rowCount = $stmt->rowCount();
            
            echo "<p>Delete query executed. Rows affected: $rowCount</p>";
            
            // Verify deletion
            $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
            $stmt->execute([$testId]);
            $record = $stmt->fetch();
            
            if (!$record) {
                echo "<p class='success'>✓ Test record successfully deleted</p>";
            } else {
                echo "<p class='error'>✗ Test record still exists after deletion!</p>";
                echo "<pre>" . print_r($record, true) . "</pre>";
            }
        } else {
            echo "<p class='error'>✗ Could not verify test record creation</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>Error in deletion test: " . $e->getMessage() . "</p>";
    }
    ?>
</div>

<div class="section">
    <h2>6. Check Foreign Key Constraints</h2>
    <?php
    try {
        $stmt = $pdo->prepare("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'board_consignment'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute();
        $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($constraints)) {
            echo "<p class='info'>No foreign key constraints found on board_consignment table</p>";
        } else {
            echo "<table>";
            echo "<tr><th>Constraint</th><th>Column</th><th>References Table</th><th>References Column</th></tr>";
            foreach ($constraints as $constraint) {
                echo "<tr>";
                echo "<td>{$constraint['CONSTRAINT_NAME']}</td>";
                echo "<td>{$constraint['COLUMN_NAME']}</td>";
                echo "<td>{$constraint['REFERENCED_TABLE_NAME']}</td>";
                echo "<td>{$constraint['REFERENCED_COLUMN_NAME']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>Error checking constraints: " . $e->getMessage() . "</p>";
    }
    ?>
</div>

<div class="section">
    <h2>7. Recommendations</h2>
    <ul>
        <li>Check if the writer field exactly matches the session name (including spaces)</li>
        <li>Verify that deletions are being committed if autocommit is OFF</li>
        <li>Check if there are any database triggers preventing deletion</li>
        <li>Review the admin deletion logs for any errors</li>
        <li>Test with the exact same user session that created the records</li>
    </ul>
</div>

</body>
</html>