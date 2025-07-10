<?php
// Test upload directory permissions
$dirs = ['uploads/', 'uploads/quote/', 'uploads/notice/', 'uploads/news/'];

echo "<h2>Upload Directory Test</h2>";
echo "<pre>";

foreach ($dirs as $dir) {
    echo "Directory: $dir\n";
    
    if (!file_exists($dir)) {
        echo "  - Does not exist. Attempting to create...\n";
        if (@mkdir($dir, 0777, true)) {
            echo "  - Created successfully!\n";
        } else {
            echo "  - Failed to create.\n";
        }
    }
    
    if (file_exists($dir)) {
        echo "  - Exists: Yes\n";
        echo "  - Writable: " . (is_writable($dir) ? "Yes" : "No") . "\n";
        echo "  - Permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
    }
    
    echo "\n";
}

echo "</pre>";
?>