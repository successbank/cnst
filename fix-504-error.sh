#!/bin/bash
# Complete fix for 504 Gateway Timeout error

echo "=== Fixing 504 Gateway Timeout Error ==="
echo ""

cd /home/successbank/projects/docker/project1

echo "1. Creating a simple PHP info file to test..."
echo '<?php phpinfo(); ?>' > html/info.php

echo ""
echo "2. Updating db.php to use correct MySQL host..."
# Backup original db.php
cp html/db.php html/db.php.backup

# Create new db.php with direct connection
cat > html/db.php << 'EOF'
<?php
// Database connection settings for Docker environment
if (!defined('DB_HOST')) {
    // Use the MySQL container IP directly to avoid DNS issues
    define('DB_HOST', '172.18.0.3');
    define('DB_PORT', '3306');
    define('DB_NAME', 'project1_db');
    define('DB_USER', 'root');
    define('DB_PASS', 'rootpassword');
}

// PDO MySQL connection function
if (!function_exists('getDB')) {
function getDB() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        // For debugging - remove in production
        die("데이터베이스 연결 실패: " . $e->getMessage());
    }
}
}

// Test connection when this file is accessed directly
if (basename($_SERVER['SCRIPT_NAME']) == 'db.php') {
    try {
        $pdo = getDB();
        echo "Database connection successful!";
    } catch (Exception $e) {
        echo "Database connection failed: " . $e->getMessage();
    }
}
?>
EOF

echo ""
echo "3. Testing the fix..."
echo ""

# Test info.php
echo "Testing PHP info page:"
curl -s http://localhost:1112/info.php | grep -o "PHP Version" | head -1 && echo " - PHP is working!" || echo " - PHP is NOT working!"

echo ""
echo "Testing database connection:"
docker exec project1_php php /var/www/html/db.php

echo ""
echo "=== Fix Applied ==="
echo ""
echo "You can now test:"
echo "1. http://211.248.112.67:1112/info.php - Should show PHP info"
echo "2. http://211.248.112.67:1112/ - Should show the main website"
echo ""
echo "If still having issues, you may need to restart containers with:"
echo "sudo docker-compose restart"