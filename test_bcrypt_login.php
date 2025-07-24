<?php
// 직접 DB 연결
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "✅ 데이터베이스 연결 성공\n\n";
} catch (PDOException $e) {
    die("❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

// 테스트할 회원 (member.xls의 원본 패스워드)
$test_cases = [
    ['user_id' => 'lovearum', 'plain_password' => '73370000'],
    ['user_id' => 'xton11', 'plain_password' => 'ddd000'],
    ['user_id' => 'a7846289', 'plain_password' => 'a11111']
];

echo "=== bcrypt 로그인 테스트 ===\n\n";

foreach ($test_cases as $test) {
    $user_id = $test['user_id'];
    $plain_password = $test['plain_password'];
    
    echo "테스트: $user_id / $plain_password\n";
    
    // DB에서 회원 정보 조회
    $stmt = $pdo->prepare("SELECT id, user_id, password, name FROM members WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $member = $stmt->fetch();
    
    if ($member) {
        echo "  회원명: {$member['name']}\n";
        echo "  저장된 패스워드: " . substr($member['password'], 0, 50) . "...\n";
        
        // bcrypt 형식 확인
        if (strpos($member['password'], '$2y$') === 0) {
            echo "  패스워드 형식: ✅ bcrypt\n";
            
            // password_verify로 검증
            if (password_verify($plain_password, $member['password'])) {
                echo "  로그인 테스트: ✅ 성공!\n";
            } else {
                echo "  로그인 테스트: ❌ 실패 (패스워드 불일치)\n";
            }
        } else {
            echo "  패스워드 형식: ❌ 평문\n";
            echo "  로그인 테스트: ❌ 실패 (bcrypt 해싱 필요)\n";
        }
    } else {
        echo "  ❌ 회원을 찾을 수 없음\n";
    }
    
    echo "\n";
}