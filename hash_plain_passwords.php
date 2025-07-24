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

echo "=== 평문 패스워드를 bcrypt로 해싱 ===\n\n";

// bcrypt로 해싱되지 않은 패스워드 찾기 (bcrypt는 $2y$로 시작)
$stmt = $pdo->query("
    SELECT id, user_id, password 
    FROM members 
    WHERE password IS NOT NULL 
    AND password != '' 
    AND password NOT LIKE '$2y$%'
    ORDER BY id
");

$plain_passwords = $stmt->fetchAll();
$total = count($plain_passwords);

echo "평문 패스워드를 가진 회원: $total 명\n\n";

if ($total == 0) {
    echo "✅ 모든 패스워드가 이미 해싱되어 있습니다.\n";
    exit;
}

// 확인 프롬프트
echo "⚠️  주의: 이 작업은 되돌릴 수 없습니다!\n";
echo "계속하시겠습니까? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if(trim($line) != 'yes'){
    echo "\n작업을 취소했습니다.\n";
    exit;
}
fclose($handle);

echo "\n해싱 작업을 시작합니다...\n\n";

$success = 0;
$errors = 0;

// 트랜잭션 시작
$pdo->beginTransaction();

try {
    foreach ($plain_passwords as $index => $member) {
        $user_id = $member['user_id'];
        $plain_password = $member['password'];
        
        // bcrypt로 해싱 (기본 cost는 10)
        $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT);
        
        // 업데이트
        $update_stmt = $pdo->prepare("UPDATE members SET password = ? WHERE id = ?");
        $update_stmt->execute([$hashed_password, $member['id']]);
        
        $success++;
        
        // 진행 상황 표시
        if (($index + 1) % 100 == 0) {
            echo "진행: " . ($index + 1) . " / $total\n";
        }
        
        // 샘플 출력 (처음 5개)
        if ($index < 5) {
            echo "✅ $user_id: " . substr($plain_password, 0, 10) . "... → " . substr($hashed_password, 0, 30) . "...\n";
        }
    }
    
    // 커밋
    $pdo->commit();
    
    echo "\n=== 해싱 완료 ===\n";
    echo "✅ 성공: $success 건\n";
    echo "❌ 실패: $errors 건\n";
    
} catch (Exception $e) {
    // 롤백
    $pdo->rollback();
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
    echo "모든 변경사항이 롤백되었습니다.\n";
    exit;
}

// 검증
echo "\n=== 해싱 검증 ===\n";

// 몇 개 샘플로 검증
$verify_stmt = $pdo->query("
    SELECT user_id, password 
    FROM members 
    WHERE user_id IN ('lovearum', 'xton11', 'a7846289') 
    LIMIT 3
");

$samples = $verify_stmt->fetchAll();

foreach ($samples as $sample) {
    echo "\n{$sample['user_id']}:\n";
    echo "  해싱된 패스워드: " . substr($sample['password'], 0, 50) . "...\n";
    echo "  bcrypt 형식: " . (strpos($sample['password'], '$2y$') === 0 ? "✅ 올바름" : "❌ 잘못됨") . "\n";
}

// 전체 통계
echo "\n=== 최종 통계 ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM members WHERE password LIKE '$2y$%'");
$bcrypt_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
$total_count = $stmt->fetch()['total'];

echo "전체 회원: $total_count 명\n";
echo "bcrypt 해싱된 패스워드: $bcrypt_count 명\n";
echo "평문 패스워드: " . ($total_count - $bcrypt_count) . " 명\n";