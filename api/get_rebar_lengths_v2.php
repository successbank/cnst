<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';

// CORS 설정
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    // 파라미터 받기 - 규격명 또는 spec_id로 조회 가능
    $spec_name = isset($_GET['spec_name']) ? $_GET['spec_name'] : null;
    $spec_id = isset($_GET['spec_id']) ? intval($_GET['spec_id']) : null;
    
    if (!$spec_name && !$spec_id) {
        echo json_encode(['success' => false, 'message' => '규격명 또는 규격 ID가 필요합니다.']);
        exit;
    }
    
    // spec_id가 있으면 spec_name 조회
    if ($spec_id && !$spec_name) {
        $stmt = $pdo->prepare("SELECT spec_name FROM rebar_specifications WHERE id = ?");
        $stmt->execute([$spec_id]);
        $spec = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($spec) {
            $spec_name = $spec['spec_name'];
        } else {
            echo json_encode(['success' => false, 'message' => '해당 규격을 찾을 수 없습니다.']);
            exit;
        }
    }
    
    // 길이 정보 조회 (rebar_length_data 테이블 사용)
    $stmt = $pdo->prepare("
        SELECT 
            length,
            piece_weight as weight_per_piece,
            pieces_per_ton,
            weight_per_ton as total_weight,
            unit_weight
        FROM rebar_length_data
        WHERE spec_name = ?
        ORDER BY length
    ");
    $stmt->execute([$spec_name]);
    $lengths = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($lengths)) {
        echo json_encode(['success' => false, 'message' => '길이 정보를 찾을 수 없습니다.']);
        exit;
    }
    
    // 기존 API와 호환되도록 형식 맞추기
    foreach ($lengths as &$length) {
        $length['length'] = number_format($length['length'], 1);
        $length['weight_per_piece'] = number_format($length['weight_per_piece'], 2);
        $length['total_weight'] = number_format($length['total_weight'], 2);
        $length['unit_weight'] = number_format($length['unit_weight'], 3);
    }
    
    echo json_encode([
        'success' => true,
        'lengths' => $lengths,
        'spec_name' => $spec_name
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
?>