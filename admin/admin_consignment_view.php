<?php
session_start();
require_once '../db.php';
require_once 'admin_check.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: admin_consignment.php');
    exit;
}

// 액션 처리 (헤더 출력 전에 처리)
$action = $_GET['action'] ?? '';

// 상태 변경 처리
if($action === 'change_status' && isset($_POST['status'])) {
    $new_status = $_POST['status'];

    if(in_array($new_status, ['pending', 'approved', 'rejected', 'sold', 'inactive'])) {
        try {
            $admin_id = $_SESSION['admin_id'] ?? null;
            if (in_array($new_status, ['approved', 'rejected'])) {
                $stmt = $pdo->prepare("UPDATE board_consignment SET status = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_status, $admin_id, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE board_consignment SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_status, $id]);
            }
            header('Location: admin_consignment_view.php?id=' . $id . '&msg=status_changed');
            exit;
        } catch(PDOException $e) {
            $error = "상태 변경 중 오류가 발생했습니다.";
        }
    }
}

// 메모 저장
if($action === 'save_note' && isset($_POST['admin_note'])) {
    try {
        $stmt = $pdo->prepare("UPDATE board_consignment SET admin_note = ? WHERE id = ?");
        $stmt->execute([trim($_POST['admin_note']), $id]);
        header('Location: admin_consignment_view.php?id=' . $id . '&msg=note_saved');
        exit;
    } catch(PDOException $e) {
        $error = "메모 저장 중 오류가 발생했습니다.";
    }
}

// 삭제 처리
if($action === 'delete') {
    try {
        $stmt = $pdo->prepare("DELETE FROM board_consignment WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: admin_consignment.php?msg=deleted');
        exit;
    } catch(PDOException $e) {
        $error = "삭제 중 오류가 발생했습니다.";
    }
}

// 게시글 정보 조회
try {
    $stmt = $pdo->prepare("SELECT * FROM board_consignment WHERE id = ?");
    $stmt->execute([$id]);
    $consignment = $stmt->fetch();

    if (!$consignment) {
        header('Location: admin_consignment.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: admin_consignment.php');
    exit;
}

// 심사자 정보 조회
$reviewer = null;
if ($consignment['reviewed_by']) {
    try {
        $stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
        $stmt->execute([$consignment['reviewed_by']]);
        $reviewer = $stmt->fetch();
    } catch(PDOException $e) {
        // 무시
    }
}

$pageTitle = '판매의뢰 상세 - ' . $consignment['title'];

// 추가 스타일 정의
$additionalStyles = '
.detail-section {
    background: #fff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.detail-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-size: 13px;
    color: #666;
    font-weight: 500;
}

.info-value {
    font-size: 15px;
    color: #333;
    font-weight: 500;
}

.content-section {
    margin-top: 32px;
    padding-top: 32px;
    border-top: 1px solid #E5E5E7;
}

.content-section h3 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
}

.content-box {
    background: #F8F9FA;
    padding: 20px;
    border-radius: 8px;
    white-space: pre-wrap;
    word-break: break-word;
    line-height: 1.6;
}

.status-pending { background: #FFF3E0; color: #F57C00; }
.status-approved { background: #E8F5E9; color: #2E7D32; }
.status-rejected { background: #FFEBEE; color: #C62828; }
.status-sold { background: #E3F2FD; color: #1976D2; }
.status-inactive { background: #F5F5F7; color: #666; }

.type-steel { background: #E3F2FD; color: #1565C0; }
.type-lumber { background: #FFF8E1; color: #F57F17; }

.review-section {
    background: #F8F9FA;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.review-section h3 {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 16px;
}

.review-buttons {
    display: flex;
    gap: 12px;
    margin-top: 16px;
    flex-wrap: wrap;
}

.btn-approve {
    padding: 12px 24px;
    background: #2E7D32;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-approve:hover {
    background: #1B5E20;
}

.btn-reject {
    padding: 12px 24px;
    background: #C62828;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-reject:hover {
    background: #B71C1C;
}

.reject-info {
    background: #FFEBEE;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #C62828;
    margin-top: 16px;
}

.review-info {
    background: #E8F5E9;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #2E7D32;
    margin-top: 16px;
}

.note-section textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    min-height: 80px;
    resize: vertical;
    font-size: 14px;
    margin-bottom: 12px;
}

.status-form {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 24px;
}

.status-form select {
    padding: 8px 16px;
    border: 1px solid #E5E5E7;
    border-radius: 8px;
    font-size: 14px;
}

.btn-primary {
    background: #1A237E;
    color: white;
    padding: 8px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.btn-primary:hover {
    background: #283593;
}

.btn-danger {
    background: #DC3545;
    color: white;
}

.btn-danger:hover {
    background: #C82333;
}

.btn-secondary {
    background: #6C757D;
    color: white;
}

.btn-secondary:hover {
    background: #5A6268;
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
}

.attachment-link:hover {
    background: #C8E6C9;
}

/* 모달 스타일 */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #E5E5E7;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    line-height: 1;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #E5E5E7;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.approve-summary {
    display: grid;
    grid-template-columns: 100px 1fr;
    gap: 8px 16px;
    background: #F8F9FA;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.approve-summary dt {
    font-weight: 600;
    color: #666;
}

.approve-summary dd {
    margin: 0;
    color: #333;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 14px;
    color: #333;
}

.form-group label .required {
    color: #C62828;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group textarea {
    min-height: 80px;
    resize: vertical;
}

.form-group .help-text {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

.preview-section {
    margin-top: 16px;
    display: none;
}

.preview-section h4 {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.message-preview {
    background: #FFF9C4;
    border: 1px solid #FFF176;
    border-radius: 12px;
    padding: 16px;
    font-size: 13px;
    line-height: 1.7;
    white-space: pre-wrap;
    word-break: break-word;
    max-height: 300px;
    overflow-y: auto;
}

.preview-meta {
    margin-top: 8px;
    font-size: 12px;
    color: #666;
}

/* 카카오 발송 상태 배지 */
.kakao-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.kakao-badge.sent { background: #E8F5E9; color: #2E7D32; }
.kakao-badge.failed { background: #FFEBEE; color: #C62828; }
.kakao-badge.pending { background: #FFF3E0; color: #F57C00; }

/* 발송 이력 테이블 */
.kakao-history-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.kakao-history-table th {
    background: #F8F9FA;
    padding: 10px 12px;
    text-align: center;
    font-weight: 600;
    border-bottom: 2px solid #E5E5E7;
}

.kakao-history-table td {
    padding: 10px 12px;
    text-align: center;
    border-bottom: 1px solid #E5E5E7;
}

.btn-resend {
    padding: 4px 12px;
    background: #FF9800;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}

.btn-resend:hover {
    background: #F57C00;
}

.btn-preview-sm {
    padding: 4px 12px;
    background: #1976D2;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    margin-left: 4px;
}

.btn-preview-sm:hover {
    background: #1565C0;
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    .approve-summary {
        grid-template-columns: 80px 1fr;
    }
}
';

require_once 'admin_head.php';
?>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg success">
                <?php
                if($_GET['msg'] === 'status_changed') echo "상태가 변경되었습니다.";
                if($_GET['msg'] === 'note_saved') echo "메모가 저장되었습니다.";
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="msg error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="page-header">
            <h1>판매의뢰 상세</h1>
            <a href="admin_consignment.php" class="btn-back">&larr; 목록으로</a>
        </div>

        <!-- 승인/거절 섹션 (pending 상태일 때만 강조 표시) -->
        <?php if($consignment['status'] === 'pending'): ?>
        <div class="review-section">
            <h3>심사 대기중</h3>
            <p>이 판매의뢰를 검토한 후 승인 또는 거절해주세요.</p>
            <div class="review-buttons">
                <button type="button" class="btn-approve" onclick="openApproveModal()">승인</button>
                <button type="button" class="btn-reject" onclick="openRejectModal()">거절</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- 거절 사유 표시 -->
        <?php if($consignment['status'] === 'rejected' && !empty($consignment['reject_reason'])): ?>
        <div class="reject-info" style="margin-bottom: 24px;">
            <strong>거절 사유:</strong> <?php echo htmlspecialchars($consignment['reject_reason']); ?>
            <?php if($reviewer): ?>
            <br><small>심사자: <?php echo htmlspecialchars($reviewer['username']); ?> | <?php echo date('Y-m-d H:i', strtotime($consignment['reviewed_at'])); ?></small>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 승인 정보 표시 -->
        <?php if($consignment['status'] === 'approved' && $consignment['reviewed_at']): ?>
        <div class="review-info" style="margin-bottom: 24px;">
            <strong>승인됨</strong>
            <?php if($reviewer): ?>
            - 심사자: <?php echo htmlspecialchars($reviewer['username']); ?> | <?php echo date('Y-m-d H:i', strtotime($consignment['reviewed_at'])); ?>
            <?php endif; ?>
            <?php if(!empty($consignment['approved_price'])): ?>
            <br><strong>중개 금액:</strong> <?php echo number_format($consignment['approved_price']); ?>원
            <?php endif; ?>
            <?php if(!empty($consignment['approved_message'])): ?>
            <br><strong>발송 메시지:</strong> <?php echo htmlspecialchars($consignment['approved_message']); ?>
            <?php endif; ?>
            <?php if($consignment['kakao_send_status']): ?>
            <br><strong>알림톡:</strong>
                <span class="kakao-badge <?php echo $consignment['kakao_send_status']; ?>">
                    <?php
                    switch($consignment['kakao_send_status']) {
                        case 'sent': echo '발송완료'; break;
                        case 'failed': echo '발송실패'; break;
                        case 'pending': echo '발송대기'; break;
                        default: echo $consignment['kakao_send_status'];
                    }
                    ?>
                </span>
                <?php if($consignment['kakao_sent_at']): ?>
                    <small>(<?php echo date('Y-m-d H:i', strtotime($consignment['kakao_sent_at'])); ?>)</small>
                <?php endif; ?>
                <?php if($consignment['kakao_send_status'] === 'failed'): ?>
                    <button type="button" class="btn-resend" onclick="resendKakao(<?php echo $id; ?>)" style="margin-left: 8px;">재발송</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 거절 상태에서의 알림톡 표시 -->
        <?php if($consignment['status'] === 'rejected' && $consignment['kakao_send_status']): ?>
        <div style="background: #FFF3E0; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #F57C00;">
            <strong>알림톡:</strong>
            <span class="kakao-badge <?php echo $consignment['kakao_send_status']; ?>">
                <?php
                switch($consignment['kakao_send_status']) {
                    case 'sent': echo '발송완료'; break;
                    case 'failed': echo '발송실패'; break;
                    case 'pending': echo '발송대기'; break;
                    default: echo $consignment['kakao_send_status'];
                }
                ?>
            </span>
            <?php if($consignment['kakao_sent_at']): ?>
                <small>(<?php echo date('Y-m-d H:i', strtotime($consignment['kakao_sent_at'])); ?>)</small>
            <?php endif; ?>
            <?php if($consignment['kakao_send_status'] === 'failed'): ?>
                <button type="button" class="btn-resend" onclick="resendKakao(<?php echo $id; ?>)" style="margin-left: 8px;">재발송</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="detail-section">
            <div class="detail-header">
                <h2><?php echo htmlspecialchars($consignment['title']); ?></h2>
                <div class="action-buttons">
                    <a href="?action=delete&id=<?php echo $id; ?>"
                       class="btn btn-danger"
                       onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
                    <a href="admin_consignment.php" class="btn btn-secondary">목록</a>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">작성자</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['writer']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">회사명</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['company_name'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">유형</span>
                    <span class="info-value">
                        <span class="status-badge type-<?php echo $consignment['item_type']; ?>">
                            <?php echo $consignment['item_type'] === 'steel' ? '철강류' : '목재류'; ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">카테고리</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['category'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">재고수량</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['stock_quantity'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">가격정보</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['price_info'] ?? '협의'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">소재지</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['location'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">담당자</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['contact_person'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">연락처</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['contact_phone'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">이메일</span>
                    <span class="info-value"><?php echo htmlspecialchars($consignment['contact_email'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">등록일</span>
                    <span class="info-value"><?php echo date('Y-m-d H:i', strtotime($consignment['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">조회수</span>
                    <span class="info-value"><?php echo number_format($consignment['view_count']); ?>회</span>
                </div>
                <div class="info-item">
                    <span class="info-label">현재 상태</span>
                    <span class="info-value">
                        <?php
                        $statusClass = 'status-' . $consignment['status'];
                        $statusText = '';
                        switch($consignment['status']) {
                            case 'pending': $statusText = '대기중'; break;
                            case 'approved': $statusText = '승인(게시중)'; break;
                            case 'rejected': $statusText = '거절'; break;
                            case 'sold': $statusText = '판매완료'; break;
                            case 'inactive': $statusText = '비활성'; break;
                            default: $statusText = $consignment['status'];
                        }
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </span>
                </div>
            </div>

            <div class="content-section">
                <h3>상세 내용</h3>
                <div class="content-box">
                    <?php echo nl2br(htmlspecialchars($consignment['content'])); ?>
                </div>
            </div>

            <?php if ($consignment['attachment']): ?>
            <div class="content-section">
                <h3>첨부파일</h3>
                <?php
                $attachments = json_decode($consignment['attachment'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($attachments)) {
                    foreach ($attachments as $attachment) {
                        ?>
                        <div style="margin-bottom: 10px;">
                            <a href="/download.php?type=consignment&file=<?php echo rawurlencode($attachment); ?>"
                               class="attachment-link">
                                📎 <?php echo htmlspecialchars($attachment); ?>
                            </a>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <a href="/download.php?type=consignment&file=<?php echo rawurlencode($consignment['attachment']); ?>"
                       class="attachment-link">
                        📎 <?php echo htmlspecialchars($consignment['attachment']); ?>
                    </a>
                    <?php
                }
                ?>
            </div>
            <?php endif; ?>

            <!-- 카카오 발송 이력 섹션 -->
            <?php if(in_array($consignment['status'], ['approved', 'rejected'])): ?>
            <div class="content-section">
                <h3>카카오 알림톡 발송 이력</h3>
                <div id="kakaoHistoryContainer">
                    <p style="color: #999; font-size: 14px;">이력을 불러오는 중...</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- 관리자 메모 -->
            <div class="content-section note-section">
                <h3>관리자 메모</h3>
                <form method="POST" action="?action=save_note&id=<?php echo $id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                    <textarea name="admin_note" placeholder="내부 메모를 입력하세요..."><?php echo htmlspecialchars($consignment['admin_note'] ?? ''); ?></textarea>
                    <button type="submit" class="btn btn-primary">메모 저장</button>
                </form>
            </div>

            <div class="content-section">
                <h3>상태 변경</h3>
                <form method="POST" action="?action=change_status&id=<?php echo $id; ?>" class="status-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                    <select name="status">
                        <option value="pending" <?php echo $consignment['status'] === 'pending' ? 'selected' : ''; ?>>대기중</option>
                        <option value="approved" <?php echo $consignment['status'] === 'approved' ? 'selected' : ''; ?>>승인(게시중)</option>
                        <option value="rejected" <?php echo $consignment['status'] === 'rejected' ? 'selected' : ''; ?>>거절</option>
                        <option value="sold" <?php echo $consignment['status'] === 'sold' ? 'selected' : ''; ?>>판매완료</option>
                        <option value="inactive" <?php echo $consignment['status'] === 'inactive' ? 'selected' : ''; ?>>비활성</option>
                    </select>
                    <button type="submit" class="btn btn-primary">상태 변경</button>
                </form>
            </div>
        </div>

<!-- 승인 모달 -->
<div class="modal-overlay" id="approveModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>판매의뢰 승인</h3>
            <button type="button" class="modal-close" onclick="closeApproveModal()">&times;</button>
        </div>
        <div class="modal-body">
            <dl class="approve-summary">
                <dt>제목</dt>
                <dd><?php echo htmlspecialchars($consignment['title']); ?></dd>
                <dt>의뢰자</dt>
                <dd><?php echo htmlspecialchars($consignment['contact_person'] ?? $consignment['writer']); ?></dd>
                <dt>카테고리</dt>
                <dd><?php echo htmlspecialchars($consignment['category'] ?? '-'); ?></dd>
                <dt>재고수량</dt>
                <dd><?php echo htmlspecialchars($consignment['stock_quantity'] ?? '-'); ?></dd>
                <dt>연락처</dt>
                <dd><?php echo htmlspecialchars($consignment['contact_phone'] ?? '미등록'); ?></dd>
            </dl>

            <div class="form-group">
                <label>중개 금액 <span class="required">*</span></label>
                <input type="text" id="approvedPrice" placeholder="예: 1,500,000" oninput="formatPrice(this)">
                <div class="help-text">카카오 알림톡으로 고객에게 안내됩니다.</div>
            </div>

            <div class="form-group">
                <label>추가 메시지 (선택)</label>
                <textarea id="approvedMessage" placeholder="고객에게 전달할 추가 안내 메시지를 입력하세요..."></textarea>
            </div>

            <button type="button" onclick="previewApproveMessage()" style="padding: 8px 16px; background: #1976D2; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;">미리보기</button>

            <div class="preview-section" id="approvePreview">
                <h4>알림톡 미리보기</h4>
                <div class="message-preview" id="approvePreviewContent"></div>
                <div class="preview-meta" id="approvePreviewMeta"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeApproveModal()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 8px; cursor: pointer;">취소</button>
            <button type="button" class="btn-approve" onclick="submitApproval()">승인 및 알림 발송</button>
        </div>
    </div>
</div>

<!-- 거절 모달 -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>판매의뢰 거절</h3>
            <button type="button" class="modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <div class="modal-body">
            <dl class="approve-summary">
                <dt>제목</dt>
                <dd><?php echo htmlspecialchars($consignment['title']); ?></dd>
                <dt>의뢰자</dt>
                <dd><?php echo htmlspecialchars($consignment['contact_person'] ?? $consignment['writer']); ?></dd>
                <dt>연락처</dt>
                <dd><?php echo htmlspecialchars($consignment['contact_phone'] ?? '미등록'); ?></dd>
            </dl>

            <div class="form-group">
                <label>거절 사유 <span class="required">*</span></label>
                <textarea id="rejectReason" placeholder="거절 사유를 입력해주세요..."></textarea>
            </div>

            <div class="form-group">
                <label>추가 메시지 (선택)</label>
                <textarea id="rejectMessage" placeholder="고객에게 전달할 추가 안내 메시지를 입력하세요..."></textarea>
            </div>

            <button type="button" onclick="previewRejectMessage()" style="padding: 8px 16px; background: #1976D2; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px;">미리보기</button>

            <div class="preview-section" id="rejectPreview">
                <h4>알림톡 미리보기</h4>
                <div class="message-preview" id="rejectPreviewContent"></div>
                <div class="preview-meta" id="rejectPreviewMeta"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeRejectModal()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 8px; cursor: pointer;">취소</button>
            <button type="button" class="btn-reject" onclick="submitRejection()">거절 확정 및 알림 발송</button>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?php echo generateCsrfToken(); ?>';
const postId = <?php echo $id; ?>;
const postStatus = '<?php echo $consignment['status']; ?>';

// 모달 제어
function openApproveModal() {
    document.getElementById('approveModal').style.display = 'flex';
    document.getElementById('approvedPrice').focus();
}

function closeApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
    document.getElementById('approvePreview').style.display = 'none';
}

function openRejectModal() {
    document.getElementById('rejectModal').style.display = 'flex';
    document.getElementById('rejectReason').focus();
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('rejectPreview').style.display = 'none';
}

// 천 단위 콤마 자동 포맷
function formatPrice(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (value) {
        input.value = Number(value).toLocaleString('ko-KR');
    }
}

// 승인 미리보기
function previewApproveMessage() {
    const price = document.getElementById('approvedPrice').value;
    const message = document.getElementById('approvedMessage').value;

    if (!price || price.replace(/[^0-9]/g, '') === '0') {
        alert('중개 금액을 입력해주세요.');
        return;
    }

    const formData = new FormData();
    formData.append('id', postId);
    formData.append('type', 'approve');
    formData.append('approved_price', price);
    formData.append('approved_message', message);
    formData.append('csrf_token', csrfToken);

    fetch('../ajax/preview_consignment_message.php', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('approvePreviewContent').textContent = data.preview;
            let meta = '수신자: ' + (data.recipient_name || '-');
            if (data.has_phone) {
                meta += ' (' + data.recipient_phone + ')';
            } else {
                meta += ' (연락처 미등록 - 알림톡 발송 불가)';
            }
            document.getElementById('approvePreviewMeta').textContent = meta;
            document.getElementById('approvePreview').style.display = 'block';
        } else {
            alert(data.message || '미리보기 생성 실패');
        }
    })
    .catch(() => alert('미리보기 요청 중 오류가 발생했습니다.'));
}

// 거절 미리보기
function previewRejectMessage() {
    const reason = document.getElementById('rejectReason').value.trim();
    const message = document.getElementById('rejectMessage').value;

    if (!reason) {
        alert('거절 사유를 입력해주세요.');
        return;
    }

    const formData = new FormData();
    formData.append('id', postId);
    formData.append('type', 'reject');
    formData.append('reject_reason', reason);
    formData.append('reject_message', message);
    formData.append('csrf_token', csrfToken);

    fetch('../ajax/preview_consignment_message.php', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('rejectPreviewContent').textContent = data.preview;
            let meta = '수신자: ' + (data.recipient_name || '-');
            if (data.has_phone) {
                meta += ' (' + data.recipient_phone + ')';
            } else {
                meta += ' (연락처 미등록 - 알림톡 발송 불가)';
            }
            document.getElementById('rejectPreviewMeta').textContent = meta;
            document.getElementById('rejectPreview').style.display = 'block';
        } else {
            alert(data.message || '미리보기 생성 실패');
        }
    })
    .catch(() => alert('미리보기 요청 중 오류가 발생했습니다.'));
}

// 승인 제출
function submitApproval() {
    const price = document.getElementById('approvedPrice').value;
    const message = document.getElementById('approvedMessage').value;

    if (!price || price.replace(/[^0-9]/g, '') === '0') {
        alert('중개 금액을 입력해주세요.');
        document.getElementById('approvedPrice').focus();
        return;
    }

    if (!confirm('이 판매의뢰를 승인하시겠습니까?\n중개 금액: ' + price + '원\n\n중개판매 게시판에 게시되고 카카오 알림톡이 발송됩니다.')) return;

    const formData = new FormData();
    formData.append('action', 'approve');
    formData.append('id', postId);
    formData.append('approved_price', price);
    formData.append('approved_message', message);
    formData.append('csrf_token', csrfToken);

    fetch('../ajax/admin_review_consignment.php', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let msg = data.message;
            if (data.kakao_result) {
                if (data.kakao_result.test_mode) {
                    msg += '\n\n[테스트 모드] 카카오 알림톡은 실제 발송되지 않았습니다.';
                } else if (data.kakao_result.success) {
                    msg += '\n\n카카오 알림톡이 발송되었습니다.';
                } else {
                    msg += '\n\n카카오 알림톡 발송 실패: ' + (data.kakao_result.error || '');
                }
            }
            alert(msg);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(() => alert('처리 중 오류가 발생했습니다.'));
}

// 거절 제출
function submitRejection() {
    const reason = document.getElementById('rejectReason').value.trim();
    const message = document.getElementById('rejectMessage').value;

    if (!reason) {
        alert('거절 사유를 입력해주세요.');
        document.getElementById('rejectReason').focus();
        return;
    }

    if (!confirm('이 판매의뢰를 거절하시겠습니까?\n거절 사유: ' + reason)) return;

    const formData = new FormData();
    formData.append('action', 'reject');
    formData.append('id', postId);
    formData.append('reject_reason', reason);
    formData.append('reject_message', message);
    formData.append('csrf_token', csrfToken);

    fetch('../ajax/admin_review_consignment.php', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let msg = data.message;
            if (data.kakao_result) {
                if (data.kakao_result.test_mode) {
                    msg += '\n\n[테스트 모드] 카카오 알림톡은 실제 발송되지 않았습니다.';
                } else if (data.kakao_result.success) {
                    msg += '\n\n카카오 알림톡이 발송되었습니다.';
                } else {
                    msg += '\n\n카카오 알림톡 발송 실패: ' + (data.kakao_result.error || '');
                }
            }
            alert(msg);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(() => alert('처리 중 오류가 발생했습니다.'));
}

// 카카오 발송 이력 로드
function loadKakaoHistory() {
    const container = document.getElementById('kakaoHistoryContainer');
    if (!container) return;

    fetch('../ajax/get_consignment_kakao_history.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': csrfToken
        },
        body: 'id=' + postId + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.history.length > 0) {
            let html = '<table class="kakao-history-table"><thead><tr>';
            html += '<th>발송일시</th><th>유형</th><th>상태</th><th>수신자</th><th>오류</th><th>관리</th>';
            html += '</tr></thead><tbody>';

            data.history.forEach(item => {
                const typeLabel = item.message_type === 'CONSIGN_APPROVED' ? '승인' :
                                  item.message_type === 'CONSIGN_REJECTED' ? '거절' : item.message_type;
                const statusLabel = item.status === 'sent' ? '발송완료' :
                                    item.status === 'failed' ? '발송실패' : '대기';
                const statusClass = item.status;
                const date = item.sent_at || item.created_at || '-';
                const error = item.error_message ? '<span style="color:#C62828;font-size:12px;">' + escapeHtml(item.error_message) + '</span>' : '-';
                const recipient = escapeHtml(item.recipient_name || '-');
                const parentInfo = item.parent_notification_id ? ' <small style="color:#999;">(재발송)</small>' : '';

                html += '<tr>';
                html += '<td>' + date + '</td>';
                html += '<td>' + typeLabel + parentInfo + '</td>';
                html += '<td><span class="kakao-badge ' + statusClass + '">' + statusLabel + '</span></td>';
                html += '<td>' + recipient + '</td>';
                html += '<td>' + error + '</td>';
                html += '<td>';
                if (item.status === 'failed') {
                    html += '<button class="btn-resend" onclick="resendKakao(' + postId + ')">재발송</button>';
                }
                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p style="color: #999; font-size: 14px;">발송 이력이 없습니다.</p>';
        }
    })
    .catch(() => {
        container.innerHTML = '<p style="color: #C62828; font-size: 14px;">이력 조회 중 오류가 발생했습니다.</p>';
    });
}

// 재발송
function resendKakao(id) {
    if (!confirm('카카오 알림톡을 재발송하시겠습니까?')) return;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', csrfToken);

    fetch('../ajax/resend_consignment_kakao.php', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    })
    .catch(() => alert('재발송 중 오류가 발생했습니다.'));
}

// HTML 이스케이프
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 모달 바깥 클릭 시 닫기
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
    }
});

// 페이지 로드 시 발송 이력 로드
document.addEventListener('DOMContentLoaded', function() {
    if (postStatus === 'approved' || postStatus === 'rejected') {
        loadKakaoHistory();
    }
});
</script>

<?php require_once 'admin_tail.php'; ?>
