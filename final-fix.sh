#!/bin/bash
# Final fix for 504 Gateway Timeout - Using host network for DB connection

echo "=== Final Fix for 504 Gateway Timeout ==="
echo ""

cd /home/successbank/projects/docker/project1

echo "1. Updating db.php to use host network connection..."
cat > html/db.php << 'EOF'
<?php
// Database connection settings - Using host network
if (!defined('DB_HOST')) {
    // Since MariaDB is exposed on port 3306, use host's gateway
    define('DB_HOST', 'host.docker.internal');
    define('DB_PORT', '3306');
    define('DB_NAME', 'project1_db');
    define('DB_USER', 'root');
    define('DB_PASS', 'rootpassword');
}

// Alternative connection if host.docker.internal doesn't work
if (!function_exists('getDB')) {
function getDB() {
    $hosts = [
        'host.docker.internal' => 'Host network',
        '172.17.0.1' => 'Docker bridge gateway',
        'project1_mysql' => 'Container name',
        '172.18.0.3' => 'Container IP'
    ];
    
    foreach ($hosts as $host => $desc) {
        try {
            $dsn = "mysql:host=$host;port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_TIMEOUT => 2,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // If connection successful, save it for later use
            if (!defined('WORKING_DB_HOST')) {
                define('WORKING_DB_HOST', $host);
            }
            
            return $pdo;
        } catch (PDOException $e) {
            continue;
        }
    }
    
    // If all connections fail
    die("데이터베이스 연결 실패: 모든 연결 방법이 실패했습니다.");
}
}

// Test connection when accessed directly
if (basename($_SERVER['SCRIPT_NAME']) == 'db.php') {
    try {
        $pdo = getDB();
        echo "Database connection successful using: " . WORKING_DB_HOST;
    } catch (Exception $e) {
        echo "Database connection failed: " . $e->getMessage();
    }
}
?>
EOF

echo ""
echo "2. Adding host.docker.internal to PHP container..."
docker exec project1_php sh -c "grep -q 'host.docker.internal' /etc/hosts || echo '172.17.0.1 host.docker.internal' >> /etc/hosts"

echo ""
echo "3. Testing database connection..."
docker exec project1_php php /var/www/html/db.php

echo ""
echo "4. Creating a simple test page..."
cat > html/test_final.php << 'EOF'
<?php
require_once 'db.php';

echo "<h1>Connection Test</h1>";
echo "<pre>";

try {
    $pdo = getDB();
    echo "✓ Database connection successful!\n";
    echo "✓ Connected via: " . (defined('WORKING_DB_HOST') ? WORKING_DB_HOST : 'unknown') . "\n\n";
    
    // Test query
    $result = $pdo->query("SELECT VERSION() as version, NOW() as time");
    $data = $result->fetch();
    
    echo "MariaDB Version: " . $data['version'] . "\n";
    echo "Server Time: " . $data['time'] . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
EOF

echo ""
echo "5. Testing via web..."
curl -s http://localhost:1112/test_final.php | grep -E "(successful|Error)" || echo "Web test failed"

echo ""
echo "=== Fix Complete ==="
echo ""
echo "Please test these URLs:"
echo "- http://211.248.112.67:1112/test_final.php"
echo "- http://211.248.112.67:1112/info.php"
echo "- http://211.248.112.67:1112/"