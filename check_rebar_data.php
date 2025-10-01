<?php
require_once 'db.php';

try {
    $pdo = getDB();

    // Check which specs are in the database
    $sql = "SELECT DISTINCT spec_name FROM rebar_length_data ORDER BY spec_name";
    $stmt = $pdo->query($sql);
    $specs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Specs in database: " . implode(', ', $specs) . "\n\n";

    // Check D51 data specifically
    $sql = "SELECT * FROM rebar_length_data WHERE spec_name = 'D51' ORDER BY length";
    $stmt = $pdo->query($sql);
    $d51_data = $stmt->fetchAll();

    echo "D51 data count: " . count($d51_data) . "\n";

    if (count($d51_data) > 0) {
        echo "Sample D51 data:\n";
        foreach (array_slice($d51_data, 0, 5) as $row) {
            echo sprintf("  Length: %.1f, Pieces: %d\n", $row['length'], $row['pieces_per_length']);
        }
    }

    // Check total record count
    $sql = "SELECT COUNT(*) as total FROM rebar_length_data";
    $stmt = $pdo->query($sql);
    $total = $stmt->fetch()['total'];

    echo "\nTotal records in database: " . $total . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>