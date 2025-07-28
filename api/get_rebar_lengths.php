<?php
header('Content-Type: application/json');
require_once '../db.php';

$response = ['success' => false, 'message' => '', 'lengths' => []];

try {
    $spec_id = isset($_GET['spec_id']) ? intval($_GET['spec_id']) : 0;
    
    if (!$spec_id) {
        throw new Exception('Invalid specification ID');
    }
    
    // Get length information for the specification
    $stmt = $pdo->prepare("
        SELECT 
            rli.length,
            rli.pieces_per_ton,
            rli.total_weight,
            rli.weight_per_piece
        FROM rebar_length_info rli
        WHERE rli.spec_id = ?
        ORDER BY rli.length
    ");
    
    $stmt->execute([$spec_id]);
    $lengths = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($lengths)) {
        throw new Exception('No length data found for this specification');
    }
    
    $response['success'] = true;
    $response['lengths'] = $lengths;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>