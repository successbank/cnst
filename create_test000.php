<?php
// 직접 DB 연결
try {
    $pdo = new PDO("mysql:host=localhost;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

// Excel에서 가져온 정보
$user_id = 'test000';
$password = 'iloveGod74';
$name = '박현';
$email = 'test@test.dddd';

echo "=== test000 사용자 생성 ===\n\n";

// 기존 사용자 확인
$stmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
$stmt->execute([$user_id]);
if ($stmt->fetch()) {
    echo "❌ test000 사용자가 이미 존재합니다.\n";
    exit;
}

// 새 사용자 생성
try {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO members (user_id, password, name, email, is_active, created_at) 
        VALUES (?, ?, ?, ?, 1, NOW())
    ");
    $stmt->execute([$user_id, $hashed_password, $name, $email]);
    
    echo "✅ test000 사용자가 성공적으로 생성되었습니다!\n\n";
    echo "생성된 정보:\n";
    echo "- User ID: $user_id\n";
    echo "- Password: $password\n";
    echo "- Name: $name\n";
    echo "- Email: $email\n";
    echo "- Password Hash: $hashed_password\n\n";
    
    // 검증
    echo "=== 패스워드 검증 ===\n";
    if (password_verify($password, $hashed_password)) {
        echo "✅ 패스워드 검증 성공! 'iloveGod74'로 로그인 가능합니다.\n";
    } else {
        echo "❌ 패스워드 검증 실패\n";
    }
    
} catch (PDOException $e) {
    echo "❌ 사용자 생성 실패: " . $e->getMessage() . "\n";
}
?>