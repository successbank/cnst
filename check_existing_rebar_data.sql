-- 철근 규격별 톤당 본수 데이터 확인
-- D10, D13, D25의 특정 길이 데이터 조회

-- D10 데이터 확인
SELECT 
    rs.spec_name,
    rs.unit_weight,
    rl.length,
    rl.pieces_per_ton,
    rl.weight_per_piece,
    rl.total_weight,
    ROUND(rl.pieces_per_ton * rl.weight_per_piece, 2) as calculated_ton_weight
FROM rebar_specifications rs
JOIN rebar_length_info rl ON rs.id = rl.spec_id
WHERE rs.spec_name = 'D10' 
    AND rl.length IN (6.0, 6.1, 7.1, 7.2)
ORDER BY rl.length;

-- D13 데이터 확인
SELECT 
    rs.spec_name,
    rs.unit_weight,
    rl.length,
    rl.pieces_per_ton,
    rl.weight_per_piece,
    rl.total_weight,
    ROUND(rl.pieces_per_ton * rl.weight_per_piece, 2) as calculated_ton_weight
FROM rebar_specifications rs
JOIN rebar_length_info rl ON rs.id = rl.spec_id
WHERE rs.spec_name = 'D13' 
    AND rl.length IN (6.1, 6.2)
ORDER BY rl.length;

-- D25 데이터 확인
SELECT 
    rs.spec_name,
    rs.unit_weight,
    rl.length,
    rl.pieces_per_ton,
    rl.weight_per_piece,
    rl.total_weight,
    ROUND(rl.pieces_per_ton * rl.weight_per_piece, 2) as calculated_ton_weight
FROM rebar_specifications rs
JOIN rebar_length_info rl ON rs.id = rl.spec_id
WHERE rs.spec_name = 'D25' 
    AND rl.length IN (8.0, 8.1)
ORDER BY rl.length;

-- 모든 철근 규격의 단위중량 확인
SELECT 
    spec_name,
    diameter,
    unit_weight
FROM rebar_specifications
WHERE is_active = TRUE
ORDER BY display_order;