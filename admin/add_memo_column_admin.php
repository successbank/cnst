<?php
require_once 'admin_check.php';
require_once '../db.php';

$pageTitle = '메모 컬럼 추가';
require_once 'admin_head.php';
?>

<div class="page-header">
    <h1>데이터베이스 업데이트</h1>
    <p>회원 테이블에 메모 컬럼을 추가합니다.</p>
</div>

<div class="content-box" style="background: white; padding: 32px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
    <?php
    if (isset($_POST['add_column'])) {
        try {
            // 먼저 컬럼이 이미 존재하는지 확인
            $stmt = $pdo->prepare("SHOW COLUMNS FROM members LIKE 'memo'");
            $stmt->execute();
            $column_exists = $stmt->fetch();
            
            if ($column_exists) {
                echo '<div class="msg" style="background: #E3F2FD; color: #1976D2; padding: 16px; border-radius: 8px; margin-bottom: 20px;">';
                echo 'ℹ️ 메모 컬럼이 이미 존재합니다.';
                echo '</div>';
            } else {
                // memo 컬럼 추가
                $sql = "ALTER TABLE members ADD COLUMN memo TEXT AFTER position";
                $pdo->exec($sql);
                
                echo '<div class="msg success" style="background: #E8F5E9; color: #2E7D32; padding: 16px; border-radius: 8px; margin-bottom: 20px;">';
                echo '✅ 회원 테이블에 메모 컬럼이 성공적으로 추가되었습니다.';
                echo '</div>';
            }
            
            // 현재 테이블 구조 표시
            echo '<h3 style="margin-top: 30px; margin-bottom: 16px;">현재 members 테이블 구조:</h3>';
            echo '<table style="width: 100%; border-collapse: collapse; border: 1px solid #E5E5E7;">';
            echo '<thead><tr style="background: #F5F5F7;">';
            echo '<th style="padding: 12px; text-align: left; border: 1px solid #E5E5E7;">Field</th>';
            echo '<th style="padding: 12px; text-align: left; border: 1px solid #E5E5E7;">Type</th>';
            echo '<th style="padding: 12px; text-align: left; border: 1px solid #E5E5E7;">Null</th>';
            echo '<th style="padding: 12px; text-align: left; border: 1px solid #E5E5E7;">Key</th>';
            echo '<th style="padding: 12px; text-align: left; border: 1px solid #E5E5E7;">Default</th>';
            echo '</tr></thead><tbody>';
            
            $stmt = $pdo->query("DESCRIBE members");
            while ($row = $stmt->fetch()) {
                $highlight = ($row['Field'] == 'memo') ? 'style="background: #FFF3E0;"' : '';
                echo "<tr $highlight>";
                echo '<td style="padding: 12px; border: 1px solid #E5E5E7;">' . htmlspecialchars($row['Field']) . '</td>';
                echo '<td style="padding: 12px; border: 1px solid #E5E5E7;">' . htmlspecialchars($row['Type']) . '</td>';
                echo '<td style="padding: 12px; border: 1px solid #E5E5E7;">' . htmlspecialchars($row['Null']) . '</td>';
                echo '<td style="padding: 12px; border: 1px solid #E5E5E7;">' . htmlspecialchars($row['Key']) . '</td>';
                echo '<td style="padding: 12px; border: 1px solid #E5E5E7;">' . htmlspecialchars($row['Default'] ?? 'NULL') . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            
        } catch(PDOException $e) {
            echo '<div class="msg error" style="background: #FFEBEE; color: #C62828; padding: 16px; border-radius: 8px; margin-bottom: 20px;">';
            echo '❌ 오류 발생: ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
    } else {
        ?>
        <form method="POST">
            <p style="margin-bottom: 20px;">이 작업은 members 테이블에 memo(메모) 컬럼을 추가합니다.</p>
            <p style="margin-bottom: 20px; color: #666;">메모 컬럼을 통해 각 회원에 대한 관리자 메모를 저장할 수 있습니다.</p>
            
            <button type="submit" name="add_column" value="1" 
                    style="padding: 12px 24px; background: #1A237E; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;"
                    onclick="return confirm('회원 테이블에 메모 컬럼을 추가하시겠습니까?')">
                메모 컬럼 추가
            </button>
        </form>
        <?php
    }
    ?>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #E5E5E7;">
        <a href="admin_members.php" style="padding: 10px 20px; background: #666; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">
            회원 관리로 돌아가기
        </a>
    </div>
</div>

<?php require_once 'admin_tail.php'; ?>