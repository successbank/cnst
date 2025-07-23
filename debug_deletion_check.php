<?php
// Debug script to check admin deletion functionality
session_start();
require_once 'db.php';

echo "<h2>Admin Deletion Debug</h2>";
echo "<hr>";

// Create a test consignment record
echo "<h3>1. Creating test consignment record...</h3>";

try {
    $testData = [
        'title' => 'TEST DELETION - ' . date('Y-m-d H:i:s'),
        'category' => '기타',
        'company_name' => 'Test Company',
        'writer' => 'test',
        'contact_person' => '테스트',
        'contact_phone' => '010-1234-5678',
        'content' => 'This is a test record for deletion debugging',
        'status' => 'active',
        'price_info' => '협의'
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO board_consignment 
        (title, category, company_name, writer, contact_person, contact_phone, content, status, price_info, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $testData['title'],
        $testData['category'],
        $testData['company_name'],
        $testData['writer'],
        $testData['contact_person'],
        $testData['contact_phone'],
        $testData['content'],
        $testData['status'],
        $testData['price_info']
    ]);
    
    $testId = $pdo->lastInsertId();
    echo "<p style='color: green;'>Test record created with ID: $testId</p>";
    
    // Verify it was created
    $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
    $stmt->execute([$testId]);
    $record = $stmt->fetch();
    
    if ($record) {
        echo "<p>Record verified in database:</p>";
        echo "<pre>" . print_r($record, true) . "</pre>";
        
        // Simulate deletion
        echo "<hr>";
        echo "<h3>2. Simulating deletion...</h3>";
        
        $stmt = $pdo->prepare("DELETE FROM board_consignment WHERE id = ?");
        $result = $stmt->execute([$testId]);
        
        if ($result) {
            echo "<p style='color: green;'>DELETE query executed successfully.</p>";
            echo "<p>Rows affected: " . $stmt->rowCount() . "</p>";
            
            // Check if record still exists
            $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
            $stmt->execute([$testId]);
            $afterDelete = $stmt->fetch();
            
            if ($afterDelete) {
                echo "<p style='color: red;'>WARNING: Record still exists after deletion!</p>";
                echo "<pre>" . print_r($afterDelete, true) . "</pre>";
            } else {
                echo "<p style='color: green;'>Record successfully deleted from database.</p>";
            }
        } else {
            echo "<p style='color: red;'>DELETE query failed!</p>";
        }
    } else {
        echo "<p style='color: red;'>Failed to create test record!</p>";
    }
    
    // Check for any triggers or constraints
    echo "<hr>";
    echo "<h3>3. Checking for triggers on board_consignment table...</h3>";
    
    $stmt = $pdo->prepare("SHOW TRIGGERS LIKE 'board_consignment'");
    $stmt->execute();
    $triggers = $stmt->fetchAll();
    
    if ($triggers) {
        echo "<p>Found triggers:</p>";
        echo "<pre>" . print_r($triggers, true) . "</pre>";
    } else {
        echo "<p>No triggers found on board_consignment table.</p>";
    }
    
    // Check for foreign key constraints
    echo "<hr>";
    echo "<h3>4. Checking for foreign key constraints...</h3>";
    
    $stmt = $pdo->prepare("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE 
            TABLE_NAME = 'board_consignment' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $stmt->execute();
    $constraints = $stmt->fetchAll();
    
    if ($constraints) {
        echo "<p>Found foreign key constraints:</p>";
        echo "<pre>" . print_r($constraints, true) . "</pre>";
    } else {
        echo "<p>No foreign key constraints found.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

// Check if there's any caching mechanism
echo "<hr>";
echo "<h3>5. Checking for caching issues...</h3>";
echo "<p>Current cache headers from my_inquiries.php:</p>";
echo "<ul>";
echo "<li>Cache-Control: no-store, no-cache, must-revalidate, max-age=0</li>";
echo "<li>Cache-Control: post-check=0, pre-check=0</li>";
echo "<li>Pragma: no-cache</li>";
echo "</ul>";
echo "<p>These headers should prevent caching, so the issue is likely not cache-related.</p>";
?>