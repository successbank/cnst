<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';

// CORS 설정
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_lengths') {
        // 특정 규격의 길이 목록 조회
        $spec_id = isset($_GET['spec_id']) ? intval($_GET['spec_id']) : null;
        
        if (!$spec_id) {
            echo json_encode(['success' => false, 'message' => '규격 ID가 필요합니다.']);
            exit;
        }
        
        // spec_id로 spec_name 조회
        $stmt = $pdo->prepare("SELECT spec_name, unit_weight FROM rebar_specifications WHERE id = ?");
        $stmt->execute([$spec_id]);
        $spec = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$spec) {
            echo json_encode(['success' => false, 'message' => '해당 규격을 찾을 수 없습니다.']);
            exit;
        }
        
        // rebar_length_data에서 길이 정보 조회
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
        $stmt->execute([$spec['spec_name']]);
        $lengths = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 기준 단가 조회
        $stmt = $pdo->prepare("
            SELECT unit_price 
            FROM rebar_prices 
            WHERE spec_id = ? AND is_active = TRUE
        ");
        $stmt->execute([$spec_id]);
        $price = $stmt->fetch(PDO::FETCH_ASSOC);
        $unit_price = $price ? $price['unit_price'] : 0;
        
        // 기존 형식에 맞춰 변환
        $formatted_lengths = [];
        foreach ($lengths as $length) {
            $formatted_lengths[] = [
                'spec_id' => $spec_id,
                'length' => $length['length'],
                'weight_per_piece' => $length['weight_per_piece'],
                'pieces_per_ton' => $length['pieces_per_ton'],
                'total_weight' => $length['total_weight'],
                'unit_weight' => $length['unit_weight'],
                'unit_price' => $unit_price
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $formatted_lengths]);
        exit;
    }
    
    if ($action === 'calculate') {
        // 견적 계산
        $spec_id = isset($_GET['spec_id']) ? intval($_GET['spec_id']) : null;
        $length = isset($_GET['length']) ? floatval($_GET['length']) : null;
        $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : null;
        $material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : null;
        
        if (!$spec_id || !$length || !$quantity) {
            echo json_encode(['success' => false, 'message' => '필수 파라미터가 누락되었습니다.']);
            exit;
        }
        
        // spec_id로 spec_name 조회
        $stmt = $pdo->prepare("SELECT spec_name FROM rebar_specifications WHERE id = ?");
        $stmt->execute([$spec_id]);
        $spec = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$spec) {
            echo json_encode(['success' => false, 'message' => '해당 규격을 찾을 수 없습니다.']);
            exit;
        }
        
        // 길이별 데이터 조회
        $stmt = $pdo->prepare("
            SELECT * FROM rebar_length_data
            WHERE spec_name = ? AND length = ?
        ");
        $stmt->execute([$spec['spec_name'], $length]);
        $length_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$length_data) {
            echo json_encode(['success' => false, 'message' => '해당 길이 정보를 찾을 수 없습니다.']);
            exit;
        }
        
        // 기준 단가 조회
        $stmt = $pdo->prepare("
            SELECT unit_price 
            FROM rebar_prices 
            WHERE spec_id = ? AND is_active = TRUE
        ");
        $stmt->execute([$spec_id]);
        $price = $stmt->fetch(PDO::FETCH_ASSOC);
        $unit_price = $price ? $price['unit_price'] : 1000; // 기본값 1000원/kg
        
        // 재질 추가 단가 조회
        $material_price = 0;
        $material_name = '';
        if ($material_id) {
            $stmt = $pdo->prepare("SELECT material_name, additional_price FROM rebar_materials WHERE id = ?");
            $stmt->execute([$material_id]);
            $material = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($material) {
                $material_price = $material['additional_price'];
                $material_name = $material['material_name'];
            }
        }
        
        // 계산
        $ton_quantity = $quantity; // 사용자 입력은 톤 단위
        $pieces_per_ton = $length_data['pieces_per_ton'];
        $actual_quantity = $ton_quantity * $pieces_per_ton; // 실제 본수 = 톤 수 × 톤당 본수
        $total_weight = $length_data['weight_per_ton'] * $ton_quantity; // 총 중량 = 톤당 중량 × 톤수
        
        // 가격 계산
        $base_price = $total_weight * $unit_price; // 기본 가격 = 총 중량 × 단가
        $material_add_price = $total_weight * $material_price; // 재질 추가 가격
        $total_price = $base_price + $material_add_price; // 총 가격
        
        echo json_encode([
            'success' => true,
            'data' => [
                'spec_name' => $spec['spec_name'],
                'length' => $length,
                'ton_quantity' => $ton_quantity,
                'actual_quantity' => $actual_quantity,
                'pieces_per_ton' => $pieces_per_ton,
                'weight_per_piece' => $length_data['piece_weight'],
                'total_weight' => $total_weight,
                'unit_price' => $unit_price,
                'material_name' => $material_name,
                'material_price' => $material_price,
                'base_price' => $base_price,
                'material_add_price' => $material_add_price,
                'total_price' => $total_price
            ]
        ]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '오류가 발생했습니다: ' . $e->getMessage()
    ]);
}
?>