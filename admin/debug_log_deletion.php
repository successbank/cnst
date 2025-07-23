<?php
// Enhanced admin_consignment.php with deletion logging
require_once '../db.php';
session_start();

// Create deletion log table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deletion_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(50),
            record_id INT,
            deleted_by VARCHAR(100),
            deletion_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            deletion_status VARCHAR(20),
            error_message TEXT,
            record_data TEXT
        )
    ");
} catch (PDOException $e) {
    // Table might already exist
}

$action = $_GET['action'] ?? 'list';

// Enhanced deletion with logging
if($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        // First, get the record data before deletion
        $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
        $stmt->execute([$id]);
        $recordData = $stmt->fetch();
        
        if ($recordData) {
            // Log the attempt
            $logStmt = $pdo->prepare("
                INSERT INTO deletion_log (table_name, record_id, deleted_by, deletion_status, record_data)
                VALUES ('board_consignment', ?, ?, 'attempting', ?)
            ");
            $logStmt->execute([$id, $_SESSION['admin_id'] ?? 'unknown', json_encode($recordData)]);
            $logId = $pdo->lastInsertId();
            
            // Attempt deletion
            $stmt = $pdo->prepare("DELETE FROM board_consignment WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Update log with success
                $updateLog = $pdo->prepare("UPDATE deletion_log SET deletion_status = 'success' WHERE id = ?");
                $updateLog->execute([$logId]);
                
                // Verify deletion
                $verifyStmt = $pdo->prepare("SELECT COUNT(*) FROM board_consignment WHERE id = ?");
                $verifyStmt->execute([$id]);
                $stillExists = $verifyStmt->fetchColumn() > 0;
                
                if ($stillExists) {
                    // Record still exists after deletion!
                    $updateLog = $pdo->prepare("
                        UPDATE deletion_log 
                        SET deletion_status = 'failed', 
                            error_message = 'Record still exists after DELETE query' 
                        WHERE id = ?
                    ");
                    $updateLog->execute([$logId]);
                    
                    header('Location: admin_consignment.php?msg=delete_failed');
                    exit;
                }
                
                header('Location: admin_consignment.php?msg=deleted');
                exit;
            } else {
                // Update log with failure
                $updateLog = $pdo->prepare("
                    UPDATE deletion_log 
                    SET deletion_status = 'failed', 
                        error_message = 'DELETE query returned false or affected 0 rows' 
                    WHERE id = ?
                ");
                $updateLog->execute([$logId]);
                
                header('Location: admin_consignment.php?msg=delete_failed');
                exit;
            }
        } else {
            // Record doesn't exist
            header('Location: admin_consignment.php?msg=not_found');
            exit;
        }
    } catch(PDOException $e) {
        // Log the error
        if (isset($logId)) {
            $updateLog = $pdo->prepare("
                UPDATE deletion_log 
                SET deletion_status = 'error', 
                    error_message = ? 
                WHERE id = ?
            ");
            $updateLog->execute([$e->getMessage(), $logId]);
        }
        
        $error = "삭제 중 오류가 발생했습니다: " . $e->getMessage();
        header('Location: admin_consignment.php?msg=error&error=' . urlencode($error));
        exit;
    }
}

// View deletion log
?>
<!DOCTYPE html>
<html>
<head>
    <title>Deletion Log Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .failed { color: red; }
        .error { color: darkred; }
        .attempting { color: orange; }
        .record-data { max-width: 300px; overflow: auto; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Board Consignment Deletion Log</h1>
    
    <h2>Recent Deletion Attempts:</h2>
    <?php
    try {
        $stmt = $pdo->query("
            SELECT * FROM deletion_log 
            WHERE table_name = 'board_consignment' 
            ORDER BY deletion_time DESC 
            LIMIT 50
        ");
        $logs = $stmt->fetchAll();
        
        if ($logs) {
            echo "<table>";
            echo "<tr>
                    <th>ID</th>
                    <th>Record ID</th>
                    <th>Deleted By</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Error</th>
                    <th>Record Data</th>
                  </tr>";
            
            foreach ($logs as $log) {
                echo "<tr>";
                echo "<td>" . $log['id'] . "</td>";
                echo "<td>" . $log['record_id'] . "</td>";
                echo "<td>" . htmlspecialchars($log['deleted_by']) . "</td>";
                echo "<td>" . $log['deletion_time'] . "</td>";
                echo "<td class='" . $log['deletion_status'] . "'>" . $log['deletion_status'] . "</td>";
                echo "<td>" . htmlspecialchars($log['error_message'] ?? '-') . "</td>";
                echo "<td class='record-data'><pre>" . htmlspecialchars($log['record_data'] ?? '') . "</pre></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No deletion logs found.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error reading logs: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h2>Currently Existing Test Records:</h2>
    <?php
    try {
        $stmt = $pdo->query("
            SELECT id, title, writer, status, created_at 
            FROM board_consignment 
            WHERE writer LIKE '%test%' OR writer LIKE '%테스트%'
            ORDER BY created_at DESC
        ");
        $records = $stmt->fetchAll();
        
        if ($records) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Title</th><th>Writer</th><th>Status</th><th>Created</th></tr>";
            foreach ($records as $record) {
                echo "<tr>";
                echo "<td>" . $record['id'] . "</td>";
                echo "<td>" . htmlspecialchars($record['title']) . "</td>";
                echo "<td>" . htmlspecialchars($record['writer']) . "</td>";
                echo "<td>" . $record['status'] . "</td>";
                echo "<td>" . $record['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No test records found.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <hr>
    <p><a href="admin_consignment.php">Back to Consignment Admin</a></p>
</body>
</html>