<?php
require_once 'member_check.php';
require_once 'db.php';
require_once 'includes/sub_layout.php';

// 로그인 체크
checkLogin();

$member_id = $_SESSION['member_id'];
$error = '';
$success = '';

// 회원 정보 가져오기
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

// 정보 수정 처리
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $address_detail = trim($_POST['address_detail'] ?? '');
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';
    
    // 유효성 검사
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '올바른 이메일 주소를 입력해주세요.';
    } else {
        try {
            // 이메일 중복 체크 (자신 제외)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ? AND id != ?");
            $stmt->execute([$email, $member_id]);
            if($stmt->fetchColumn() > 0) {
                $error = '이미 사용중인 이메일입니다.';
            } else {
                // 비밀번호 변경 처리
                if($new_password) {
                    if(!password_verify($current_password, $member['password'])) {
                        $error = '현재 비밀번호가 올바르지 않습니다.';
                    } elseif(strlen($new_password) < 6) {
                        $error = '새 비밀번호는 6자 이상이어야 합니다.';
                    } elseif($new_password !== $new_password_confirm) {
                        $error = '새 비밀번호가 일치하지 않습니다.';
                    } else {
                        // 비밀번호 업데이트
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE members SET password = ? WHERE id = ?");
                        $stmt->execute([$hashed_password, $member_id]);
                    }
                }
                
                if(!$error) {
                    // 정보 업데이트
                    $stmt = $pdo->prepare("
                        UPDATE members SET 
                            name = ?, email = ?, phone = ?, company = ?, 
                            position = ?, zipcode = ?, address = ?, address_detail = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $phone, $company, $position, $zipcode, $address, $address_detail, $member_id]);
                    
                    // 세션 정보 업데이트
                    $_SESSION['member_name'] = $name;
                    $_SESSION['member_email'] = $email;
                    
                    $success = '정보가 수정되었습니다.';
                }
            }
        } catch(PDOException $e) {
            $error = '정보 수정 중 오류가 발생했습니다.';
        }
    }
}

$currentPage = 'mypage';
$pageTitle = '회원정보 수정';
include 'head.php';

// 서브페이지 레이아웃 시작
startSubPage('회원정보 수정', 'edit');

// 사이드바
myPageSidebar('edit');
?>

<main class="sub-content">
    <div class="content-header">
        <h2>회원정보 수정</h2>
        <p>회원님의 정보를 수정하실 수 있습니다.</p>
    </div>
    
    <div class="content-body">
        <?php if($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- 기본정보 -->
            <div class="info-section">
                <h3>기본정보</h3>
                
                <div class="form-group">
                    <label>아이디</label>
                    <input type="text" value="<?php echo htmlspecialchars($member['user_id']); ?>" readonly style="background: #F8F9FA;">
                </div>
                
                <div class="form-group">
                    <label>이름 *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $member['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>이메일 *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $member['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>휴대폰</label>
                    <input type="tel" name="phone" placeholder="010-0000-0000" 
                           pattern="[0-9]{3}-[0-9]{4}-[0-9]{4}"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? $member['phone']); ?>">
                </div>
            </div>
            
            <!-- 회사정보 -->
            <div class="info-section">
                <h3>회사정보</h3>
                
                <div class="form-group">
                    <label>회사명</label>
                    <input type="text" name="company" value="<?php echo htmlspecialchars($_POST['company'] ?? $member['company']); ?>">
                </div>
                
                <div class="form-group">
                    <label>직급/부서</label>
                    <input type="text" name="position" value="<?php echo htmlspecialchars($_POST['position'] ?? $member['position']); ?>">
                </div>
            </div>
            
            <!-- 주소정보 -->
            <div class="info-section">
                <h3>주소정보</h3>
                
                <div class="form-group">
                    <label>주소</label>
                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <input type="text" id="zipcode" name="zipcode" placeholder="우편번호" readonly
                               style="width: 150px; background: #F8F9FA;"
                               value="<?php echo htmlspecialchars($_POST['zipcode'] ?? $member['zipcode']); ?>">
                        <button type="button" class="btn btn-secondary" onclick="findZipcode()">우편번호 찾기</button>
                    </div>
                    <input type="text" id="address" name="address" placeholder="기본주소" readonly
                           style="background: #F8F9FA; margin-bottom: 12px;"
                           value="<?php echo htmlspecialchars($_POST['address'] ?? $member['address']); ?>">
                    <input type="text" id="address_detail" name="address_detail" placeholder="상세주소"
                           value="<?php echo htmlspecialchars($_POST['address_detail'] ?? $member['address_detail']); ?>">
                </div>
            </div>
            
            <!-- 비밀번호 변경 -->
            <div class="info-section">
                <h3>비밀번호 변경</h3>
                <p style="font-size: 14px; color: #666; margin-bottom: 20px;">비밀번호를 변경하지 않으시려면 빈칸으로 두세요.</p>
                
                <div class="form-group">
                    <label>현재 비밀번호</label>
                    <input type="password" name="current_password">
                </div>
                
                <div class="form-group">
                    <label>새 비밀번호</label>
                    <input type="password" name="new_password" minlength="6">
                </div>
                
                <div class="form-group">
                    <label>새 비밀번호 확인</label>
                    <input type="password" name="new_password_confirm" minlength="6">
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">정보수정</button>
                <a href="mypage.php" class="btn btn-secondary">취소</a>
            </div>
        </form>
    </div>
</main>

<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
function findZipcode() {
    new daum.Postcode({
        oncomplete: function(data) {
            document.getElementById('zipcode').value = data.zonecode;
            document.getElementById('address').value = data.roadAddress;
            document.getElementById('address_detail').focus();
        }
    }).open();
}
</script>

<style>
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert.error {
    background: #FFEBEE;
    color: #C62828;
}

.alert.success {
    background: #E8F5E9;
    color: #2E7D32;
}
</style>

<?php 
endSubPage();
include 'tail.php'; 
?>