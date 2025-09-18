<?php
echo "Testing database connection...\n";

$hosts = [
    "project1_mysql" => "Docker hostname",
    "172.18.0.3" => "Direct IP",
    "mysql" => "Service name",
    "127.0.0.1" => "Localhost"
];

foreach ($hosts as $host => $desc) {
    echo "\nTrying $desc ($host):\n";
    
    $socket = @fsockopen($host, 3306, $errno, $errstr, 2);
    if ($socket) {
        echo "  ✓ Socket connection successful\n";
        fclose($socket);
        
        try {
            $dsn = "mysql:host=$host;port=3306;dbname=project5_db;charset=utf8mb4";
            $pdo = new PDO($dsn, "root", "rootpassword");
            echo "  ✓ PDO connection successful\n";
            
            $result = $pdo->query("SELECT VERSION()");
            $version = $result->fetchColumn();
            echo "  ✓ MySQL Version: $version\n";
            
        } catch (PDOException $e) {
            echo "  ✗ PDO Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  ✗ Socket connection failed: $errstr ($errno)\n";
    }
}
?>
