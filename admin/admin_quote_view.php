<?php
require_once 'admin_check.php';
require_once '../db.php';

$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

if ($quote_id === 0) {
    header('Location: admin_quotes.php');
    exit;
}

// 답변 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_reply'])) {
    $admin_reply = trim($_POST['admin_reply']);
    
    if ($admin_reply) {
        try {
            $stmt = $pdo->prepare("UPDATE board_quote SET admin_reply = ?, is_answered = 1, replied_at = NOW() WHERE id = ?");
            $stmt->execute([$admin_reply, $quote_id]);
            $message = '답변이 저장되었습니다.';
        } catch(PDOException $e) {
            $error = '답변 저장 중 오류가 발생했습니다.';
        }
    } else {
        $error = '답변 내용을 입력해주세요.';
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM board_quote WHERE id = ?");
    $stmt->execute([$quote_id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        header('Location: admin_quotes.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: admin_quotes.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>견적문의 상세 | 충남스틸</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #F5F5F7;
        }
        
        .admin-header {
            background: #1A237E;
            color: white;
            padding: 16px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title {
            font-size: 24px;
            font-weight: 700;
        }
        
        .header-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .admin-info {
            font-size: 14px;
        }
        
        .logout-btn {
            padding: 8px 16px;
            background: white;
            color: #1A237E;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #F5F5F7;
        }
        
        .admin-nav {
            background: white;
            border-bottom: 1px solid #E5E5E7;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav-menu {
            display: flex;
            gap: 0;
        }
        
        .nav-item {
            padding: 16px 24px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .nav-item:hover {
            color: #1A237E;
            background: #F5F5F7;
        }
        
        .nav-item.active {
            color: #1A237E;
            border-bottom-color: #1A237E;
        }
        
        .admin-content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .detail-view {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .detail-header {
            background: #F8F9FA;
            padding: 32px;
            border-bottom: 1px solid #E5E5E7;
        }
        
        .detail-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 16px;
        }
        
        .meta-info {
            display: flex;
            gap: 32px;
            flex-wrap: wrap;
        }
        
        .meta-info span {
            font-size: 14px;
            color: #666;
        }
        
        .meta-info strong {
            color: #333;
        }
        
        .detail-body {
            padding: 32px;
        }
        
        .info-section {
            margin-bottom: 32px;
        }
        
        .info-section:last-child {
            margin-bottom: 0;
        }
        
        .info-section h3 {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #E5E5E7;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .info-label {
            font-size: 14px;
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            padding: 12px 16px;
            background: #F8F9FA;
            border-radius: 6px;
            border: 1px solid #E5E5E7;
        }
        
        .content-box {
            background: #F8F9FA;
            padding: 24px;
            border-radius: 8px;
            border: 1px solid #E5E5E7;
            line-height: 1.6;
            white-space: pre-wrap;
            font-size: 15px;
            color: #333;
        }
        
        .attachment-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: #E8F5E9;
            color: #2E7D32;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .attachment-link:hover {
            background: #C8E6C9;
        }
        
        .file-info {
            margin-left: 16px;
            font-size: 14px;
            color: #666;
        }
        
        .file-info a {
            color: #1428A0;
            text-decoration: none;
        }
        
        .file-info a:hover {
            text-decoration: underline;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-waiting {
            background: #FFF3E0;
            color: #F57C00;
        }
        
        .status-answered {
            background: #E8F5E9;
            color: #2E7D32;
        }
        
        .detail-footer {
            background: #F8F9FA;
            padding: 24px 32px;
            border-top: 1px solid #E5E5E7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: #1A237E;
            color: white;
        }
        
        .btn-primary:hover {
            background: #283593;
        }
        
        .btn-secondary {
            background: white;
            color: #666;
            border: 2px solid #E5E5E7;
        }
        
        .btn-secondary:hover {
            background: #F8F9FA;
        }
        
        .btn-success {
            background: #2E7D32;
            color: white;
        }
        
        .btn-success:hover {
            background: #388E3C;
        }
        
        .btn-warning {
            background: #F57C00;
            color: white;
        }
        
        .btn-warning:hover {
            background: #FB8C00;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
        }
        
        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
        }
        
        .reply-display {
            background: #E8F5E9;
            border: 1px solid #C8E6C9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .reply-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #C8E6C9;
        }
        
        .reply-status {
            background: #2E7D32;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .reply-date {
            font-size: 12px;
            color: #666;
        }
        
        .reply-content {
            line-height: 1.6;
            color: #333;
        }
        
        .reply-form {
            background: #F8F9FA;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #E5E5E7;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #E5E5E7;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.6;
            resize: vertical;
            min-height: 150px;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #1A237E;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
        }
        
        @media (max-width: 768px) {
            .meta-info {
                flex-direction: column;
                gap: 16px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .detail-header,
            .detail-body {
                padding: 20px;
            }
            
            .detail-footer {
                flex-direction: column;
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="header-content">
            <h1 class="header-title">충남스틸 관리자</h1>
            <div class="header-info">
                <span class="admin-info">관리자: <?php echo $_SESSION['admin_id']; ?></span>
                <a href="admin_logout.php" class="logout-btn">로그아웃</a>
            </div>
        </div>
    </header>
    
    <nav class="admin-nav">
        <div class="nav-content">
            <div class="nav-menu">
                <a href="admin_index.php" class="nav-item">대시보드</a>
                <a href="admin_notices.php" class="nav-item">공지사항 관리</a>
                <a href="admin_quotes.php" class="nav-item active">견적문의 관리</a>
                <a href="admin_members.php" class="nav-item">회원 관리</a>
                <a href="../index.php" class="nav-item" target="_blank">사이트 보기</a>
            </div>
        </div>
    </nav>
    
    <main class="admin-content">
        <?php if($message): ?>
            <div class="alert alert-success">
                <strong>성공!</strong> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <strong>오류!</strong> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="detail-view">
            <div class="detail-header">
                <h2><?php echo htmlspecialchars($quote['title']); ?></h2>
                <div class="meta-info">
                    <span><strong>작성자:</strong> <?php echo htmlspecialchars($quote['writer']); ?></span>
                    <span><strong>작성일:</strong> <?php echo date('Y-m-d H:i:s', strtotime($quote['created_at'])); ?></span>
                    <span><strong>조회수:</strong> <?php echo number_format($quote['view_count']); ?></span>
                    <span><strong>답변상태:</strong> 
                        <?php if($quote['is_answered']): ?>
                            <span class="status-badge status-answered">답변완료</span>
                        <?php else: ?>
                            <span class="status-badge status-waiting">대기중</span>
                        <?php endif; ?>
                    </span>
                    <?php if($quote['replied_at']): ?>
                        <span><strong>답변일:</strong> <?php echo date('Y-m-d H:i:s', strtotime($quote['replied_at'])); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="detail-body">
                <div class="info-section">
                    <h3>연락처 정보</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">회사명</div>
                            <div class="info-value"><?php echo htmlspecialchars($quote['company'] ?: '미입력'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">이메일</div>
                            <div class="info-value"><?php echo htmlspecialchars($quote['email'] ?: '미입력'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">연락처</div>
                            <div class="info-value"><?php echo htmlspecialchars($quote['phone'] ?: '미입력'); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>견적문의 내용</h3>
                    <div class="content-box"><?php echo htmlspecialchars($quote['content']); ?></div>
                </div>
                
                <?php if ($quote['attachment']): ?>
                <div class="info-section">
                    <h3>첨부파일</h3>
                    <?php
                    // JSON 형식인지 확인
                    $attachments = json_decode($quote['attachment'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($attachments)) {
                        // 다중 파일
                        foreach ($attachments as $attachment) {
                            ?>
                            <div style="margin-bottom: 10px;">
                                <a href="/download.php?type=quote&file=<?php echo rawurlencode($attachment); ?>" 
                                   class="attachment-link">
                                    📎 <?php echo htmlspecialchars($attachment); ?>
                                </a>
                                <span class="file-info">
                                    (이미지 보기: <a href="/view_image.php?type=quote&file=<?php echo rawurlencode($attachment); ?>" target="_blank">새 창에서 열기</a>)
                                </span>
                            </div>
                            <?php
                        }
                    } else {
                        // 단일 파일 (기존 데이터와의 호환성)
                        ?>
                        <a href="/download.php?type=quote&file=<?php echo rawurlencode($quote['attachment']); ?>" 
                           class="attachment-link">
                            📎 <?php echo htmlspecialchars($quote['attachment']); ?>
                        </a>
                        <span class="file-info">
                            (이미지 보기: <a href="/view_image.php?type=quote&file=<?php echo rawurlencode($quote['attachment']); ?>" target="_blank">새 창에서 열기</a>)
                        </span>
                        <?php
                    }
                    ?>
                </div>
                <?php endif; ?>
                
                <!-- 관리자 답변 섹션 -->
                <div class="info-section">
                    <h3>관리자 답변</h3>
                    <?php if($quote['admin_reply']): ?>
                        <div class="reply-display">
                            <div class="reply-header">
                                <span class="reply-status">답변 완료</span>
                                <span class="reply-date"><?php echo date('Y-m-d H:i:s', strtotime($quote['replied_at'])); ?></span>
                            </div>
                            <div class="reply-content">
                                <?php echo nl2br(htmlspecialchars($quote['admin_reply'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="reply-form">
                        <div class="form-group">
                            <label for="admin_reply">
                                <?php echo $quote['admin_reply'] ? '답변 수정' : '답변 작성'; ?>
                            </label>
                            <textarea 
                                id="admin_reply" 
                                name="admin_reply" 
                                rows="8" 
                                placeholder="고객에게 보낼 답변을 작성해주세요..."
                                required><?php echo htmlspecialchars($quote['admin_reply'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $quote['admin_reply'] ? '답변 수정' : '답변 저장'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="detail-footer">
                <a href="admin_quotes.php" class="btn btn-secondary">목록으로</a>
                <div style="display: flex; gap: 12px;">
                    <a href="admin_quotes.php?action=toggle_answer&id=<?php echo $quote['id']; ?>" 
                       class="btn <?php echo $quote['is_answered'] ? 'btn-warning' : 'btn-success'; ?>">
                        <?php echo $quote['is_answered'] ? '답변취소' : '답변완료'; ?>
                    </a>
                    <a href="admin_quotes.php?action=delete&id=<?php echo $quote['id']; ?>" 
                       class="btn btn-danger" 
                       onclick="return confirm('정말 삭제하시겠습니까?');"
                       style="background: #E53935; color: white;">
                        삭제
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>