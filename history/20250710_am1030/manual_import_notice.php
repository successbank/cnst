<?php
require_once 'db.php';
require_once 'board/board_template.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 여러 공지사항을 한 번에 입력
    $notices = $_POST['notices'];
    $board = new BoardTemplate($db, 'notice');
    
    $successCount = 0;
    $failCount = 0;
    
    foreach ($notices as $notice) {
        if (empty($notice['title']) || empty($notice['content'])) {
            continue;
        }
        
        try {
            $sql = "INSERT INTO board_notice 
                    (title, content, writer, password, attachment, is_important, created_at, view_count) 
                    VALUES (:title, :content, :writer, :password, '', :is_important, :created_at, :view_count)";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':title', $notice['title']);
            $stmt->bindParam(':content', $notice['content']);
            $stmt->bindParam(':writer', $notice['writer']);
            $stmt->bindValue(':password', 'admin123');
            $stmt->bindValue(':is_important', isset($notice['is_important']) ? 1 : 0);
            $stmt->bindParam(':created_at', $notice['date']);
            $stmt->bindParam(':view_count', $notice['view_count']);
            
            if ($stmt->execute()) {
                $successCount++;
            } else {
                $failCount++;
            }
        } catch (Exception $e) {
            $failCount++;
        }
    }
    
    $message = "입력 완료! 성공: {$successCount}개, 실패: {$failCount}개";
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>공지사항 수동 입력</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { 
            color: #333; 
            border-bottom: 2px solid #1428A0;
            padding-bottom: 10px;
        }
        .notice-form { 
            background: white;
            border: 1px solid #ddd; 
            padding: 20px; 
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        label { 
            display: block; 
            font-weight: bold; 
            margin-bottom: 5px;
            color: #555;
        }
        input[type="text"], input[type="date"], input[type="number"], textarea { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea { 
            height: 150px; 
            resize: vertical;
        }
        .btn { 
            background: #1428A0; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn:hover { 
            background: #0F1F7A; 
        }
        .btn-add { 
            background: #28a745; 
            margin-bottom: 20px;
        }
        .btn-add:hover { 
            background: #218838; 
        }
        .message { 
            padding: 15px; 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .instructions {
            background: #e8f0fe;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #1428A0;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h3 {
            color: #1428A0;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>충남스틸 공지사항 수동 입력</h1>
    
    <div class="instructions">
        <h3>사용 방법:</h3>
        <ol>
            <li>CNST 웹사이트(cnst.co.kr)의 공지사항을 확인합니다.</li>
            <li>각 공지사항의 제목, 내용, 작성자, 날짜, 조회수를 아래 양식에 입력합니다.</li>
            <li>"공지사항 추가" 버튼을 클릭하여 여러 개의 공지사항을 추가할 수 있습니다.</li>
            <li>모든 입력이 완료되면 "일괄 저장" 버튼을 클릭합니다.</li>
        </ol>
    </div>
    
    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <button type="button" class="btn btn-add" onclick="addNoticeForm()">공지사항 추가</button>
    
    <form method="post">
        <div id="notices-container">
            <div class="notice-form">
                <h3>공지사항 #1</h3>
                <div class="form-group">
                    <label>제목</label>
                    <input type="text" name="notices[0][title]" required>
                </div>
                <div class="form-group">
                    <label>내용</label>
                    <textarea name="notices[0][content]" required></textarea>
                </div>
                <div class="form-group">
                    <label>작성자</label>
                    <input type="text" name="notices[0][writer]" value="관리자" required>
                </div>
                <div class="form-group">
                    <label>작성일</label>
                    <input type="date" name="notices[0][date]" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>조회수</label>
                    <input type="number" name="notices[0][view_count]" value="0" required>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="notices[0][is_important]" id="important_0">
                        <label for="important_0">중요 공지</label>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn">일괄 저장</button>
        <a href="notice.php" style="margin-left: 10px;">공지사항 페이지로 이동</a>
    </form>
    
    <script>
        let noticeCount = 1;
        
        function addNoticeForm() {
            const container = document.getElementById('notices-container');
            const div = document.createElement('div');
            div.className = 'notice-form';
            div.innerHTML = `
                <h3>공지사항 #${noticeCount + 1}</h3>
                <div class="form-group">
                    <label>제목</label>
                    <input type="text" name="notices[${noticeCount}][title]" required>
                </div>
                <div class="form-group">
                    <label>내용</label>
                    <textarea name="notices[${noticeCount}][content]" required></textarea>
                </div>
                <div class="form-group">
                    <label>작성자</label>
                    <input type="text" name="notices[${noticeCount}][writer]" value="관리자" required>
                </div>
                <div class="form-group">
                    <label>작성일</label>
                    <input type="date" name="notices[${noticeCount}][date]" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>조회수</label>
                    <input type="number" name="notices[${noticeCount}][view_count]" value="0" required>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="notices[${noticeCount}][is_important]" id="important_${noticeCount}">
                        <label for="important_${noticeCount}">중요 공지</label>
                    </div>
                </div>
            `;
            container.appendChild(div);
            noticeCount++;
        }
    </script>
</body>
</html>