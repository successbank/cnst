<?php
session_start();

// 이미 관리자로 로그인되어 있으면 관리자 페이지로 리다이렉트
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin_index.php');
    exit;
}

// 로그인 처리
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_POST['admin_id'] ?? '';
    $admin_pw = $_POST['admin_pw'] ?? '';
    
    // 기본 관리자 계정 (실제 운영시에는 DB에서 관리)
    if($admin_id === 'admin' && $admin_pw === 'admin1234') {
        // 기존 세션 완전히 초기화
        session_destroy();
        session_start();
        
        // 관리자 세션만 설정
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin_id;
        $_SESSION['user_role'] = 'admin'; // 역할 추가
        $_SESSION['admin_login_time'] = time();
        
        header('Location: admin_index.php');
        exit;
    } else {
        $error_msg = "아이디 또는 비밀번호가 올바르지 않습니다.";
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인 | 충남스틸</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #1A237E 0%, #283593 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header h1 {
            font-size: 28px;
            color: #1A237E;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E5E7;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1A237E;
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: #1A237E;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            background: #283593;
            transform: translateY(-1px);
        }
        
        .error-msg {
            background: #FFEBEE;
            color: #C62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #1A237E;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>충남스틸 관리자</h1>
            <p>관리자 로그인이 필요합니다</p>
        </div>
        
        <?php if(isset($error_msg)): ?>
            <div class="error-msg"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="admin_id">관리자 ID</label>
                <input type="text" id="admin_id" name="admin_id" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="admin_pw">비밀번호</label>
                <input type="password" id="admin_pw" name="admin_pw" required>
            </div>
            
            <button type="submit" class="login-btn">로그인</button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">← 메인 페이지로 돌아가기</a>
        </div>
    </div>
</body>
</html>