<?php
require_once '../db.php';
require_once '../member_check.php';

header('Content-Type: application/json');

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$member_id = $_SESSION['member_id'];

if (!in_array($type, ['quote', 'consignment']) || !$id) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    // 게시글 조회
    if ($type === 'quote') {
        $stmt = $pdo->prepare("SELECT * FROM board_quote WHERE id = ?");
        $stmt->execute([$id]);
        $post = $stmt->fetch();
        
        // 권한 확인
        if (!$post || ($post['member_id'] != $member_id && !isAdmin())) {
            echo json_encode(['success' => false, 'message' => '조회 권한이 없습니다.']);
            exit;
        }
        
        // HTML 생성
        ob_start();
        ?>
        <div class="detail-section">
            <h4>기본 정보</h4>
            <div class="detail-info">
                <p><strong>제목:</strong> <?php echo htmlspecialchars($post['title']); ?></p>
                <p><strong>작성일:</strong> <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></p>
                <p><strong>회사명:</strong> <?php echo htmlspecialchars($post['company'] ?: '-'); ?></p>
                <p><strong>작성자:</strong> <?php echo htmlspecialchars($post['writer']); ?></p>
                <p><strong>연락처:</strong> <?php echo htmlspecialchars($post['phone'] ?: '-'); ?></p>
                <p><strong>이메일:</strong> <?php echo htmlspecialchars($post['email'] ?: '-'); ?></p>
                <p><strong>상태:</strong> 
                    <?php if($post['is_answered']): ?>
                        <span class="status-badge status-answered">답변완료</span>
                    <?php else: ?>
                        <span class="status-badge status-waiting">대기중</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>문의 내용</h4>
            <div class="content-box">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
        </div>
        
        <?php if ($post['attachment']): ?>
        <div class="detail-section">
            <h4>첨부파일</h4>
            <div class="detail-info">
                <p><a href="<?php echo htmlspecialchars($post['attachment']); ?>" download><?php echo basename($post['attachment']); ?></a></p>
            </div>
        </div>
        <?php endif; ?>
        <?php
        
    } else { // consignment
        $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
        $stmt->execute([$id]);
        $post = $stmt->fetch();
        
        // 권한 확인 - writer로 체크
        if (!$post || ($post['writer'] != $_SESSION['name'] && !isAdmin())) {
            echo json_encode(['success' => false, 'message' => '조회 권한이 없습니다.']);
            exit;
        }
        
        // HTML 생성
        ob_start();
        ?>
        <div class="detail-section">
            <h4>기본 정보</h4>
            <div class="detail-info">
                <p><strong>제목:</strong> <?php echo htmlspecialchars($post['title']); ?></p>
                <p><strong>작성일:</strong> <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></p>
                <p><strong>회사명:</strong> <?php echo htmlspecialchars($post['company_name']); ?></p>
                <p><strong>카테고리:</strong> <?php echo htmlspecialchars($post['category']); ?></p>
                <p><strong>재고수량:</strong> <?php echo htmlspecialchars($post['stock_quantity'] ?: '-'); ?></p>
                <p><strong>가격정보:</strong> <?php echo htmlspecialchars($post['price_info'] ?: '-'); ?></p>
                <p><strong>상태:</strong> 
                    <?php 
                    $statusLabels = [
                        'active' => '진행중',
                        'sold' => '판매완료',
                        'inactive' => '종료'
                    ];
                    $statusClass = 'status-' . $post['status'];
                    ?>
                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabels[$post['status']] ?? '진행중'; ?></span>
                </p>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>담당자 정보</h4>
            <div class="detail-info">
                <p><strong>담당자:</strong> <?php echo htmlspecialchars($post['contact_person'] ?: '-'); ?></p>
                <p><strong>연락처:</strong> <?php echo htmlspecialchars($post['contact_phone'] ?: '-'); ?></p>
                <p><strong>이메일:</strong> <?php echo htmlspecialchars($post['contact_email'] ?: '-'); ?></p>
                <p><strong>소재지:</strong> <?php echo htmlspecialchars($post['location'] ?: '-'); ?></p>
            </div>
        </div>
        
        <div class="detail-section">
            <h4>상세 내용</h4>
            <div class="content-box">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>
        </div>
        
        <?php if ($post['attachment']): ?>
        <div class="detail-section">
            <h4>첨부파일</h4>
            <div class="detail-info">
                <p><a href="<?php echo htmlspecialchars($post['attachment']); ?>" download><?php echo basename($post['attachment']); ?></a></p>
            </div>
        </div>
        <?php endif; ?>
        <?php
    }
    
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'title' => htmlspecialchars($post['title']),
        'html' => $html
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 오류가 발생했습니다.']);
}
?>