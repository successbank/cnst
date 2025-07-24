<?php
// 직접 DB 연결
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=project1_db;charset=utf8mb4", "root", "rootpassword");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ 데이터베이스 연결 성공\n\n";
} catch (PDOException $e) {
    die("❌ 데이터베이스 연결 실패: " . $e->getMessage() . "\n");
}

echo "=== 전체 회원 로그인 이력 가져오기 ===\n";
echo "이 작업은 시간이 오래 걸릴 수 있습니다...\n\n";

// CSV 파일로 member.xls 데이터를 먼저 파싱
exec("python3 -c \"
import pandas as pd

# Read HTML table
dfs = pd.read_html('./114/member.xls')
df = dfs[0]

# Set column names and clean data
df.columns = df.iloc[0]
df = df.iloc[1:].reset_index(drop=True)

# Extract login data
login_data = df[['MB_ID', 'MB_LOGNUM', 'MB_LOGDATE']].copy()
login_data = login_data[login_data['MB_ID'].notna()]
login_data.to_csv('temp_login_data.csv', index=False)
print(f'Exported {len(login_data)} records')
\"");

// CSV 파일 읽기
if (!file_exists('temp_login_data.csv')) {
    die("❌ 임시 CSV 파일을 생성할 수 없습니다.\n");
}

$processed = 0;
$updated = 0;
$errors = 0;

// CSV 파일 처리
if (($handle = fopen('temp_login_data.csv', 'r')) !== FALSE) {
    // 헤더 건너뛰기
    fgetcsv($handle);
    
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        $mb_id = trim($data[0]);
        $mb_lognum = trim($data[1]);
        $mb_logdate = trim($data[2]);
        
        if (empty($mb_id) || $mb_lognum == '0') {
            continue;
        }
        
        $processed++;
        
        try {
            // 회원 조회
            $stmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
            $stmt->execute([$mb_id]);
            $member = $stmt->fetch();
            
            if ($member) {
                // 날짜 파싱
                $last_login_date = null;
                if (!empty($mb_logdate) && $mb_logdate != '0' && strlen($mb_logdate) >= 8) {
                    $year = substr($mb_logdate, 0, 4);
                    $month = substr($mb_logdate, 4, 2);
                    $day = substr($mb_logdate, 6, 2);
                    
                    if (checkdate($month, $day, $year)) {
                        if (strlen($mb_logdate) >= 14) {
                            $hour = substr($mb_logdate, 8, 2);
                            $minute = substr($mb_logdate, 10, 2);
                            $second = substr($mb_logdate, 12, 2);
                            $last_login_date = "$year-$month-$day $hour:$minute:$second";
                        } else {
                            $last_login_date = "$year-$month-$day 00:00:00";
                        }
                    }
                }
                
                // members 테이블 업데이트
                $stmt = $pdo->prepare("UPDATE members SET 
                    total_login_count = ?, 
                    last_login_imported = ?,
                    login_history_imported = 1
                    WHERE id = ?");
                $stmt->execute([$mb_lognum, $last_login_date, $member['id']]);
                
                // 요약 테이블 업데이트
                $stmt = $pdo->prepare("INSERT INTO member_login_summary 
                    (member_id, user_id, last_login_date, total_login_count) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    last_login_date = VALUES(last_login_date),
                    total_login_count = VALUES(total_login_count)");
                $stmt->execute([$member['id'], $mb_id, $last_login_date, $mb_lognum]);
                
                $updated++;
                
                if ($updated % 100 == 0) {
                    echo "진행: $updated 명 업데이트...\n";
                }
            }
            
        } catch (PDOException $e) {
            $errors++;
        }
    }
    fclose($handle);
}

// 임시 파일 삭제
unlink('temp_login_data.csv');

echo "\n=== 가져오기 완료 ===\n";
echo "✅ 처리된 레코드: $processed\n";
echo "✅ 업데이트된 회원: $updated\n";
echo "❌ 오류: $errors\n";