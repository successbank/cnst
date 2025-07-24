<?php
require_once 'member_check.php';
require_once 'db.php';
require_once 'includes/sub_layout.php';

// 로그인 체크
checkLogin();

// 회원 정보 가져오기
$member_id = $_SESSION['member_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if(!$member) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch(PDOException $e) {
    $error = '정보를 불러올 수 없습니다.';
}

$currentPage = 'mypage';
$pageTitle = '마이페이지';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('마이페이지', 'info');

// 사이드바
myPageSidebar('info');
?>

<main class="sub-content">
    <div class="content-header">
        <h2><?php echo htmlspecialchars($member['name']); ?>님, 안녕하세요!</h2>
        <p>마이페이지에서 회원정보와 주문내역을 확인하실 수 있습니다.</p>
    </div>
    
    <div class="content-body">
        <!-- 기본정보 -->
        <div class="info-section">
            <h3>기본정보</h3>
            <div class="info-row">
                <div class="info-label">아이디</div>
                <div class="info-value"><?php echo htmlspecialchars($member['user_id']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">이름</div>
                <div class="info-value"><?php echo htmlspecialchars($member['name']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">이메일</div>
                <div class="info-value"><?php echo htmlspecialchars($member['email']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">휴대폰</div>
                <div class="info-value"><?php echo htmlspecialchars($member['phone'] ?: '미등록'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">일반전화번호</div>
                <div class="info-value"><?php echo htmlspecialchars($member['landline'] ?: '미등록'); ?></div>
            </div>
        </div>
        
        <!-- 회사정보 -->
        <div class="info-section">
            <h3>회사정보</h3>
            <div class="info-row">
                <div class="info-label">회사명</div>
                <div class="info-value"><?php echo htmlspecialchars($member['company'] ?: '미등록'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">홈페이지</div>
                <div class="info-value">
                    <?php if($member['homepage']): ?>
                        <a href="<?php echo htmlspecialchars($member['homepage']); ?>" target="_blank" style="color: #1A237E;">
                            <?php echo htmlspecialchars($member['homepage']); ?>
                        </a>
                    <?php else: ?>
                        미등록
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">직급/부서</div>
                <div class="info-value"><?php echo htmlspecialchars($member['position'] ?: '미등록'); ?></div>
            </div>
        </div>
        
        <!-- 주소정보 -->
        <div class="info-section">
            <h3>주소정보</h3>
            <div class="info-row">
                <div class="info-label">주소</div>
                <div class="info-value">
                    <?php if($member['zipcode']): ?>
                        (<?php echo htmlspecialchars($member['zipcode']); ?>)
                        <?php echo htmlspecialchars($member['address']); ?>
                        <?php echo htmlspecialchars($member['address_detail']); ?>
                    <?php else: ?>
                        미등록
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 기타정보 -->
        <div class="info-section">
            <h3>기타정보</h3>
            <div class="info-row">
                <div class="info-label">가입일</div>
                <div class="info-value"><?php echo date('Y년 m월 d일', strtotime($member['created_at'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">마지막 로그인</div>
                <div class="info-value">
                    <?php echo $member['last_login'] ? date('Y년 m월 d일 H:i', strtotime($member['last_login'])) : '첫 로그인'; ?>
                </div>
            </div>
        </div>
        
        <div class="btn-group">
            <a href="edit_profile.php" class="btn btn-primary">정보수정</a>
            <a href="index.php" class="btn btn-secondary">메인으로</a>
        </div>
    </div>
</main>

<?php 
endSubPage();
include 'tail.php'; 
?>