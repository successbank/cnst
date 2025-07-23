<?php
require_once '../db.php';

header('Content-Type: application/json');

$spec_id = isset($_GET['spec_id']) ? (int)$_GET['spec_id'] : 0;

if ($spec_id <= 0) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            length,
            weight_per_piece,
            pieces_per_ton
        FROM rebar_length_info
        WHERE spec_id = ?
        ORDER BY length
    ");
    $stmt->execute([$spec_id]);
    $lengths = $stmt->fetchAll();
    
    echo json_encode($lengths);
} catch (Exception $e) {
    echo json_encode([]);
}
?>