-- ============================================
-- 제품 계산식 관리 시스템 데이터베이스 스키마
-- 생성일: 2025-09-29
-- 설명: 카테고리/제품별 계산식을 유연하게 관리하는 시스템
-- ============================================

-- 1. 계산식 마스터 테이블
CREATE TABLE IF NOT EXISTS calculation_formulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_name VARCHAR(100) NOT NULL COMMENT '계산식 이름',
    category_code VARCHAR(50) COMMENT '카테고리 코드 (NULL이면 공용)',
    product_id INT COMMENT '제품 ID (NULL이면 카테고리 전체 적용)',
    description TEXT COMMENT '계산식 설명',
    formula_expression TEXT NOT NULL COMMENT '계산식 (JSON 형식)',
    rounding_rule VARCHAR(20) DEFAULT 'round_2' COMMENT '반올림 규칙',
    is_active TINYINT(1) DEFAULT 1 COMMENT '활성화 여부',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
    created_by INT COMMENT '생성 관리자 ID',

    UNIQUE KEY unique_category_product (category_code, product_id),
    KEY idx_category (category_code),
    KEY idx_product (product_id),
    KEY idx_is_active (is_active),
    FOREIGN KEY (category_code) REFERENCES product_categories(category_code) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='제품 계산식 정의';

-- 2. 계산 파라미터 테이블
CREATE TABLE IF NOT EXISTS calculation_parameters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL COMMENT '계산식 ID',
    parameter_name VARCHAR(50) NOT NULL COMMENT '파라미터 명 (length, quantity 등)',
    parameter_label VARCHAR(100) NOT NULL COMMENT '표시 라벨',
    parameter_type ENUM('number', 'select', 'text', 'product_field') DEFAULT 'number' COMMENT '파라미터 타입',
    source_field VARCHAR(50) COMMENT '제품 테이블 필드명 (type이 product_field인 경우)',
    default_value VARCHAR(100) COMMENT '기본값',
    min_value DECIMAL(10,2) COMMENT '최소값',
    max_value DECIMAL(10,2) COMMENT '최대값',
    step_value DECIMAL(10,2) COMMENT '증감 단위',
    unit VARCHAR(20) COMMENT '단위 (m, kg, mm 등)',
    validation_rule TEXT COMMENT '유효성 검사 규칙 (JSON)',
    options TEXT COMMENT '선택 옵션 (JSON, type이 select인 경우)',
    display_order INT DEFAULT 0 COMMENT '표시 순서',
    is_required TINYINT(1) DEFAULT 1 COMMENT '필수 여부',

    KEY idx_formula (formula_id),
    KEY idx_display_order (display_order),
    FOREIGN KEY (formula_id) REFERENCES calculation_formulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='계산식 파라미터 정의';

-- 3. 계산 상수 테이블
CREATE TABLE IF NOT EXISTS calculation_constants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    constant_name VARCHAR(50) NOT NULL UNIQUE COMMENT '상수명 (STEEL_DENSITY 등)',
    constant_value DECIMAL(20,10) NOT NULL COMMENT '상수값',
    description TEXT COMMENT '설명',
    unit VARCHAR(20) COMMENT '단위',
    is_editable TINYINT(1) DEFAULT 1 COMMENT '수정 가능 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',

    KEY idx_constant_name (constant_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='계산 상수 관리';

-- 4. 계산 히스토리 테이블
CREATE TABLE IF NOT EXISTS calculation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formula_id INT NOT NULL COMMENT '계산식 ID',
    version INT NOT NULL DEFAULT 1 COMMENT '버전',
    formula_expression TEXT NOT NULL COMMENT '변경된 계산식',
    parameters_snapshot TEXT COMMENT '파라미터 스냅샷 (JSON)',
    changed_by INT COMMENT '변경한 관리자 ID',
    change_description TEXT COMMENT '변경 사유',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',

    KEY idx_formula (formula_id),
    KEY idx_created_at (created_at),
    KEY idx_version (formula_id, version),
    FOREIGN KEY (formula_id) REFERENCES calculation_formulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='계산식 변경 이력';

-- ============================================
-- 기본 데이터 삽입
-- ============================================

-- 계산 상수 데이터
INSERT INTO calculation_constants (constant_name, constant_value, description, unit, is_editable) VALUES
('STEEL_DENSITY', 7850.0000000000, '철 밀도', 'kg/m³', 1),
('PI', 3.1415926536, '원주율', '', 0),
('STEEL_FACTOR_1', 0.0061700000, '철근 계산 계수 (d² × 0.00617)', '', 1),
('STEEL_FACTOR_2', 0.0078500000, '철판/평철 계산 계수', '', 1),
('STEEL_FACTOR_3', 0.0246600000, '원형파이프 계산 계수', '', 1)
ON DUPLICATE KEY UPDATE constant_value = VALUES(constant_value);

-- ============================================
-- 기존 계산식 마이그레이션 (카테고리별 기본 계산식)
-- ============================================

-- 1. 철근 (rebar) 계산식
INSERT INTO calculation_formulas
    (formula_name, category_code, product_id, description, formula_expression, rounding_rule, is_active, display_order)
VALUES
    (
        '철근 중량 계산',
        'rebar',
        NULL,
        '직경 기반 철근 중량 계산: 직경² × 0.00617 × 길이 × 수량',
        JSON_OBJECT(
            'type', 'expression',
            'expression', '(diameter * diameter) * STEEL_FACTOR_1 * length * quantity',
            'variables', JSON_OBJECT(
                'diameter', JSON_OBJECT('source', 'product', 'field', 'diameter', 'required', true),
                'length', JSON_OBJECT('source', 'user_input', 'required', true),
                'quantity', JSON_OBJECT('source', 'user_input', 'required', true)
            ),
            'constants', JSON_ARRAY('STEEL_FACTOR_1'),
            'rounding', JSON_OBJECT(
                'final', JSON_OBJECT('decimals', 2, 'method', 'round')
            )
        ),
        'round_2',
        1,
        1
    )
ON DUPLICATE KEY UPDATE
    formula_expression = VALUES(formula_expression),
    updated_at = CURRENT_TIMESTAMP;

-- 철근 파라미터
INSERT INTO calculation_parameters
    (formula_id, parameter_name, parameter_label, parameter_type, source_field, default_value, min_value, max_value, step_value, unit, is_required, display_order)
SELECT
    id, 'diameter', '직경', 'product_field', 'diameter', NULL, 6, 50, 1, 'mm', 1, 1
FROM calculation_formulas WHERE category_code = 'rebar' AND product_id IS NULL
ON DUPLICATE KEY UPDATE parameter_label = VALUES(parameter_label);

INSERT INTO calculation_parameters
    (formula_id, parameter_name, parameter_label, parameter_type, default_value, min_value, max_value, step_value, unit, is_required, display_order)
SELECT
    id, 'length', '길이', 'number', '8', 0.1, 20, 0.1, 'm', 1, 2
FROM calculation_formulas WHERE category_code = 'rebar' AND product_id IS NULL
ON DUPLICATE KEY UPDATE parameter_label = VALUES(parameter_label);

INSERT INTO calculation_parameters
    (formula_id, parameter_name, parameter_label, parameter_type, default_value, min_value, step_value, unit, is_required, display_order)
SELECT
    id, 'quantity', '수량', 'number', '1', 1, 1, '본', 1, 3
FROM calculation_formulas WHERE category_code = 'rebar' AND product_id IS NULL
ON DUPLICATE KEY UPDATE parameter_label = VALUES(parameter_label);

-- 2. H형강 (h-beam) 계산식
INSERT INTO calculation_formulas
    (formula_name, category_code, product_id, description, formula_expression, rounding_rule, is_active, display_order)
VALUES
    (
        'H형강 중량 계산',
        'h-beam',
        NULL,
        '단위중량 기반 H형강 중량 계산: 본당중량(반올림) × 수량',
        JSON_OBJECT(
            'type', 'expression',
            'expression', 'round(unit_weight * length, 1) * quantity',
            'variables', JSON_OBJECT(
                'unit_weight', JSON_OBJECT('source', 'product', 'field', 'unit_weight', 'required', true),
                'length', JSON_OBJECT('source', 'user_input', 'required', true),
                'quantity', JSON_OBJECT('source', 'user_input', 'required', true)
            ),
            'rounding', JSON_OBJECT(
                'intermediate', JSON_OBJECT('step', 'weight_per_piece', 'decimals', 1, 'method', 'round'),
                'final', JSON_OBJECT('decimals', 2, 'method', 'round')
            )
        ),
        'round_1_then_2',
        1,
        2
    )
ON DUPLICATE KEY UPDATE
    formula_expression = VALUES(formula_expression),
    updated_at = CURRENT_TIMESTAMP;

-- H형강 파라미터
INSERT INTO calculation_parameters
    (formula_id, parameter_name, parameter_label, parameter_type, source_field, unit, is_required, display_order)
SELECT
    id, 'unit_weight', '단위중량', 'product_field', 'unit_weight', 'kg/m', 1, 1
FROM calculation_formulas WHERE category_code = 'h-beam' AND product_id IS NULL
ON DUPLICATE KEY UPDATE parameter_label = VALUES(parameter_label);

INSERT INTO calculation_parameters
    (formula_id, parameter_name, parameter_label, parameter_type, default_value, min_value, max_value, step_value, unit, is_required, display_order)
SELECT
    id, 'length', '길이', 'number', '6', 0.1, 20, 0.1, 'm', 1, 2
FROM calculation_formulas WHERE category_code = 'h-beam' AND product_id IS NULL
ON DUPLICATE KEY UPDATE parameter_label = VALUES(parameter_label);

INSERT INTO calculation_parameters
    (formula_id, parameter_name, parameter_label, parameter_type, default_value, min_value, step_value, unit, is_required, display_order)
SELECT
    id, 'quantity', '수량', 'number', '1', 1, 1, '본', 1, 3
FROM calculation_formulas WHERE category_code = 'h-beam' AND product_id IS NULL
ON DUPLICATE KEY UPDATE parameter_label = VALUES(parameter_label);

-- 3. 경량 H형강 (light-h-beam) - H형강과 동일
INSERT INTO calculation_formulas
    (formula_name, category_code, product_id, description, formula_expression, rounding_rule, is_active, display_order)
VALUES
    (
        '경량 H형강 중량 계산',
        'light-h-beam',
        NULL,
        '단위중량 기반 경량 H형강 중량 계산',
        JSON_OBJECT(
            'type', 'expression',
            'expression', 'round(unit_weight * length, 1) * quantity',
            'variables', JSON_OBJECT(
                'unit_weight', JSON_OBJECT('source', 'product', 'field', 'unit_weight', 'required', true),
                'length', JSON_OBJECT('source', 'user_input', 'required', true),
                'quantity', JSON_OBJECT('source', 'user_input', 'required', true)
            ),
            'rounding', JSON_OBJECT(
                'intermediate', JSON_OBJECT('step', 'weight_per_piece', 'decimals', 1, 'method', 'round'),
                'final', JSON_OBJECT('decimals', 2, 'method', 'round')
            )
        ),
        'round_1_then_2',
        1,
        3
    )
ON DUPLICATE KEY UPDATE
    formula_expression = VALUES(formula_expression),
    updated_at = CURRENT_TIMESTAMP;

-- 4. I형강 (i-beam)
INSERT INTO calculation_formulas
    (formula_name, category_code, product_id, description, formula_expression, rounding_rule, is_active, display_order)
VALUES
    (
        'I형강 중량 계산',
        'i-beam',
        NULL,
        '단위중량 기반 I형강 중량 계산',
        JSON_OBJECT(
            'type', 'expression',
            'expression', 'round(unit_weight * length, 1) * quantity',
            'variables', JSON_OBJECT(
                'unit_weight', JSON_OBJECT('source', 'product', 'field', 'unit_weight', 'required', true),
                'length', JSON_OBJECT('source', 'user_input', 'required', true),
                'quantity', JSON_OBJECT('source', 'user_input', 'required', true)
            ),
            'rounding', JSON_OBJECT(
                'intermediate', JSON_OBJECT('step', 'weight_per_piece', 'decimals', 1, 'method', 'round'),
                'final', JSON_OBJECT('decimals', 2, 'method', 'round')
            )
        ),
        'round_1_then_2',
        1,
        4
    )
ON DUPLICATE KEY UPDATE
    formula_expression = VALUES(formula_expression),
    updated_at = CURRENT_TIMESTAMP;

-- 5. 철판 (steel-plate)
INSERT INTO calculation_formulas
    (formula_name, category_code, product_id, description, formula_expression, rounding_rule, is_active, display_order)
VALUES
    (
        '철판 중량 계산',
        'steel-plate',
        NULL,
        '두께 × 폭 × 길이 × 7.85 × 10⁻⁶ × 수량',
        JSON_OBJECT(
            'type', 'expression',
            'expression', 'thickness * width * (length * 1000) * (STEEL_DENSITY / 1000000000) * quantity',
            'variables', JSON_OBJECT(
                'thickness', JSON_OBJECT('source', 'product', 'field', 'thickness', 'required', true),
                'width', JSON_OBJECT('source', 'product', 'field', 'width', 'required', true),
                'length', JSON_OBJECT('source', 'user_input', 'required', true),
                'quantity', JSON_OBJECT('source', 'user_input', 'required', true)
            ),
            'constants', JSON_ARRAY('STEEL_DENSITY'),
            'rounding', JSON_OBJECT(
                'final', JSON_OBJECT('decimals', 2, 'method', 'round')
            )
        ),
        'round_2',
        1,
        5
    )
ON DUPLICATE KEY UPDATE
    formula_expression = VALUES(formula_expression),
    updated_at = CURRENT_TIMESTAMP;

-- 6. 앵글 (angle)
INSERT INTO calculation_formulas
    (formula_name, category_code, product_id, description, formula_expression, rounding_rule, is_active, display_order)
VALUES
    (
        'ㄱ형강 중량 계산',
        'angle',
        NULL,
        '단위중량 × 길이 (소수점 둘째자리)',
        JSON_OBJECT(
            'type', 'expression',
            'expression', 'unit_weight * length * quantity',
            'variables', JSON_OBJECT(
                'unit_weight', JSON_OBJECT('source', 'product', 'field', 'unit_weight', 'required', true),
                'length', JSON_OBJECT('source', 'user_input', 'required', true),
                'quantity', JSON_OBJECT('source', 'user_input', 'required', true)
            ),
            'rounding', JSON_OBJECT(
                'intermediate', JSON_OBJECT('step', 'weight_per_piece', 'decimals', 2, 'method', 'round'),
                'final', JSON_OBJECT('decimals', 2, 'method', 'round')
            )
        ),
        'round_2',
        1,
        6
    )
ON DUPLICATE KEY UPDATE
    formula_expression = VALUES(formula_expression),
    updated_at = CURRENT_TIMESTAMP;

-- 7. 사각파이프 (square-pipe)
INSERT INTO calculation_formulas
    (formula_name, category_code, product_id, description, formula_expression, rounding_rule, is_active, display_order)
VALUES
    (
        '사각파이프 중량 계산',
        'square-pipe',
        NULL,
        '[(외경둘레 - 4×두께) × 두께 × 0.00785] × 길이 × 수량',
        JSON_OBJECT(
            'type', 'expression',
            'expression', '((2 * (width + height)) - 4 * thickness) * thickness * STEEL_FACTOR_2 * length * quantity',
            'variables', JSON_OBJECT(
                'width', JSON_OBJECT('source', 'product', 'field', 'width', 'required', true),
                'height', JSON_OBJECT('source', 'product', 'field', 'height', 'required', true),
                'thickness', JSON_OBJECT('source', 'product', 'field', 'thickness', 'required', true),
                'length', JSON_OBJECT('source', 'user_input', 'required', true),
                'quantity', JSON_OBJECT('source', 'user_input', 'required', true)
            ),
            'constants', JSON_ARRAY('STEEL_FACTOR_2'),
            'rounding', JSON_OBJECT(
                'final', JSON_OBJECT('decimals', 2, 'method', 'round')
            )
        ),
        'round_2',
        1,
        7
    )
ON DUPLICATE KEY UPDATE
    formula_expression = VALUES(formula_expression),
    updated_at = CURRENT_TIMESTAMP;

-- 8. 원형파이프 (round-pipe)
INSERT INTO calculation_formulas
    (formula_name, category_code, product_id, description, formula_expression, rounding_rule, is_active, display_order)
VALUES
    (
        '원형파이프 중량 계산',
        'round-pipe',
        NULL,
        '[(외경 - 두께) × 두께 × 0.02466] × 길이 × 수량',
        JSON_OBJECT(
            'type', 'expression',
            'expression', '(outer_diameter - thickness) * thickness * STEEL_FACTOR_3 * length * quantity',
            'variables', JSON_OBJECT(
                'outer_diameter', JSON_OBJECT('source', 'product', 'field', 'outer_diameter', 'required', true),
                'thickness', JSON_OBJECT('source', 'product', 'field', 'thickness', 'required', true),
                'length', JSON_OBJECT('source', 'user_input', 'required', true),
                'quantity', JSON_OBJECT('source', 'user_input', 'required', true)
            ),
            'constants', JSON_ARRAY('STEEL_FACTOR_3'),
            'rounding', JSON_OBJECT(
                'final', JSON_OBJECT('decimals', 2, 'method', 'round')
            )
        ),
        'round_2',
        1,
        8
    )
ON DUPLICATE KEY UPDATE
    formula_expression = VALUES(formula_expression),
    updated_at = CURRENT_TIMESTAMP;

-- ============================================
-- 인덱스 최적화 (이미 생성되어 있지만 확인용)
-- ============================================

-- ALTER TABLE calculation_formulas ADD INDEX idx_category_active (category_code, is_active);
-- ALTER TABLE calculation_formulas ADD INDEX idx_product_active (product_id, is_active);

-- ============================================
-- 완료
-- ============================================
-- 총 4개 테이블 생성:
--   1. calculation_formulas (계산식)
--   2. calculation_parameters (파라미터)
--   3. calculation_constants (상수)
--   4. calculation_history (히스토리)
--
-- 8개 카테고리 기본 계산식 생성:
--   1. 철근, 2. H형강, 3. 경량 H형강, 4. I형강
--   5. 철판, 6. 앵글, 7. 사각파이프, 8. 원형파이프
-- ============================================