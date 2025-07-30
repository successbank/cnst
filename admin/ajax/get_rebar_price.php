<?php
session_start();
require_once '../../db.php';
require_once '../admin_check.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

try {
    // 요청 파라미터 검증
    $category_code = $_GET['category_code'] ?? '';
    $specifications = $_GET['specifications'] ?? '';
    
    // 철근 카테고리인지 확인
    if ($category_code !== 'rebar') {
        $response['message'] = '철근 카테고리가 아닙니다.';
        echo json_encode($response);
        exit;
    }
    
    // 규격에서 직경 추출 (D10, D13, D16 등)
    if (preg_match('/D(\d+)/', $specifications, $matches)) {
        $spec_name = 'D' . $matches[1];
        
        // 철근 규격과 현재 단가 조회
        $stmt = $pdo->prepare("
            SELECT 
                rs.id,
                rs.spec_name,
                rs.diameter,
                rs.unit_weight,
                rp.unit_price as base_price,
                rp.effective_date
            FROM rebar_specifications rs
            LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id AND rp.is_active = TRUE
            WHERE rs.spec_name = ? AND rs.is_active = TRUE
        ");
        $stmt->execute([$spec_name]);
        $rebar_data = $stmt->fetch();
        
        if ($rebar_data && $rebar_data['base_price']) {
            $response['success'] = true;
            $response['data'] = [
                'base_price' => $rebar_data['base_price'],
                'unit_weight' => $rebar_data['unit_weight'],
                'spec_name' => $rebar_data['spec_name'],
                'effective_date' => $rebar_data['effective_date'],
                'message' => sprintf(
                    '철근 %s의 기본 단가(%s원/kg)가 적용되었습니다.',
                    $rebar_data['spec_name'],
                    number_format($rebar_data['base_price'])
                )
            ];
        } else {
            $response['message'] = '해당 규격의 철근 단가를 찾을 수 없습니다.';
        }
    } else {
        $response['message'] = '규격에서 철근 직경을 추출할 수 없습니다.';
    }
    
} catch (Exception $e) {
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
}

echo json_encode($response);
?>