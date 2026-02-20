<?php
require_once 'member_check.php';
require_once 'db.php';
require_once 'includes/sub_layout.php';
require_once 'includes/csrf.php';

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
    // CSRF 검증
    if (!verifyCsrfToken(false)) {
        $error = '잘못된 요청입니다. 페이지를 새로고침 해주세요.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $landline = trim($_POST['landline'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $homepage = trim($_POST['homepage'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $zipcode = trim($_POST['zipcode'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $address_detail = trim($_POST['address_detail'] ?? '');
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';
    
    // 유효성 검사
    if($error) {
        // CSRF 실패 시 건너뛰기
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
                    } elseif(strlen($new_password) < 8 || strlen($new_password) > 64) {
                        $error = '새 비밀번호는 8자 이상이어야 합니다.';
                    } elseif(!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                        $error = '새 비밀번호는 영문자와 숫자를 모두 포함해야 합니다.';
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
                            name = ?, email = ?, phone = ?, landline = ?, company = ?, homepage = ?,
                            position = ?, zipcode = ?, address = ?, address_detail = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $phone, $landline, $company, $homepage, $position, $zipcode, $address, $address_detail, $member_id]);
                    
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
        
        <form method="POST" action="" id="edit-profile-form">
            <?php echo csrfField(); ?>
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
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <?php
                        $phone_parts = [];
                        $phone = $_POST['phone'] ?? $member['phone'] ?? '';
                        if ($phone) {
                            $phone_parts = explode('-', $phone);
                        }
                        ?>
                        <select name="phone_prefix" style="width: 80px;">
                            <option value="010" <?php echo (!isset($phone_parts[0]) || $phone_parts[0] == '010') ? 'selected' : ''; ?>>010</option>
                            <option value="011" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '011') ? 'selected' : ''; ?>>011</option>
                            <option value="016" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '016') ? 'selected' : ''; ?>>016</option>
                            <option value="017" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '017') ? 'selected' : ''; ?>>017</option>
                            <option value="018" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '018') ? 'selected' : ''; ?>>018</option>
                            <option value="019" <?php echo (isset($phone_parts[0]) && $phone_parts[0] == '019') ? 'selected' : ''; ?>>019</option>
                        </select>
                        <span style="padding: 0 5px;">-</span>
                        <input type="text" name="phone_middle" placeholder="0000" maxlength="4" 
                               style="width: 80px;" value="<?php echo htmlspecialchars($phone_parts[1] ?? ''); ?>">
                        <span style="padding: 0 5px;">-</span>
                        <input type="text" name="phone_last" placeholder="0000" maxlength="4" 
                               style="width: 80px;" value="<?php echo htmlspecialchars($phone_parts[2] ?? ''); ?>">
                    </div>
                    <input type="hidden" name="phone" id="phone-hidden" value="">
                </div>
                
                <div class="form-group">
                    <label>일반전화번호</label>
                    <div style="display: flex; gap: 8px;">
                        <?php
                        $landline_parts = [];
                        $landline = $_POST['landline'] ?? $member['landline'] ?? '';
                        if ($landline) {
                            $landline_parts = explode('-', $landline);
                        }
                        // 기존 지역번호 목록에 없는 경우 확인
                        $known_areas = ['02', '031', '032', '033', '041', '042', '043', '044', '050', '051', '052', '053', '054', '055', '061', '062', '063', '064', '070'];
                        $is_other = isset($landline_parts[0]) && !in_array($landline_parts[0], $known_areas);
                        ?>
                        <select name="landline_area" style="width: 115px;">
                            <option value="">지역번호</option>
                            <option value="02" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '02') ? 'selected' : ''; ?>>02 (서울)</option>
                            <option value="031" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '031') ? 'selected' : ''; ?>>031 (경기)</option>
                            <option value="032" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '032') ? 'selected' : ''; ?>>032 (인천)</option>
                            <option value="033" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '033') ? 'selected' : ''; ?>>033 (강원)</option>
                            <option value="041" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '041') ? 'selected' : ''; ?>>041 (충남)</option>
                            <option value="042" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '042') ? 'selected' : ''; ?>>042 (대전)</option>
                            <option value="043" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '043') ? 'selected' : ''; ?>>043 (충북)</option>
                            <option value="044" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '044') ? 'selected' : ''; ?>>044 (세종)</option>
                            <option value="050" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '050') ? 'selected' : ''; ?>>050 (평신)</option>
                            <option value="051" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '051') ? 'selected' : ''; ?>>051 (부산)</option>
                            <option value="052" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '052') ? 'selected' : ''; ?>>052 (울산)</option>
                            <option value="053" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '053') ? 'selected' : ''; ?>>053 (대구)</option>
                            <option value="054" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '054') ? 'selected' : ''; ?>>054 (경북)</option>
                            <option value="055" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '055') ? 'selected' : ''; ?>>055 (경남)</option>
                            <option value="061" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '061') ? 'selected' : ''; ?>>061 (전남)</option>
                            <option value="062" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '062') ? 'selected' : ''; ?>>062 (광주)</option>
                            <option value="063" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '063') ? 'selected' : ''; ?>>063 (전북)</option>
                            <option value="064" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '064') ? 'selected' : ''; ?>>064 (제주)</option>
                            <option value="070" <?php echo (isset($landline_parts[0]) && $landline_parts[0] == '070') ? 'selected' : ''; ?>>070 (인터넷)</option>
                            <option value="other" <?php echo $is_other ? 'selected' : ''; ?>>기타</option>
                        </select>
                        <input type="text" name="landline_area_other" placeholder="0000" maxlength="4" 
                               style="width: 80px; display: <?php echo $is_other ? 'inline-block' : 'none'; ?>;" 
                               value="<?php echo $is_other ? htmlspecialchars($landline_parts[0]) : ''; ?>">
                        <span style="padding: 10px 5px;">-</span>
                        <input type="text" name="landline_middle" placeholder="0000" maxlength="4" 
                               style="width: 80px;" value="<?php echo htmlspecialchars($landline_parts[1] ?? ''); ?>">
                        <span style="padding: 10px 5px;">-</span>
                        <input type="text" name="landline_last" placeholder="0000" maxlength="4" 
                               style="width: 80px;" value="<?php echo htmlspecialchars($landline_parts[2] ?? ''); ?>">
                    </div>
                    <input type="hidden" name="landline" id="landline-hidden" value="">
                </div>
            </div>
            
            <!-- 회사정보 -->
            <div class="info-section">
                <h3>회사정보</h3>
                
                <div class="form-group">
                    <label>회사명</label>
                    <input type="text" name="company" value="<?php echo htmlspecialchars($_POST['company'] ?? $member['company'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>홈페이지</label>
                    <input type="url" name="homepage" placeholder="https://example.com"
                           value="<?php echo htmlspecialchars($_POST['homepage'] ?? $member['homepage'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>직급/부서</label>
                    <input type="text" name="position" value="<?php echo htmlspecialchars($_POST['position'] ?? $member['position'] ?? ''); ?>">
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
                               value="<?php echo htmlspecialchars($_POST['zipcode'] ?? $member['zipcode'] ?? ''); ?>">
                        <button type="button" class="btn btn-secondary" onclick="findZipcode()">우편번호 찾기</button>
                    </div>
                    <input type="text" id="address" name="address" placeholder="기본주소" readonly
                           style="background: #F8F9FA; margin-bottom: 12px;"
                           value="<?php echo htmlspecialchars($_POST['address'] ?? $member['address'] ?? ''); ?>">
                    <input type="text" id="address_detail" name="address_detail" placeholder="상세주소"
                           value="<?php echo htmlspecialchars($_POST['address_detail'] ?? $member['address_detail'] ?? ''); ?>">
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
                    <input type="password" name="new_password" minlength="8" maxlength="64" placeholder="영문+숫자 8자 이상">
                </div>

                <div class="form-group">
                    <label>새 비밀번호 확인</label>
                    <input type="password" name="new_password_confirm" minlength="8" maxlength="64">
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
// 지역번호 선택 변경 시 기타 입력 필드 표시/숨김
document.querySelector('select[name="landline_area"]').addEventListener('change', function() {
    const otherInput = document.querySelector('input[name="landline_area_other"]');
    if (this.value === 'other') {
        otherInput.style.display = 'inline-block';
        otherInput.focus();
    } else {
        otherInput.style.display = 'none';
        otherInput.value = '';
    }
});

// 폼 제출 시 전화번호 조합
document.getElementById('edit-profile-form').addEventListener('submit', function(e) {
    // 휴대폰 번호 조합
    const phonePrefix = document.querySelector('select[name="phone_prefix"]').value;
    const phoneMiddle = document.querySelector('input[name="phone_middle"]').value;
    const phoneLast = document.querySelector('input[name="phone_last"]').value;
    
    const phoneHidden = document.getElementById('phone-hidden');
    if (phonePrefix && phoneMiddle && phoneLast) {
        phoneHidden.value = phonePrefix + '-' + phoneMiddle + '-' + phoneLast;
    } else {
        phoneHidden.value = '';
    }
    
    // 일반전화번호 조합
    const areaSelect = document.querySelector('select[name="landline_area"]');
    let area = areaSelect.value;
    
    // 기타 선택 시 직접 입력한 값 사용
    if (area === 'other') {
        area = document.querySelector('input[name="landline_area_other"]').value;
    }
    
    const middle = document.querySelector('input[name="landline_middle"]').value;
    const last = document.querySelector('input[name="landline_last"]').value;
    
    const landlineHidden = document.getElementById('landline-hidden');
    if (area && middle && last) {
        landlineHidden.value = area + '-' + middle + '-' + last;
    } else {
        landlineHidden.value = '';
    }
});

// 숫자만 입력 가능하도록 제한
document.querySelectorAll('input[name="phone_middle"], input[name="phone_last"], input[name="landline_middle"], input[name="landline_last"], input[name="landline_area_other"]').forEach(input => {
    input.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
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