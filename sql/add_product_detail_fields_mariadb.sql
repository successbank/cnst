-- 제품 테이블에 상세내용 관련 필드 추가 (MariaDB용)

-- 먼저 컬럼이 존재하는지 확인하고 추가
SET @dbname = DATABASE();

-- 1. detailed_description
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'detailed_description'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE products ADD COLUMN detailed_description TEXT DEFAULT NULL COMMENT ''상세 설명'' AFTER description',
    'SELECT ''detailed_description already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. key_features
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'key_features'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE products ADD COLUMN key_features TEXT DEFAULT NULL COMMENT ''주요 특징'' AFTER detailed_description',
    'SELECT ''key_features already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. technical_specs
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'technical_specs'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE products ADD COLUMN technical_specs TEXT DEFAULT NULL COMMENT ''기술 사양'' AFTER key_features',
    'SELECT ''technical_specs already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. applications
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'applications'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE products ADD COLUMN applications TEXT DEFAULT NULL COMMENT ''사용 용도'' AFTER technical_specs',
    'SELECT ''applications already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. certifications
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'certifications'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE products ADD COLUMN certifications TEXT DEFAULT NULL COMMENT ''관련 규격/인증'' AFTER applications',
    'SELECT ''certifications already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. additional_images
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'additional_images'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE products ADD COLUMN additional_images JSON DEFAULT NULL COMMENT ''추가 이미지들'' AFTER certifications',
    'SELECT ''additional_images already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. brochure_url
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'brochure_url'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE products ADD COLUMN brochure_url VARCHAR(500) DEFAULT NULL COMMENT ''브로셔 URL'' AFTER additional_images',
    'SELECT ''brochure_url already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. show_details
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'show_details'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE products ADD COLUMN show_details BOOLEAN DEFAULT TRUE COMMENT ''상세내용 표시 여부'' AFTER brochure_url',
    'SELECT ''show_details already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 9. details_updated_at
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname 
    AND TABLE_NAME = 'products' 
    AND COLUMN_NAME = 'details_updated_at'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE products ADD COLUMN details_updated_at TIMESTAMP NULL DEFAULT NULL COMMENT ''상세내용 수정일'' AFTER show_details',
    'SELECT ''details_updated_at already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;