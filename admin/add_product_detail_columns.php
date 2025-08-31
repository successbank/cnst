<?php
require_once '../db.php';

try {
    echo "데이터베이스 스키마 업데이트 시작...\n";
    
    // quality_cert 컬럼 확인 및 추가
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'quality_cert'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE products ADD COLUMN quality_cert VARCHAR(500) DEFAULT NULL COMMENT '품질 인증' AFTER delivery_info");
        echo "✓ quality_cert 컬럼 추가 완료\n";
    } else {
        echo "- quality_cert 컬럼이 이미 존재합니다.\n";
    }
    
    // product_features 컬럼 확인 및 추가
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'product_features'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE products ADD COLUMN product_features TEXT DEFAULT NULL COMMENT '제품 특징' AFTER quality_cert");
        echo "✓ product_features 컬럼 추가 완료\n";
    } else {
        echo "- product_features 컬럼이 이미 존재합니다.\n";
    }
    
    // 사각파이프 제품들에 기본값 설정
    $stmt = $pdo->prepare("
        UPDATE products 
        SET 
            features = CASE WHEN features IS NULL OR features = '' THEN '건축 구조물, 철골 공사, 산업 설비' ELSE features END,
            material = CASE WHEN material IS NULL OR material = '' THEN 'SS400 구조용 강재' ELSE material END,
            manufacturer = CASE WHEN manufacturer IS NULL OR manufacturer = '' THEN '국내 주요 제철소' ELSE manufacturer END,
            quality_cert = CASE WHEN quality_cert IS NULL OR quality_cert = '' THEN 'KS D 3568 규격 인증' ELSE quality_cert END,
            product_features = CASE WHEN product_features IS NULL OR product_features = '' THEN '고강도, 경량화, 우수한 내구성' ELSE product_features END,
            delivery_info = CASE WHEN delivery_info IS NULL OR delivery_info = '' THEN '주문 후 2-3일 이내 배송' ELSE delivery_info END
        WHERE category_code = 'square-pipe'
    ");
    $updated = $stmt->execute();
    
    if ($updated) {
        echo "✓ 사각파이프 제품들의 기본값 설정 완료\n";
    }
    
    echo "\n데이터베이스 업데이트 완료!\n";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
?>