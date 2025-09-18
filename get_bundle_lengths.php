<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$standard = $_GET['standard'] ?? '';
$material = $_GET['material'] ?? '';

if (empty($standard) || empty($material)) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            p_unit_length as length,
            p_bd_count as bd_count,
            p_bd_weight as bd_weight
        FROM rebar_bundle_data
        WHERE p_standard = :standard 
        AND p_material = :material
        AND p_bd_count > 0 
        AND p_bd_weight > 0
        ORDER BY p_unit_length
    ");
    
    $stmt->execute([
        'standard' => $standard,
        'material' => $material
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>