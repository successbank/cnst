<?php
// 직접 DB 연결
try {
    $pdo = new PDO("mysql:host=localhost;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // 127.0.0.1로 재시도
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        die("데이터베이스 연결 실패: " . $e2->getMessage() . "\n");
    }
}

// 테스트할 계정 정보
$test_user_id = 'lovearum';
$test_password = '73370000';

echo "=== lovearum 계정 로그인 테스트 ===\n\n";

// 1. 사용자 정보 조회
try {
    $stmt = $pdo->prepare("SELECT id, user_id, password, name, email, is_active, created_at, last_login FROM members WHERE user_id = ?");
    $stmt->execute([$test_user_id]);
    $member = $stmt->fetch();
    
    if ($member) {
        echo "✅ 사용자 발견됨\n";
        echo "================================\n";
        echo "ID: " . $member['id'] . "\n";
        echo "User ID: " . $member['user_id'] . "\n";
        echo "Name: " . $member['name'] . "\n";
        echo "Email: " . $member['email'] . "\n";
        echo "Active: " . ($member['is_active'] ? 'Yes' : 'No') . "\n";
        echo "Created: " . $member['created_at'] . "\n";
        echo "Last Login: " . ($member['last_login'] ?: 'Never') . "\n";
        echo "Password Hash: " . substr($member['password'], 0, 20) . "...\n";
        echo "================================\n\n";
        
        // 2. 패스워드 검증
        echo "패스워드 검증 중...\n";
        if (password_verify($test_password, $member['password'])) {
            echo "✅ 패스워드 검증 성공!\n";
            echo "로그인이 가능합니다.\n\n";
            
            // 3. 계정 상태 확인
            if ($member['is_active'] == 1) {
                echo "✅ 계정이 활성화되어 있습니다.\n";
                echo "정상적으로 로그인할 수 있습니다.\n";
            } else {
                echo "❌ 계정이 비활성화되어 있습니다.\n";
                echo "로그인 시 '정지된 계정입니다' 오류가 발생합니다.\n";
            }
        } else {
            echo "❌ 패스워드 검증 실패!\n";
            echo "제공된 패스워드 '73370000'이 DB의 해시된 패스워드와 일치하지 않습니다.\n\n";
            
            // 패스워드 업데이트 옵션 제공
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            echo "패스워드를 '73370000'으로 업데이트하려면 다음 SQL을 실행하세요:\n";
            echo "UPDATE members SET password = '" . $new_hash . "' WHERE user_id = 'lovearum';\n\n";
            
            // 실제로 업데이트 실행
            echo "자동으로 패스워드를 업데이트하시겠습니까? (실제 실행)\n";
            $update_stmt = $pdo->prepare("UPDATE members SET password = ? WHERE user_id = ?");
            $update_stmt->execute([$new_hash, $test_user_id]);
            echo "✅ 패스워드가 업데이트되었습니다.\n\n";
            
            // 다시 검증
            echo "업데이트 후 재검증...\n";
            if (password_verify($test_password, $new_hash)) {
                echo "✅ 새 패스워드로 검증 성공!\n";
            }
        }
        
        // 4. 실제 로그인 시뮬레이션
        echo "\n=== 로그인 프로세스 시뮬레이션 ===\n";
        echo "1. 사용자가 login.php에서 아이디와 패스워드 입력\n";
        echo "2. POST 요청으로 user_id='lovearum', password='73370000' 전송\n";
        echo "3. 데이터베이스에서 사용자 조회 - " . ($member ? "성공" : "실패") . "\n";
        echo "4. 패스워드 검증 - " . (password_verify($test_password, $member['password']) ? "성공" : "실패") . "\n";
        echo "5. 계정 활성화 상태 확인 - " . ($member['is_active'] ? "활성" : "비활성") . "\n";
        echo "6. 세션 설정 및 리다이렉트\n";
        
    } else {
        echo "❌ 사용자를 찾을 수 없습니다.\n";
        echo "user_id 'lovearum'이 데이터베이스에 존재하지 않습니다.\n\n";
        
        // 비슷한 사용자 검색
        $stmt = $pdo->prepare("SELECT user_id, name, email FROM members WHERE user_id LIKE ? ORDER BY user_id LIMIT 10");
        $stmt->execute(['%love%']);
        $similar_users = $stmt->fetchAll();
        
        if ($similar_users) {
            echo "유사한 사용자:\n";
            foreach ($similar_users as $user) {
                echo "- " . $user['user_id'] . " (" . $user['name'] . ")\n";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "❌ 데이터베이스 오류: " . $e->getMessage() . "\n";
}

// 5. 추가 진단 정보
echo "\n=== 추가 진단 정보 ===\n";
$count_stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
$total = $count_stmt->fetch()['total'];
echo "전체 회원 수: " . $total . "\n";

// 최근 가입 회원
$recent_stmt = $pdo->query("SELECT user_id, name, created_at FROM members ORDER BY id DESC LIMIT 5");
$recent_members = $recent_stmt->fetchAll();
echo "\n최근 가입 회원:\n";
foreach ($recent_members as $m) {
    echo "- " . $m['user_id'] . " (" . $m['name'] . ") - " . $m['created_at'] . "\n";
}
?>