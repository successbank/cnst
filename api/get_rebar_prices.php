<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';

// CORS 설정 (필요시)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

try {
    // 파라미터 받기
    $spec_id = isset($_GET['spec_id']) ? intval($_GET['spec_id']) : null;
    $material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : null;
    $include_history = isset($_GET['include_history']) ? filter_var($_GET['include_history'], FILTER_VALIDATE_BOOLEAN) : false;
    
    // 기본 응답 구조
    $response = [
        'success' => true,
        'data' => []
    ];
    
    if ($spec_id) {
        // 특정 규격의 재질별 가격 조회
        $sql = "
            SELECT 
                rs.id AS spec_id,
                rs.spec_name,
                rs.diameter,
                rs.unit_weight,
                rm.id AS material_id,
                rm.material_code,
                rm.material_name,
                rm.additional_price AS material_price,
                COALESCE(rp.unit_price, 0) AS base_price,
                (COALESCE(rp.unit_price, 0) + rm.additional_price) AS total_price,
                rp.effective_date
            FROM rebar_specifications rs
            CROSS JOIN rebar_materials rm
            LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id 
                AND rp.is_active = TRUE 
                AND rp.effective_date <= CURDATE()
                AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE())
            WHERE rs.id = :spec_id 
                AND rs.is_active = TRUE 
                AND rm.is_active = TRUE
        ";
        
        $params = ['spec_id' => $spec_id];
        
        if ($material_id) {
            $sql .= " AND rm.id = :material_id";
            $params['material_id'] = $material_id;
        }
        
        $sql .= " ORDER BY rm.display_order";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $response['data']['prices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 가격 히스토리 포함 옵션
        if ($include_history && $spec_id) {
            $history_sql = "
                SELECT 
                    rp.unit_price,
                    rp.effective_date,
                    rp.expiry_date,
                    rp.created_at,
                    CASE 
                        WHEN rp.is_active = TRUE AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE()) 
                        THEN '현재가격' 
                        ELSE '과거가격' 
                    END AS status
                FROM rebar_prices rp
                WHERE rp.spec_id = :spec_id
                ORDER BY rp.effective_date DESC
                LIMIT 10
            ";
            
            $stmt = $pdo->prepare($history_sql);
            $stmt->execute(['spec_id' => $spec_id]);
            $response['data']['price_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
    } else {
        // 전체 규격의 재질별 가격 조회
        $sql = "
            SELECT 
                rs.id AS spec_id,
                rs.spec_name,
                rs.diameter,
                rs.unit_weight,
                rm.id AS material_id,
                rm.material_code,
                rm.material_name,
                rm.additional_price AS price,
                rp.effective_date
            FROM rebar_specifications rs
            CROSS JOIN rebar_materials rm
            LEFT JOIN rebar_prices rp ON rs.id = rp.spec_id 
                AND rp.is_active = TRUE 
                AND rp.effective_date <= CURDATE()
                AND (rp.expiry_date IS NULL OR rp.expiry_date >= CURDATE())
            WHERE rs.is_active = TRUE AND rm.is_active = TRUE
            ORDER BY rs.display_order, rm.display_order
        ";
        
        $stmt = $pdo->query($sql);
        $response['data']['all_prices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 재질 목록도 포함
    $materials_sql = "SELECT id, material_code, material_name, additional_price FROM rebar_materials WHERE is_active = TRUE ORDER BY display_order";
    $stmt = $pdo->query($materials_sql);
    $response['data']['materials'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>