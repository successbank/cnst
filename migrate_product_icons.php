<?php
require_once 'db.php';

echo "=== Product Icons 테이블 마이그레이션 시작 ===\n";

try {
    // SQL 파일 읽기
    $sql = file_get_contents('sql/create_product_icons_table.sql');
    
    if ($sql === false) {
        throw new Exception("SQL 파일을 읽을 수 없습니다.");
    }
    
    // SQL 문을 세미콜론으로 분리
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    // 각 쿼리 실행
    foreach ($queries as $query) {
        if (!empty($query)) {
            echo "쿼리 실행 중...\n";
            $pdo->exec($query);
        }
    }
    
    echo "\n✅ Product Icons 테이블 생성 완료!\n";
    echo "✅ 초기 데이터 입력 완료!\n\n";
    
    // 테이블 확인
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_icons");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "총 " . $result['count'] . "개의 제품 아이콘이 등록되었습니다.\n";
    
    echo "\n=== 마이그레이션 완료 ===\n";
    
} catch (Exception $e) {
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
    exit(1);
}
?>