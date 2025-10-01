<?php
require_once 'db.php';

try {
    $pdo = getDB();

    // Show all tables
    $sql = "SHOW TABLES";
    $stmt = $pdo->query($sql);
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    // Check rebar_length_data structure
    echo "\nrebar_length_data structure:\n";
    $sql = "SHOW CREATE TABLE rebar_length_data";
    try {
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch();
        echo $result['Create Table'] . "\n";
    } catch (PDOException $e) {
        echo "Table does not exist: " . $e->getMessage() . "\n";
    }

    // Check indexes
    echo "\nIndexes on rebar_length_data:\n";
    $sql = "SHOW INDEXES FROM rebar_length_data";
    try {
        $stmt = $pdo->query($sql);
        $indexes = $stmt->fetchAll();
        foreach ($indexes as $index) {
            echo sprintf("  - %s on %s (unique: %s)\n",
                $index['Key_name'],
                $index['Column_name'],
                $index['Non_unique'] == 0 ? 'YES' : 'NO'
            );
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>