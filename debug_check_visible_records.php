<?php
require_once 'db.php';
session_start();

// Set headers
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Visible Records Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .info { color: #0066cc; }
        .warning { color: #ff9900; }
        .error { color: #cc0000; }
        .success { color: #009900; }
    </style>
</head>
<body>
    <h1>Visible Records Check</h1>
    
    <h2>Current Session Info:</h2>
    <?php
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
    echo "<p>Name: " . ($_SESSION['name'] ?? 'Not set') . "</p>";
    echo "<p>Member ID: " . ($_SESSION['member_id'] ?? 'Not set') . "</p>";
    ?>
    
    <h2>Check Records by Writer:</h2>
    <?php
    try {
        // Check for records with writer = '성공은행'
        $writers_to_check = ['성공은행', 'successbank', '성공은행1', 'test', '테스트'];
        
        foreach ($writers_to_check as $writer) {
            $stmt = $pdo->prepare("SELECT id, title, writer, company_name, created_at, status FROM board_consignment WHERE writer = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$writer]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($records);
            
            echo "<h3>Writer = '$writer': Found $count records</h3>";
            
            if ($count > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Title</th><th>Writer</th><th>Company</th><th>Created</th><th>Status</th></tr>";
                foreach ($records as $record) {
                    echo "<tr>";
                    echo "<td>{$record['id']}</td>";
                    echo "<td>{$record['title']}</td>";
                    echo "<td class='warning'>'{$record['writer']}'</td>";
                    echo "<td>{$record['company_name']}</td>";
                    echo "<td>{$record['created_at']}</td>";
                    echo "<td>{$record['status']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        // Show all unique writers
        echo "<h2>All Unique Writers in Consignment Table:</h2>";
        $stmt = $pdo->query("SELECT writer, COUNT(*) as count FROM board_consignment GROUP BY writer ORDER BY count DESC");
        $writers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Writer</th><th>Count</th><th>Hex</th></tr>";
        foreach ($writers as $writer) {
            $hex = bin2hex($writer['writer']);
            echo "<tr>";
            echo "<td class='info'>'{$writer['writer']}'</td>";
            echo "<td>{$writer['count']}</td>";
            echo "<td style='font-family: monospace; font-size: 12px;'>$hex</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check specific IDs from screenshot
        echo "<h2>Check Specific Records (from screenshot):</h2>";
        $specific_ids = [1, 2, 3]; // IDs visible in screenshot
        
        $id_list = implode(',', $specific_ids);
        $stmt = $pdo->query("SELECT * FROM board_consignment WHERE id IN ($id_list)");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($records)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Title</th><th>Writer</th><th>Company</th><th>Status</th></tr>";
            foreach ($records as $record) {
                echo "<tr>";
                echo "<td>{$record['id']}</td>";
                echo "<td>{$record['title']}</td>";
                echo "<td class='error'>'{$record['writer']}'</td>";
                echo "<td>{$record['company_name']}</td>";
                echo "<td>{$record['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='success'>Records with IDs 1, 2, 3 have been deleted successfully.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>Database error: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h2>Possible Issues:</h2>
    <ul>
        <li>The screenshots might be showing cached data</li>
        <li>The user might be looking at a different account's data</li>
        <li>There might be a session mix-up</li>
        <li>The records shown in the screenshot belong to a different writer (성공은행)</li>
    </ul>
</body>
</html>