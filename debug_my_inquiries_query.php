<?php
// Debug script to trace the exact queries being run in my_inquiries.php
session_start();
require_once 'member_check.php';
require_once 'db.php';

// Set test session values
$_SESSION['user_id'] = 'test';
$_SESSION['name'] = '테스트';
$_SESSION['member_id'] = 1; // Adjust this based on actual member ID

echo "<h2>My Inquiries Query Debug</h2>";
echo "<hr>";

echo "<h3>Session Information:</h3>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "name: " . ($_SESSION['name'] ?? 'NOT SET') . "\n";
echo "member_id: " . ($_SESSION['member_id'] ?? 'NOT SET') . "\n";
echo "</pre>";

$member_id = $_SESSION['member_id'];
$user_id = $_SESSION['user_id'] ?? '';

try {
    // Replicate the exact query from my_inquiries.php line 50-52
    echo "<hr>";
    echo "<h3>1. Consignment Query (Exact replica from my_inquiries.php):</h3>";
    
    $writer = $_SESSION['name'] ?? $user_id;
    echo "<p>Writer value being used: <strong>" . htmlspecialchars($writer) . "</strong></p>";
    
    // Execute the exact query
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_consignment WHERE writer = ?");
    $stmt->execute([$writer]);
    $totalConsignments = $stmt->fetchColumn();
    
    echo "<p>Total consignments found: <strong>$totalConsignments</strong></p>";
    
    // Get the actual records
    echo "<h4>Consignment records details:</h4>";
    $stmt = $pdo->prepare("
        SELECT 'consignment' as type, id, title, company_name as company, created_at, status
        FROM board_consignment 
        WHERE writer = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$writer]);
    $consignments = $stmt->fetchAll();
    
    if ($consignments) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Company</th><th>Status</th><th>Created At</th></tr>";
        foreach ($consignments as $row) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['company']) . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No consignment records found.</p>";
    }
    
    // Check for variations in writer field
    echo "<hr>";
    echo "<h3>2. Check all variations of writer field:</h3>";
    
    $variations = ['test', '테스트', 'TEST', '테스트1', 'test1'];
    
    foreach ($variations as $variant) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_consignment WHERE writer = ?");
        $stmt->execute([$variant]);
        $count = $stmt->fetchColumn();
        echo "<p>Writer = '$variant': <strong>$count</strong> records</p>";
    }
    
    // Check with LIKE operator
    echo "<hr>";
    echo "<h3>3. Check with LIKE operator:</h3>";
    
    $stmt = $pdo->prepare("SELECT writer, COUNT(*) as count FROM board_consignment WHERE writer LIKE ? GROUP BY writer");
    $stmt->execute(['%test%']);
    $results = $stmt->fetchAll();
    
    if ($results) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Writer</th><th>Count</th></tr>";
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['writer']) . "</td>";
            echo "<td>" . $row['count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check for quote inquiries too
    echo "<hr>";
    echo "<h3>4. Quote inquiries check:</h3>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM board_quote WHERE member_id = ? OR writer = ?");
    $stmt->execute([$member_id, $_SESSION['name'] ?? $user_id]);
    $totalQuotes = $stmt->fetchColumn();
    
    echo "<p>Total quotes found: <strong>$totalQuotes</strong></p>";
    
    // Summary
    echo "<hr>";
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li>The query in my_inquiries.php uses: <code>WHERE writer = ?</code> with value: <strong>" . htmlspecialchars($writer) . "</strong></li>";
    echo "<li>This is an exact match query, not a LIKE query</li>";
    echo "<li>The writer field must match exactly with the session name or user_id</li>";
    echo "<li>If records are not showing after deletion, they must still exist in the database</li>";
    echo "</ul>";
    
    // Recommendation
    echo "<hr>";
    echo "<h3>Recommendations:</h3>";
    echo "<ol>";
    echo "<li>Check if the admin deletion is actually executing (check admin_consignment.php line 14)</li>";
    echo "<li>Verify that the writer field exactly matches the session name</li>";
    echo "<li>Consider adding logging to track deletions</li>";
    echo "<li>Check if there are any database replication issues or delayed commits</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>