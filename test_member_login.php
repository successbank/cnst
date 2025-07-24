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

// 테스트할 회원 정보 (member.xls에서 몇 개 샘플)
$test_users = [
    ['id' => 'lovearum', 'password' => '73370000'],
    ['id' => 'xton11', 'password' => 'ddd000'],
    ['id' => 'a7846289', 'password' => 'a11111'],
    ['id' => 'japan7096', 'password' => 'lee2595'],
    ['id' => 'skyhyun747', 'password' => 'a1011624']
];

echo "=== 회원 로그인 테스트 ===\n\n";

foreach ($test_users as $user) {
    $user_id = $user['id'];
    $password = $user['password'];
    
    echo "테스트: $user_id / $password\n";
    
    try {
        // DB에서 회원 정보 조회
        $stmt = $pdo->prepare("SELECT id, user_id, password, name FROM members WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $member = $stmt->fetch();
        
        if ($member) {
            echo "  - 회원 찾음: {$member['name']} (ID: {$member['id']})\n";
            echo "  - DB 패스워드: {$member['password']}\n";
            
            // 패스워드 매칭 확인
            if ($member['password'] === $password) {
                echo "  ✅ 로그인 성공!\n";
            } else {
                echo "  ❌ 패스워드 불일치\n";
            }
        } else {
            echo "  ❌ 회원을 찾을 수 없음\n";
        }
        
    } catch (PDOException $e) {
        echo "  ❌ 오류: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// 전체 통계
echo "=== 전체 회원 통계 ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
$total = $stmt->fetch()['total'];
echo "전체 회원 수: $total 명\n";

// 패스워드가 있는 회원 수
$stmt = $pdo->query("SELECT COUNT(*) as with_password FROM members WHERE password IS NOT NULL AND password != ''");
$with_password = $stmt->fetch()['with_password'];
echo "패스워드가 있는 회원: $with_password 명\n";

// 최근 업데이트된 회원
echo "\n=== 최근 업데이트된 회원 (5명) ===\n";
$stmt = $pdo->query("SELECT user_id, name, updated_at FROM members ORDER BY updated_at DESC LIMIT 5");
$recent = $stmt->fetchAll();

foreach($recent as $member) {
    echo "{$member['user_id']} ({$member['name']}) - 업데이트: {$member['updated_at']}\n";
}