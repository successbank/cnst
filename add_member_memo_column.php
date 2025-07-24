<?php
require_once 'db.php';

try {
    // memo 컬럼 추가
    $sql = "ALTER TABLE members ADD COLUMN memo TEXT AFTER position";
    $pdo->exec($sql);
    
    echo "✅ 회원 테이블에 메모 컬럼이 성공적으로 추가되었습니다.\n";
    
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ℹ️ 메모 컬럼이 이미 존재합니다.\n";
    } else {
        echo "❌ 오류 발생: " . $e->getMessage() . "\n";
    }
}
?>