<?php
require_once 'db.php';

// JSON 파일 읽기
$json_data = file_get_contents('all_members_data.json');
if (!$json_data) {
    die("JSON 파일을 읽을 수 없습니다.\n");
}

$members = json_decode($json_data, true);
if (!$members) {
    die("JSON 데이터를 파싱할 수 없습니다.\n");
}

echo "총 " . count($members) . "명의 회원 데이터를 처리합니다.\n\n";

$success_count = 0;
$update_count = 0;
$error_count = 0;
$skip_count = 0;

foreach ($members as $index => $member) {
    try {
        // user_id가 없으면 건너뛰기
        if (empty($member['user_id'])) {
            $skip_count++;
            continue;
        }
        
        // 기존 회원 확인
        $check_stmt = $pdo->prepare("SELECT id, user_id FROM members WHERE user_id = ?");
        $check_stmt->execute([$member['user_id']]);
        $existing = $check_stmt->fetch();
        
        if ($existing) {
            // 기존 회원 업데이트 (비밀번호와 중요 정보만)
            $update_fields = [];
            $update_values = [];
            
            // 비밀번호가 있고 평문인 경우 해시화
            if (!empty($member['password']) && strlen($member['password']) < 60) {
                $update_fields[] = "password = ?";
                $update_values[] = password_hash($member['password'], PASSWORD_BCRYPT);
            }
            
            // 이름, 회사명, 이메일 등 업데이트
            if (!empty($member['name'])) {
                $update_fields[] = "name = ?";
                $update_values[] = $member['name'];
            }
            
            if (!empty($member['company'])) {
                $update_fields[] = "company = ?";
                $update_values[] = $member['company'];
            }
            
            if (!empty($member['email'])) {
                $update_fields[] = "email = ?";
                $update_values[] = $member['email'];
            }
            
            if (!empty($member['phone'])) {
                $update_fields[] = "phone = ?";
                $update_values[] = $member['phone'];
            }
            
            if (!empty($member['address'])) {
                $update_fields[] = "address = ?";
                $update_values[] = $member['address'];
            }
            
            if (!empty($member['zip_code'])) {
                $update_fields[] = "zipcode = ?";
                $update_values[] = $member['zip_code'];
            }
            
            if (!empty($member['homepage'])) {
                $update_fields[] = "homepage = ?";
                $update_values[] = $member['homepage'];
            }
            
            if (!empty($update_fields)) {
                $update_values[] = $member['user_id'];
                $update_sql = "UPDATE members SET " . implode(", ", $update_fields) . " WHERE user_id = ?";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute($update_values);
                $update_count++;
                echo "업데이트: {$member['user_id']}\n";
            }
            
        } else {
            // 새 회원 추가
            $password_hash = '';
            if (!empty($member['password'])) {
                // 평문 비밀번호를 해시화
                $password_hash = password_hash($member['password'], PASSWORD_BCRYPT);
            }
            
            // 날짜 형식 변환
            $join_date = null;
            if (!empty($member['join_date']) && $member['join_date'] !== '0000-00-00 00:00:00') {
                try {
                    $join_date = date('Y-m-d H:i:s', strtotime($member['join_date']));
                } catch (Exception $e) {
                    $join_date = null;
                }
            }
            
            // 이메일 중복 체크
            $email_to_use = $member['email'] ?: '';
            if (!empty($email_to_use)) {
                $email_check = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ?");
                $email_check->execute([$email_to_use]);
                if ($email_check->fetchColumn() > 0) {
                    // 이메일이 중복되면 user_id@example.com 형식으로 변경
                    $email_to_use = $member['user_id'] . '@example.com';
                }
            }
            
            $insert_stmt = $pdo->prepare("
                INSERT INTO members (
                    user_id, password, name, email, phone, 
                    company, address, zipcode, homepage,
                    memo, is_active, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, 1, ?
                )
            ");
            
            $insert_stmt->execute([
                $member['user_id'],
                $password_hash,
                $member['name'] ?: '',
                $email_to_use,
                $member['phone'] ?: '',
                $member['company'] ?: '',
                $member['address'] ?: '',
                $member['zip_code'] ?: '',
                $member['homepage'] ?: '',
                $member['memo'] ?: '',
                $join_date ?: date('Y-m-d H:i:s')
            ]);
            
            $success_count++;
            echo "추가: {$member['user_id']}\n";
        }
        
    } catch (Exception $e) {
        $error_count++;
        echo "오류 ({$member['user_id']}): " . $e->getMessage() . "\n";
    }
}

echo "\n=== 처리 완료 ===\n";
echo "새로 추가: {$success_count}명\n";
echo "업데이트: {$update_count}명\n";
echo "건너뛰기: {$skip_count}명\n";
echo "오류: {$error_count}명\n";
?>