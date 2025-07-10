<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h2>Upload Test Results</h2>";
    echo "<pre>";
    
    echo "File Details:\n";
    print_r($_FILES['test_file']);
    
    echo "\nPHP Settings:\n";
    echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
    echo "post_max_size: " . ini_get('post_max_size') . "\n";
    echo "file_uploads: " . ini_get('file_uploads') . "\n";
    echo "upload_tmp_dir: " . ini_get('upload_tmp_dir') . "\n";
    
    echo "\nDirectory Check:\n";
    $uploadDir = 'uploads/quote/';
    echo "Directory: $uploadDir\n";
    echo "Exists: " . (file_exists($uploadDir) ? 'Yes' : 'No') . "\n";
    echo "Is Dir: " . (is_dir($uploadDir) ? 'Yes' : 'No') . "\n";
    echo "Is Writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "\n";
    
    if (file_exists($uploadDir)) {
        echo "Permissions: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
        echo "Owner: " . posix_getpwuid(fileowner($uploadDir))['name'] . "\n";
        echo "Group: " . posix_getgrgid(filegroup($uploadDir))['name'] . "\n";
    }
    
    echo "\nCurrent User:\n";
    echo "User: " . get_current_user() . "\n";
    echo "UID: " . getmyuid() . "\n";
    echo "GID: " . getmygid() . "\n";
    
    // Try to upload
    if ($_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = time() . '_' . $_FILES['test_file']['name'];
        $targetPath = $uploadDir . $fileName;
        
        echo "\nUpload Attempt:\n";
        echo "Source: " . $_FILES['test_file']['tmp_name'] . "\n";
        echo "Target: " . $targetPath . "\n";
        
        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $targetPath)) {
            echo "SUCCESS! File uploaded to: $targetPath\n";
        } else {
            echo "FAILED! Error: " . error_get_last()['message'] . "\n";
        }
    }
    
    echo "</pre>";
} else {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Test</title>
</head>
<body>
    <h1>Upload Test</h1>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="test_file" required>
        <button type="submit">Test Upload</button>
    </form>
</body>
</html>
<?php } ?>