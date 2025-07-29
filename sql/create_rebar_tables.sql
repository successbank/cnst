-- 철근 규격 테이블
CREATE TABLE IF NOT EXISTS rebar_specs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spec_name VARCHAR(10) NOT NULL UNIQUE,
    diameter DECIMAL(5,2),
    unit_weight DECIMAL(10,3),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 철근 규격 데이터 삽입
INSERT IGNORE INTO rebar_specs (spec_name, diameter, unit_weight, display_order) VALUES
('D10', 9.53, 0.560, 1),
('D13', 12.70, 0.995, 2),
('D16', 15.90, 1.560, 3),
('D19', 19.10, 2.250, 4),
('D22', 22.20, 3.040, 5),
('D25', 25.40, 3.980, 6),
('D29', 28.60, 5.040, 7),
('D32', 31.80, 6.230, 8),
('D35', 34.90, 7.510, 9),
('D38', 38.10, 8.950, 10),
('D41', 41.30, 10.500, 11),
('D51', 50.80, 15.900, 12);

-- 철근 길이 옵션 테이블
CREATE TABLE IF NOT EXISTS rebar_lengths (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    spec_id INT NOT NULL,
    length DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES rebar_materials(id),
    FOREIGN KEY (spec_id) REFERENCES rebar_specs(id),
    UNIQUE KEY unique_material_spec_length (material_id, spec_id, length)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 모든 재질과 규격에 대한 기본 길이 옵션 삽입
INSERT IGNORE INTO rebar_lengths (material_id, spec_id, length)
SELECT 
    m.id as material_id,
    s.id as spec_id,
    l.length
FROM 
    rebar_materials m
    CROSS JOIN rebar_specs s
    CROSS JOIN (
        SELECT 3.0 as length UNION ALL
        SELECT 3.5 UNION ALL
        SELECT 4.0 UNION ALL
        SELECT 4.5 UNION ALL
        SELECT 5.0 UNION ALL
        SELECT 5.5 UNION ALL
        SELECT 6.0 UNION ALL
        SELECT 6.5 UNION ALL
        SELECT 7.0 UNION ALL
        SELECT 7.5 UNION ALL
        SELECT 8.0 UNION ALL
        SELECT 8.5 UNION ALL
        SELECT 9.0 UNION ALL
        SELECT 9.5 UNION ALL
        SELECT 10.0 UNION ALL
        SELECT 10.5 UNION ALL
        SELECT 11.0 UNION ALL
        SELECT 11.5 UNION ALL
        SELECT 12.0
    ) l
WHERE m.is_active = 1;