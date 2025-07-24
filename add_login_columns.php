<?php
// 직접 DB 연결
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 데이터베이스 연결 성공\n\n";
} catch (PDOException $e) {
    die("❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

echo "=== members 테이블에 로그인 관련 컬럼 추가 ===\n";

// 컬럼 존재 여부 확인 함수
function columnExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = ? 
        AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return $stmt->fetchColumn() > 0;
}

$columns = [
    ['name' => 'total_login_count', 'type' => 'INT DEFAULT 0'],
    ['name' => 'last_login_imported', 'type' => 'DATETIME'],
    ['name' => 'login_history_imported', 'type' => 'TINYINT DEFAULT 0']
];

foreach ($columns as $col) {
    if (!columnExists($pdo, 'members', $col['name'])) {
        try {
            $sql = "ALTER TABLE members ADD COLUMN {$col['name']} {$col['type']}";
            $pdo->exec($sql);
            echo "✅ 컬럼 추가: {$col['name']}\n";
        } catch (PDOException $e) {
            echo "❌ 오류 ({$col['name']}): " . $e->getMessage() . "\n";
        }
    } else {
        echo "ℹ️  컬럼 이미 존재: {$col['name']}\n";
    }
}

echo "\n✅ 작업 완료!\n";