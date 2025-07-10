<?php
// 견적문의 데이터 삭제 스크립트
require_once 'db.php';

// 관리자 권한 확인 (임시)
$admin_password = "admin123";
$input_password = $_POST['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $input_password === $admin_password) {
    try {
        // 견적문의 테이블의 모든 데이터 삭제
        $stmt = $pdo->prepare("DELETE FROM board_quote");
        $stmt->execute();
        
        // AUTO_INCREMENT 값 리셋
        $stmt = $pdo->prepare("ALTER TABLE board_quote AUTO_INCREMENT = 1");
        $stmt->execute();
        
        $message = "견적문의 데이터가 모두 삭제되었습니다.";
        $success = true;
        
    } catch (PDOException $e) {
        $message = "삭제 중 오류가 발생했습니다: " . $e->getMessage();
        $success = false;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>견적문의 데이터 삭제</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #c82333;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>견적문의 데이터 삭제</h2>
        
        <div class="warning">
            <strong>경고:</strong> 이 작업은 board_quote 테이블의 모든 데이터를 삭제합니다. 이 작업은 되돌릴 수 없습니다.
        </div>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!isset($success) || !$success): ?>
        <form method="POST" action="" onsubmit="return confirm('정말로 모든 견적문의 데이터를 삭제하시겠습니까?');">
            <div class="form-group">
                <label for="password">관리자 비밀번호:</label>
                <input type="password" id="password" name="password" required placeholder="admin123">
            </div>
            <button type="submit">모든 데이터 삭제</button>
        </form>
        <?php endif; ?>
        
        <p style="margin-top: 30px;">
            <a href="quote.php">견적문의 페이지로 돌아가기</a>
        </p>
    </div>
</body>
</html>