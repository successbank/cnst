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

// Excel에서 가져온 원본 패스워드
$original_password = 'iloveGod74';

// test000 사용자 검색
try {
    $stmt = $pdo->prepare("SELECT id, user_id, password, name FROM members WHERE user_id = ?");
    $stmt->execute(['test000']);
    $member = $stmt->fetch();
    
    if ($member) {
        echo "=== test000 사용자 정보 ===\n";
        echo "ID: " . $member['id'] . "\n";
        echo "User ID: " . $member['user_id'] . "\n";
        echo "Name: " . $member['name'] . "\n";
        echo "Password Hash: " . $member['password'] . "\n\n";
        
        // 패스워드 검증
        if (password_verify($original_password, $member['password'])) {
            echo "✅ 패스워드 검증 성공!\n";
            echo "Excel의 패스워드 'iloveGod74'가 현재 DB의 해시된 패스워드와 일치합니다.\n";
        } else {
            echo "❌ 패스워드 검증 실패\n";
            echo "Excel의 패스워드와 DB의 패스워드가 일치하지 않습니다.\n\n";
            
            // 새로운 해시 생성
            $new_hash = password_hash($original_password, PASSWORD_DEFAULT);
            echo "원본 패스워드로 새로운 해시를 생성하면:\n";
            echo $new_hash . "\n\n";
            
            echo "DB의 패스워드를 업데이트하려면 다음 SQL을 실행하세요:\n";
            echo "UPDATE members SET password = '" . $new_hash . "' WHERE user_id = 'test000';\n";
        }
    } else {
        echo "test000 사용자를 찾을 수 없습니다.\n";
        
        // 다른 가능한 사용자 검색
        $stmt = $pdo->prepare("SELECT user_id, name, email, created_at FROM members WHERE user_id LIKE ? ORDER BY user_id LIMIT 20");
        $stmt->execute(['%test%']);
        $similar_users = $stmt->fetchAll();
        
        if ($similar_users) {
            echo "\n현재 DB의 test 관련 사용자:\n";
            echo str_repeat("-", 70) . "\n";
            printf("%-15s %-20s %-30s %s\n", "User ID", "Name", "Email", "Created");
            echo str_repeat("-", 70) . "\n";
            foreach ($similar_users as $user) {
                printf("%-15s %-20s %-30s %s\n", 
                    $user['user_id'], 
                    $user['name'], 
                    $user['email'],
                    substr($user['created_at'], 0, 10)
                );
            }
            
            echo "\n이 중 하나의 사용자로 패스워드를 테스트하시겠습니까?\n";
            echo "예: test2 사용자의 패스워드를 'iloveGod74'로 업데이트하려면:\n";
            echo "UPDATE members SET password = '" . password_hash($original_password, PASSWORD_DEFAULT) . "' WHERE user_id = 'test2';\n";
        }
    }
} catch (PDOException $e) {
    echo "데이터베이스 오류: " . $e->getMessage() . "\n";
}
?>