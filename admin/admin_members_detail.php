<?php
require_once 'admin_check.php';
require_once '../db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([$id]);
        $member = $stmt->fetch();
        
        if($member):
?>
<div class="info-section">
    <h4>기본 정보</h4>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">아이디</div>
            <div class="info-value"><?php echo htmlspecialchars($member['user_id']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">이름</div>
            <div class="info-value"><?php echo htmlspecialchars($member['name']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">이메일</div>
            <div class="info-value"><?php echo htmlspecialchars($member['email']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">휴대폰</div>
            <div class="info-value"><?php echo htmlspecialchars($member['phone'] ?: '미등록'); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">일반전화번호</div>
            <div class="info-value"><?php echo htmlspecialchars($member['landline'] ?: '미등록'); ?></div>
        </div>
    </div>
</div>

<div class="info-section">
    <h4>회사 정보</h4>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">회사명</div>
            <div class="info-value"><?php echo htmlspecialchars($member['company'] ?: '미등록'); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">직급/부서</div>
            <div class="info-value"><?php echo htmlspecialchars($member['position'] ?: '미등록'); ?></div>
        </div>
    </div>
</div>

<div class="info-section">
    <h4>주소 정보</h4>
    <div class="info-item">
        <div class="info-label">주소</div>
        <div class="info-value">
            <?php if($member['zipcode']): ?>
                (<?php echo htmlspecialchars($member['zipcode']); ?>)<br>
                <?php echo htmlspecialchars($member['address']); ?><br>
                <?php echo htmlspecialchars($member['address_detail']); ?>
            <?php else: ?>
                미등록
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="info-section">
    <h4>계정 정보</h4>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">계정 상태</div>
            <div class="info-value">
                <?php if($member['is_active']): ?>
                    <span class="status-badge status-active">활성</span>
                <?php else: ?>
                    <span class="status-badge status-inactive">비활성</span>
                <?php endif; ?>
                <?php if($member['is_admin']): ?>
                    <span class="status-badge admin-badge">관리자</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="info-item">
            <div class="info-label">가입일</div>
            <div class="info-value"><?php echo date('Y년 m월 d일 H:i', strtotime($member['created_at'])); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">마지막 로그인</div>
            <div class="info-value">
                <?php echo $member['last_login'] ? date('Y년 m월 d일 H:i', strtotime($member['last_login'])) : '없음'; ?>
            </div>
        </div>
        <div class="info-item">
            <div class="info-label">정보 수정일</div>
            <div class="info-value"><?php echo date('Y년 m월 d일 H:i', strtotime($member['updated_at'])); ?></div>
        </div>
    </div>
</div>

<!-- 비밀번호 변경 섹션 -->
<div class="info-section">
    <h4>비밀번호 변경</h4>
    <form method="POST" action="admin_members_action.php" style="margin-top: 16px;">
        <input type="hidden" name="action" value="change_password">
        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
        <div class="info-grid">
            <div class="info-item" style="grid-column: 1 / -1;">
                <div class="info-label">새 비밀번호</div>
                <input type="password" name="new_password" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div class="info-item" style="grid-column: 1 / -1;">
                <div class="info-label">새 비밀번호 확인</div>
                <input type="password" name="new_password_confirm" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
        </div>
        <button type="submit" style="margin-top: 16px; padding: 10px 20px; background: #1A237E; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">비밀번호 변경</button>
    </form>
</div>
<?php
        else:
            echo '<p>회원 정보를 찾을 수 없습니다.</p>';
        endif;
    } catch(PDOException $e) {
        echo '<p>오류가 발생했습니다.</p>';
    }
} else {
    echo '<p>잘못된 요청입니다.</p>';
}
?>