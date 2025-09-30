<?php
require_once 'db.php';

// Get filter parameters
$spec_filter = isset($_GET['spec']) ? $_GET['spec'] : '';
$length_filter = isset($_GET['length']) ? $_GET['length'] : '';

try {
    $pdo = getDB();

    // Build query with filters
    $query = "SELECT * FROM rebar_length_data WHERE 1=1";
    $params = [];

    if ($spec_filter) {
        $query .= " AND spec_name = :spec";
        $params[':spec'] = $spec_filter;
    }

    if ($length_filter) {
        $query .= " AND length = :length";
        $params[':length'] = $length_filter;
    }

    $query .= " ORDER BY spec_name ASC, length ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set headers for Excel download
    $filename = "rebar_data_" . date('Y-m-d_His') . ".csv";
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Add BOM for UTF-8 Excel compatibility
    echo "\xEF\xBB\xBF";

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write headers
    fputcsv($output, [
        'ID',
        '규격',
        '길이(m)',
        '본중(kg)',
        '본수',
        '톤당 중량',
        '단위 중량',
        '등록일'
    ]);

    // Write data
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['spec_name'],
            $row['length'],
            $row['piece_weight'] ?? '',
            $row['pieces_per_length'] ?? '',
            $row['weight_per_ton'] ?? '',
            $row['unit_weight'] ?? '',
            $row['created_at']
        ]);
    }

    fclose($output);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>