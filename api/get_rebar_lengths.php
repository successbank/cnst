<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';

// CORS 설정 (필요시)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    // 파라미터 받기
    $spec_id = isset($_GET['spec_id']) ? intval($_GET['spec_id']) : null;
    
    if (!$spec_id) {
        echo json_encode(['success' => false, 'message' => '규격 ID가 필요합니다.']);
        exit;
    }
    
    // 길이 정보 조회
    $stmt = $pdo->prepare("
        SELECT 
            rl.length,
            rl.pieces_per_ton,
            rl.weight_per_piece,
            rl.total_weight,
            rs.unit_weight
        FROM rebar_length_info rl
        JOIN rebar_specifications rs ON rl.spec_id = rs.id
        WHERE rl.spec_id = ?
        ORDER BY rl.length
    ");
    $stmt->execute([$spec_id]);
    $lengths = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($lengths)) {
        echo json_encode(['success' => false, 'message' => '길이 정보를 찾을 수 없습니다.']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'lengths' => $lengths
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
?>