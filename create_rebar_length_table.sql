CREATE TABLE IF NOT EXISTS rebar_length_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spec_name VARCHAR(50) NOT NULL,
    length DECIMAL(5,2) NOT NULL,
    piece_weight DECIMAL(10,4),
    pieces_per_length INT,
    weight_per_ton DECIMAL(10,4),
    unit_weight DECIMAL(10,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_spec_length (spec_name, length)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;