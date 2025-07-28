-- Add all missing lengths for D35, D38, D41, D51
-- First, get all possible lengths from D10 (which has all 61 lengths)

-- D35
INSERT INTO rebar_length_info (spec_id, length, weight_per_piece, pieces_per_ton, total_weight)
SELECT 
    (SELECT id FROM rebar_specifications WHERE spec_name = 'D35'),
    d10.length,
    7.51 * d10.length,  -- D35 unit weight is 7.51
    0,
    NULL
FROM rebar_length_info d10
WHERE d10.spec_id = (SELECT id FROM rebar_specifications WHERE spec_name = 'D10')
AND d10.length NOT IN (
    SELECT length FROM rebar_length_info 
    WHERE spec_id = (SELECT id FROM rebar_specifications WHERE spec_name = 'D35')
);

-- D38
INSERT INTO rebar_length_info (spec_id, length, weight_per_piece, pieces_per_ton, total_weight)
SELECT 
    (SELECT id FROM rebar_specifications WHERE spec_name = 'D38'),
    d10.length,
    8.95 * d10.length,  -- D38 unit weight is 8.95
    0,
    NULL
FROM rebar_length_info d10
WHERE d10.spec_id = (SELECT id FROM rebar_specifications WHERE spec_name = 'D10')
AND d10.length NOT IN (
    SELECT length FROM rebar_length_info 
    WHERE spec_id = (SELECT id FROM rebar_specifications WHERE spec_name = 'D38')
);

-- D41
INSERT INTO rebar_length_info (spec_id, length, weight_per_piece, pieces_per_ton, total_weight)
SELECT 
    (SELECT id FROM rebar_specifications WHERE spec_name = 'D41'),
    d10.length,
    10.5 * d10.length,  -- D41 unit weight is 10.5
    0,
    NULL
FROM rebar_length_info d10
WHERE d10.spec_id = (SELECT id FROM rebar_specifications WHERE spec_name = 'D10')
AND d10.length NOT IN (
    SELECT length FROM rebar_length_info 
    WHERE spec_id = (SELECT id FROM rebar_specifications WHERE spec_name = 'D41')
);

-- D51
INSERT INTO rebar_length_info (spec_id, length, weight_per_piece, pieces_per_ton, total_weight)
SELECT 
    (SELECT id FROM rebar_specifications WHERE spec_name = 'D51'),
    d10.length,
    15.9 * d10.length,  -- D51 unit weight is 15.9
    0,
    NULL
FROM rebar_length_info d10
WHERE d10.spec_id = (SELECT id FROM rebar_specifications WHERE spec_name = 'D10')
AND d10.length NOT IN (
    SELECT length FROM rebar_length_info 
    WHERE spec_id = (SELECT id FROM rebar_specifications WHERE spec_name = 'D51')
);